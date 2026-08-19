import { Router } from "express";
import db from "../db.js";
import { requireAuth } from "../middleware/auth.js";

const router = Router();
router.use(requireAuth);

const today = () => new Date().toISOString().slice(0, 10);

function dayShift(days) {
  const d = new Date();
  d.setDate(d.getDate() - days);
  return d.toISOString().slice(0, 10);
}

/** inclusive overlap (in days) between [a,b] and [pStart,pEnd] (YYYY-MM-DD strings) */
function overlapDays(a, b, pStart, pEnd) {
  const s = a > pStart ? a : pStart;
  const e = b < pEnd ? b : pEnd;
  if (e < s) return 0;
  return Math.floor((new Date(e) - new Date(s)) / 86400000) + 1;
}

function mergeIntervals(list) {
  if (!list.length) return [];
  list.sort((x, y) => (x[0] < y[0] ? -1 : 1));
  const out = [];
  let cur = list[0];
  for (let i = 1; i < list.length; i++) {
    const [s, e] = list[i];
    if (s <= cur[1]) cur[1] = cur[1] > e ? cur[1] : e;
    else {
      out.push(cur);
      cur = list[i];
    }
  }
  out.push(cur);
  return out;
}

function currentStatus(asset, risActive, disposalSet, maintBySerial) {
  if (disposalSet.has(asset.pid)) return { status: "For Disposal", in_use: false };
  if (risActive[asset.pid]) {
    const r = risActive[asset.pid];
    const overdue = r.end_datetime && r.end_datetime.slice(0, 10) < today();
    return {
      status: overdue ? "Overdue" : "Borrowed",
      in_use: true,
      status_detail: `Borrowed via ${r.ris_no}${overdue ? " (OVERDUE" : ""} until ${r.end_datetime || "—"}${overdue ? ")" : ""}`,
    };
  }
  const maint = maintBySerial.get(asset.serial_number);
  if (maint && maint.next_maintenance_date && maint.next_maintenance_date <= today()) {
    return {
      status: "In Maintenance",
      in_use: true,
      status_detail: `Maintenance due (last ${maint.previous_maintenance_date || "—"}, next ${maint.next_maintenance_date})`,
    };
  }
  if (asset.assigned_to || asset.office_id) {
    return {
      status: "Assigned",
      in_use: true,
      status_detail: `Assigned to ${asset.assigned_to || "office"}${asset.assigned_date ? ` since ${asset.assigned_date}` : ""}`,
    };
  }
  return { status: "Available", in_use: false };
}

function buildHistory(asset, ctx) {
  const events = [];

  const asg = db
    .prepare(
      `SELECT assigned_to, department, remarks, date_assigned FROM tbl_asset_assignments
       WHERE asset_id = ? AND is_archived = 0`
    )
    .all(asset.pid);
  for (const a of asg) {
    events.push({ date: a.date_assigned, type: "Assigned", detail: `Assigned to ${a.assigned_to}${a.department ? ` (${a.department})` : ""}${a.remarks ? ` — ${a.remarks}` : ""}` });
  }
  if (asset.assigned_date && !asg.length && asset.assigned_to) {
    events.push({ date: asset.assigned_date, type: "Assigned", detail: `Assigned to ${asset.assigned_to}` });
  }

  const ris = ctx.risItems.filter((i) => i.asset_id === asset.pid);
  for (const r of ris) {
    const ret = r.is_returned ? ` — returned ${r.return_date || ""}` : ` — still out`;
    events.push({ date: (r.start_datetime || r.end_datetime || "").slice(0, 10), type: "Borrowed", detail: `Borrowed via ${r.ris_no} until ${r.end_datetime || "—"}${ret}` });
  }

  const ptr = ctx.ptrItems.filter((i) => i.asset_id === asset.pid);
  for (const p of ptr) {
    events.push({ date: (p.transfer_date || "").slice(0, 10), type: "Transferred", detail: `PTR ${p.ptr_no}: ${p.from_office_name || "—"} → ${p.to_office_name || "—"}${p.remarks ? ` — ${p.remarks}` : ""}` });
  }

  const maint = ctx.maintBySerial.get(asset.serial_number);
  if (maint) {
    events.push({
      date: maint.next_maintenance_date || "",
      type: "Maintenance",
      detail: `Maintenance scheduled (${maint.maintenance_task || "—"}) last ${maint.previous_maintenance_date || "—"}, next ${maint.next_maintenance_date || "—"}`,
    });
  }

  const dis = ctx.disposals.find((d) => d.asset_id === asset.pid);
  if (dis) {
    events.push({ date: (dis.disposed_at || "").slice(0, 10), type: "Disposed", detail: `Disposed via ${dis.dis_no}${dis.remarks ? ` — ${dis.remarks}` : ""}` });
  }

  return events
    .filter((e) => e.date)
    .sort((a, b) => (a.date < b.date ? 1 : -1));
}

