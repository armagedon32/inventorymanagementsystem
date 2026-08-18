import { Router } from "express";
import db from "../db.js";
import { requireAuth } from "../middleware/auth.js";

const router = Router();
router.use(requireAuth);

const INVENTORY_HEADERS = [
  { label: "Barcode", key: "barcode" },
  { label: "Name", key: "name" },
  { label: "Brand", key: "brand" },
  { label: "Category", key: "category_name" },
  { label: "Unit", key: "unit" },
  { label: "Stock", key: "stock" },
  { label: "Reorder Level", key: "reorder_level" },
  { label: "Status", key: "status" },
  { label: "Unit Cost", key: "unit_cost" },
  { label: "Value", key: "value" },
];

const ASSET_HEADERS = [
  { label: "Asset Tag", key: "barcode" },
  { label: "Name", key: "name" },
  { label: "Brand", key: "brand" },
  { label: "Category", key: "category_name" },
  { label: "Serial No.", key: "serial_number" },
  { label: "Condition", key: "condition" },
  { label: "Assigned To", key: "assigned_to" },
  { label: "Quantity", key: "stock" },
  { label: "Unit Cost", key: "unit_cost" },
  { label: "Value", key: "value" },
];

const REQ_HEADERS = [
  { label: "Requisition No.", key: "req_no" },
  { label: "Purpose", key: "purpose" },
  { label: "Requested By", key: "requested_name" },
  { label: "Date Created", key: "date_created" },
  { label: "Status", key: "status" },
  { label: "Reject Reason", key: "reject_reason" },
];

const TX_HEADERS = [
  { label: "Type", key: "type" },
  { label: "Product", key: "product_name" },
  { label: "Quantity", key: "quantity" },
  { label: "Office / Person", key: "recipient" },
  { label: "Date", key: "date" },
  { label: "Remarks", key: "remarks" },
];

router.get("/inventory", (req, res) => {
  const rows = db
    .prepare(
      `SELECT p.*, c.category AS category_name,
        CASE WHEN p.stock = 0 THEN 'Out of Stock'
             WHEN p.stock <= p.reorder_level THEN 'Low'
             ELSE 'OK' END AS status,
        (p.stock * p.unit_cost) AS value
       FROM tbl_product p
       LEFT JOIN tbl_category c ON p.category = c.catid
       WHERE p.is_archived = 0 AND p.product_type = 'Stock'
       ORDER BY p.name`
    )
    .all();
  const stats = {
    totalItems: rows.length,
    totalStock: rows.reduce((s, r) => s + (r.stock || 0), 0),
    totalValue: rows.reduce((s, r) => s + (r.value || 0), 0),
    low: rows.filter((r) => r.stock > 0 && r.stock <= r.reorder_level).length,
    outOfStock: rows.filter((r) => r.stock === 0).length,
  };
  const chart = db
    .prepare(
      `SELECT COALESCE(c.category, 'Uncategorized') AS name, COUNT(*) AS value
       FROM tbl_product p LEFT JOIN tbl_category c ON p.category = c.catid
       WHERE p.is_archived = 0 AND p.product_type = 'Stock'
       GROUP BY name ORDER BY value DESC`
    )
    .all();
  res.json({ rows, stats, chart, headers: INVENTORY_HEADERS });
});

router.get("/assets", (req, res) => {
  const rows = db
    .prepare(
      `SELECT p.*, c.category AS category_name, (p.stock * p.unit_cost) AS value
       FROM tbl_product p
       LEFT JOIN tbl_category c ON p.category = c.catid
       WHERE p.is_archived = 0 AND p.product_type = 'Asset'
       ORDER BY p.name`
    )
    .all();
  const cond = rows.reduce((m, r) => {
    const k = r.condition || "Good";
    m[k] = (m[k] || 0) + 1;
    return m;
  }, {});
  const stats = {
    totalAssets: rows.length,
    totalValue: rows.reduce((s, r) => s + (r.value || 0), 0),
    byCondition: cond,
    assigned: rows.filter((r) => r.assigned_to).length,
    unassigned: rows.filter((r) => !r.assigned_to).length,
  };
  const chart = Object.entries(cond).map(([name, value]) => ({ name, value }));
  res.json({ rows, stats, chart, headers: ASSET_HEADERS });
});

router.get("/requisitions", (req, res) => {
  const rows = db
    .prepare(
      `SELECT r.*, u.fullname AS requested_name,
        (SELECT COUNT(*) FROM tbl_requisition_item i WHERE i.requisition_id = r.id AND i.is_archived = 0) AS item_count
       FROM tbl_requisition r
       LEFT JOIN tbl_user u ON u.userid = r.requested_by
       WHERE r.is_archived = 0
       ORDER BY r.id DESC`
    )
    .all();
  const byStatus = rows.reduce((m, r) => {
    m[r.status] = (m[r.status] || 0) + 1;
    return m;
  }, {});
  const stats = {
    total: rows.length,
    pending: byStatus["Pending"] || 0,
    approved: byStatus["Approved"] || 0,
    rejected: byStatus["Rejected"] || 0,
  };
  const chart = [
    { name: "Pending", value: stats.pending },
    { name: "Approved", value: stats.approved },
    { name: "Rejected", value: stats.rejected },
  ].filter((c) => c.value > 0);
  res.json({ rows, stats, chart, headers: REQ_HEADERS });
});

router.get("/transactions", (req, res) => {
  const rows = db
    .prepare(
      `SELECT 'Stock In' AS type, t.quantity, t.remarks, t.stock_date AS date,
              p.name AS product_name, '' AS recipient
       FROM tbl_stockin t JOIN tbl_product p ON p.pid = t.product_id
       WHERE t.is_archived = 0
       UNION ALL
       SELECT 'Stock Out' AS type, t.quantity, t.remarks, t.stockout_date AS date,
              p.name AS product_name,
              COALESCE(o.office_name, i.fullname, '') AS recipient
       FROM tbl_stockout t
       JOIN tbl_product p ON p.pid = t.product_id
       LEFT JOIN tbl_office o ON o.id = t.office_id
       LEFT JOIN tbl_instructors i ON i.id = t.instructor_id
       WHERE t.is_archived = 0
       ORDER BY date DESC`
    )
    .all();
  const totalIn = rows.filter((r) => r.type === "Stock In").reduce((s, r) => s + (r.quantity || 0), 0);
  const totalOut = rows.filter((r) => r.type === "Stock Out").reduce((s, r) => s + (r.quantity || 0), 0);
  const stats = {
    totalTransactions: rows.length,
    totalIn,
    totalOut,
    net: totalIn - totalOut,
  };

  const monthLabels = [];
  const now = new Date();
  for (let i = 11; i >= 0; i--) {
    const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
    monthLabels.push(`${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`);
  }
  const sumIn = (month) =>
    db.prepare("SELECT IFNULL(SUM(quantity),0) s FROM tbl_stockin WHERE is_archived=0 AND strftime('%Y-%m', stock_date)=?").get(month).s;
  const sumOut = (month) =>
    db.prepare("SELECT IFNULL(SUM(quantity),0) s FROM tbl_stockout WHERE is_archived=0 AND strftime('%Y-%m', stockout_date)=?").get(month).s;
  const chart = monthLabels.map((month) => ({ month, in: sumIn(month), out: sumOut(month) }));
  res.json({ rows, stats, chart, headers: TX_HEADERS });
});

export default router;