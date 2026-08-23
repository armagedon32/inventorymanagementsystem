import { Router } from "express";
import db from "../db.js";
import { logActivity } from "../activity.js";
import { requireAuth } from "../middleware/auth.js";

const router = Router();
router.use(requireAuth);

const CONDITION_OPTIONS = ["Excellent", "Good", "Slightly Damaged", "Broken"];

function nextRisNo() {
  const row = db.prepare("SELECT id FROM tbl_ris_header ORDER BY id DESC LIMIT 1").get();
  const seq = row ? String(row.id + 1).padStart(3, "0") : "001";
  const today = new Date().toISOString().slice(0, 10).replace(/-/g, "");
  return `RIS-${today}-${seq}`;
}

function statusOf(h) {
  if (h.is_returned) return "Returned";
  if (h.end_datetime && h.end_datetime < new Date().toISOString().slice(0, 16).replace("T", " ")) return "Overdue";
  return "Borrowed";
}

const officeName = (id) =>
  id ? db.prepare("SELECT office_name FROM tbl_office WHERE id = ?").get(id)?.office_name || null : null;

router.get("/", (req, res) => {
  const rows = db
    .prepare("SELECT * FROM tbl_ris_header WHERE is_archived = 0 ORDER BY id DESC")
    .all()
    .map((h) => ({
      ...h,
      status: statusOf(h),
      item_count: db.prepare(
        "SELECT COUNT(*) AS n FROM tbl_ris_items WHERE ris_id = ? AND is_archived = 0"
      ).get(h.id).n,
    }));
  res.json(rows);
});

// ============ SEED RIS DATA ============

const RIS_EVENTS = [
  "Sports Day", "Orientation Week", "Faculty Meeting", "Seminar Workshop",
  "Graduation Rehearsal", "School Program", "Foundation Day", "Science Fair",
  "Annual Concert", "Board Meeting", "Training Session", "Community Outreach",
  "Academic Week", "Career Day", "Cultural Night", "Registration Day",
  "Parents Conference", "Film Showing", "Club Assembly", "Leadership Training",
];

const RIS_POSITIONS = [
  "Faculty", "Staff", "Student", "Coordinator", "Director",
  "Manager", "Supervisor", "Officer", "Assistant", "Head",
];

function splitName(fullname) {
  const parts = (fullname || "").trim().split(/\s+/);
  if (parts.length === 0) return { last_name: "Unknown", first_name: "User", mi_name: "" };
  if (parts.length === 1) return { last_name: parts[0], first_name: "", mi_name: "" };
  if (parts.length === 2) return { last_name: parts[1], first_name: parts[0], mi_name: "" };
  return { last_name: parts[parts.length - 1], first_name: parts[0], mi_name: parts.slice(1, -1).map((p) => p.charAt(0).toUpperCase() + ".").join(" ") };
}

