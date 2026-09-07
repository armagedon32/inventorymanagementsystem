import { Router } from "express";
import db from "../db.js";
import { logActivity } from "../activity.js";
import { notify, notifyAdmins } from "../notify.js";
import { requireAuth, requireAdmin } from "../middleware/auth.js";

const router = Router();
router.use(requireAuth);

function nextReqNo() {
  const row = db.prepare("SELECT id FROM tbl_requisition ORDER BY id DESC LIMIT 1").get();
  const seq = row ? String(row.id + 1).padStart(3, "0") : "001";
  const today = new Date().toISOString().slice(0, 10).replace(/-/g, "");
  return `REQ-${today}-${seq}`;
}

function withUserInfo(r) {
  return {
    ...r,
    requested_name: r.requested_by
      ? db.prepare("SELECT fullname FROM tbl_user WHERE userid = ?").get(r.requested_by)?.fullname || null
      : null,
    processed_name: r.processed_by
      ? db.prepare("SELECT fullname FROM tbl_user WHERE userid = ?").get(r.processed_by)?.fullname || null
      : null,
  };
}

// ============ LIST ============

router.get("/", (req, res) => {
  const rows = db
    .prepare(
      `SELECT r.*, (SELECT COUNT(*) FROM tbl_requisition_item i WHERE i.requisition_id = r.id AND i.is_archived = 0) AS item_count
       FROM tbl_requisition r
       WHERE r.is_archived = 0
       ORDER BY r.id DESC`
    )
    .all()
    .map(withUserInfo);
  res.json(rows);
});

// ============ RULE CONFIGURATION ============

router.get("/rules", requireAdmin, (req, res) => {
  const rows = db.prepare("SELECT * FROM tbl_req_rule ORDER BY id").all();
  for (const r of rows) {
    try { r.rule_meta = JSON.parse(r.rule_meta || "{}"); } catch { r.rule_meta = {}; }
  }
  res.json(rows);
});

router.put("/rules/:id", requireAdmin, (req, res) => {
  const rule = db.prepare("SELECT * FROM tbl_req_rule WHERE id = ?").get(req.params.id);
  if (!rule) return res.status(404).json({ error: "Rule not found" });
  const { rule_name, description, policy_basis, rule_meta, is_enabled } = req.body || {};
  db.prepare(
    `UPDATE tbl_req_rule
     SET rule_name = ?, description = ?, policy_basis = ?, rule_meta = ?, is_enabled = ?
     WHERE id = ?`
  ).run(
    rule_name !== undefined ? String(rule_name).trim() : rule.rule_name,
    description !== undefined ? String(description).trim() : rule.description,
    policy_basis !== undefined ? String(policy_basis).trim() : rule.policy_basis,
    rule_meta !== undefined ? JSON.stringify(rule_meta) : String(rule.rule_meta || "{}"),
    is_enabled !== undefined ? (is_enabled === true || is_enabled === 1 ? 1 : 0) : rule.is_enabled,
    rule.id
  );
  logActivity(req, `Updated Requisition Rule: ${rule.rule_code}`);
  res.json({ success: true });
});

// ============ RULE EVALUATION ============

const FAIL_VERDICT = { "RULE-002": "reject", "RULE-004": "reject" };

function loadRuleMeta(ruleId) {
  const meta = db.prepare("SELECT rule_meta FROM tbl_req_rule WHERE id = ?").get(ruleId)?.rule_meta || "{}";
  try { return JSON.parse(meta); } catch { return {}; }
}

function evalItemList(r) {
  return db
    .prepare(
      `SELECT i.id, i.product_id, i.quantity, p.name AS product_name, p.brand, p.unit_cost,
              p.stock AS current_stock, p.unit, p.category, p.product_type, p.is_archived AS product_archived
       FROM tbl_requisition_item i
       JOIN tbl_product p ON p.pid = i.product_id
       WHERE i.requisition_id = ? AND i.is_archived = 0`
    )
    .all(r.id);
}

