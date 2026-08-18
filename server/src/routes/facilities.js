import { Router } from "express";
import db from "../db.js";
import { requireAuth } from "../middleware/auth.js";

const router = Router();
router.use(requireAuth);

function nextReqNo() {
  const row = db.prepare("SELECT id FROM tbl_facility_header ORDER BY id DESC LIMIT 1").get();
  const seq = row ? String(row.id + 1).padStart(3, "0") : "001";
  const today = new Date().toISOString().slice(0, 10).replace(/-/g, "");
  return `FAC-${today}-${seq}`;
}

router.get("/", (req, res) => {
  const rows = db
    .prepare(
      `SELECT h.*, r.room_name,
        (SELECT COUNT(*) FROM tbl_facility_equipment e WHERE e.facility_request_id = h.id AND e.is_archived = 0) AS equip_count
       FROM tbl_facility_header h
       LEFT JOIN tbl_room r ON r.id = h.facility_id
       WHERE h.is_archived = 0
       ORDER BY h.id DESC`
    )
    .all();
  res.json(rows);
});

router.get("/:id", (req, res) => {
  const h = db.prepare("SELECT * FROM tbl_facility_header WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!h) return res.status(404).json({ error: "Facility request not found" });
  const equip = db
    .prepare(
      `SELECT e.id, e.asset_id, e.quantity, e.item_name, e.description, p.name AS asset_name, p.barcode AS inventory_no
       FROM tbl_facility_equipment e
       LEFT JOIN tbl_product p ON p.pid = e.asset_id
       WHERE e.facility_request_id = ? AND e.is_archived = 0`
    )
    .all(h.id);
  const room = h.facility_id
    ? db.prepare("SELECT room_name, capacity, location FROM tbl_room WHERE id = ?").get(h.facility_id)
    : null;
  res.json({ ...h, room, equip });
});

router.post("/", (req, res) => {
  const { office_or_org, requesting_name, contact_no, address, date_of_filing, event_name, num_participants, start_datetime, end_datetime, facility_id, equipment } = req.body || {};
  if (!event_name || !String(event_name).trim()) {
    return res.status(400).json({ error: "Event name is required." });
  }
  if (!facility_id) return res.status(400).json({ error: "Select the facility/room to book." });

  // Overlap check against existing active requests for the same room
  const overlap = db.prepare(
    `SELECT request_no FROM tbl_facility_header
     WHERE facility_id = ? AND is_archived = 0 AND status IN ('Pending','Issued')
       AND ((? BETWEEN start_datetime AND end_datetime) OR (? BETWEEN start_datetime AND end_datetime) OR (start_datetime BETWEEN ? AND ?))
     LIMIT 1`
  );
  if (start_datetime && end_datetime) {
    const hit = overlap.get(facility_id, start_datetime, end_datetime, start_datetime, end_datetime);
    if (hit) return res.status(400).json({ error: `Time slot overlaps with existing request ${hit.request_no}.` });
  }

  const equipmentArr = Array.isArray(equipment) ? equipment : [];
  for (const it of equipmentArr) {
    if (it.asset_id) {
      const a = db.prepare("SELECT pid, name, stock FROM tbl_product WHERE pid = ? AND product_type = 'Asset' AND is_archived = 0").get(it.asset_id);
      if (!a) return res.status(400).json({ error: `Asset (id ${it.asset_id}) is not valid.` });
      if (Number(it.quantity) > a.stock) return res.status(400).json({ error: `Insufficient units for ${a.name}. Available: ${a.stock}.` });
    }
  }

  const reqNo = nextReqNo();
  let newId;
  db.transaction(() => {
    newId = db
      .prepare(
        `INSERT INTO tbl_facility_header (request_no, office_or_org, requesting_name, contact_no, address, date_of_filing, event_name, num_participants, start_datetime, end_datetime, facility_id, status, is_archived)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 0)`
      )
      .run(
        reqNo, office_or_org || "", requesting_name || "", contact_no || "", address || "",
        date_of_filing || null, String(event_name).trim(), Number(num_participants) || 0,
        start_datetime || null, end_datetime || null, facility_id
      ).lastInsertRowid;
    const insEquip = db.prepare(
      "INSERT INTO tbl_facility_equipment (facility_request_id, asset_id, quantity, item_name, description) VALUES (?, ?, ?, ?, ?)"
    );
    for (const it of equipmentArr) {
      let itemName = "";
      let desc = "";
      if (it.asset_id) {
        const a = db.prepare("SELECT name, description FROM tbl_product WHERE pid = ?").get(it.asset_id);
        itemName = a.name;
        desc = a.description;
      } else {
        itemName = it.item_name || "";
        desc = it.description || "";
      }
      insEquip.run(newId, it.asset_id || null, Number(it.quantity) || 1, itemName, desc);
    }
    db.prepare(
      "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
    ).run(req.user.userid, `Filed Facility Request: ${reqNo}`);
  })();
  res.status(201).json({ id: Number(newId), request_no: reqNo });
});

router.post("/:id/approve", (req, res) => {
  const h = db.prepare("SELECT * FROM tbl_facility_header WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!h) return res.status(404).json({ error: "Facility request not found" });
  if (h.status !== "Pending") return res.status(400).json({ error: `Only pending requests can be approved (current: ${h.status}).` });
  db.transaction(() => {
    const equip = db.prepare("SELECT asset_id, quantity FROM tbl_facility_equipment WHERE facility_request_id = ? AND is_archived = 0").all(h.id);
    for (const it of equip) {
      if (it.asset_id) db.prepare("UPDATE tbl_product SET stock = stock - ? WHERE pid = ?").run(it.quantity, it.asset_id);
    }
    db.prepare("UPDATE tbl_facility_header SET status = 'Issued' WHERE id = ?").run(h.id);
    db.prepare(
      "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
    ).run(req.user.userid, `Approved Facility Request: ${h.request_no}`);
  })();
  res.json({ success: true });
});

router.post("/:id/return", (req, res) => {
  const h = db.prepare("SELECT * FROM tbl_facility_header WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!h) return res.status(404).json({ error: "Facility request not found" });
  if (h.status !== "Issued") return res.status(400).json({ error: `Only issued requests can be returned (current: ${h.status}).` });
  db.transaction(() => {
    const equip = db.prepare("SELECT asset_id, quantity FROM tbl_facility_equipment WHERE facility_request_id = ? AND is_archived = 0").all(h.id);
    for (const it of equip) {
      if (it.asset_id) db.prepare("UPDATE tbl_product SET stock = stock + ? WHERE pid = ?").run(it.quantity, it.asset_id);
    }
    db.prepare("UPDATE tbl_facility_header SET status = 'Returned' WHERE id = ?").run(h.id);
    db.prepare(
      "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
    ).run(req.user.userid, `Returned Facility Request: ${h.request_no}`);
  })();
  res.json({ success: true });
});

router.post("/:id/cancel", (req, res) => {
  const h = db.prepare("SELECT * FROM tbl_facility_header WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!h) return res.status(404).json({ error: "Facility request not found" });
  if (h.status !== "Pending") return res.status(400).json({ error: `Only pending requests can be cancelled (current: ${h.status}).` });
  db.prepare("UPDATE tbl_facility_header SET status = 'Cancelled' WHERE id = ?").run(h.id);
  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(req.user.userid, `Cancelled Facility Request: ${h.request_no}`);
  res.json({ success: true });
});

router.delete("/:id", (req, res) => {
  const h = db.prepare("SELECT * FROM tbl_facility_header WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!h) return res.status(404).json({ error: "Facility request not found" });
  db.transaction(() => {
    if (h.status === "Issued") {
      const equip = db.prepare("SELECT asset_id, quantity FROM tbl_facility_equipment WHERE facility_request_id = ? AND is_archived = 0").all(h.id);
      for (const it of equip) {
        if (it.asset_id) db.prepare("UPDATE tbl_product SET stock = stock + ? WHERE pid = ?").run(it.quantity, it.asset_id);
      }
    }
    db.prepare("UPDATE tbl_facility_header SET is_archived = 1 WHERE id = ?").run(h.id);
    db.prepare("UPDATE tbl_facility_equipment SET is_archived = 1 WHERE facility_request_id = ?").run(h.id);
    db.prepare(
      "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
    ).run(req.user.userid, `Deleted Facility Request: ${h.request_no}`);
  })();
  res.json({ success: true });
});

export default router;