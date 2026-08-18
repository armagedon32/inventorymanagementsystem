import { Router } from "express";
import db from "../db.js";
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
  const invalid = db.prepare("SELECT pid FROM tbl_product WHERE pid = ? AND product_type = 'Stock' AND is_archived = 0");
  for (const it of items) {
    if (!invalid.get(it.product_id)) {
      return res.status(400).json({ error: `Selected item (id ${it.product_id}) is not a valid stock item.` });
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
    db.prepare(
      "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
    ).run(req.user.userid, `Created Requisition: ${reqNo}`);
    return id;
  })();

  res.status(201).json({ id: Number(info), req_no: reqNo });
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
    db.prepare(
      "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
    ).run(req.user.userid, `Approved Requisition: ${r.req_no}`);
  })();

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
    db.prepare(
      "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
    ).run(req.user.userid, `Rejected Requisition: ${r.req_no} - ${reason}`);
  })();
  res.json({ success: true });
});

// ============ DELETE (archive) ============

router.delete("/:id", requireAdmin, (req, res) => {
  const r = db.prepare("SELECT * FROM tbl_requisition WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!r) return res.status(404).json({ error: "Requisition not found" });
  db.transaction(() => {
    db.prepare("UPDATE tbl_requisition SET is_archived = 1 WHERE id = ?").run(r.id);
    db.prepare("UPDATE tbl_requisition_item SET is_archived = 1 WHERE requisition_id = ?").run(r.id);
    db.prepare(
      "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
    ).run(req.user.userid, `Deleted Requisition: ${r.req_no}`);
  })();
  res.json({ success: true });
});

export default router;