router.get("/seed", (req, res) => {
  try {
    const users = db.prepare("SELECT fullname, contact_number, department, role FROM tbl_user WHERE is_archived = 0").all();
    if (users.length === 0) return res.status(400).json({ error: "No users found in User Management." });

    const assets = db.prepare("SELECT pid, name, serial_number, barcode, stock, office_id, is_archived, product_type FROM tbl_product WHERE product_type = 'Asset' AND is_archived = 0").all();
    if (assets.length === 0) return res.status(400).json({ error: "No assets found at all." });

    const available = assets.filter((a) => a.stock > 0);
    if (available.length === 0) return res.status(400).json({ error: `Found ${assets.length} assets but all have stock = 0.`, debug: assets.map((a) => ({ name: a.name, stock: a.stock })) });

    let created = 0;
    const count = Math.min(50, users.length * 3);

    db.transaction(() => {
      for (let i = 0; i < count; i++) {
        const user = users[Math.floor(Math.random() * users.length)];
        const { last_name, first_name, mi_name } = splitName(user.fullname);
        const event = RIS_EVENTS[Math.floor(Math.random() * RIS_EVENTS.length)];
        const position = RIS_POSITIONS[Math.floor(Math.random() * RIS_POSITIONS.length)];

        const d = new Date();
        d.setDate(d.getDate() - Math.floor(Math.random() * 90));
        const eventDate = d.toISOString().slice(0, 10);
        const startD = new Date(d);
        startD.setHours(8 + Math.floor(Math.random() * 4), 0, 0, 0);
        const endD = new Date(d);
        endD.setDate(endD.getDate() + Math.floor(Math.random() * 7) + 1);
        endD.setHours(17, 0, 0, 0);

        const isReturned = Math.random() > 0.4;
        const risNo = nextRisNo();

        const newId = db
          .prepare(
            `INSERT INTO tbl_ris_header (ris_no, last_name, first_name, mi_name, cp_number, position, department, event_name, event_date, start_datetime, end_datetime, is_returned, return_date, is_archived)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)`
          )
          .run(
            risNo, last_name, first_name, mi_name,
            user.contact_number || "", position,
            user.department || null, event, eventDate,
            startD.toISOString().slice(0, 16).replace("T", " "),
            endD.toISOString().slice(0, 16).replace("T", " "),
            isReturned ? 1 : 0,
            isReturned ? endD.toISOString().slice(0, 10) : null
          ).lastInsertRowid;

        const itemCount = Math.floor(Math.random() * Math.min(3, assets.length)) + 1;
        const shuffled = [...assets].sort(() => Math.random() - 0.5).slice(0, itemCount);
        const conditions = ["Excellent", "Good", "Slightly Damaged"];
        const insItem = db.prepare("INSERT INTO tbl_ris_items (ris_id, asset_id, quantity, borrowed_from, condition) VALUES (?, ?, ?, ?, ?)");

        for (const a of shuffled) {
          const qty = Math.min(Math.floor(Math.random() * 2) + 1, a.stock);
          insItem.run(newId, a.pid, qty, null, conditions[Math.floor(Math.random() * conditions.length)]);
          if (!isReturned) {
            db.prepare("UPDATE tbl_product SET stock = stock - ? WHERE pid = ?").run(qty, a.pid);
          }
        }
        created++;
      }
    })();

    logActivity(req, `Seeded ${created} RIS transactions`, undefined, undefined, req.user?.userid);
    res.json({ success: true, created, users: users.length, assets: assets.length });
  } catch (err) {
    res.status(500).json({ error: "Seed failed: " + err.message });
  }
});

