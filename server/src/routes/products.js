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
  const type = req.query.type;
  const base = `SELECT p.*, c.category AS category_name, o.office_name AS office_name
                FROM tbl_product p
                LEFT JOIN tbl_category c ON p.category = c.catid
                LEFT JOIN tbl_office o ON o.id = p.office_id
                WHERE p.is_archived = 0`;
  const rows = type
    ? db.prepare(`${base} AND p.product_type = ? ORDER BY p.pid DESC`).all(type)
    : db.prepare(`${base} ORDER BY p.pid DESC`).all();
  res.json(rows);
});

router.get("/:id", (req, res) => {
  const p = db
    .prepare(
      `SELECT p.*, c.category AS category_name, o.office_name AS office_name
       FROM tbl_product p
       LEFT JOIN tbl_category c ON p.category = c.catid
       LEFT JOIN tbl_office o ON o.id = p.office_id
       WHERE p.pid = ? AND p.is_archived = 0`
    )
    .get(req.params.id);
  if (!p) return res.status(404).json({ error: "Product not found" });
  res.json(p);
});

router.post("/", (req, res) => {
  const { barcode, name, brand, acquisition_type, category, description, stock, reorder_level, unit_cost, unit, product_type, serial_number, condition, assigned_to, office_id } = req.body || {};
  if (!name || !category) return res.status(400).json({ error: "Name and category are required." });

  const code = barcode || nextBarcode();
  const info = db
    .prepare(
      `INSERT INTO tbl_product
        (barcode, name, brand, acquisition_type, category, description, stock, reorder_level, unit_cost, unit, product_type, serial_number, condition, assigned_to, office_id, date_added, is_archived)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, date('now','localtime'), 0)`
    )
    .run(
      code, name, brand || "", acquisition_type || "Purchased", category, description || "",
      stock || 0, reorder_level || 0, unit_cost || 0, unit || "pcs", product_type || "Stock",
      serial_number || null, condition || "Good", assigned_to || null, office_id || null
    );

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

  const { barcode, name, brand, acquisition_type, category, description, reorder_level, unit_cost, unit, product_type, serial_number, condition, assigned_to, office_id } = req.body || {};
  db.prepare(
    `UPDATE tbl_product SET
       barcode = ?, name = ?, brand = ?, acquisition_type = ?, category = ?, description = ?,
       reorder_level = ?, unit_cost = ?, unit = ?, product_type = ?, serial_number = ?, condition = ?, assigned_to = ?, office_id = ?
     WHERE pid = ?`
  ).run(
    barcode || p.barcode,
    name || p.name,
    brand !== undefined ? brand : p.brand,
    acquisition_type || p.acquisition_type,
    category || p.category,
    description !== undefined ? description : p.description,
    reorder_level !== undefined ? reorder_level : p.reorder_level,
    unit_cost !== undefined ? unit_cost : p.unit_cost,
    unit !== undefined ? unit : p.unit || "pcs",
    product_type || p.product_type || "Stock",
    serial_number !== undefined ? serial_number : p.serial_number,
    condition || p.condition || "Good",
    assigned_to !== undefined ? assigned_to : p.assigned_to,
    office_id !== undefined ? office_id : p.office_id,
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

// ============ ASSET ASSIGNMENT ============

router.post("/:id/assign", (req, res) => {
  const { office_id, instructor_id, remarks } = req.body || {};
  const p = db.prepare("SELECT * FROM tbl_product WHERE pid = ? AND is_archived = 0").get(req.params.id);
  if (!p) return res.status(404).json({ error: "Asset not found" });

  let assignedTo = "";
  if (office_id) {
    const off = db.prepare("SELECT office_name FROM tbl_office WHERE id = ? AND is_archived = 0").get(office_id);
    if (off) assignedTo = off.office_name;
  }
  if (instructor_id) {
    const inst = db.prepare("SELECT fullname FROM tbl_instructors WHERE id = ? AND is_archived = 0").get(instructor_id);
    if (inst) assignedTo = assignedTo ? `${inst.fullname} (${assignedTo})` : inst.fullname;
  }
  if (!assignedTo) {
    return res.status(400).json({ error: "Choose an office or personnel to assign the asset to." });
  }

  db.transaction(() => {
    db.prepare(
      "UPDATE tbl_product SET assigned_to = ?, assigned_remarks = ?, assigned_date = date('now','localtime'), office_id = ? WHERE pid = ?"
    ).run(assignedTo, remarks || "", office_id || null, p.pid);
    db.prepare(
      "INSERT INTO tbl_asset_assignments (asset_id, assigned_to, office_id, instructor_id, remarks) VALUES (?, ?, ?, ?, ?)"
    ).run(p.pid, assignedTo, office_id || null, instructor_id || null, remarks || "");
    db.prepare(
      "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
    ).run(req.user.userid, `Assigned Asset: ${p.name} to ${assignedTo}`);
  })();

  res.json({ success: true, assigned_to: assignedTo });
});

router.get("/:id/assignments", (req, res) => {
  const rows = db
    .prepare(
      "SELECT * FROM tbl_asset_assignments WHERE asset_id = ? AND is_archived = 0 ORDER BY date_assigned DESC, id DESC"
    )
    .all(req.params.id);
  res.json(rows);
});

// ============ CATEGORIES ============

router.get("/meta/categories", (req, res) => {
  const rows = db
    .prepare(
      `SELECT c.*, (SELECT COUNT(*) FROM tbl_product p WHERE p.category = c.catid AND p.is_archived = 0) AS item_count
       FROM tbl_category c WHERE c.is_archived = 0 ORDER BY c.category`
    )
    .all();
  res.json(rows);
});

router.post("/meta/categories", (req, res) => {
  const { category, description } = req.body || {};
  if (!category || !String(category).trim())
    return res.status(400).json({ error: "Category name is required." });
  const exists = db
    .prepare("SELECT catid FROM tbl_category WHERE category = ? AND is_archived = 0")
    .get(String(category).trim());
  if (exists) return res.status(400).json({ error: "Category already exists." });
  const info = db
    .prepare("INSERT INTO tbl_category (category, description, is_archived) VALUES (?, ?, 0)")
    .run(String(category).trim(), description || "");
  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(req.user.userid, `Added Category: ${category}`);
  res.status(201).json({ catid: Number(info.lastInsertRowid) });
});

router.put("/meta/categories/:id", (req, res) => {
  const c = db.prepare("SELECT * FROM tbl_category WHERE catid = ?").get(req.params.id);
  if (!c) return res.status(404).json({ error: "Category not found" });
  const { category, description } = req.body || {};
  db.prepare("UPDATE tbl_category SET category = ?, description = ? WHERE catid = ?").run(
    category !== undefined && String(category).trim() ? String(category).trim() : c.category,
    description !== undefined ? description : c.description || "",
    c.catid
  );
  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(req.user.userid, `Updated Category: ${category || c.category}`);
  res.json({ success: true });
});

router.delete("/meta/categories/:id", (req, res) => {
  const c = db.prepare("SELECT * FROM tbl_category WHERE catid = ?").get(req.params.id);
  if (!c) return res.status(404).json({ error: "Category not found" });
  const used = db
    .prepare("SELECT COUNT(*) AS n FROM tbl_product WHERE category = ? AND is_archived = 0")
    .get(c.catid);
  if (used.n > 0)
    return res.status(400).json({ error: `Cannot archive: ${used.n} item(s) still use this category.` });
  db.prepare("UPDATE tbl_category SET is_archived = 1 WHERE catid = ?").run(c.catid);
  db.prepare(
    "INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, datetime('now','localtime'))"
  ).run(req.user.userid, `Deleted Category: ${c.category}`);
  res.json({ success: true });
});

export default router;