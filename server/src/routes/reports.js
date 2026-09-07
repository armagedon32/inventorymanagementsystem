import { Router } from "express";
import db from "../db.js";
import { requireAuth } from "../middleware/auth.js";

const router = Router();
router.use(requireAuth);

const INVENTORY_HEADERS = [
  { label: "Barcode", key: "barcode" },
  { label: "Name", key: "name" },
  { label: "Brand", key: "brand" },
  { label: "Category", key: "category_name" },
  { label: "Unit", key: "unit" },
  { label: "Stock", key: "stock" },
  { label: "Reorder Level", key: "reorder_level" },
  { label: "Status", key: "status" },
  { label: "Unit Cost", key: "unit_cost" },
  { label: "Value", key: "value" },
];

const ASSET_HEADERS = [
  { label: "Asset Tag", key: "barcode" },
  { label: "Name", key: "name" },
  { label: "Brand", key: "brand" },
  { label: "Category", key: "category_name" },
  { label: "Serial No.", key: "serial_number" },
  { label: "Condition", key: "condition" },
  { label: "Assigned To", key: "assigned_to" },
  { label: "Quantity", key: "stock" },
  { label: "Unit Cost", key: "unit_cost" },
  { label: "Value", key: "value" },
];

const REQ_HEADERS = [
  { label: "Requisition No.", key: "req_no" },
  { label: "Purpose", key: "purpose" },
  { label: "Requested By", key: "requested_name" },
  { label: "Date Created", key: "date_created" },
  { label: "Status", key: "status" },
  { label: "Reject Reason", key: "reject_reason" },
];

const TX_HEADERS = [
  { label: "Type", key: "type" },
  { label: "Product", key: "product_name" },
  { label: "Quantity", key: "quantity" },
  { label: "Office / Person", key: "recipient" },
  { label: "Date", key: "date" },
  { label: "Remarks", key: "remarks" },
];

const PERIOD_WINDOW = { day: 30, week: 12, month: 48 };

function periodLabel(period) {
  return period === "day" ? "Daily" : period === "week" ? "Weekly" : "Monthly";
}

function bucketExpr(period, col) {
  if (period === "day") return `strftime('%Y-%m-%d', ${col})`;
  if (period === "week") return `(strftime('%Y', ${col}) || '-W' || printf('%02d', CAST(strftime('%W', ${col}) AS INTEGER)))`;
  return `strftime('%Y-%m', ${col})`;
}

