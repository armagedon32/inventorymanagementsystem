import { Router } from "express";
import db from "../db.js";
import bcrypt from "bcryptjs";
import { requireAuth, requireAdmin } from "../middleware/auth.js";

const router = Router();
router.use(requireAuth);

router.get("/", requireAdmin, (req, res) => {
  const rows = db
    .prepare("SELECT * FROM tbl_user WHERE is_archived = 0 ORDER BY userid DESC")
    .all()
    .map((u) => ({ ...u, userpassword: undefined }));
  res.json(rows);
});

router.get("/offices", (req, res) => {
  const rows = db
    .prepare("SELECT id, office_name, address FROM tbl_office WHERE is_archived = 0 ORDER BY office_name")
    .all();
  res.json(rows);
});

router.get("/instructors", (req, res) => {
  const rows = db
    .prepare("SELECT * FROM tbl_instructors WHERE is_archived = 0 ORDER BY fullname")
    .all();
  res.json(rows);
});

router.post("/", requireAdmin, (req, res) => {
  const {
    fullname, username, useremail, contact_number, department,
    password, role, photo,
  } = req.body || {};
  if (!fullname || !username || !useremail || !password || !role) {
    return res.status(400).json({ error: "Fullname, username, email, password and role are required." });
  }
  const exists = db
    .prepare("SELECT userid FROM tbl_user WHERE (username = ? OR useremail = ?) AND is_archived = 0")
    .get(username, useremail);
  if (exists) return res.status(400).json({ error: "Username or email already taken." });

  const hash = bcrypt.hashSync(password, 10);
  const info = db
    .prepare(
      `INSERT INTO tbl_user (fullname, username, useremail, contact_number, department, userpassword, role, photo, is_archived)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)`
    )
    .run(fullname, username, useremail, contact_number || null, department || null, hash, role, photo || null);

  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(req.user.userid, `Added New User: ${fullname}`);

  res.status(201).json({ userid: Number(info.lastInsertRowid) });
});

router.put("/:id", requireAdmin, (req, res) => {
  const u = db.prepare("SELECT * FROM tbl_user WHERE userid = ?").get(req.params.id);
  if (!u) return res.status(404).json({ error: "User not found" });

  const { fullname, username, useremail, contact_number, department, role, photo, password } = req.body || {};
  db.prepare(
    `UPDATE tbl_user SET fullname = ?, username = ?, useremail = ?, contact_number = ?, department = ?, role = ?, photo = ?
     WHERE userid = ?`
  ).run(
    fullname || u.fullname,
    username || u.username,
    useremail || u.useremail,
    contact_number !== undefined ? contact_number : u.contact_number,
    department !== undefined ? department : u.department,
    role || u.role,
    photo !== undefined ? photo : u.photo,
    u.userid
  );

  if (password) {
    const hash = bcrypt.hashSync(password, 10);
    db.prepare("UPDATE tbl_user SET userpassword = ?, must_change_password = 1 WHERE userid = ?").run(hash, u.userid);
  }

  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(req.user.userid, `Updated User: ${fullname || u.fullname}`);

  res.json({ success: true });
});

router.delete("/:id", requireAdmin, (req, res) => {
  const u = db.prepare("SELECT * FROM tbl_user WHERE userid = ?").get(req.params.id);
  if (!u) return res.status(404).json({ error: "User not found" });
  if (Number(u.userid) === 1) return res.status(400).json({ error: "Cannot archive the primary admin." });
  db.prepare("UPDATE tbl_user SET is_archived = 1 WHERE userid = ?").run(u.userid);
  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(req.user.userid, `Archived User: ${u.fullname}`);
  res.json({ success: true });
});

export default router;