router.get("/:id", (req, res) => {
  const h = db.prepare("SELECT * FROM tbl_ris_header WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!h) return res.status(404).json({ error: "RIS not found" });
  const items = db
    .prepare(
      `SELECT i.id, i.asset_id, i.quantity, i.borrowed_from, i.condition, o.office_name AS borrowed_from_name,
        p.name AS asset_name, p.brand, p.barcode AS inventory_no, p.serial_number
       FROM tbl_ris_items i
       LEFT JOIN tbl_product p ON p.pid = i.asset_id
       LEFT JOIN tbl_office o ON o.id = i.borrowed_from
       WHERE i.ris_id = ? AND i.is_archived = 0`
    )
    .all(h.id);
  res.json({ ...h, status: statusOf(h), items });
});

router.post("/", (req, res) => {
  const { last_name, first_name, mi_name, cp_number, position, department, event_name, event_date, start_datetime, end_datetime, items } = req.body || {};
  if (!last_name || !first_name || !String(last_name).trim() || !String(first_name).trim()) {
    return res.status(400).json({ error: "Borrower first and last name are required." });
  }
  if (!event_name || !String(event_name).trim()) {
    return res.status(400).json({ error: "Event name is required." });
  }
  if (!Array.isArray(items) || items.length === 0) {
    return res.status(400).json({ error: "Select at least one asset to borrow." });
  }
  const asset = db.prepare("SELECT pid, name, barcode, serial_number, stock, office_id FROM tbl_product WHERE pid = ? AND product_type = 'Asset' AND is_archived = 0");
  for (const it of items) {
    const qty = Number(it.quantity);
    if (!Number.isInteger(qty) || qty <= 0) {
      return res.status(400).json({ error: "Quantities must be positive integers." });
    }
    const a = asset.get(it.asset_id);
    if (!a) return res.status(400).json({ error: `Asset (id ${it.asset_id}) is not a valid asset.` });
    if (a.office_id) {
      return res.status(400).json({
        error: `${a.name} is already assigned to ${officeName(a.office_id) || "an office"} and cannot be borrowed.`,
      });
    }
    if (a.stock < qty) return res.status(400).json({ error: `Insufficient units for ${a.name}. Available: ${a.stock}.` });
    if (it.condition && !CONDITION_OPTIONS.includes(it.condition)) {
      return res.status(400).json({ error: `Invalid condition for ${a.name}. Use Excellent, Good, Slightly Damaged or Broken.` });
    }
  }

  const risNo = nextRisNo();
  let newId;
  db.transaction(() => {
    newId = db
      .prepare(
        `INSERT INTO tbl_ris_header (ris_no, last_name, first_name, mi_name, cp_number, position, department, event_name, event_date, start_datetime, end_datetime, is_archived)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)`
      )
      .run(risNo, String(last_name).trim(), String(first_name).trim(), mi_name || "", cp_number || "", position || "", department || null, String(event_name).trim(), event_date || null, start_datetime || null, end_datetime || null).lastInsertRowid;
    const insItem = db.prepare(
      "INSERT INTO tbl_ris_items (ris_id, asset_id, quantity, borrowed_from, condition) VALUES (?, ?, ?, ?, ?)"
    );
    for (const it of items) {
      insItem.run(newId, it.asset_id, Number(it.quantity), it.borrowed_from || null, it.condition || "Good");
      db.prepare("UPDATE tbl_product SET stock = stock - ? WHERE pid = ?").run(Number(it.quantity), it.asset_id);
    }
    logActivity(req, `Created RIS: ${risNo} for ${last_name}, ${first_name}`, `RIS ${risNo} — ${items.length} item(s) borrowed`, Number(newId));
  })();
  res.status(201).json({ id: Number(newId), ris_no: risNo });
});

router.post("/:id/return", (req, res) => {
  const h = db.prepare("SELECT * FROM tbl_ris_header WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!h) return res.status(404).json({ error: "RIS not found" });
  if (h.is_returned) return res.status(400).json({ error: "This RIS is already returned." });
  db.transaction(() => {
    const items = db.prepare("SELECT asset_id, quantity FROM tbl_ris_items WHERE ris_id = ? AND is_archived = 0").all(h.id);
    const inc = db.prepare("UPDATE tbl_product SET stock = stock + ? WHERE pid = ?");
    for (const it of items) inc.run(it.quantity, it.asset_id);
    db.prepare("UPDATE tbl_ris_header SET is_returned = 1, return_date = date('now','localtime') WHERE id = ?").run(h.id);
        logActivity(req, `Returned RIS: ${h.ris_no}`);
  })();
  res.json({ success: true });
});

router.delete("/:id", (req, res) => {
  const h = db.prepare("SELECT * FROM tbl_ris_header WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!h) return res.status(404).json({ error: "RIS not found" });
  db.transaction(() => {
    if (!h.is_returned) {
      const items = db.prepare("SELECT asset_id, quantity FROM tbl_ris_items WHERE ris_id = ? AND is_archived = 0").all(h.id);
      const inc = db.prepare("UPDATE tbl_product SET stock = stock + ? WHERE pid = ?");
      for (const it of items) inc.run(it.quantity, it.asset_id);
    }
    db.prepare("UPDATE tbl_ris_header SET is_archived = 1 WHERE id = ?").run(h.id);
    db.prepare("UPDATE tbl_ris_items SET is_archived = 1 WHERE ris_id = ?").run(h.id);
        logActivity(req, `Deleted RIS: ${h.ris_no}`);
  })();
  res.json({ success: true });
});

export default router;