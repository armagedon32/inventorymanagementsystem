import db from "../db.js";
import bcrypt from "bcryptjs";
import jwt from "jsonwebtoken";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import { Router } from "express";
import { requireAuth, signToken, JWT_SECRET } from "../middleware/auth.js";

const router = Router();
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const UPLOADS_DIR = path.join(__dirname, "..", "..", "uploads");
if (!fs.existsSync(UPLOADS_DIR)) fs.mkdirSync(UPLOADS_DIR, { recursive: true });

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
    address: user.address,
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

router.put("/profile", requireAuth, (req, res) => {
  const u = db.prepare("SELECT * FROM tbl_user WHERE userid = ? AND is_archived = 0").get(req.user.userid);
  if (!u) return res.status(404).json({ error: "User not found." });

  const { fullname, useremail, contact_number, address, course, major, year_level, photo } = req.body || {};

  if (fullname !== undefined && !String(fullname).trim()) {
    return res.status(400).json({ error: "Full name cannot be empty." });
  }
  if (useremail !== undefined) {
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(useremail).trim())) {
      return res.status(400).json({ error: "Please enter a valid email address." });
    }
    const taken = db
      .prepare("SELECT userid FROM tbl_user WHERE useremail = ? AND userid != ? AND is_archived = 0")
      .get(String(useremail).trim(), u.userid);
    if (taken) return res.status(400).json({ error: "Email is already in use by another account." });
  }

  let photoPath = photo !== undefined ? photo : u.photo;
  if (photo && typeof photo === "string" && photo.startsWith("data:image")) {
    const m = photo.match(/^data:image\/(png|jpe?g|webp);base64,(.+)$/);
    if (!m) return res.status(400).json({ error: "Unsupported image format. Use PNG, JPG or WEBP." });
    const ext = m[1] === "jpeg" ? "jpg" : m[1];
    const filename = `profile-${u.userid}-${Date.now()}.${ext}`;
    const target = path.join(UPLOADS_DIR, filename);
    const data = Buffer.from(m[2], "base64");
    if (data.length > 5 * 1024 * 1024) {
      return res.status(400).json({ error: "Image is too large. Maximum size is 5 MB." });
    }
    fs.writeFileSync(target, data);
    if (u.photo && u.photo.startsWith("/uploads/")) {
      const old = path.join(UPLOADS_DIR, path.basename(u.photo));
      if (fs.existsSync(old)) fs.unlinkSync(old);
    }
    photoPath = `/uploads/${filename}`;
  }

  db.prepare(
    `UPDATE tbl_user SET fullname = ?, useremail = ?, contact_number = ?, address = ?, course = ?, major = ?, year_level = ?, photo = ? WHERE userid = ?`
  ).run(
    fullname !== undefined && String(fullname).trim() ? String(fullname).trim() : u.fullname,
    useremail !== undefined ? String(useremail).trim() : u.useremail,
    contact_number !== undefined ? contact_number : u.contact_number,
    address !== undefined ? address : u.address,
    course !== undefined ? course : u.course,
    major !== undefined ? major : u.major,
    year_level !== undefined ? year_level : u.year_level,
    photoPath,
    u.userid
  );

  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(u.userid, "Updated Profile");

  const updated = db.prepare("SELECT * FROM tbl_user WHERE userid = ?").get(u.userid);
  res.json({
    user: {
      userid: updated.userid,
      fullname: updated.fullname,
      username: updated.username,
      useremail: updated.useremail,
      role: updated.role,
      photo: updated.photo,
      contact_number: updated.contact_number,
      address: updated.address,
      course: updated.course,
      major: updated.major,
      year_level: updated.year_level,
      must_change_password: updated.must_change_password,
    },
  });
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