function periodBuckets(period) {
  const now = new Date();
  const pad = (n) => String(n).padStart(2, "0");
  const pad2 = (n) => String(Math.max(n, 0)).padStart(2, "0");
  const keyOf = (d) => {
    const y = d.getFullYear();
    if (period === "day") return `${y}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    if (period === "week") {
      const doy = Math.floor((d - new Date(y, 0, 0)) / 864e5);
      const offset = (7 - new Date(y, 0, 1).getDay()) % 7;
      return `${y}-W${pad2(Math.floor((doy - offset) / 7))}`;
    }
    return `${y}-${pad(d.getMonth() + 1)}`;
  };
  const n = PERIOD_WINDOW[period];
  const out = [];
  if (period === "month") {
    for (let i = n; i >= 1; i--) out.push(keyOf(new Date(now.getFullYear(), now.getMonth() - i, 1)));
  } else {
    for (let i = n - 1; i >= 0; i--) out.push(keyOf(new Date(now.getFullYear(), now.getMonth(), now.getDate() - i * (period === "week" ? 7 : 1))));
  }
  return out;
}

function movementData(period, labels) {
  const sum = (table, expr) => {
    const m = {};
    for (const r of db.prepare(`SELECT ${expr} AS k, IFNULL(SUM(quantity),0) AS s FROM ${table} WHERE is_archived=0 GROUP BY k`).all()) m[r.k] = r.s;
    return m;
  };
  const byProd = (table, expr) => {
    const m = {};
    for (const r of db.prepare(`SELECT product_id AS pid, ${expr} AS k, IFNULL(SUM(quantity),0) AS s FROM ${table} WHERE is_archived=0 GROUP BY pid, k`).all()) m[`${r.pid}|${r.k}`] = r.s;
    return m;
  };
  const inTot = sum("tbl_stockin", bucketExpr(period, "stock_date"));
  const outTot = sum("tbl_stockout", bucketExpr(period, "stockout_date"));
  const inProd = byProd("tbl_stockin", bucketExpr(period, "stock_date"));
  const outProd = byProd("tbl_stockout", bucketExpr(period, "stockout_date"));
  const totalIn = labels.reduce((s, k) => s + (inTot[k] || 0), 0);
  const totalOut = labels.reduce((s, k) => s + (outTot[k] || 0), 0);
  const chart = labels.map((k) => ({ name: k, in: inTot[k] || 0, out: outTot[k] || 0 }));
  return { totalIn, totalOut, chart, perProduct: (pid) => {
    let tin = 0, tout = 0;
    for (const k of labels) { tin += inProd[`${pid}|${k}`] || 0; tout += outProd[`${pid}|${k}`] || 0; }
    return { tin, tout };
  } };
}

router.get("/inventory", (req, res) => {
  const period = ["day", "week", "month"].includes(req.query.period) ? req.query.period : "all";
  const rows = db
    .prepare(
      `SELECT p.*, c.category AS category_name,
        CASE WHEN p.stock = 0 THEN 'Out of Stock'
             WHEN p.stock <= p.reorder_level THEN 'Low'
             ELSE 'OK' END AS status,
        (p.stock * p.unit_cost) AS value
       FROM tbl_product p
       LEFT JOIN tbl_category c ON p.category = c.catid
       WHERE p.is_archived = 0 AND p.product_type = 'Stock'
       ORDER BY p.name`
    )
    .all();
  const stats = {
    totalItems: rows.length,
    totalStock: rows.reduce((s, r) => s + (r.stock || 0), 0),
    totalValue: rows.reduce((s, r) => s + (r.value || 0), 0),
    low: rows.filter((r) => r.stock > 0 && r.stock <= r.reorder_level).length,
    outOfStock: rows.filter((r) => r.stock === 0).length,
  };

  if (period !== "all") {
    const labels = periodBuckets(period);
    const mv = movementData(period, labels);
    for (const r of rows) {
      const m = mv.perProduct(r.pid);
      r.in_period = m.tin;
      r.out_period = m.tout;
    }
    stats.totalIn = mv.totalIn;
    stats.totalOut = mv.totalOut;
    stats.net = mv.totalIn - mv.totalOut;
    stats.periodLabel = periodLabel(period);
    res.json({
      rows,
      stats,
      chart: mv.chart,
      headers: [
        ...INVENTORY_HEADERS,
        { label: "In (period)", key: "in_period" },
        { label: "Out (period)", key: "out_period" },
      ],
      period,
    });
    return;
  }

  const chart = db
    .prepare(
      `SELECT COALESCE(c.category, 'Uncategorized') AS name, COUNT(*) AS value
       FROM tbl_product p LEFT JOIN tbl_category c ON p.category = c.catid
       WHERE p.is_archived = 0 AND p.product_type = 'Stock'
       GROUP BY name ORDER BY value DESC`
    )
    .all();
  res.json({ rows, stats, chart, headers: INVENTORY_HEADERS, period });
});

router.get("/assets", (req, res) => {
  const rows = db
    .prepare(
      `SELECT p.*, c.category AS category_name, (p.stock * p.unit_cost) AS value
       FROM tbl_product p
       LEFT JOIN tbl_category c ON p.category = c.catid
       WHERE p.is_archived = 0 AND p.product_type = 'Asset'
       ORDER BY p.name`
    )
    .all();
  const cond = rows.reduce((m, r) => {
    const k = r.condition || "Good";
    m[k] = (m[k] || 0) + 1;
    return m;
  }, {});
  const stats = {
    totalAssets: rows.length,
    totalValue: rows.reduce((s, r) => s + (r.value || 0), 0),
    byCondition: cond,
    assigned: rows.filter((r) => r.assigned_to).length,
    unassigned: rows.filter((r) => !r.assigned_to).length,
  };
  const chart = Object.entries(cond).map(([name, value]) => ({ name, value }));
  res.json({ rows, stats, chart, headers: ASSET_HEADERS });
});

router.get("/requisitions", (req, res) => {
  const rows = db
    .prepare(
      `SELECT r.*, u.fullname AS requested_name,
        (SELECT COUNT(*) FROM tbl_requisition_item i WHERE i.requisition_id = r.id AND i.is_archived = 0) AS item_count
       FROM tbl_requisition r
       LEFT JOIN tbl_user u ON u.userid = r.requested_by
       WHERE r.is_archived = 0
       ORDER BY r.id DESC`
    )
    .all();
  const byStatus = rows.reduce((m, r) => {
    m[r.status] = (m[r.status] || 0) + 1;
    return m;
  }, {});
  const stats = {
    total: rows.length,
    pending: byStatus["Pending"] || 0,
    approved: byStatus["Approved"] || 0,
    rejected: byStatus["Rejected"] || 0,
  };
  const chart = [
    { name: "Pending", value: stats.pending },
    { name: "Approved", value: stats.approved },
    { name: "Rejected", value: stats.rejected },
  ].filter((c) => c.value > 0);
  res.json({ rows, stats, chart, headers: REQ_HEADERS });
});

router.get("/transactions", (req, res) => {
  const period = req.query.period === "day" ? "day" : req.query.period === "week" ? "week" : "month";
  const rows = db
    .prepare(
      `SELECT 'Stock In' AS type, t.quantity, t.remarks, t.stock_date AS date,
              p.name AS product_name, '' AS recipient
       FROM tbl_stockin t JOIN tbl_product p ON p.pid = t.product_id
       WHERE t.is_archived = 0
       UNION ALL
       SELECT 'Stock Out' AS type, t.quantity, t.remarks, t.stockout_date AS date,
              p.name AS product_name,
              COALESCE(o.office_name, i.fullname, '') AS recipient
       FROM tbl_stockout t
       JOIN tbl_product p ON p.pid = t.product_id
       LEFT JOIN tbl_office o ON o.id = t.office_id
       LEFT JOIN tbl_instructors i ON i.id = t.instructor_id
       WHERE t.is_archived = 0
       ORDER BY date DESC`
    )
    .all();
  const totalIn = rows.filter((r) => r.type === "Stock In").reduce((s, r) => s + (r.quantity || 0), 0);
  const totalOut = rows.filter((r) => r.type === "Stock Out").reduce((s, r) => s + (r.quantity || 0), 0);
  const stats = {
    totalTransactions: rows.length,
    totalIn,
    totalOut,
    net: totalIn - totalOut,
  };

  const now = new Date();
  const pad = (n) => String(n).padStart(2, "0");
  const pad2 = (n) => String(Math.max(n, 0)).padStart(2, "0");
  const keyOf = (d) => {
    const y = d.getFullYear();
    if (period === "day") return `${y}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    if (period === "week") {
      const doy = Math.floor((d - new Date(y, 0, 0)) / 864e5);
      const offset = (7 - new Date(y, 0, 1).getDay()) % 7;
      return `${y}-W${pad2(Math.floor((doy - offset) / 7))}`;
    }
    return `${y}-${pad(d.getMonth() + 1)}`;
  };
  const windowSize = period === "day" ? 30 : period === "week" ? 12 : 48;
  const labels = [];
  if (period === "month") {
    for (let i = windowSize; i >= 1; i--) {
      labels.push(keyOf(new Date(now.getFullYear(), now.getMonth() - i, 1)));
    }
  } else {
    for (let i = windowSize - 1; i >= 0; i--) {
      labels.push(keyOf(new Date(now.getFullYear(), now.getMonth(), now.getDate() - i * (period === "week" ? 7 : 1))));
    }
  }

  const hits = (table, col) => {
    const expr =
      period === "day"
        ? `strftime('%Y-%m-%d', ${col})`
        : period === "week"
          ? `(strftime('%Y', ${col}) || '-W' || printf('%02d', CAST(strftime('%W', ${col}) AS INTEGER)))`
          : `strftime('%Y-%m', ${col})`;
    const map = {};
    for (const r of db.prepare(`SELECT ${expr} AS k, IFNULL(SUM(quantity),0) AS s FROM ${table} WHERE is_archived=0 GROUP BY k`).all()) {
      map[r.k] = r.s;
    }
    return map;
  };
  const inMap = hits("tbl_stockin", "stock_date");
  const outMap = hits("tbl_stockout", "stockout_date");
  const chart = labels.map((k) => ({ month: k, in: inMap[k] || 0, out: outMap[k] || 0 }));
  res.json({ rows, stats, chart, headers: TX_HEADERS, period });
});

export default router;