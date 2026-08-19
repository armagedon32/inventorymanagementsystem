import { Router } from "express";
import db from "../db.js";
import { logActivity } from "../activity.js";
import { requireAuth } from "../middleware/auth.js";

const router = Router();
router.use(requireAuth);

function log(userId, action) {
  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(userId, action);
}

// ============ OFFICES ============

router.get("/offices", (req, res) => {
  const rows = db
    .prepare(
      `SELECT o.*, p.office_name AS parent_name,
        (SELECT COUNT(*) FROM tbl_product pr WHERE pr.office_id = o.id AND pr.is_archived = 0) AS asset_count
       FROM tbl_office o
       LEFT JOIN tbl_office p ON p.id = o.parent_id
       WHERE o.is_archived = 0
       ORDER BY o.office_name`
    )
    .all();
  res.json(rows);
});

router.post("/offices", (req, res) => {
  const { office_name, parent_id, address, contact, max_capacity, instructor_id } = req.body || {};
  if (!office_name || !String(office_name).trim())
    return res.status(400).json({ error: "Office name is required." });
  const exists = db
    .prepare("SELECT id FROM tbl_office WHERE office_name = ? AND is_archived = 0")
    .get(String(office_name).trim());
  if (exists) return res.status(400).json({ error: "Office already exists." });
  const info = db
    .prepare(
      "INSERT INTO tbl_office (parent_id, office_name, address, contact, max_capacity, instructor_id, is_archived) VALUES (?, ?, ?, ?, ?, ?, 0)"
    )
    .run(parent_id || null, String(office_name).trim(), address || "", contact || "", max_capacity || 0, instructor_id || null);
  logActivity(req, `Added Office: ${office_name}`);
  res.status(201).json({ id: Number(info.lastInsertRowid) });
});

router.put("/offices/:id", (req, res) => {
  const o = db.prepare("SELECT * FROM tbl_office WHERE id = ?").get(req.params.id);
  if (!o) return res.status(404).json({ error: "Office not found" });
  const { office_name, parent_id, address, contact, max_capacity, instructor_id } = req.body || {};
  db.prepare(
    "UPDATE tbl_office SET office_name = ?, parent_id = ?, address = ?, contact = ?, max_capacity = ?, instructor_id = ? WHERE id = ?"
  ).run(
    office_name !== undefined && String(office_name).trim() ? String(office_name).trim() : o.office_name,
    parent_id !== undefined ? parent_id : o.parent_id,
    address !== undefined ? address : o.address,
    contact !== undefined ? contact : o.contact,
    max_capacity !== undefined ? max_capacity : o.max_capacity,
    instructor_id !== undefined ? instructor_id : o.instructor_id,
    o.id
  );
  logActivity(req, `Updated Office: ${o.office_name}`);
  res.json({ success: true });
});

router.delete("/offices/:id", (req, res) => {
  const o = db.prepare("SELECT * FROM tbl_office WHERE id = ?").get(req.params.id);
  if (!o) return res.status(404).json({ error: "Office not found" });
  db.transaction(() => {
    db.prepare("UPDATE tbl_office SET is_archived = 1 WHERE id = ?").run(o.id);
    db.prepare("UPDATE tbl_office SET parent_id = NULL WHERE parent_id = ?").run(o.id);
    logActivity(req, `Deleted Office: ${o.office_name}`);
  })();
  res.json({ success: true });
});

// ============ INSTRUCTORS ============

router.get("/instructors", (req, res) => {
  const rows = db
    .prepare("SELECT * FROM tbl_instructors WHERE is_archived = 0 ORDER BY fullname")
    .all();
  res.json(rows);
});

router.post("/instructors", (req, res) => {
  const { fullname, contact, email, assigned_dept } = req.body || {};
  if (!fullname || !String(fullname).trim())
    return res.status(400).json({ error: "Instructor name is required." });
  const info = db
    .prepare(
      "INSERT INTO tbl_instructors (fullname, contact, email, assigned_dept, is_archived) VALUES (?, ?, ?, ?, 0)"
    )
    .run(String(fullname).trim(), contact || "", email || "", assigned_dept || "");
  logActivity(req, `Added Instructor: ${fullname}`);
  res.status(201).json({ id: Number(info.lastInsertRowid) });
});

router.put("/instructors/:id", (req, res) => {
  const i = db.prepare("SELECT * FROM tbl_instructors WHERE id = ?").get(req.params.id);
  if (!i) return res.status(404).json({ error: "Instructor not found" });
  const { fullname, contact, email, assigned_dept } = req.body || {};
  db.prepare("UPDATE tbl_instructors SET fullname = ?, contact = ?, email = ?, assigned_dept = ? WHERE id = ?").run(
    fullname !== undefined && String(fullname).trim() ? String(fullname).trim() : i.fullname,
    contact !== undefined ? contact : i.contact,
    email !== undefined ? email : i.email,
    assigned_dept !== undefined ? assigned_dept : i.assigned_dept,
    i.id
  );
  logActivity(req, `Updated Instructor: ${i.fullname}`);
  res.json({ success: true });
});

router.delete("/instructors/:id", (req, res) => {
  const i = db.prepare("SELECT * FROM tbl_instructors WHERE id = ?").get(req.params.id);
  if (!i) return res.status(404).json({ error: "Instructor not found" });
  db.prepare("UPDATE tbl_instructors SET is_archived = 1 WHERE id = ?").run(i.id);
  logActivity(req, `Deleted Instructor: ${i.fullname}`);
  res.json({ success: true });
});

// ============ ORGANIZATIONS ============

router.get("/organizations", (req, res) => {
  const rows = db
    .prepare(
      `SELECT o.*, (SELECT COUNT(*) FROM tbl_facility_header f WHERE f.office_or_org = o.org_name AND f.is_archived = 0) AS use_count
       FROM tbl_organization o WHERE o.is_archived = 0 ORDER BY o.org_name`
    )
    .all();
  res.json(rows);
});

router.post("/organizations", (req, res) => {
  const { org_name, president, org_logo } = req.body || {};
  if (!org_name || !String(org_name).trim())
    return res.status(400).json({ error: "Organization name is required." });
  const info = db
    .prepare(
      "INSERT INTO tbl_organization (org_name, president, org_logo, is_archived) VALUES (?, ?, ?, 0)"
    )
    .run(String(org_name).trim(), president || "", org_logo || "");
  logActivity(req, `Added Organization: ${org_name}`);
  res.status(201).json({ id: Number(info.lastInsertRowid) });
});

router.put("/organizations/:id", (req, res) => {
  const org = db.prepare("SELECT * FROM tbl_organization WHERE id = ?").get(req.params.id);
  if (!org) return res.status(404).json({ error: "Organization not found" });
  const { org_name, president, org_logo } = req.body || {};
  db.prepare("UPDATE tbl_organization SET org_name = ?, president = ?, org_logo = ? WHERE id = ?").run(
    org_name !== undefined && String(org_name).trim() ? String(org_name).trim() : org.org_name,
    president !== undefined ? president : org.president,
    org_logo !== undefined ? org_logo : org.org_logo,
    org.id
  );
  logActivity(req, `Updated Organization: ${org.org_name}`);
  res.json({ success: true });
});

router.delete("/organizations/:id", (req, res) => {
  const org = db.prepare("SELECT * FROM tbl_organization WHERE id = ?").get(req.params.id);
  if (!org) return res.status(404).json({ error: "Organization not found" });
  db.prepare("UPDATE tbl_organization SET is_archived = 1 WHERE id = ?").run(org.id);
  logActivity(req, `Deleted Organization: ${org.org_name}`);
  res.json({ success: true });
});

export default router;