function runRule(rule, r, items) {
  const meta = loadRuleMeta(rule.id);
  switch (rule.rule_code) {
    case "RULE-001": {
      const bad = items.filter((it) => (it.current_stock || 0) < it.quantity);
      if (bad.length) {
        return { result: "FAIL", detail: `Insufficient stock: ${bad.map((b) => `${b.product_name} (needed ${b.quantity}, have ${b.current_stock})`).join("; ")}.` };
      }
      return { result: "PASS", detail: "All requested quantities are within available stock." };
    }
    case "RULE-002": {
      const u = db.prepare("SELECT userid, fullname, is_archived FROM tbl_user WHERE userid = ?").get(r.requested_by);
      if (!u) return { result: "FAIL", detail: "Request source user record was not found; requester is not authorized." };
      if (u.is_archived === 1) return { result: "FAIL", detail: `Requester "${u.fullname}" has an archived/inactive account and is not authorized to file requisitions.` };
      return { result: "PASS", detail: `Requester "${u.fullname}" is an active, registered user.` };
    }
    case "RULE-003": {
      const u = db.prepare("SELECT department FROM tbl_user WHERE userid = ?").get(r.requested_by);
      const dept = u?.department || "";
      const max = Number(meta.max_qty_per_item_per_month) || 0;
      if (!max) return { result: "PASS", detail: "No allocation limit is configured; allocation is not enforced." };
      if (!dept) return { result: "PASS", detail: "Requester has no department assigned; allocation scoping is unavailable." };
      const ym = new Date().toISOString().slice(0, 7);
      const violations = [];
      for (const it of items) {
        const usedRow = db
          .prepare(
            `SELECT IFNULL(SUM(i.quantity), 0) AS s
             FROM tbl_requisition_item i
             JOIN tbl_requisition r ON r.id = i.requisition_id
             JOIN tbl_user u ON u.userid = r.requested_by
             WHERE r.is_archived = 0 AND r.id != ? AND r.status IN ('Pending','Approved')
               AND substr(r.date_created, 1, 7) = ? AND i.product_id = ? AND IFNULL(u.department,'') = ?`
          )
          .get(r.id, ym, it.product_id, dept);
        const used = (usedRow?.s || 0) + it.quantity;
        if (used > max) violations.push(`${it.product_name} (${used}/${max} this month)`);
      }
      if (violations.length) return { result: "FAIL", detail: `Monthly allocation limit exceeded for: ${violations.join("; ")}.` };
      return { result: "PASS", detail: `All items are within the monthly allocation limit of ${max} per item.` };
    }
    case "RULE-004": {
      if (!r.requested_by || items.length === 0) return { result: "PASS", detail: "No requester or items to compare for duplicates." };
      const ph = items.map(() => "?").join(",");
      const dups = db
        .prepare(
          `SELECT DISTINCT i.product_id, r.req_no, r.date_created
           FROM tbl_requisition_item i
           JOIN tbl_requisition r ON r.id = i.requisition_id
           WHERE r.is_archived = 0 AND r.id != ? AND r.status IN ('Pending','Approved')
             AND r.requested_by = ? AND i.product_id IN (${ph})
             AND date(r.date_created) >= date('now','-7 day')`
        )
        .all(r.id, r.requested_by, ...items.map((it) => it.product_id));
      if (dups.length) {
        return { result: "FAIL", detail: `Duplicate request(s) within the last 7 days: ${dups.map((d) => `${d.req_no} (${d.date_created})`).join("; ")}.` };
      }
      return { result: "PASS", detail: "No duplicate pending or approved request for the same items within the last 7 days." };
    }
    case "RULE-005": {
      const bad = [];
      for (const it of items) {
        if (!it.product_id) { bad.push("A requested item has no product reference"); continue; }
        const p = db.prepare("SELECT pid, name, category FROM tbl_product WHERE pid = ?").get(it.product_id);
        if (!p || it.product_archived === 1 || it.product_type !== "Stock") bad.push(`${it.product_name} is not active or not a stock item`);
        else {
          const cat = db.prepare("SELECT catid FROM tbl_category WHERE catid = ?").get(p.category);
          if (!cat) bad.push(`${it.product_name} has no valid category`);
        }
      }
      if (bad.length) return { result: "FAIL", detail: bad.join("; ") + "." };
      return { result: "PASS", detail: "All requested items are active cataloged stock items with a defined category." };
    }
    case "RULE-006": {
      const purpose = String(r.purpose || "").trim();
      const threshold = Number(meta.high_value_threshold_peso) || 50000;
      if (purpose.length < 10) return { result: "FAIL", detail: "The stated purpose is too short to document the request (minimum 10 characters)." };
      const totalValue = items.reduce((s, it) => s + (it.quantity || 0) * (it.unit_cost || 0), 0);
      if (totalValue >= threshold) {
        return { result: "PASS", detail: `Total request value is ₱${totalValue.toLocaleString("en-PH")} (≥ ₱${threshold.toLocaleString("en-PH")}); flagged for review by the BAC / approving authority under RA 9184.` };
      }
      return { result: "PASS", detail: `Purpose is complete and total value ₱${totalValue.toLocaleString("en-PH")} is below the procurement threshold.` };
    }
    case "RULE-007": {
      const u = db.prepare("SELECT fullname, department FROM tbl_user WHERE userid = ?").get(r.requested_by);
      if (!u?.fullname) {
        return { result: "FAIL", detail: "Requester record is missing the full name, so the request cannot be attributed to an accountable person." };
      }
      if (!u.department) {
        return { result: "PASS", detail: `Requester "${u.fullname}" has no department on record; assign a department for fuller attribution.` };
      }
      return { result: "PASS", detail: `Requester "${u.fullname}" (${u.department}) and purpose are complete.` };
    }
    default:
      return { result: "PASS", detail: "Rule evaluated." };
  }
}

