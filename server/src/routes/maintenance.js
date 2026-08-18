import { Router } from "express";
import db from "../db.js";
import { requireAuth } from "../middleware/auth.js";

const router = Router();
router.use(requireAuth);

function nextCode() {
  const row = db.prepare("SELECT id FROM tbl_maintenance_reports ORDER BY id DESC LIMIT 1").get();
  const seq = row ? String(row.id + 1).padStart(4, "0") : "0001";
  const year = new Date().getFullYear();
  return `MC-${year}-${seq}`;
}

router.get("/", (req, res) => {
  const rows = db
    .prepare("SELECT * FROM tbl_maintenance_reports WHERE is_archived = 0 ORDER BY id DESC")
    .all()
    .map((m) => {
      let days_before_due = null;
      if (m.next_maintenance_date) {
        const due = db.prepare("SELECT CAST(julianday(?) - julianday('now','localtime') AS INTEGER) AS d").get(m.next_maintenance_date).d;
        days_before_due = Number(due);
      }
      return { ...m, days_before_due };
    });
  res.json(rows);
});

router.post("/", (req, res) => {
  const { item_name, office, brand, serial_number, maintenance_task, frequency_days, next_maintenance_date } = req.body || {};
  if (!item_name || !String(item_name).trim()) {
    return res.status(400).json({ error: "Item name is required." });
  }
  const code = nextCode();
  const info = db
    .prepare(
      `INSERT INTO tbl_maintenance_reports (item_name, office, brand, serial_number, maintenance_code, maintenance_task, frequency_days, previous_maintenance_date, next_maintenance_date, is_archived)
       VALUES (?, ?, ?, ?, ?, ?, ?, date('now','localtime'), ?, 0)`
    )
    .run(
      String(item_name).trim(), office || "", brand || "", serial_number || "",
      code, maintenance_task || "", Number(frequency_days) || 0,
      next_maintenance_date || null
    );
  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(req.user.userid, `Created Maintenance Record: ${code}`);
  res.status(201).json({ id: Number(info.lastInsertRowid), maintenance_code: code });
});

router.put("/:id", (req, res) => {
  const m = db.prepare("SELECT * FROM tbl_maintenance_reports WHERE id = ?").get(req.params.id);
  if (!m) return res.status(404).json({ error: "Maintenance record not found" });
  const { item_name, office, brand, serial_number, maintenance_task, frequency_days, next_maintenance_date } = req.body || {};
  db.prepare(
    "UPDATE tbl_maintenance_reports SET item_name = ?, office = ?, brand = ?, serial_number = ?, maintenance_task = ?, frequency_days = ?, next_maintenance_date = ? WHERE id = ?"
  ).run(
    item_name !== undefined && String(item_name).trim() ? String(item_name).trim() : m.item_name,
    office !== undefined ? office : m.office,
    brand !== undefined ? brand : m.brand,
    serial_number !== undefined ? serial_number : m.serial_number,
    maintenance_task !== undefined ? maintenance_task : m.maintenance_task,
    frequency_days !== undefined ? frequency_days : m.frequency_days,
    next_maintenance_date !== undefined ? next_maintenance_date : m.next_maintenance_date,
    m.id
  );
  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(req.user.userid, `Updated Maintenance Record: ${m.maintenance_code}`);
  res.json({ success: true });
});

router.post("/:id/complete", (req, res) => {
  const m = db.prepare("SELECT * FROM tbl_maintenance_reports WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!m) return res.status(404).json({ error: "Maintenance record not found" });
  const days = Number(m.frequency_days) || 0;
  db.prepare(
    "UPDATE tbl_maintenance_reports SET previous_maintenance_date = date('now','localtime'), next_maintenance_date = date('now','localtime', ?) WHERE id = ?"
  ).run(`+${days} days`, m.id);
  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(req.user.userid, `Completed Maintenance: ${m.maintenance_code}`);
  res.json({ success: true });
});

router.delete("/:id", (req, res) => {
  const m = db.prepare("SELECT * FROM tbl_maintenance_reports WHERE id = ?").get(req.params.id);
  if (!m) return res.status(404).json({ error: "Maintenance record not found" });
  db.prepare("UPDATE tbl_maintenance_reports SET is_archived = 1 WHERE id = ?").run(m.id);
  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(req.user.userid, `Deleted Maintenance Record: ${m.maintenance_code}`);
  res.json({ success: true });
});

export default router;