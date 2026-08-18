import { Router } from "express";
import db from "../db.js";
import { requireAuth } from "../middleware/auth.js";

const router = Router();
router.use(requireAuth);

function nextRisNo() {
  const row = db.prepare("SELECT id FROM tbl_ris_header ORDER BY id DESC LIMIT 1").get();
  const seq = row ? String(row.id + 1).padStart(3, "0") : "001";
  const today = new Date().toISOString().slice(0, 10).replace(/-/g, "");
  return `RIS-${today}-${seq}`;
}

function statusOf(h) {
  if (h.is_returned) return "Returned";
  if (h.end_datetime && h.end_datetime < new Date().toISOString().slice(0, 16).replace("T", " ")) return "Overdue";
  return "Borrowed";
}

const officeName = (id) =>
  id ? db.prepare("SELECT office_name FROM tbl_office WHERE id = ?").get(id)?.office_name || null : null;

router.get("/", (req, res) => {
  const rows = db
    .prepare("SELECT * FROM tbl_ris_header WHERE is_archived = 0 ORDER BY id DESC")
    .all()
    .map((h) => ({
      ...h,
      status: statusOf(h),
      item_count: db.prepare(
        "SELECT COUNT(*) AS n FROM tbl_ris_items WHERE ris_id = ? AND is_archived = 0"
      ).get(h.id).n,
    }));
  res.json(rows);
});

router.get("/:id", (req, res) => {
  const h = db.prepare("SELECT * FROM tbl_ris_header WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!h) return res.status(404).json({ error: "RIS not found" });
  const items = db
    .prepare(
      `SELECT i.id, i.asset_id, i.quantity, i.borrowed_from, o.office_name AS borrowed_from_name,
        p.name AS asset_name, p.brand, p.barcode AS inventory_no, p.serial_number
       FROM tbl_ris_items i
       LEFT JOIN tbl_product p ON p.pid = i.asset_id
       LEFT JOIN tbl_office o ON o.id = i.borrowed_from
       WHERE i.ris_id = ? AND i.is_archived = 0`
    )
    .all(h.id);
  res.json({ ...h, status: statusOf(h), items });
});

router.post("/", (req, res) => {
  const { last_name, first_name, mi_name, cp_number, position, event_name, event_date, start_datetime, end_datetime, items } = req.body || {};
  if (!last_name || !first_name || !String(last_name).trim() || !String(first_name).trim()) {
    return res.status(400).json({ error: "Borrower first and last name are required." });
  }
  if (!event_name || !String(event_name).trim()) {
    return res.status(400).json({ error: "Event name is required." });
  }
  if (!Array.isArray(items) || items.length === 0) {
    return res.status(400).json({ error: "Select at least one asset to borrow." });
  }
  const asset = db.prepare("SELECT pid, name, barcode, serial_number, stock FROM tbl_product WHERE pid = ? AND product_type = 'Asset' AND is_archived = 0");
  for (const it of items) {
    const qty = Number(it.quantity);
    if (!Number.isInteger(qty) || qty <= 0) {
      return res.status(400).json({ error: "Quantities must be positive integers." });
    }
    const a = asset.get(it.asset_id);
    if (!a) return res.status(400).json({ error: `Asset (id ${it.asset_id}) is not a valid asset.` });
    if (a.stock < qty) return res.status(400).json({ error: `Insufficient units for ${a.name}. Available: ${a.stock}.` });
  }

  const risNo = nextRisNo();
  let newId;
  db.transaction(() => {
    newId = db
      .prepare(
        `INSERT INTO tbl_ris_header (ris_no, last_name, first_name, mi_name, cp_number, position, event_name, event_date, start_datetime, end_datetime, is_archived)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)`
      )
      .run(risNo, String(last_name).trim(), String(first_name).trim(), mi_name || "", cp_number || "", position || "", String(event_name).trim(), event_date || null, start_datetime || null, end_datetime || null).lastInsertRowid;
    const insItem = db.prepare(
      "INSERT INTO tbl_ris_items (ris_id, asset_id, quantity, borrowed_from) VALUES (?, ?, ?, ?)"
    );
    for (const it of items) {
      insItem.run(newId, it.asset_id, Number(it.quantity), it.borrowed_from || null);
      db.prepare("UPDATE tbl_product SET stock = stock - ? WHERE pid = ?").run(Number(it.quantity), it.asset_id);
    }
    db.prepare(
      "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
    ).run(req.user.userid, `Created RIS: ${risNo} for ${last_name}, ${first_name}`);
  })();
  res.status(201).json({ id: Number(newId), ris_no: risNo });
});

router.post("/:id/return", (req, res) => {
  const h = db.prepare("SELECT * FROM tbl_ris_header WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!h) return res.status(404).json({ error: "RIS not found" });
  if (h.is_returned) return res.status(400).json({ error: "This RIS is already returned." });
  db.transaction(() => {
    const items = db.prepare("SELECT asset_id, quantity FROM tbl_ris_items WHERE ris_id = ? AND is_archived = 0").all(h.id);
    const inc = db.prepare("UPDATE tbl_product SET stock = stock + ? WHERE pid = ?");
    for (const it of items) inc.run(it.quantity, it.asset_id);
    db.prepare("UPDATE tbl_ris_header SET is_returned = 1, return_date = date('now','localtime') WHERE id = ?").run(h.id);
    db.prepare(
      "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
    ).run(req.user.userid, `Returned RIS: ${h.ris_no}`);
  })();
  res.json({ success: true });
});

router.delete("/:id", (req, res) => {
  const h = db.prepare("SELECT * FROM tbl_ris_header WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!h) return res.status(404).json({ error: "RIS not found" });
  db.transaction(() => {
    if (!h.is_returned) {
      const items = db.prepare("SELECT asset_id, quantity FROM tbl_ris_items WHERE ris_id = ? AND is_archived = 0").all(h.id);
      const inc = db.prepare("UPDATE tbl_product SET stock = stock + ? WHERE pid = ?");
      for (const it of items) inc.run(it.quantity, it.asset_id);
    }
    db.prepare("UPDATE tbl_ris_header SET is_archived = 1 WHERE id = ?").run(h.id);
    db.prepare("UPDATE tbl_ris_items SET is_archived = 1 WHERE ris_id = ?").run(h.id);
    db.prepare(
      "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
    ).run(req.user.userid, `Deleted RIS: ${h.ris_no}`);
  })();
  res.json({ success: true });
});

export default router;