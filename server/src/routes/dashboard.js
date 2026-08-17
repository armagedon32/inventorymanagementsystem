import { Router } from "express";
import db from "../db.js";
import { requireAuth } from "../middleware/auth.js";

const router = Router();
router.use(requireAuth);

router.get("/summary", (req, res) => {
  const totalProducts = db.prepare("SELECT COUNT(*) AS c FROM tbl_product WHERE is_archived = 0").get().c;
  const totalAssets = db.prepare("SELECT COUNT(*) AS c FROM tbl_product WHERE is_archived = 0 AND product_type = 'Asset'").get().c;
  const totalStock = db
    .prepare("SELECT IFNULL(SUM(stock), 0) AS s FROM tbl_product WHERE is_archived = 0 AND product_type = 'Stock'")
    .get().s;
  const totalValue = db
    .prepare("SELECT IFNULL(SUM(stock * unit_cost), 0) AS v FROM tbl_product WHERE is_archived = 0")
    .get().v;
  const lowStock = db
    .prepare("SELECT COUNT(*) AS c FROM tbl_product WHERE is_archived = 0 AND product_type = 'Stock' AND stock > 0 AND stock <= reorder_level")
    .get().c;
  const outOfStock = db
    .prepare("SELECT COUNT(*) AS c FROM tbl_product WHERE is_archived = 0 AND product_type = 'Stock' AND stock = 0")
    .get().c;
  const reorderAlerts = db
    .prepare("SELECT COUNT(*) AS c FROM tbl_product WHERE is_archived = 0 AND product_type = 'Stock' AND stock <= reorder_level")
    .get().c;
  const totalUsers = db
    .prepare("SELECT COUNT(*) AS c FROM tbl_user WHERE is_archived = 0")
    .get().c;
  const totalStockout = db
    .prepare("SELECT IFNULL(SUM(quantity), 0) AS s FROM tbl_stockout WHERE is_archived = 0 AND datetime(stockout_date) >= datetime('now','localtime','-30 days')")
    .get().s;

  const acqRows = db
    .prepare("SELECT acquisition_type, COUNT(*) AS total FROM tbl_product WHERE is_archived = 0 GROUP BY acquisition_type")
    .all();

  const catRows = db
    .prepare(
      `SELECT COALESCE(c.category, 'Uncategorized') AS category_name, COUNT(*) AS total
       FROM tbl_product p
       LEFT JOIN tbl_category c ON p.category = c.catid
       WHERE p.is_archived = 0
       GROUP BY category_name`
    )
    .all();

  const last7Stockout = db
    .prepare(
      `SELECT date(stockout_date) AS day, SUM(quantity) AS total
       FROM tbl_stockout
       WHERE is_archived = 0 AND date(stockout_date) >= date('now','localtime','-6 days')
       GROUP BY day ORDER BY day`
    )
    .all();

  const monthlyIssuance = db
    .prepare(
      `SELECT strftime('%Y-%m', stockout_date) AS month, SUM(quantity) AS total
       FROM tbl_stockout
       WHERE is_archived = 0
         AND date(stockout_date) >= date('now','localtime','start of month','-11 months')
       GROUP BY month ORDER BY month`
    )
    .all();

  const recentActivity = db
    .prepare(
      `SELECT a.*, u.fullname FROM activity_log a
       LEFT JOIN tbl_user u ON a.user_id = u.userid
       ORDER BY a.id DESC LIMIT 10`
    )
    .all();

  res.json({
    totalProducts,
    totalAssets,
    totalStock,
    totalValue,
    lowStock,
    outOfStock,
    reorderAlerts,
    totalUsers,
    totalStockout,
    acquisition: acqRows,
    categories: catRows,
    last7Stockout,
    monthlyIssuance,
    recentActivity,
  });
});

export default router;