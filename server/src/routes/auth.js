import db from "../db.js";
import bcrypt from "bcryptjs";
import jwt from "jsonwebtoken";
import { Router } from "express";
import { requireAuth, signToken, JWT_SECRET } from "../middleware/auth.js";

const router = Router();

function makeCaptcha() {
  const a = 1 + Math.floor(Math.random() * 20);
  const b = 1 + Math.floor(Math.random() * 20);
  const add = Math.random() < 0.5;
  const bigger = Math.max(a, b);
  const smaller = Math.min(a, b);
  const question = add ? `${bigger} + ${smaller}` : `${bigger} - ${smaller}`;
  const answer = add ? bigger + smaller : bigger - smaller;
  const token = jwt.sign({ a: answer }, JWT_SECRET, { expiresIn: "5m" });
  return { question: `${question} = ?`, token };
}

function verifyCaptcha(token, answer) {
  try {
    const payload = jwt.verify(token, JWT_SECRET);
    return payload.a === Number(answer);
  } catch {
    return false;
  }
}

router.get("/captcha", (req, res) => {
  res.json(makeCaptcha());
});

router.post("/login", (req, res) => {
  const { identifier, password, captchaToken, captchaAnswer } = req.body;
  if (!identifier || !password) {
    return res.status(400).json({ error: "Email/Username and password are required." });
  }

  if (!verifyCaptcha(captchaToken, captchaAnswer)) {
    return res.status(400).json({ error: "Incorrect CAPTCHA answer. Please try again." });
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