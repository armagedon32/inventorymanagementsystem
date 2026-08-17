import { Router } from "express";
import db from "../db.js";
import { requireAuth } from "../middleware/auth.js";

const router = Router();
router.use(requireAuth);

function nextBarcode() {
  const row = db
    .prepare("SELECT pid, name FROM tbl_product ORDER BY pid DESC LIMIT 1")
    .get();
  const seq = row ? String(row.pid + 1).padStart(4, "0") : "0001";
  const today = new Date().toISOString().slice(0, 10).replace(/-/g, "");
  return `BC-${today}-${seq}`;
}

// ============ PRODUCTS ============

router.get("/", (req, res) => {
  const rows = db
    .prepare(
      `SELECT p.*, c.category AS category_name
       FROM tbl_product p
       LEFT JOIN tbl_category c ON p.category = c.catid
       WHERE p.is_archived = 0
       ORDER BY p.pid DESC`
    )
    .all();
  res.json(rows);
});

router.get("/:id", (req, res) => {
  const p = db
    .prepare(
      `SELECT p.*, c.category AS category_name
       FROM tbl_product p
       LEFT JOIN tbl_category c ON p.category = c.catid
       WHERE p.pid = ? AND p.is_archived = 0`
    )
    .get(req.params.id);
  if (!p) return res.status(404).json({ error: "Product not found" });
  res.json(p);
});

router.post("/", (req, res) => {
  const { barcode, name, brand, acquisition_type, category, description, stock, reorder_level } = req.body || {};
  if (!name || !category) return res.status(400).json({ error: "Name and category are required." });

  const code = barcode || nextBarcode();
  const info = db
    .prepare(
      `INSERT INTO tbl_product
        (barcode, name, brand, acquisition_type, category, description, stock, reorder_level, date_added, is_archived)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, date('now','localtime'), 0)`
    )
    .run(code, name, brand || "", acquisition_type || "Purchased", category, description || "", stock || 0, reorder_level || 0);

  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(req.user.userid, `Added New Product: ${name}`);

  if ((stock || 0) > 0) {
    db.prepare(
      "INSERT INTO tbl_stockin (product_id, quantity, remarks, stock_date) VALUES (?, ?, ?, date('now','localtime'))"
    ).run(info.lastInsertRowid, stock, "initial stock");
  }

  res.status(201).json({ pid: Number(info.lastInsertRowid) });
});

router.put("/:id", (req, res) => {
  const p = db.prepare("SELECT * FROM tbl_product WHERE pid = ?").get(req.params.id);
  if (!p) return res.status(404).json({ error: "Product not found" });

  const { barcode, name, brand, acquisition_type, category, description, reorder_level } = req.body || {};
  db.prepare(
    `UPDATE tbl_product SET
       barcode = ?, name = ?, brand = ?, acquisition_type = ?, category = ?, description = ?, reorder_level = ?
     WHERE pid = ?`
  ).run(
    barcode || p.barcode,
    name || p.name,
    brand !== undefined ? brand : p.brand,
    acquisition_type || p.acquisition_type,
    category || p.category,
    description !== undefined ? description : p.description,
    reorder_level !== undefined ? reorder_level : p.reorder_level,
    p.pid
  );

  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(req.user.userid, `Updated Product: ${name || p.name}`);

  res.json({ success: true });
});

router.delete("/:id", (req, res) => {
  const p = db.prepare("SELECT * FROM tbl_product WHERE pid = ?").get(req.params.id);
  if (!p) return res.status(404).json({ error: "Product not found" });
  db.prepare("UPDATE tbl_product SET is_archived = 1 WHERE pid = ?").run(p.pid);
  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(req.user.userid, `Deleted Product: ${p.name}`);
  res.json({ success: true });
});

// ============ STOCK IN / RESTOCK ============

router.post("/:id/stock-in", (req, res) => {
  const { quantity, remarks } = req.body || {};
  const qty = Number(quantity);
  if (!Number.isInteger(qty) || qty <= 0) {
    return res.status(400).json({ error: "Quantity must be a positive integer." });
  }
  const p = db.prepare("SELECT * FROM tbl_product WHERE pid = ? AND is_archived = 0").get(req.params.id);
  if (!p) return res.status(404).json({ error: "Product not found" });

  db.transaction(() => {
    db.prepare("UPDATE tbl_product SET stock = stock + ? WHERE pid = ?").run(qty, p.pid);
    db.prepare(
      "INSERT INTO tbl_stockin (product_id, quantity, remarks, stock_date) VALUES (?, ?, ?, datetime('now','localtime'))"
    ).run(p.pid, qty, remarks || "Restocked");
    db.prepare(
      "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
    ).run(req.user.userid, `Restocked Product: ${p.name} (+${qty})`);
  })();

  res.json({ success: true, stock: p.stock + qty });
});

// ============ STOCK OUT / ISSUANCE ============

router.post("/:id/stock-out", (req, res) => {
  const { quantity, office_id, instructor_id, remarks } = req.body || {};
  const qty = Number(quantity);
  if (!Number.isInteger(qty) || qty <= 0) {
    return res.status(400).json({ error: "Quantity must be a positive integer." });
  }
  const p = db.prepare("SELECT * FROM tbl_product WHERE pid = ? AND is_archived = 0").get(req.params.id);
  if (!p) return res.status(404).json({ error: "Product not found" });
  if (p.stock < qty) {
    return res.status(400).json({ error: `Insufficient stock. Available: ${p.stock}` });
  }

  db.transaction(() => {
    db.prepare("UPDATE tbl_product SET stock = stock - ? WHERE pid = ?").run(qty, p.pid);
    db.prepare(
      `INSERT INTO tbl_stockout (product_id, office_id, instructor_id, quantity, stockout_date, remarks)
       VALUES (?, ?, ?, ?, datetime('now','localtime'), ?)`
    ).run(p.pid, office_id || null, instructor_id || null, qty, remarks || "");
    db.prepare(
      "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
    ).run(req.user.userid, `Stock Out: ${p.name} (-${qty})`);
  })();

  res.json({ success: true, stock: p.stock - qty });
});

// ============ STOCK HISTORY ============

router.get("/:id/history", (req, res) => {
  const rows = db
    .prepare(
      `SELECT 'in' AS type, id, quantity, remarks, stock_date AS date FROM tbl_stockin WHERE product_id = ? AND is_archived = 0
       UNION ALL
       SELECT 'out' AS type, id, quantity, remarks, stockout_date AS date FROM tbl_stockout WHERE product_id = ? AND is_archived = 0
       ORDER BY date DESC, id DESC`
    )
    .all(req.params.id, req.params.id);
  res.json(rows);
});

// ============ CATEGORIES ============

router.get("/meta/categories", (req, res) => {
  const rows = db
    .prepare("SELECT * FROM tbl_category WHERE is_archived = 0 ORDER BY category")
    .all();
  res.json(rows);
});

export default router;