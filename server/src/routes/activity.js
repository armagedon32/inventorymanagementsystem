import { Router } from "express";
import db from "../db.js";
import { requireAuth, requireAdmin } from "../middleware/auth.js";

const router = Router();
router.use(requireAuth, requireAdmin);

router.get("/", (req, res) => {
  const { q, user_id, date, limit = 50, offset = 0 } = req.query;
  const where = ["1=1"];
  const params = [];
  if (q && String(q).trim()) {
    where.push("(a.action LIKE ? OR a.description LIKE ? OR a.target_id LIKE ? OR u.fullname LIKE ?)");
    const like = `%${String(q).trim()}%`;
    params.push(like, like, like, like);
  }
  if (user_id) {
    where.push("a.user_id = ?");
    params.push(Number(user_id));
  }
  if (date) {
    where.push("date(a.date_created) = ?");
    params.push(String(date));
  }

  const total = db
    .prepare(
      `SELECT COUNT(*) AS n FROM activity_log a LEFT JOIN tbl_user u ON u.userid = a.user_id WHERE ${where.join(" AND ")}`
    )
    .get(...params).n;

  const rows = db
    .prepare(
      `SELECT a.id, a.user_id, a.action, a.description, a.target_id, a.ip_address, a.date_created, u.fullname AS user_name
       FROM activity_log a LEFT JOIN tbl_user u ON u.userid = a.user_id
       WHERE ${where.join(" AND ")}
       ORDER BY a.id DESC LIMIT ? OFFSET ?`
    )
    .all(...params, Number(limit), Number(offset));

  res.json({ total, rows });
});

export default router;