router.post("/:id/evaluate", requireAdmin, (req, res) => {
  const r = db.prepare("SELECT * FROM tbl_requisition WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!r) return res.status(404).json({ error: "Requisition not found" });
  const items = evalItemList(r);
  if (items.length === 0) return res.status(400).json({ error: "Requisition has no items to evaluate." });
  const rules = db.prepare("SELECT * FROM tbl_req_rule WHERE is_enabled = 1 ORDER BY id").all();

  const results = rules.map((rule) => ({ rule, ...runRule(rule, r, items) }));

  const hardFail = results.filter((x) => x.result === "FAIL" && (FAIL_VERDICT[x.rule.rule_code] === "reject"));
  const softFail = results.filter((x) => x.result === "FAIL" && FAIL_VERDICT[x.rule.rule_code] !== "reject");
  const passedCount = results.filter((x) => x.result === "PASS").length;
  const failedCount = results.length - passedCount;

  let recommendation;
  let reason;
  if (hardFail.length) {
    recommendation = "REJECT";
    reason = `Blocking rule(s) violated: ${hardFail.map((x) => `${x.rule.rule_code} - ${x.rule.rule_name}`).join(", ")}. ${hardFail[0].detail}`;
  } else if (softFail.length) {
    recommendation = "REVISE";
    reason = `Rule(s) requiring revision: ${softFail.map((x) => `${x.rule.rule_code} - ${x.rule.rule_name}`).join(", ")}. ${softFail[0].detail}`;
  } else {
    recommendation = "APPROVE";
    reason = "All applicable rules passed. Forward to the authorized administrator for final decision.";
  }

  const evalId = db.transaction(() => {
    const id = db
      .prepare(
        "INSERT INTO tbl_req_eval (requisition_id, evaluated_by, recommendation, reason, passed, failed) VALUES (?, ?, ?, ?, ?, ?)"
      )
      .run(r.id, req.user.userid, recommendation, reason, passedCount, failedCount).lastInsertRowid;
    const ins = db.prepare(
      "INSERT INTO tbl_req_eval_rule (eval_id, rule_id, rule_code, result, detail) VALUES (?, ?, ?, ?, ?)"
    );
    for (const x of results) ins.run(Number(id), x.rule.id, x.rule.rule_code, x.result, x.detail);
    return id;
  })();

  logActivity(req, `Rule-evaluated Requisition: ${r.req_no} → ${recommendation}`, undefined, r.id);

  res.json({
    eval_id: Number(evalId),
    recommendation,
    reason,
    passed: passedCount,
    failed: failedCount,
    rules: results.map((x) => ({
      rule_id: x.rule.id,
      rule_code: x.rule.rule_code,
      rule_name: x.rule.rule_name,
      category: x.rule.category,
      policy_basis: x.rule.policy_basis,
      result: x.result,
      detail: x.detail,
    })),
    evaluated_at: db.prepare("SELECT eval_date FROM tbl_req_eval WHERE id = ?").get(Number(evalId)).eval_date,
  });
});

router.get("/:id/evaluations", (req, res) => {
  const r = db.prepare("SELECT * FROM tbl_requisition WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!r) return res.status(404).json({ error: "Requisition not found" });
  const evals = db.prepare("SELECT * FROM tbl_req_eval WHERE requisition_id = ? ORDER BY id DESC").all(r.id);
  const out = evals.map((e) => {
    const rules = db
      .prepare(
        `SELECT er.rule_code, er.result, er.detail, rr.rule_name, rr.category, rr.policy_basis
         FROM tbl_req_eval_rule er
         LEFT JOIN tbl_req_rule rr ON rr.id = er.rule_id
         WHERE er.eval_id = ? ORDER BY er.id`
      )
      .all(e.id);
    return {
      ...e,
      evaluated_by_name: e.evaluated_by
        ? db.prepare("SELECT fullname FROM tbl_user WHERE userid = ?").get(e.evaluated_by)?.fullname || null
        : null,
      rules,
    };
  });
  res.json(out);
});

// ============ DETAILS ============

router.get("/:id", (req, res) => {
  const r = db.prepare("SELECT * FROM tbl_requisition WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!r) return res.status(404).json({ error: "Requisition not found" });
  const items = db
    .prepare(
      `SELECT i.id, i.product_id, i.quantity, p.name AS product_name, p.brand, p.stock AS current_stock, p.unit
       FROM tbl_requisition_item i
       JOIN tbl_product p ON p.pid = i.product_id
       WHERE i.requisition_id = ? AND i.is_archived = 0`
    )
    .all(r.id);
  res.json({ ...withUserInfo(r), items });
});

// ============ CREATE ============

router.post("/", (req, res) => {
  const { purpose, items } = req.body || {};
  if (!purpose || !String(purpose).trim()) {
    return res.status(400).json({ error: "Purpose is required." });
  }
  if (!Array.isArray(items) || items.length === 0) {
    return res.status(400).json({ error: "Select at least one item." });
  }
  for (const it of items) {
    const qty = Number(it.quantity);
    if (!Number.isInteger(qty) || qty <= 0) {
      return res.status(400).json({ error: "Quantities must be positive integers." });
    }
  }
  const invalid = db.prepare("SELECT pid, name, stock FROM tbl_product WHERE pid = ? AND product_type = 'Stock' AND is_archived = 0");
  for (const it of items) {
    const p = invalid.get(it.product_id);
    if (!p) {
      return res.status(400).json({ error: `Selected item (id ${it.product_id}) is not a valid stock item.` });
    }
    if (Number(it.quantity) > p.stock) {
      return res.status(400).json({
        error: `Insufficient stock for ${p.name}. Requested: ${it.quantity}, Available: ${p.stock}.`,
      });
    }
  }

  const reqNo = nextReqNo();
  const info = db.transaction(() => {
    const id = db
      .prepare("INSERT INTO tbl_requisition (req_no, purpose, requested_by) VALUES (?, ?, ?)")
      .run(reqNo, String(purpose).trim(), req.user.userid).lastInsertRowid;
    const insItem = db.prepare(
      "INSERT INTO tbl_requisition_item (requisition_id, product_id, quantity) VALUES (?, ?, ?)"
    );
    for (const it of items) insItem.run(id, it.product_id, Number(it.quantity));
        logActivity(req, `Created Requisition: ${reqNo}`, undefined, Number(id));
    return id;
  })();

  res.status(201).json({ id: Number(info), req_no: reqNo });

  const requesterName = db.prepare("SELECT fullname FROM tbl_user WHERE userid = ?").get(req.user.userid)?.fullname || req.user.username || "User";
  notifyAdmins(
    "New Requisition",
    `${reqNo} — ${requesterName} requested ${items.length} item(s) and is waiting for approval.`,
    `/requisitions/${Number(info)}`
  );
});

// ============ APPROVE ============

router.post("/:id/approve", requireAdmin, (req, res) => {
  const r = db.prepare("SELECT * FROM tbl_requisition WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!r) return res.status(404).json({ error: "Requisition not found" });
  if (r.status !== "Pending") return res.status(400).json({ error: `Only pending requisitions can be approved (current: ${r.status}).` });

  const shortages = db
    .prepare(
      `SELECT p.name AS product_name, i.quantity AS requested, p.stock AS available
       FROM tbl_requisition_item i JOIN tbl_product p ON p.pid = i.product_id
       WHERE i.requisition_id = ? AND i.is_archived = 0 AND p.stock < i.quantity`
    )
    .all(r.id);
  if (shortages.length > 0) {
    return res.status(400).json({
      error: `Insufficient stock for approval: ${shortages.map((s) => `${s.product_name} (need ${s.requested}, have ${s.available})`).join(", ")}`,
    });
  }

  db.transaction(() => {
    db.prepare("UPDATE tbl_requisition SET status = 'Approved', date_processed = datetime('now','localtime'), processed_by = ? WHERE id = ?").run(
      req.user.userid, r.id
    );
    const items = db.prepare("SELECT product_id, quantity FROM tbl_requisition_item WHERE requisition_id = ? AND is_archived = 0").all(r.id);
    const dec = db.prepare("UPDATE tbl_product SET stock = stock - ? WHERE pid = ?");
    const insOut = db.prepare(
      "INSERT INTO tbl_stockout (product_id, quantity, remarks, stockout_date) VALUES (?, ?, ?, datetime('now','localtime'))"
    );
    for (const it of items) {
      dec.run(it.quantity, it.product_id);
      insOut.run(it.product_id, it.quantity, `Auto-issued from approved requisition ${r.req_no}`);
    }
logActivity(req, `Approved Requisition: ${r.req_no}`, undefined, r.id);
  })();

  if (r.requested_by) {
    notify(
      r.requested_by,
      "Requisition Approved",
      `${r.req_no} has been approved and the items have been automatically issued to stock.`,
      `/requisitions/${r.id}`
    );
  }

  res.json({ success: true });
});

// ============ REJECT ============

router.post("/:id/reject", requireAdmin, (req, res) => {
  const r = db.prepare("SELECT * FROM tbl_requisition WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!r) return res.status(404).json({ error: "Requisition not found" });
  if (r.status !== "Pending") return res.status(400).json({ error: `Only pending requisitions can be rejected (current: ${r.status}).` });
  const { reason } = req.body || {};
  if (!reason || !String(reason).trim()) {
    return res.status(400).json({ error: "A rejection reason is required." });
  }
  db.transaction(() => {
    db.prepare(
      "UPDATE tbl_requisition SET status = 'Rejected', reject_reason = ?, date_processed = datetime('now','localtime'), processed_by = ? WHERE id = ?"
    ).run(String(reason).trim(), req.user.userid, r.id);
logActivity(req, `Rejected Requisition: ${r.req_no} - ${reason}`, undefined, r.id);
  })();
  if (r.requested_by) {
    notify(
      r.requested_by,
      "Requisition Rejected",
      `${r.req_no} was rejected. Reason: ${String(reason).trim()}`,
      `/requisitions/${r.id}`
    );
  }
  res.json({ success: true });
});

// ============ DELETE (archive) ============

router.delete("/:id", requireAdmin, (req, res) => {
  const r = db.prepare("SELECT * FROM tbl_requisition WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!r) return res.status(404).json({ error: "Requisition not found" });
  db.transaction(() => {
    db.prepare("UPDATE tbl_requisition SET is_archived = 1 WHERE id = ?").run(r.id);
    db.prepare("UPDATE tbl_requisition_item SET is_archived = 1 WHERE requisition_id = ?").run(r.id);
        logActivity(req, `Deleted Requisition: ${r.req_no}`, undefined, r.id);
  })();
  res.json({ success: true });
});

export default router;