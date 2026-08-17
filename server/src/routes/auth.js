import db from "../db.js";
import bcrypt from "bcryptjs";
import { Router } from "express";
import { requireAuth, signToken } from "../middleware/auth.js";

const router = Router();

router.post("/login", (req, res) => {
  const { identifier, password } = req.body;
  if (!identifier || !password) {
    return res.status(400).json({ error: "Email/Username and password are required." });
  }

  const user = db
    .prepare(
      `SELECT * FROM tbl_user
       WHERE (useremail = ? OR username = ? OR fullname = ?)
         AND is_archived = 0
       LIMIT 1`
    )
    .get(identifier, identifier, identifier);

  if (!user) {
    return res.status(401).json({ error: "User not found." });
  }

  const ok = bcrypt.compareSync(password, user.userpassword);
  if (!ok) {
    return res.status(401).json({ error: "Incorrect Password." });
  }

  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(user.userid, `${user.role} Logged In`);

  const token = signToken(user);
  res.json({
    token,
    user: {
      userid: user.userid,
      fullname: user.fullname,
      username: user.username,
      useremail: user.useremail,
      role: user.role,
      photo: user.photo,
      must_change_password: user.must_change_password,
    },
  });
});

router.get("/me", requireAuth, (req, res) => {
  const user = db.prepare("SELECT * FROM tbl_user WHERE userid = ? AND is_archived = 0").get(req.user.userid);
  if (!user) return res.status(401).json({ error: "User not found." });
  res.json({
    userid: user.userid,
    fullname: user.fullname,
    username: user.username,
    useremail: user.useremail,
    role: user.role,
    photo: user.photo,
    contact_number: user.contact_number,
    course: user.course,
    major: user.major,
    year_level: user.year_level,
    must_change_password: user.must_change_password,
  });
});

router.post("/logout", requireAuth, (req, res) => {
  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(req.user.userid, "User Logged Out");
  res.json({ success: true });
});

router.post("/change-password", requireAuth, (req, res) => {
  const { currentPassword, newPassword } = req.body;
  if (!currentPassword || !newPassword) {
    return res.status(400).json({ error: "Current and new password are required." });
  }
  const user = db.prepare("SELECT * FROM tbl_user WHERE userid = ?").get(req.user.userid);
  if (!user) return res.status(404).json({ error: "User not found." });
  if (!bcrypt.compareSync(currentPassword, user.userpassword)) {
    return res.status(400).json({ error: "Current password is incorrect." });
  }
  const hash = bcrypt.hashSync(newPassword, 10);
  db.prepare("UPDATE tbl_user SET userpassword = ?, must_change_password = 0 WHERE userid = ?").run(hash, req.user.userid);
  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(req.user.userid, "Password Changed");
  res.json({ success: true });
});

export default router;