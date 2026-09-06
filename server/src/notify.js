import db from "./db.js";

export function notify(userId, title, message, link) {
  try {
    db.prepare("INSERT INTO tbl_notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)").run(
      userId,
      String(title),
      message ? String(message) : null,
      link || null
    );
  } catch (err) {
    console.error("notify error:", err.message);
  }
}

export function notifyAdmins(title, message, link) {
  const rows = db.prepare("SELECT userid FROM tbl_user WHERE role = 'Admin' AND is_archived = 0").all();
  for (const r of rows) notify(r.userid, title, message, link);
}