/** merged in-use day intervals per asset for time-based utilization */
function inUseIntervals(asset, ctx, start) {
  const list = [];
  if (asset.assigned_date) list.push([asset.assigned_date, today()]);
  const asg = db
    .prepare(`SELECT date_assigned FROM tbl_asset_assignments WHERE asset_id = ? AND is_archived = 0`)
    .all(asset.pid);
  for (const a of asg) if (a.date_assigned) list.push([a.date_assigned.slice(0, 10), today()]);

  const ris = ctx.risItems.filter((i) => i.asset_id === asset.pid);
  for (const r of ris) {
    const s = (r.start_datetime || r.end_datetime || "").slice(0, 10);
    if (!s) continue;
    let e = r.end_datetime ? r.end_datetime.slice(0, 10) : today();
    if (r.is_returned && r.return_date) e = r.return_date.slice(0, 10);
    if (e < s) e = s;
    list.push([s, e]);
  }

  const maint = ctx.maintBySerial.get(asset.serial_number);
  if (maint && maint.previous_maintenance_date) {
    list.push([maint.previous_maintenance_date.slice(0, 10), (maint.next_maintenance_date || today()).slice(0, 10)]);
  }

  return mergeIntervals(list.filter(([s, e]) => e >= start));
}

router.get("/", (req, res) => {
  const periodDays = Math.max(1, Math.min(365, Number(req.query.days) || 90));
  const start = dayShift(periodDays);

  const assets = db
    .prepare(
      `SELECT p.*, c.category AS category_name
       FROM tbl_product p
       LEFT JOIN tbl_category c ON p.category = c.catid
       WHERE p.is_archived = 0 AND p.product_type = 'Asset'
       ORDER BY p.pid DESC`
    )
    .all();

  const disposals = db.prepare("SELECT * FROM tbl_disposal WHERE is_archived = 0").all();
  const disposalSet = new Set(disposals.filter((d) => d.asset_id).map((d) => d.asset_id));

  const risRows = db
    .prepare(
      `SELECT i.asset_id, h.ris_no, h.start_datetime, h.end_datetime, h.is_returned, h.return_date
       FROM tbl_ris_items i
       JOIN tbl_ris_header h ON h.id = i.ris_id
       WHERE i.is_archived = 0 AND h.is_archived = 0`
    )
    .all();

  const risActive = {};
  for (const r of risRows) {
    if (!r.is_returned && !risActive[r.asset_id]) risActive[r.asset_id] = r;
  }

  const maintRows = db
    .prepare(
      `SELECT serial_number, maintenance_task, previous_maintenance_date, next_maintenance_date
       FROM tbl_maintenance_reports WHERE is_archived = 0`
    )
    .all();
  const maintBySerial = new Map(maintRows.filter((m) => m.serial_number).map((m) => [m.serial_number, m]));

  const ptrRows = db
    .prepare(
      `SELECT i.asset_id, h.ptr_no, h.transfer_date, h.remarks, o1.office_name AS from_office_name, o2.office_name AS to_office_name
       FROM tbl_ptr_items i
       JOIN tbl_ptr_header h ON h.id = i.ptr_id
       LEFT JOIN tbl_office o1 ON o1.id = h.from_office
       LEFT JOIN tbl_office o2 ON o2.id = h.to_office
       WHERE i.is_archived = 0 AND h.is_archived = 0`
    )
    .all();

  const ctx = { risItems: risRows, ptrItems: ptrRows, maintBySerial, disposals };

  const officeNames = {};
  const offRows = db.prepare("SELECT id, office_name FROM tbl_office WHERE is_archived = 0").all();
  for (const o of offRows) officeNames[o.id] = o.office_name;

  const tracked = assets.map((a) => {
    const st = currentStatus(a, risActive, disposalSet, maintBySerial);
    return {
      pid: a.pid,
      name: a.name,
      barcode: a.barcode,
      serial_number: a.serial_number,
      condition: a.condition || "Good",
      department: a.department || "—",
      category_name: a.category_name || "Uncategorized",
      unit_cost: a.unit_cost || 0,
      stock: a.stock,
      office_name: a.office_id ? officeNames[a.office_id] || null : null,
      assigned_to: a.assigned_to,
      status: st.status,
      in_use: st.in_use,
      status_detail: st.status_detail || "",
      history: buildHistory(a, ctx),
      utilization_days: inUseIntervals(a, ctx, start).reduce((s, [x, y]) => s + overlapDays(x, y, start, today()), 0),
    };
  });

  const total = tracked.length;
  const inUse = tracked.filter((a) => a.in_use).length;
  const overall = total ? Math.round((inUse / total) * 10000) / 100 : 0;

  const byDepartment = [];
  const deptMap = {};
  for (const a of tracked) {
    const k = a.department || "—";
    if (!deptMap[k]) deptMap[k] = { department: k, total: 0, in_use: 0 };
    deptMap[k].total += 1;
    if (a.in_use) deptMap[k].in_use += 1;
  }
  for (const k of Object.keys(deptMap)) {
    const d = deptMap[k];
    byDepartment.push({ ...d, percent: d.total ? Math.round((d.in_use / d.total) * 10000) / 100 : 0 });
  }

  const byCategory = [];
  const catMap = {};
  for (const a of tracked) {
    const k = a.category_name;
    if (!catMap[k]) catMap[k] = { category: k, total: 0, in_use: 0 };
    catMap[k].total += 1;
    if (a.in_use) catMap[k].in_use += 1;
  }
  for (const k of Object.keys(catMap)) {
    const c = catMap[k];
    byCategory.push({ ...c, percent: c.total ? Math.round((c.in_use / c.total) * 10000) / 100 : 0 });
  }

  const byOffice = [];
  const offMap = {};
  for (const a of tracked) {
    const k = a.office_name || "Not Assigned";
    if (!offMap[k]) offMap[k] = { office: k, total: 0, in_use: 0 };
    offMap[k].total += 1;
    if (a.in_use) offMap[k].in_use += 1;
  }
  for (const k of Object.keys(offMap)) {
    const o = offMap[k];
    byOffice.push({ ...o, percent: o.total ? Math.round((o.in_use / o.total) * 10000) / 100 : 0 });
  }

  const totalDays = tracked.length * periodDays;
  const inUseDays = tracked.reduce((s, a) => s + a.utilization_days, 0);
  const timeBased = {
    period_days: periodDays,
    period_start: start,
    total_assets: total,
    in_use_days: inUseDays,
    total_asset_days: totalDays,
    percent: totalDays ? Math.round((inUseDays / totalDays) * 10000) / 100 : 0,
  };

  res.json({
    generated_at: new Date().toISOString(),
    period_days: periodDays,
    total_assets: total,
    in_use_assets: inUse,
    status_counts: {
      Available: tracked.filter((a) => a.status === "Available").length,
      Assigned: tracked.filter((a) => a.status === "Assigned").length,
      Borrowed: tracked.filter((a) => a.status === "Borrowed").length,
      Overdue: tracked.filter((a) => a.status === "Overdue").length,
      "In Maintenance": tracked.filter((a) => a.status === "In Maintenance").length,
      "For Disposal": tracked.filter((a) => a.status === "For Disposal").length,
    },
    utilization: {
      overall: { total, in_use: inUse, percent: overall },
      by_department: byDepartment,
      by_category: byCategory,
      by_office: byOffice,
      time_based: timeBased,
    },
    assets: tracked,
  });
});

export default router;