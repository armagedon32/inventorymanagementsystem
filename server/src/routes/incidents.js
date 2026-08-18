import { Router } from "express";
import db from "../db.js";
import { requireAuth } from "../middleware/auth.js";

const router = Router();
router.use(requireAuth);

function nextReportNo() {
  const row = db.prepare("SELECT id FROM tbl_incident_reports ORDER BY id DESC LIMIT 1").get();
  const seq = row ? String(row.id + 1).padStart(4, "0") : "0001";
  const year = new Date().getFullYear();
  return `IR-${year}-${seq}`;
}

const officeName = (id) =>
  id ? db.prepare("SELECT office_name FROM tbl_office WHERE id = ?").get(id)?.office_name || null : null;

router.get("/", (req, res) => {
  const rows = db
    .prepare(
      `SELECT r.*, o.office_name,
        (SELECT COUNT(*) FROM tbl_incident_items i WHERE i.incident_id = r.id AND i.is_archived = 0) AS item_count
       FROM tbl_incident_reports r
       LEFT JOIN tbl_office o ON o.id = r.office
       WHERE r.is_archived = 0
       ORDER BY r.id DESC`
    )
    .all();
  res.json(rows);
});

router.get("/:id", (req, res) => {
  const r = db.prepare("SELECT * FROM tbl_incident_reports WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!r) return res.status(404).json({ error: "Incident report not found" });
  const items = db
    .prepare(
      `SELECT i.id, i.asset_id, i.quantity, i.serial_number, i.location, i.last_borrower, p.name AS asset_name, p.brand, p.barcode AS inventory_no
       FROM tbl_incident_items i
       LEFT JOIN tbl_product p ON p.pid = i.asset_id
       WHERE i.incident_id = ? AND i.is_archived = 0`
    )
    .all(r.id);
  res.json({ ...r, office_name: officeName(r.office), items });
});

router.post("/", (req, res) => {
  const { reported_by, office, incident_date, incident_time, description, extent_of_damage, items } = req.body || {};
  if (!reported_by || !String(reported_by).trim()) {
    return res.status(400).json({ error: "Reporter name is required." });
  }
  if (!description || !String(description).trim()) {
    return res.status(400).json({ error: "Incident description is required." });
  }
  if (!Array.isArray(items) || items.length === 0) {
    return res.status(400).json({ error: "Select at least one property item involved." });
  }
  const reportNo = nextReportNo();
  let newId;
  db.transaction(() => {
    newId = db
      .prepare(
        `INSERT INTO tbl_incident_reports (report_number, reported_by, office, incident_date, incident_time, description, extent_of_damage, status, is_archived)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'Open', 0)`
      )
      .run(reportNo, String(reported_by).trim(), office || null, incident_date || null, incident_time || null, String(description).trim(), extent_of_damage || "").lastInsertRowid;
    const insItem = db.prepare(
      "INSERT INTO tbl_incident_items (incident_id, asset_id, quantity, serial_number, location, last_borrower) VALUES (?, ?, ?, ?, ?, ?)"
    );
    for (const it of items) {
      const a = it.asset_id
        ? db.prepare("SELECT pid, serial_number FROM tbl_product WHERE pid = ? AND is_archived = 0").get(it.asset_id)
        : null;
      insItem.run(
        newId,
        it.asset_id || null,
        Number(it.quantity) || 1,
        it.serial_number || a?.serial_number || "",
        it.location || "",
        it.last_borrower || ""
      );
      if (a) db.prepare("UPDATE tbl_product SET condition = 'Needs Repair' WHERE pid = ? AND condition = 'Good'").run(a.pid);
    }
    db.prepare(
      "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
    ).run(req.user.userid, `Filed Incident Report: ${reportNo}`);
  })();
  res.status(201).json({ id: Number(newId), report_number: reportNo });
});

router.post("/:id/resolve", (req, res) => {
  const r = db.prepare("SELECT * FROM tbl_incident_reports WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!r) return res.status(404).json({ error: "Incident report not found" });
  if (r.status === "Resolved") return res.status(400).json({ error: "Incident already resolved." });
  db.prepare("UPDATE tbl_incident_reports SET status = 'Resolved' WHERE id = ?").run(r.id);
  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(req.user.userid, `Resolved Incident: ${r.report_number}`);
  res.json({ success: true });
});

router.delete("/:id", (req, res) => {
  const r = db.prepare("SELECT * FROM tbl_incident_reports WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!r) return res.status(404).json({ error: "Incident report not found" });
  db.transaction(() => {
    db.prepare("UPDATE tbl_incident_reports SET is_archived = 1 WHERE id = ?").run(r.id);
    db.prepare("UPDATE tbl_incident_items SET is_archived = 1 WHERE incident_id = ?").run(r.id);
    db.prepare(
      "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
    ).run(req.user.userid, `Deleted Incident: ${r.report_number}`);
  })();
  res.json({ success: true });
});

export default router;