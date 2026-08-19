import { Router } from "express";
import db from "../db.js";
import { logActivity } from "../activity.js";
import { requireAuth } from "../middleware/auth.js";

const router = Router();
router.use(requireAuth);

function nextPtrNo() {
  const row = db.prepare("SELECT id FROM tbl_ptr_header ORDER BY id DESC LIMIT 1").get();
  const seq = row ? String(row.id + 1).padStart(3, "0") : "001";
  const today = new Date().toISOString().slice(0, 10).replace(/-/g, "");
  return `PTR-${today}-${seq}`;
}

const officeName = (id) =>
  id ? db.prepare("SELECT office_name FROM tbl_office WHERE id = ?").get(id)?.office_name || null : null;

router.get("/", (req, res) => {
  const rows = db
    .prepare(
      `SELECT h.*, o1.office_name AS from_office_name, o2.office_name AS to_office_name,
        (SELECT COUNT(*) FROM tbl_ptr_items i WHERE i.ptr_id = h.id AND i.is_archived = 0) AS item_count
       FROM tbl_ptr_header h
       LEFT JOIN tbl_office o1 ON o1.id = h.from_office
       LEFT JOIN tbl_office o2 ON o2.id = h.to_office
       WHERE h.is_archived = 0
       ORDER BY h.id DESC`
    )
    .all();
  res.json(rows);
});

router.get("/:id", (req, res) => {
  const h = db.prepare("SELECT * FROM tbl_ptr_header WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!h) return res.status(404).json({ error: "PTR not found" });
  const items = db
    .prepare(
      `SELECT i.id, i.asset_id, i.inventory_no, i.description, i.quantity, p.name AS asset_name, p.brand, p.serial_number, p.condition
       FROM tbl_ptr_items i
       LEFT JOIN tbl_product p ON p.pid = i.asset_id
       WHERE i.ptr_id = ? AND i.is_archived = 0`
    )
    .all(h.id);
  res.json({ ...h, from_office_name: officeName(h.from_office), to_office_name: officeName(h.to_office), items });
});

router.post("/", (req, res) => {
  const { from_office, to_office, transfer_date, remarks, items } = req.body || {};
  if (!from_office || !to_office) return res.status(400).json({ error: "Select source and destination offices." });
  if (Number(from_office) === Number(to_office)) {
    return res.status(400).json({ error: "Source and destination offices must be different." });
  }
  if (!Array.isArray(items) || items.length === 0) {
    return res.status(400).json({ error: "Select at least one asset to transfer." });
  }
  const owned = db.prepare("SELECT pid, name, barcode, description, stock FROM tbl_product WHERE pid = ? AND product_type = 'Asset' AND is_archived = 0 AND office_id = ?");
  for (const it of items) {
    const a = owned.get(it.asset_id, from_office);
    if (!a) {
      return res.status(400).json({ error: `Asset (id ${it.asset_id}) is not a valid asset owned by the source office.` });
    }
  }

  const ptrNo = nextPtrNo();
  let newId;
  db.transaction(() => {
    newId = db
      .prepare(
        "INSERT INTO tbl_ptr_header (ptr_no, transfer_date, from_office, to_office, remarks, is_archived) VALUES (?, ?, ?, ?, ?, 0)"
      )
      .run(ptrNo, transfer_date || new Date().toISOString().slice(0, 10), from_office, to_office, remarks || "").lastInsertRowid;
    const insItem = db.prepare(
      "INSERT INTO tbl_ptr_items (ptr_id, asset_id, inventory_no, description, quantity) VALUES (?, ?, ?, ?, ?)"
    );
    for (const it of items) {
      const a = owned.get(it.asset_id, from_office);
      insItem.run(newId, it.asset_id, a.barcode, a.description, a.stock);
      db.prepare("UPDATE tbl_product SET office_id = ?, assigned_to = ? WHERE pid = ?").run(
        to_office, officeName(to_office), it.asset_id
      );
    }
        logActivity(req, `Created PTR: ${ptrNo}`);
  })();
  res.status(201).json({ id: Number(newId), ptr_no: ptrNo });
});

router.delete("/:id", (req, res) => {
  const h = db.prepare("SELECT * FROM tbl_ptr_header WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!h) return res.status(404).json({ error: "PTR not found" });
  db.transaction(() => {
    db.prepare("UPDATE tbl_ptr_header SET is_archived = 1 WHERE id = ?").run(h.id);
    db.prepare("UPDATE tbl_ptr_items SET is_archived = 1 WHERE ptr_id = ?").run(h.id);
        logActivity(req, `Deleted PTR: ${h.ptr_no}`);
  })();
  res.json({ success: true });
});

export default router;