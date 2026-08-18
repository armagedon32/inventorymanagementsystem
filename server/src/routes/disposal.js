import { Router } from "express";
import db from "../db.js";
import { requireAuth } from "../middleware/auth.js";

const router = Router();
router.use(requireAuth);

function nextDisNo() {
  const row = db.prepare("SELECT id FROM tbl_disposal ORDER BY id DESC LIMIT 1").get();
  const seq = row ? String(row.id + 1).padStart(3, "0") : "001";
  const today = new Date().toISOString().slice(0, 10).replace(/-/g, "");
  return `DIS-${today}-${seq}`;
}

const officeName = (id) =>
  id ? db.prepare("SELECT office_name FROM tbl_office WHERE id = ?").get(id)?.office_name || null : null;

router.get("/", (req, res) => {
  const rows = db
    .prepare(
      `SELECT d.*, o.office_name, u.fullname AS disposed_by_name
       FROM tbl_disposal d
       LEFT JOIN tbl_office o ON o.id = d.office_id
       LEFT JOIN tbl_user u ON u.userid = d.disposed_by
       WHERE d.is_archived = 0
       ORDER BY d.id DESC`
    )
    .all();
  res.json(rows);
});

router.get("/:id", (req, res) => {
  const d = db.prepare("SELECT * FROM tbl_disposal WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!d) return res.status(404).json({ error: "Disposal not found" });
  res.json({ ...d, office_name: officeName(d.office_id) });
});

router.post("/", (req, res) => {
  const { asset_id, quantity, remarks } = req.body || {};
  const qty = Number(quantity);
  if (!Number.isInteger(qty) || qty <= 0) {
    return res.status(400).json({ error: "Quantity must be a positive integer." });
  }
  const a = db.prepare("SELECT * FROM tbl_product WHERE pid = ? AND product_type = 'Asset' AND is_archived = 0").get(asset_id);
  if (!a) return res.status(400).json({ error: "Select a valid asset." });
  if (a.stock < qty) return res.status(400).json({ error: `Insufficient units. Available: ${a.stock}.` });
  if (a.condition === "Disposed") return res.status(400).json({ error: "This asset is already marked as disposed." });

  const disNo = nextDisNo();
  let newId;
  db.transaction(() => {
    newId = db
      .prepare(
        "INSERT INTO tbl_disposal (dis_no, asset_id, item_name, inventory_no, serial_number, office_id, quantity, remarks, disposed_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
      )
      .run(disNo, a.pid, a.name, a.barcode, a.serial_number || "", a.office_id, qty, remarks || "", req.user.userid).lastInsertRowid;
    db.prepare("UPDATE tbl_product SET stock = stock - ? WHERE pid = ?").run(qty, a.pid);
    const left = db.prepare("SELECT stock FROM tbl_product WHERE pid = ?").get(a.pid).stock;
    if (left <= 0) db.prepare("UPDATE tbl_product SET condition = 'Disposed' WHERE pid = ?").run(a.pid);
    db.prepare(
      "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
    ).run(req.user.userid, `Disposed ${qty} unit(s) of ${a.name}: ${disNo}`);
  })();
  res.status(201).json({ id: Number(newId), dis_no: disNo });
});

router.delete("/:id", (req, res) => {
  const d = db.prepare("SELECT * FROM tbl_disposal WHERE id = ? AND is_archived = 0").get(req.params.id);
  if (!d) return res.status(404).json({ error: "Disposal not found" });
  db.transaction(() => {
    db.prepare("UPDATE tbl_product SET stock = stock + ? WHERE pid = ?").run(d.quantity, d.asset_id);
    db.prepare("UPDATE tbl_product SET condition = 'Good' WHERE pid = ? AND stock > 0").run(d.asset_id);
    db.prepare("UPDATE tbl_disposal SET is_archived = 1 WHERE id = ?").run(d.id);
    db.prepare(
      "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
    ).run(req.user.userid, `Reversed Disposal: ${d.dis_no}`);
  })();
  res.json({ success: true });
});

export default router;