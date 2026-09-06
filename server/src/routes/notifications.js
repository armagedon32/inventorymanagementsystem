import { Router } from "express";
import db from "../db.js";
import { requireAuth } from "../middleware/auth.js";

const router = Router();
router.use(requireAuth);

// ============ LIST ============

router.get("/", (req, res) => {
  const items = db
    .prepare("SELECT * FROM tbl_notifications WHERE user_id = ? ORDER BY id DESC LIMIT 40")
    .all(req.user.userid);
  const unread = db
    .prepare("SELECT COUNT(*) AS c FROM tbl_notifications WHERE user_id = ? AND is_read = 0")
    .get(req.user.userid).c;
  res.json({ items, unread });
});

// ============ MARK ONE AS READ ============

router.post("/:id/read", (req, res) => {
  db.prepare("UPDATE tbl_notifications SET is_read = 1 WHERE id = ? AND user_id = ?").run(
    req.params.id,
    req.user.userid
  );
  res.json({ success: true });
});

// ============ MARK ALL AS READ ============

router.post("/read-all", (req, res) => {
  db.prepare("UPDATE tbl_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0").run(req.user.userid);
  res.json({ success: true });
});

export default router;