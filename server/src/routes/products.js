import { Router } from "express";
import db from "../db.js";
import { logActivity } from "../activity.js";
import { requireAuth, requireAdmin } from "../middleware/auth.js";

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

// ============ BULK IMPORT / EXPORT ============

import ExcelJS from "exceljs";

router.get("/import-template", requireAdmin, async (req, res) => {
  try {
    const categories = db.prepare("SELECT category FROM tbl_category WHERE is_archived = 0 ORDER BY category").all().map((c) => c.category);
    const departments = [
      "Library Office",
      "Registrar Office",
      "College President/MIS Office",
      "Faculty Office",
      "Deans Office",
      "Clinic Office",
      "NSTP Office",
      "OSA ADMISSION & SCHOLARSHIP Office",
      "Guidance Office",
      "Academic Affairs Office",
      "Property Custodian Office",
      "Research Office",
      "Planning Office",
      "Maintenance Office",
      "Head Sports Athletics Office",
      "Utility Office",
      "AVR Internet Laboratory Office",
      "Guard House",
      "Computer Laboratory",
      "Administrator Office",
      "Kitchen Laboratory",
      "Bartending",
      "Tourism",
      "Housekeeping",
      "Front Office",
      "Food and Beverage",
    ];
    const units = ["pcs", "box", "ream", "pack", "bottle", "set", "unit", "liter", "kg", "pair"];
    const acquisitionTypes = ["Purchased", "Donated", "Created"];
    const conditions = ["Good", "Fair", "Needs Repair", "For Disposal"];

    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet("Assets", { views: [{ state: "frozen", ySplit: 1 }] });

    ws.columns = [
      { header: "Barcode (blank=auto)", key: "barcode", width: 22 },
      { header: "Name *", key: "name", width: 30 },
      { header: "Brand", key: "brand", width: 20 },
      { header: "Acquisition Type", key: "acquisition_type", width: 18 },
      { header: "Category", key: "category", width: 22 },
      { header: "Description", key: "description", width: 35 },
      { header: "Unit", key: "unit", width: 12 },
      { header: "Quantity", key: "stock", width: 12 },
      { header: "Serial Number", key: "serial_number", width: 25 },
      { header: "Condition", key: "condition", width: 16 },
      { header: "Assigned To", key: "assigned_to", width: 25 },
      { header: "Office", key: "department", width: 25 },
    ];

    const headerRow = ws.getRow(1);
    headerRow.font = { bold: true, color: { argb: "FFFFFFFF" } };
    headerRow.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FF333333" } };
    headerRow.alignment = { horizontal: "center" };

    const sampleRow = {
      barcode: "AST-2026-0001",
      name: "Desktop Computer",
      brand: "Dell",
      acquisition_type: "Purchased",
      category: categories[0] || "",
      description: "Core i5 8GB RAM",
      unit: "pcs",
      stock: 1,
      serial_number: "DELL-8Y7KD33",
      condition: "Good",
      assigned_to: "Comlab 1",
      department: "Computer Laboratory",
    };
    ws.addRow(sampleRow);

    const maxRows = 500;

    if (acquisitionTypes.length) {
      ws.dataValidations.add(`D2:D${maxRows}`, {
        type: "list",
        allowBlank: true,
        formulae: [`"${acquisitionTypes.join(",")}"`],
        showErrorMessage: true,
        errorTitle: "Invalid",
        error: `Please select: ${acquisitionTypes.join(", ")}`,
      });
    }

    if (categories.length) {
      ws.dataValidations.add(`E2:E${maxRows}`, {
        type: "list",
        allowBlank: true,
        formulae: [`"${categories.join(",")}"`],
        showErrorMessage: true,
        errorTitle: "Invalid Category",
        error: `Please select a valid category`,
      });
    }

    if (units.length) {
      ws.dataValidations.add(`G2:G${maxRows}`, {
        type: "list",
        allowBlank: true,
        formulae: [`"${units.join(",")}"`],
        showErrorMessage: true,
        errorTitle: "Invalid",
        error: `Please select: ${units.join(", ")}`,
      });
    }

    if (conditions.length) {
      ws.dataValidations.add(`J2:J${maxRows}`, {
        type: "list",
        allowBlank: true,
        formulae: [`"${conditions.join(",")}"`],
        showErrorMessage: true,
        errorTitle: "Invalid",
        error: `Please select: ${conditions.join(", ")}`,
      });
    }

    if (departments.length) {
      ws.dataValidations.add(`L2:L${maxRows}`, {
        type: "list",
        allowBlank: true,
        formulae: [`"${departments.join(",")}"`],
        showErrorMessage: true,
        errorTitle: "Invalid Office",
        error: `Please select a valid office`,
      });
    }

    ws.dataValidations.add(`H2:H${maxRows}`, {
      type: "whole",
      operator: "greaterThanOrEqual",
      formulae: ["0"],
      showErrorMessage: true,
      errorTitle: "Invalid Quantity",
      error: "Must be a non-negative whole number",
    });

    res.setHeader("Content-Type", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    res.setHeader("Content-Disposition", 'attachment; filename="asset-import-template.xlsx"');
    await wb.xlsx.write(res);
    res.end();
  } catch (err) {
    res.status(500).json({ error: "Failed to generate template: " + err.message });
  }
});

router.post("/import", requireAdmin, async (req, res) => {
  try {
    const { csv } = req.body || {};
    if (!csv || !String(csv).trim()) {
      return res.status(400).json({ error: "Excel data is required." });
    }

    const base64 = String(csv).trim();
    const buffer = Buffer.from(base64, "base64");
    const wb = new ExcelJS.Workbook();
    await wb.xlsx.load(buffer);
    const ws = wb.getWorksheet(1) || wb.getWorksheet("Assets");
    if (!ws || ws.rowCount < 2) {
      return res.status(400).json({ error: "Excel file must have a header row and at least one data row." });
    }

    const headerRow = ws.getRow(1);
    const headers = [];
    const headerMap = {
      barcode: "barcode", barcodeblankauto: "barcode",
      name: "name", itemname: "name",
      brand: "brand",
      acquisition_type: "acquisition_type", acquisitiontype: "acquisition_type", acqtype: "acquisition_type",
      category: "category",
      description: "description",
      unit: "unit",
      stock: "stock", initialstock: "stock", quantity: "stock", qty: "stock",
      reorder_level: "reorder_level", reorderlevel: "reorder_level", reorder: "reorder_level",
      serial_number: "serial_number", serialnumber: "serial_number", serial: "serial_number", serialno: "serial_number",
      condition: "condition",
      assigned_to: "assigned_to", assignedto: "assigned_to", assigned: "assigned_to",
      department: "department",
      unit_cost: "unit_cost", unitcost: "unit_cost",
    };
    headerRow.eachCell((cell, colNumber) => {
      const raw = String(cell.value || "").trim().toLowerCase().replace(/[^a-z0-9_]/g, "").replace(/_+$/, "");
      headers[colNumber] = headerMap[raw] || raw;
    });

    const required = ["name", "category"];
    for (const r of required) {
      if (!headers.includes(r) && !Object.values(headers).some((h) => h && h.startsWith(r))) {
        return res.status(400).json({ error: `Missing required column: ${r}` });
      }
    }

    const catMap = {};
    db.prepare("SELECT catid, category FROM tbl_category WHERE is_archived = 0").all().forEach((c) => {
      catMap[c.category.toLowerCase()] = c.catid;
    });

    let imported = 0;
    let skipped = 0;
    const errors = [];

    const insert = db.prepare(
      `INSERT INTO tbl_product (barcode, name, brand, acquisition_type, category, description, stock, reorder_level, unit_cost, unit, product_type, serial_number, condition, assigned_to, office_id, department, date_added, is_archived)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Asset', ?, ?, ?, NULL, ?, date('now','localtime'), 0)`
    );

    db.transaction(() => {
      for (let i = 2; i <= ws.rowCount; i++) {
        const rowValues = ws.getRow(i).values || [];
        const row = {};
        headers.forEach((h, colIdx) => {
          if (h) {
            const val = rowValues[colIdx];
            row[h] = val != null ? String(val).trim() : "";
          }
        });

        if (!row.name) {
          skipped++;
          errors.push(`Row ${i}: Missing name`);
          continue;
        }

        const catId = catMap[row.category?.toLowerCase()];
        let categoryId = catId || null;
        if (!categoryId && row.category) {
          const newCat = db.prepare("INSERT INTO tbl_category (category, description, is_archived) VALUES (?, '', 0)").run(row.category);
          categoryId = newCat.lastInsertRowid;
        }

        const code = row.barcode || nextBarcode();
        const serial = row.serial_number || null;
        if (serial) {
          const dup = db.prepare("SELECT pid FROM tbl_product WHERE serial_number = ? AND product_type = 'Asset' AND is_archived = 0").get(serial);
          if (dup) {
            skipped++;
            errors.push(`Row ${i}: Duplicate serial "${serial}"`);
            continue;
          }
        }

        try {
          insert.run(
            code, row.name, row.brand || "", row.acquisition_type || "Purchased",
            categoryId, row.description || "", Number(row.stock) || 0,
            0, Number(row.unit_cost) || 0, row.unit || "unit",
            serial, row.condition || "Good", row.assigned_to || null, row.department || null
          );
          imported++;
        } catch (err) {
          skipped++;
          errors.push(`Row ${i}: ${err.message}`);
        }
      }
    })();

    logActivity(req, `Bulk imported ${imported} asset(s)`, undefined, undefined, req.user.userid);
    res.json({ imported, skipped, errors });
  } catch (err) {
    res.status(500).json({ error: "Import failed: " + err.message });
  }
});

router.get("/import-template/stock", requireAdmin, async (req, res) => {
  try {
    const categories = db.prepare("SELECT category FROM tbl_category WHERE is_archived = 0 ORDER BY category").all().map((c) => c.category);
    const units = ["pcs", "box", "ream", "pack", "bottle", "set", "unit", "liter", "kg", "pair"];

    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet("Supplies", { views: [{ state: "frozen", ySplit: 1 }] });

    ws.columns = [
      { header: "Name *", key: "name", width: 30 },
      { header: "Brand", key: "brand", width: 20 },
      { header: "Category", key: "category", width: 22 },
      { header: "Description", key: "description", width: 35 },
      { header: "Unit", key: "unit", width: 12 },
      { header: "Initial Stock", key: "stock", width: 14 },
      { header: "Reorder Level", key: "reorder_level", width: 14 },
    ];

    const headerRow = ws.getRow(1);
    headerRow.font = { bold: true, color: { argb: "FFFFFFFF" } };
    headerRow.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FF333333" } };
    headerRow.alignment = { horizontal: "center" };

    ws.addRow({
      name: "Bond Paper A4",
      brand: "Plus",
      category: categories[0] || "",
      description: "500 sheets per ream",
      unit: "ream",
      stock: 10,
      reorder_level: 5,
    });

    const maxRows = 500;

    if (categories.length) {
      ws.dataValidations.add(`C2:C${maxRows}`, {
        type: "list",
        allowBlank: true,
        formulae: [`"${categories.join(",")}"`],
        showErrorMessage: true,
        errorTitle: "Invalid Category",
        error: `Please select a valid category`,
      });
    }

    if (units.length) {
      ws.dataValidations.add(`E2:E${maxRows}`, {
        type: "list",
        allowBlank: true,
        formulae: [`"${units.join(",")}"`],
        showErrorMessage: true,
        errorTitle: "Invalid",
        error: `Please select: ${units.join(", ")}`,
      });
    }

    ws.dataValidations.add(`F2:F${maxRows}`, {
      type: "whole",
      operator: "greaterThanOrEqual",
      formulae: ["0"],
      showErrorMessage: true,
      errorTitle: "Invalid Stock",
      error: "Must be a non-negative whole number",
    });

    ws.dataValidations.add(`G2:G${maxRows}`, {
      type: "whole",
      operator: "greaterThanOrEqual",
      formulae: ["0"],
      showErrorMessage: true,
      errorTitle: "Invalid Reorder Level",
      error: "Must be a non-negative whole number",
    });

    res.setHeader("Content-Type", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    res.setHeader("Content-Disposition", 'attachment; filename="supply-import-template.xlsx"');
    await wb.xlsx.write(res);
    res.end();
  } catch (err) {
    res.status(500).json({ error: "Failed to generate template: " + err.message });
  }
});

router.post("/import/stock", requireAdmin, async (req, res) => {
  try {
    const { csv } = req.body || {};
    if (!csv || !String(csv).trim()) {
      return res.status(400).json({ error: "Excel data is required." });
    }

    const base64 = String(csv).trim();
    const buffer = Buffer.from(base64, "base64");
    const wb = new ExcelJS.Workbook();
    await wb.xlsx.load(buffer);
    const ws = wb.getWorksheet(1) || wb.getWorksheet("Supplies");
    if (!ws || ws.rowCount < 2) {
      return res.status(400).json({ error: "Excel file must have a header row and at least one data row." });
    }

    const headerRow = ws.getRow(1);
    const headers = [];
    const headerMap = {
      barcode: "barcode", barcodeblankauto: "barcode",
      name: "name", itemname: "name",
      brand: "brand",
      acquisition_type: "acquisition_type", acquisitiontype: "acquisition_type", acqtype: "acquisition_type",
      category: "category",
      description: "description",
      unit: "unit",
      stock: "stock", initialstock: "stock", quantity: "stock", qty: "stock",
      reorder_level: "reorder_level", reorderlevel: "reorder_level", reorder: "reorder_level",
      serial_number: "serial_number", serialnumber: "serial_number", serial: "serial_number", serialno: "serial_number",
      condition: "condition",
      assigned_to: "assigned_to", assignedto: "assigned_to", assigned: "assigned_to",
      department: "department",
      unit_cost: "unit_cost", unitcost: "unit_cost",
    };
    headerRow.eachCell((cell, colNumber) => {
      const raw = String(cell.value || "").trim().toLowerCase().replace(/[^a-z0-9_]/g, "").replace(/_+$/, "");
      headers[colNumber] = headerMap[raw] || raw;
    });

    const required = ["name", "category"];
    for (const r of required) {
      if (!headers.includes(r) && !Object.values(headers).some((h) => h && h.startsWith(r))) {
        return res.status(400).json({ error: `Missing required column: ${r}` });
      }
    }

    const catMap = {};
    db.prepare("SELECT catid, category FROM tbl_category WHERE is_archived = 0").all().forEach((c) => {
      catMap[c.category.toLowerCase()] = c.catid;
    });

    let imported = 0;
    let skipped = 0;
    const errors = [];

    const insert = db.prepare(
      `INSERT INTO tbl_product (barcode, name, brand, acquisition_type, category, description, stock, reorder_level, unit_cost, unit, product_type, serial_number, condition, assigned_to, office_id, department, date_added, is_archived)
       VALUES (?, ?, ?, 'Purchased', ?, ?, ?, ?, 0, ?, 'Stock', NULL, NULL, NULL, NULL, NULL, date('now','localtime'), 0)`
    );

    db.transaction(() => {
      for (let i = 2; i <= ws.rowCount; i++) {
        const rowValues = ws.getRow(i).values || [];
        const row = {};
        headers.forEach((h, colIdx) => {
          if (h) {
            const val = rowValues[colIdx];
            row[h] = val != null ? String(val).trim() : "";
          }
        });

        if (!row.name) {
          skipped++;
          errors.push(`Row ${i}: Missing name`);
          continue;
        }

        const catId = catMap[row.category?.toLowerCase()];
        let categoryId = catId || null;
        if (!categoryId && row.category) {
          const newCat = db.prepare("INSERT INTO tbl_category (category, description, is_archived) VALUES (?, '', 0)").run(row.category);
          categoryId = newCat.lastInsertRowid;
        }

        const code = nextBarcode();

        try {
          insert.run(
            code, row.name, row.brand || "",
            categoryId, row.description || "", Number(row.stock) || 0,
            Number(row.reorder_level) || 0, row.unit || "pcs"
          );
          imported++;
        } catch (err) {
          skipped++;
          errors.push(`Row ${i}: ${err.message}`);
        }
      }
    })();

    logActivity(req, `Bulk imported ${imported} supply item(s)`, undefined, undefined, req.user.userid);
    res.json({ imported, skipped, errors });
  } catch (err) {
    res.status(500).json({ error: "Import failed: " + err.message });
  }
});

// ============ ARCHIVE ============

router.get("/archived", requireAdmin, (req, res) => {
  const type = req.query.type;
  const base = `SELECT p.*, c.category AS category_name, o.office_name AS office_name
                FROM tbl_product p
                LEFT JOIN tbl_category c ON p.category = c.catid
                LEFT JOIN tbl_office o ON o.id = p.office_id
                WHERE p.is_archived = 1`;
  const rows = type
    ? db.prepare(`${base} AND p.product_type = ? ORDER BY p.pid DESC`).all(type)
    : db.prepare(`${base} ORDER BY p.pid DESC`).all();
  res.json(rows);
});

router.delete("/archived/all", requireAdmin, (req, res) => {
  const type = req.query.type;
  if (!type) return res.status(400).json({ error: "type query param is required" });
  const info = db.prepare("DELETE FROM tbl_product WHERE product_type = ? AND is_archived = 1").run(type);
  logActivity(req, `Permanently deleted all ${info.changes} archived ${type}(s)`, undefined, undefined, req.user.userid);
  res.json({ deleted: info.changes });
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

router.post("/", requireAdmin, (req, res) => {
  const { barcode, name, brand, acquisition_type, category, description, stock, reorder_level, unit_cost, unit, product_type, serial_number, condition, assigned_to, office_id, department } = req.body || {};
  if (!name || !category) return res.status(400).json({ error: "Name and category are required." });

  const isAsset = (product_type || "Stock") === "Asset";
  if (isAsset && (!serial_number || !String(serial_number).trim())) {
    return res.status(400).json({ error: "Serial number is required for assets." });
  }
  if (isAsset) {
    const sn = String(serial_number).trim();
    const dup = db.prepare("SELECT pid FROM tbl_product WHERE serial_number = ? AND product_type = 'Asset' AND is_archived = 0").get(sn);
    if (dup) return res.status(400).json({ error: `Serial number "${sn}" is already used by another asset.` });
  }

  const code = barcode || nextBarcode();
  const info = db
    .prepare(
      `INSERT INTO tbl_product
        (barcode, name, brand, acquisition_type, category, description, stock, reorder_level, unit_cost, unit, product_type, serial_number, condition, assigned_to, office_id, department, date_added, is_archived)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, date('now','localtime'), 0)`
    )
    .run(
      code, name, brand || "", acquisition_type || "Purchased", category, description || "",
      stock || 0, reorder_level || 0, unit_cost || 0, unit || "pcs", product_type || "Stock",
      serial_number || null, condition || "Good", assigned_to || null, office_id || null, department || null
    );

    logActivity(req, `Added New Product: ${name}`, undefined, Number(info.lastInsertRowid));

  if ((stock || 0) > 0) {
    db.prepare(
      "INSERT INTO tbl_stockin (product_id, quantity, remarks, stock_date) VALUES (?, ?, ?, date('now','localtime'))"
    ).run(info.lastInsertRowid, stock, "initial stock");
  }

  res.status(201).json({ pid: Number(info.lastInsertRowid) });
});

router.put("/:id", requireAdmin, (req, res) => {
  const p = db.prepare("SELECT * FROM tbl_product WHERE pid = ?").get(req.params.id);
  if (!p) return res.status(404).json({ error: "Product not found" });

  const { barcode, name, brand, acquisition_type, category, description, reorder_level, unit_cost, unit, product_type, serial_number, condition, assigned_to, office_id, department } = req.body || {};

  const isAsset = (p.product_type || "Stock") === "Asset";
  if (isAsset) {
    const sn = String(serial_number ?? p.serial_number ?? "").trim();
    if (!sn) return res.status(400).json({ error: "Serial number is required for assets." });
    const dup = db.prepare("SELECT pid FROM tbl_product WHERE serial_number = ? AND product_type = 'Asset' AND is_archived = 0 AND pid != ?").get(sn, p.pid);
    if (dup) return res.status(400).json({ error: `Serial number "${sn}" is already used by another asset.` });
  }
  db.prepare(
    `UPDATE tbl_product SET
       barcode = ?, name = ?, brand = ?, acquisition_type = ?, category = ?, description = ?,
       reorder_level = ?, unit_cost = ?, unit = ?, product_type = ?, serial_number = ?, condition = ?, assigned_to = ?, office_id = ?, department = ?
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
    department !== undefined ? department : p.department,
    p.pid
  );

    logActivity(req, `Updated Product: ${name || p.name}`, undefined, p.pid);

  res.json({ success: true });
});

router.delete("/all", requireAdmin, (req, res) => {
  const type = req.query.type;
  if (!type) return res.status(400).json({ error: "type query param is required (Asset or Stock)" });
  const info = db.prepare("UPDATE tbl_product SET is_archived = 1 WHERE product_type = ? AND is_archived = 0").run(type);
  logActivity(req, `Deleted all ${info.changes} ${type}(s)`, undefined, undefined, req.user.userid);
  res.json({ deleted: info.changes });
});

router.put("/:id/restore", requireAdmin, (req, res) => {
  const p = db.prepare("SELECT * FROM tbl_product WHERE pid = ? AND is_archived = 1").get(req.params.id);
  if (!p) return res.status(404).json({ error: "Archived product not found." });
  db.prepare("UPDATE tbl_product SET is_archived = 0 WHERE pid = ?").run(p.pid);
  logActivity(req, `Restored Product: ${p.name}`, undefined, p.pid);
  res.json({ success: true });
});

router.delete("/:id/permanent", requireAdmin, (req, res) => {
  const p = db.prepare("SELECT * FROM tbl_product WHERE pid = ? AND is_archived = 1").get(req.params.id);
  if (!p) return res.status(404).json({ error: "Archived product not found." });
  db.prepare("DELETE FROM tbl_product WHERE pid = ?").run(p.pid);
  logActivity(req, `Permanently deleted Product: ${p.name}`, undefined, p.pid);
  res.json({ success: true });
});

router.delete("/:id", (req, res) => {
  const p = db.prepare("SELECT * FROM tbl_product WHERE pid = ?").get(req.params.id);
  if (!p) return res.status(404).json({ error: "Product not found" });
  db.prepare("UPDATE tbl_product SET is_archived = 1 WHERE pid = ?").run(p.pid);
    logActivity(req, `Deleted Product: ${p.name}`, undefined, p.pid);
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
        logActivity(req, `Restocked Product: ${p.name} (+${qty})`, undefined, p.pid);
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
        logActivity(req, `Stock Out: ${p.name} (-${qty})`, undefined, p.pid);
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
  const { office_id, instructor_id, department, remarks } = req.body || {};
  const p = db.prepare("SELECT * FROM tbl_product WHERE pid = ? AND is_archived = 0").get(req.params.id);
  if (!p) return res.status(404).json({ error: "Asset not found" });
  if (p.product_type !== "Asset") return res.status(400).json({ error: "Only assets can be assigned." });

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
      "UPDATE tbl_product SET assigned_to = ?, assigned_remarks = ?, assigned_date = date('now','localtime'), office_id = ?, department = ? WHERE pid = ?"
    ).run(assignedTo, remarks || "", office_id || null, department || p.department, p.pid);
    db.prepare(
      "INSERT INTO tbl_asset_assignments (asset_id, assigned_to, office_id, instructor_id, department, remarks) VALUES (?, ?, ?, ?, ?, ?)"
    ).run(p.pid, assignedTo, office_id || null, instructor_id || null, department || p.department, remarks || "");
    logActivity(req, `Assigned Asset: ${p.name} (${p.serial_number || ""}) to ${assignedTo}`, undefined, p.pid);
  })();

  res.json({ success: true, assigned_to: assignedTo, department: department || p.department });
});

router.post("/:id/unassign", (req, res) => {
  const p = db.prepare("SELECT * FROM tbl_product WHERE pid = ? AND is_archived = 0").get(req.params.id);
  if (!p) return res.status(404).json({ error: "Asset not found" });
  if (p.product_type !== "Asset") return res.status(400).json({ error: "Only assets can be unassigned." });
  if (!p.assigned_to && !p.office_id) return res.status(400).json({ error: "This asset is not assigned." });

  db.transaction(() => {
    db.prepare(
      "UPDATE tbl_product SET assigned_to = NULL, assigned_remarks = NULL, assigned_date = NULL, office_id = NULL WHERE pid = ?"
    ).run(p.pid);
    logActivity(req, `Unassigned Asset: ${p.name} (${p.serial_number || ""})`, undefined, p.pid);
  })();

  res.json({ success: true });
});

router.get("/:id/assignments", (req, res) => {
  const rows = db
    .prepare(
      `SELECT a.*, p.name AS asset_name, p.serial_number AS serial_number
       FROM tbl_asset_assignments a
       LEFT JOIN tbl_product p ON p.pid = a.asset_id
       WHERE a.asset_id = ? AND a.is_archived = 0
       ORDER BY a.date_assigned DESC, a.id DESC`
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
    logActivity(req, `Added Category: ${category}`, undefined, Number(info.lastInsertRowid));
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
    logActivity(req, `Updated Category: ${category || c.category}`, undefined, c.catid);
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
    logActivity(req, `Deleted Category: ${c.category}`, undefined, c.catid);
  res.json({ success: true });
});

export default router;