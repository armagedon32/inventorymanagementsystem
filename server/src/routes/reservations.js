import { Router } from "express";
import db from "../db.js";
import { requireAuth, requireAdmin } from "../middleware/auth.js";

const router = Router();
router.use(requireAuth);

// ============ ROOMS ============

router.get("/rooms", (req, res) => {
  const rows = db
    .prepare("SELECT * FROM tbl_room WHERE is_archived = 0 ORDER BY room_name")
    .all();
  res.json(rows);
});

router.post("/rooms", requireAdmin, (req, res) => {
  const { room_name, capacity, location } = req.body || {};
  if (!room_name || !String(room_name).trim())
    return res.status(400).json({ error: "Room name is required." });
  const exists = db
    .prepare("SELECT id FROM tbl_room WHERE room_name = ? AND is_archived = 0")
    .get(String(room_name).trim());
  if (exists) return res.status(400).json({ error: "Room already exists." });
  const info = db
    .prepare("INSERT INTO tbl_room (room_name, capacity, location) VALUES (?, ?, ?)")
    .run(String(room_name).trim(), capacity || 0, location || "");
  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(req.user.userid, `Added Room: ${room_name}`);
  res.status(201).json({ id: Number(info.lastInsertRowid) });
});

// ============ RESERVATIONS ============

router.get("/reservations", (req, res) => {
  const { status } = req.query;
  let sql = `SELECT r.*, rm.room_name, u.fullname AS reserved_name
             FROM tbl_room_reservation r
             JOIN tbl_room rm ON rm.id = r.room_id
             LEFT JOIN tbl_user u ON u.userid = r.reserved_by
             WHERE r.is_archived = 0`;
  const params = [];
  if (status) {
    sql += " AND r.status = ?";
    params.push(status);
  }
  sql += " ORDER BY r.start_time DESC, r.id DESC";
  res.json(db.prepare(sql).all(...params));
});

router.get("/reservations/:id", (req, res) => {
  const r = db
    .prepare(
      `SELECT r.*, rm.room_name, rm.capacity, rm.location, u.fullname AS reserved_name
       FROM tbl_room_reservation r
       JOIN tbl_room rm ON rm.id = r.room_id
       LEFT JOIN tbl_user u ON u.userid = r.reserved_by
       WHERE r.id = ? AND r.is_archived = 0`
    )
    .get(req.params.id);
  if (!r) return res.status(404).json({ error: "Reservation not found" });
  res.json(r);
});

router.post("/reservations", (req, res) => {
  const { room_id, event_name, purpose, start_time, end_time } = req.body || {};
  if (!room_id) return res.status(400).json({ error: "Select a room." });
  if (!event_name || !String(event_name).trim())
    return res.status(400).json({ error: "Event name is required." });
  if (!start_time || !end_time)
    return res.status(400).json({ error: "Start and end date/time are required." });
  if (start_time >= end_time)
    return res.status(400).json({ error: "End date/time must be after start." });

  const room = db.prepare("SELECT * FROM tbl_room WHERE id = ? AND is_archived = 0").get(room_id);
  if (!room) return res.status(400).json({ error: "Room not found." });

  const clash = db
    .prepare(
      `SELECT id FROM tbl_room_reservation
       WHERE room_id = ? AND is_archived = 0 AND status != 'Cancelled'
         AND start_time < ? AND ? < end_time`
    )
    .get(room_id, end_time, start_time);
  if (clash) {
    return res.status(400).json({ error: `${room.room_name} is already booked during the selected time.` });
  }

  const info = db
    .prepare(
      "INSERT INTO tbl_room_reservation (room_id, event_name, purpose, start_time, end_time, reserved_by) VALUES (?, ?, ?, ?, ?, ?)"
    )
    .run(room_id, String(event_name).trim(), purpose || "", start_time, end_time, req.user.userid);
  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(req.user.userid, `Booked Room: ${room.room_name} - ${event_name}`);

  res.status(201).json({ id: Number(info.lastInsertRowid) });
});

router.post("/reservations/:id/cancel", (req, res) => {
  const r = db
    .prepare("SELECT * FROM tbl_room_reservation WHERE id = ? AND is_archived = 0")
    .get(req.params.id);
  if (!r) return res.status(404).json({ error: "Reservation not found" });
  if (r.status === "Cancelled")
    return res.status(400).json({ error: "This reservation is already cancelled." });
  db.prepare("UPDATE tbl_room_reservation SET status = 'Cancelled' WHERE id = ?").run(r.id);
  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(req.user.userid, `Cancelled Reservation: ${r.event_name}`);
  res.json({ success: true });
});

export default router;