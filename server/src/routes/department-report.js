import { Router } from "express";
import db from "../db.js";
import { requireAuth } from "../middleware/auth.js";

const router = Router();
router.use(requireAuth);

export const DEPARTMENTS = ["Admin/Staff", "HM", "BED", "TED", "CSD"];

router.get("/", (req, res) => {
  const dept = (req.query.dept || "").trim();
  if (!dept) return res.status(400).json({ error: "Select a department." });
  if (!DEPARTMENTS.includes(dept)) return res.status(400).json({ error: "Invalid department." });

  const requested = db
    .prepare(
      `SELECT h.id, h.ris_no, h.last_name, h.first_name, h.mi_name, h.position, h.department,
              h.event_name, h.event_date, h.start_datetime, h.end_datetime, h.is_returned, h.return_date,
              CASE WHEN h.is_returned THEN 'Returned'
                   WHEN h.end_datetime AND h.end_datetime < datetime('now','localtime') THEN 'Overdue'
                   ELSE 'Borrowed' END AS status
       FROM tbl_ris_header h
       WHERE h.is_archived = 0 AND h.department = ?
       ORDER BY h.id DESC`
    )
    .all(dept)
    .map((h) => ({
      ...h,
      items: db
        .prepare(
          `SELECT i.id, i.quantity, i.condition, o.office_name AS borrowed_from_name,
                  p.name AS asset_name, p.brand, p.barcode AS inventory_no, p.serial_number
           FROM tbl_ris_items i
           LEFT JOIN tbl_product p ON p.pid = i.asset_id
           LEFT JOIN tbl_office o ON o.id = i.borrowed_from
           WHERE i.ris_id = ? AND i.is_archived = 0`
        )
        .all(h.id),
    }));

  const released = db
    .prepare(
      `SELECT r.id, r.req_no, r.purpose, r.status, r.date_created, r.date_processed,
              u.fullname AS requested_by_name, u.department AS department
       FROM tbl_requisition r
       LEFT JOIN tbl_user u ON u.userid = r.requested_by
       WHERE r.is_archived = 0 AND r.status = 'Approved' AND u.department = ?
       ORDER BY r.id DESC`
    )
    .all(dept)
    .map((r) => ({
      ...r,
      items: db
        .prepare(
          `SELECT i.id, i.quantity, p.name AS product_name, p.unit, p.barcode AS inventory_no
           FROM tbl_requisition_item i
           LEFT JOIN tbl_product p ON p.pid = i.product_id
           WHERE i.requisition_id = ? AND i.is_archived = 0`
        )
        .all(r.id),
    }));

  const inventories = db
    .prepare(
      `SELECT p.pid, p.name, p.brand, p.barcode AS inventory_no, p.serial_number, p.condition,
              p.stock, p.unit, p.unit_cost, p.product_type, p.assigned_to, p.department,
              c.category AS category_name
       FROM tbl_product p
       LEFT JOIN tbl_category c ON p.category = c.catid
       WHERE p.is_archived = 0 AND p.department = ?
       ORDER BY p.product_type, p.name`
    )
    .all(dept);

  res.json({ department: dept, requested, released, inventories });
});

export default router;