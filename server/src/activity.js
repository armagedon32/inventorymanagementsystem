import db from "./db.js";

export function logActivity(req, action, description, targetId, userId) {
  const ip = req.headers["x-forwarded-for"]
    ? String(req.headers["x-forwarded-for"]).split(",")[0].trim()
    : req.socket?.remoteAddress || "";
  db.prepare(
    "INSERT INTO activity_log (user_id, action, description, target_id, ip_address, date_created) VALUES (?, ?, ?, ?, ?, datetime('now','localtime'))"
  ).run(userId ?? req.user?.userid, action, description ?? action, targetId ?? null, ip);
}