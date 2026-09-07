import Database from "better-sqlite3";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const DATA_DIR = path.join(__dirname, "..", "data");
const DB_PATH = process.env.DB_PATH || path.join(DATA_DIR, "custodian.db");

if (!fs.existsSync(DATA_DIR)) fs.mkdirSync(DATA_DIR, { recursive: true });

const db = new Database(DB_PATH);
db.pragma("journal_mode = WAL");
db.pragma("foreign_keys = ON");

db.exec(`
CREATE TABLE IF NOT EXISTS tbl_user (
  userid         INTEGER PRIMARY KEY AUTOINCREMENT,
  fullname       TEXT NOT NULL,
  username       TEXT NOT NULL,
  useremail      TEXT NOT NULL,
  contact_number TEXT,
  course         TEXT,
  major          TEXT,
  year_level     TEXT,
  department     TEXT,
  userpassword   TEXT NOT NULL,
  must_change_password INTEGER DEFAULT 0,
  role           TEXT NOT NULL DEFAULT 'Admin',
  photo          TEXT,
  recovery_question TEXT,
  recovery_answer   TEXT,
  is_archived    INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_category (
  catid       INTEGER PRIMARY KEY AUTOINCREMENT,
  category    TEXT NOT NULL,
  description TEXT,
  is_archived INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_product (
  pid             INTEGER PRIMARY KEY AUTOINCREMENT,
  barcode         TEXT NOT NULL,
  name            TEXT NOT NULL,
  brand           TEXT NOT NULL,
  acquisition_type TEXT NOT NULL,
  category        INTEGER NOT NULL,
  description     TEXT NOT NULL,
  stock           INTEGER DEFAULT 0,
  reorder_level   INTEGER DEFAULT 0,
  unit_cost       REAL DEFAULT 0,
  unit            TEXT DEFAULT 'pcs',
  product_type    TEXT DEFAULT 'Stock',
  serial_number   TEXT,
  condition       TEXT DEFAULT 'Good',
  assigned_to     TEXT,
  assigned_remarks TEXT,
  assigned_date   TEXT,
  date_added      TEXT,
  image           TEXT,
  department      TEXT,
  is_archived     INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_office (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  parent_id     INTEGER,
  office_name   TEXT NOT NULL,
  address       TEXT,
  contact       TEXT,
  max_capacity  INTEGER DEFAULT 0,
  instructor_id INTEGER,
  is_archived   INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_instructors (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  fullname       TEXT NOT NULL,
  contact        TEXT,
  email          TEXT,
  assigned_dept  TEXT,
  is_archived    INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_stockin (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  product_id  INTEGER NOT NULL,
  quantity    INTEGER NOT NULL,
  remarks     TEXT,
  stock_date  TEXT DEFAULT (datetime('now','localtime')),
  is_archived INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_stockout (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  product_id    INTEGER NOT NULL,
  office_id     INTEGER,
  instructor_id INTEGER,
  quantity      INTEGER NOT NULL,
  stockout_date TEXT DEFAULT (datetime('now','localtime')),
  remarks       TEXT,
  is_archived   INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS activity_log (
  id           INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id      INTEGER,
  action       TEXT,
  description  TEXT,
  target_id    INTEGER,
  ip_address   TEXT,
  date_created TEXT DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS tbl_asset_assignments (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  asset_id      INTEGER NOT NULL,
  assigned_to   TEXT NOT NULL,
  office_id     INTEGER,
  instructor_id INTEGER,
  department    TEXT,
  remarks       TEXT,
  date_assigned TEXT DEFAULT (datetime('now','localtime')),
  is_archived   INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_requisition (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  req_no        TEXT NOT NULL,
  purpose       TEXT NOT NULL,
  requested_by  INTEGER,
  status        TEXT DEFAULT 'Pending',
  reject_reason TEXT,
  date_created  TEXT DEFAULT (datetime('now','localtime')),
  date_processed TEXT,
  processed_by  INTEGER,
  is_archived   INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_requisition_item (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  requisition_id INTEGER NOT NULL,
  product_id     INTEGER NOT NULL,
  quantity       INTEGER NOT NULL,
  is_archived    INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_room (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  room_name  TEXT NOT NULL,
  capacity   INTEGER DEFAULT 0,
  location   TEXT,
  is_archived INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_room_reservation (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  room_id     INTEGER NOT NULL,
  event_name  TEXT NOT NULL,
  purpose     TEXT,
  start_time  TEXT NOT NULL,
  end_time    TEXT NOT NULL,
  reserved_by INTEGER,
  status      TEXT DEFAULT 'Confirmed',
  date_created TEXT DEFAULT (datetime('now','localtime')),
  is_archived INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS settings (
  id INTEGER PRIMARY KEY CHECK (id = 1),
  oic_property TEXT,
  oic_president TEXT,
  terms_of_service TEXT
);

CREATE TABLE IF NOT EXISTS tbl_supplier (
  sup_id        INTEGER PRIMARY KEY AUTOINCREMENT,
  supplier_name TEXT NOT NULL,
  contact       TEXT,
  address       TEXT,
  is_archived   INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_organization (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  org_name    TEXT NOT NULL,
  president   TEXT,
  org_logo    TEXT,
  is_archived INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_ris_header (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  ris_no         TEXT NOT NULL,
  last_name      TEXT NOT NULL,
  first_name     TEXT NOT NULL,
  mi_name        TEXT,
  cp_number      TEXT,
  position       TEXT,
  event_name     TEXT,
  event_date     TEXT,
  start_datetime TEXT,
  end_datetime   TEXT,
  department     TEXT,
  is_returned    INTEGER DEFAULT 0,
  return_date    TEXT,
  is_archived    INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_ris_items (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  ris_id         INTEGER NOT NULL,
  asset_id       INTEGER NOT NULL,
  quantity       INTEGER NOT NULL,
  borrowed_from  INTEGER,
  condition      TEXT DEFAULT 'Good',
  return_condition TEXT,
  return_remarks TEXT,
  return_date    TEXT,
  is_archived    INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_ptr_header (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  ptr_no        TEXT NOT NULL,
  transfer_date TEXT,
  from_office   INTEGER,
  to_office     INTEGER,
  remarks       TEXT,
  is_archived   INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_ptr_items (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  ptr_id        INTEGER NOT NULL,
  asset_id      INTEGER NOT NULL,
  inventory_no  TEXT,
  description   TEXT,
  quantity      INTEGER,
  is_archived   INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_disposal (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  dis_no        TEXT NOT NULL,
  asset_id      INTEGER,
  item_name     TEXT,
  inventory_no  TEXT,
  serial_number TEXT,
  office_id     INTEGER,
  quantity      INTEGER,
  remarks       TEXT,
  disposed_by   INTEGER,
  disposed_at   TEXT DEFAULT (datetime('now','localtime')),
  is_archived   INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_incident_reports (
  id               INTEGER PRIMARY KEY AUTOINCREMENT,
  report_number    TEXT NOT NULL,
  reported_by      TEXT,
  office           INTEGER,
  incident_date    TEXT,
  incident_time    TEXT,
  description      TEXT,
  extent_of_damage TEXT,
  status           TEXT DEFAULT 'Open',
  is_archived      INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_incident_items (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  incident_id   INTEGER NOT NULL,
  asset_id      INTEGER,
  quantity      INTEGER DEFAULT 1,
  serial_number TEXT,
  location      TEXT,
  last_borrower TEXT,
  is_archived   INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_maintenance_reports (
  id                        INTEGER PRIMARY KEY AUTOINCREMENT,
  item_name                 TEXT NOT NULL,
  office                    TEXT,
  brand                     TEXT,
  serial_number             TEXT,
  maintenance_code          TEXT,
  maintenance_task          TEXT,
  frequency_days            INTEGER DEFAULT 0,
  previous_maintenance_date TEXT,
  next_maintenance_date     TEXT,
  is_archived               INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_facility_header (
  id               INTEGER PRIMARY KEY AUTOINCREMENT,
  request_no       TEXT NOT NULL,
  office_or_org    TEXT,
  requesting_name  TEXT,
  contact_no       TEXT,
  address          TEXT,
  date_of_filing   TEXT,
  event_name       TEXT NOT NULL,
  num_participants INTEGER DEFAULT 0,
  start_datetime   TEXT,
  end_datetime     TEXT,
  facility_id      INTEGER,
  status           TEXT DEFAULT 'Pending',
  is_archived      INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_facility_equipment (
  id                  INTEGER PRIMARY KEY AUTOINCREMENT,
  facility_request_id INTEGER NOT NULL,
  asset_id            INTEGER,
  quantity            INTEGER DEFAULT 1,
  item_name           TEXT,
  description         TEXT,
  is_archived         INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tbl_notifications (
  id           INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id      INTEGER NOT NULL,
  title        TEXT NOT NULL,
  message      TEXT,
  link         TEXT,
  is_read      INTEGER DEFAULT 0,
  date_created TEXT DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS tbl_forecast_runs (
  id               INTEGER PRIMARY KEY AUTOINCREMENT,
  trained_at       TEXT DEFAULT (datetime('now','localtime')),
  triggered_by     INTEGER,
  train_time_ms    INTEGER,
  trained_products INTEGER,
  total_products   INTEGER,
  mae              REAL,
  rmse             REAL,
  mape             REAL,
  data_first       TEXT,
  data_last        TEXT,
  data_months      INTEGER,
  data_rows        INTEGER,
  status           TEXT DEFAULT 'Done'
);

CREATE TABLE IF NOT EXISTS tbl_req_rule (
  id           INTEGER PRIMARY KEY AUTOINCREMENT,
  rule_code    TEXT NOT NULL UNIQUE,
  rule_name    TEXT NOT NULL,
  category     TEXT NOT NULL DEFAULT 'General',
  description  TEXT,
  policy_basis TEXT,
  rule_meta    TEXT DEFAULT '{}',
  is_enabled   INTEGER DEFAULT 1,
  created_at   TEXT DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS tbl_req_eval (
  id              INTEGER PRIMARY KEY AUTOINCREMENT,
  requisition_id  INTEGER NOT NULL,
  evaluated_by    INTEGER,
  recommendation  TEXT NOT NULL,
  reason          TEXT,
  passed          INTEGER DEFAULT 0,
  failed          INTEGER DEFAULT 0,
  eval_date       TEXT DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS tbl_req_eval_rule (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  eval_id       INTEGER NOT NULL,
  rule_id       INTEGER,
  rule_code     TEXT,
  result        TEXT NOT NULL,
  detail        TEXT,
  is_overridden INTEGER DEFAULT 0
);
`);

// Migrations for existing databases
const productCols = db.prepare("PRAGMA table_info(tbl_product)").all().map((c) => c.name);
if (!productCols.includes("unit_cost")) {
  db.exec("ALTER TABLE tbl_product ADD COLUMN unit_cost REAL DEFAULT 0");
  db.exec("UPDATE tbl_product SET unit_cost = 25 WHERE unit_cost = 0");
}
if (!productCols.includes("unit")) db.exec("ALTER TABLE tbl_product ADD COLUMN unit TEXT DEFAULT 'pcs'");
if (!productCols.includes("product_type")) db.exec("ALTER TABLE tbl_product ADD COLUMN product_type TEXT DEFAULT 'Stock'");
if (!productCols.includes("serial_number")) db.exec("ALTER TABLE tbl_product ADD COLUMN serial_number TEXT");
if (!productCols.includes("condition")) db.exec("ALTER TABLE tbl_product ADD COLUMN condition TEXT DEFAULT 'Good'");
if (!productCols.includes("assigned_to")) db.exec("ALTER TABLE tbl_product ADD COLUMN assigned_to TEXT");
if (!productCols.includes("assigned_remarks")) db.exec("ALTER TABLE tbl_product ADD COLUMN assigned_remarks TEXT");
if (!productCols.includes("assigned_date")) db.exec("ALTER TABLE tbl_product ADD COLUMN assigned_date TEXT");
if (!productCols.includes("office_id")) db.exec("ALTER TABLE tbl_product ADD COLUMN office_id INTEGER");

const categoryCols = db.prepare("PRAGMA table_info(tbl_category)").all().map((c) => c.name);
if (!categoryCols.includes("description")) db.exec("ALTER TABLE tbl_category ADD COLUMN description TEXT");

const userCols = db.prepare("PRAGMA table_info(tbl_user)").all().map((c) => c.name);
if (!userCols.includes("address")) db.exec("ALTER TABLE tbl_user ADD COLUMN address TEXT");
if (!userCols.includes("department")) db.exec("ALTER TABLE tbl_user ADD COLUMN department TEXT");

const settingsCols = db.prepare("PRAGMA table_info(settings)").all().map((c) => c.name);
if (!settingsCols.includes("terms_of_service")) db.exec("ALTER TABLE settings ADD COLUMN terms_of_service TEXT");

const actCols = db.prepare("PRAGMA table_info(activity_log)").all().map((c) => c.name);
if (!actCols.includes("description")) db.exec("ALTER TABLE activity_log ADD COLUMN description TEXT");
if (!actCols.includes("target_id")) db.exec("ALTER TABLE activity_log ADD COLUMN target_id INTEGER");
if (!actCols.includes("ip_address")) db.exec("ALTER TABLE activity_log ADD COLUMN ip_address TEXT");
db.exec("UPDATE activity_log SET description = action WHERE description IS NULL AND action IS NOT NULL");

const prodCols = db.prepare("PRAGMA table_info(tbl_product)").all().map((c) => c.name);
if (!prodCols.includes("department")) db.exec("ALTER TABLE tbl_product ADD COLUMN department TEXT");

const risHCols = db.prepare("PRAGMA table_info(tbl_ris_header)").all().map((c) => c.name);
if (!risHCols.includes("department")) db.exec("ALTER TABLE tbl_ris_header ADD COLUMN department TEXT");

const risICols = db.prepare("PRAGMA table_info(tbl_ris_items)").all().map((c) => c.name);
if (!risICols.includes("condition")) db.exec("ALTER TABLE tbl_ris_items ADD COLUMN condition TEXT DEFAULT 'Good'");
if (!risICols.includes("return_condition")) db.exec("ALTER TABLE tbl_ris_items ADD COLUMN return_condition TEXT");
if (!risICols.includes("return_remarks")) db.exec("ALTER TABLE tbl_ris_items ADD COLUMN return_remarks TEXT");
if (!risICols.includes("return_date")) db.exec("ALTER TABLE tbl_ris_items ADD COLUMN return_date TEXT");

const asgCols = db.prepare("PRAGMA table_info(tbl_asset_assignments)").all().map((c) => c.name);
if (!asgCols.includes("department")) db.exec("ALTER TABLE tbl_asset_assignments ADD COLUMN department TEXT");

// ===================== REQUISITION RULES SEED =====================
const ruleCount = db.prepare("SELECT COUNT(*) AS c FROM tbl_req_rule").get().c;
if (ruleCount === 0) {
  const seedRules = [
    {
      rule_code: "RULE-001",
      rule_name: "Stock Availability",
      category: "Availability",
      description: "Each requested quantity must not exceed the available stock of the item.",
      policy_basis: "Property & Supplies Office - Inventory Management Policy (PSO-IMP-2026) Sec. 3.1: requests shall not exceed available inventory stock.",
      rule_meta: "{}",
    },
    {
      rule_code: "RULE-002",
      rule_name: "Requester Authorization",
      category: "Authorization",
      description: "Only registered, active personnel of the institution may file requisitions.",
      policy_basis: "Property & Supplies Office - Requisition Policy (PSO-RP-2026) Sec. 2.1: only enrolled, active users with a valid account may file requisitions.",
      rule_meta: "{}",
    },
    {
      rule_code: "RULE-003",
      rule_name: "Allocation Limit",
      category: "Allocation",
      description: "Monthly per-item quantity per department must stay within the configured allocation limit.",
      policy_basis: "Property & Supplies Office - Allocation Policy (PSO-AP-2026) Sec. 4.2: monthly per-item allocation per department/program is enforced to ensure equitable distribution.",
      rule_meta: JSON.stringify({ max_qty_per_item_per_month: 120 }),
    },
    {
      rule_code: "RULE-004",
      rule_name: "Duplicate Request",
      category: "Duplicate",
      description: "A pending or approved request for the same item by the same requester within the last 7 days is not allowed.",
      policy_basis: "Property & Supplies Office - Requisition Policy (PSO-RP-2026) Sec. 2.4: duplicate or repeated requests for the same item are disallowed.",
      rule_meta: "{}",
    },
    {
      rule_code: "RULE-005",
      rule_name: "Item / Category Requirement",
      category: "Item & Category",
      description: "Only active, cataloged stock items with a defined category may be requisitioned.",
      policy_basis: "Property & Supplies Office - Inventory Catalog Policy (PSO-ICP-2026) Sec. 2.2: only active cataloged items with an assigned category are eligible for requisition.",
      rule_meta: "{}",
    },
    {
      rule_code: "RULE-006",
      rule_name: "Procurement Requirements",
      category: "Procurement",
      description: "Procurement and documentation requirements under RA 9184 and its IRR must be satisfied (thresholds, purpose, approval authority).",
      policy_basis: "RA 9184 (Government Procurement Reform Act) and DBM-issued Implementing Rules and Regulations Sec. 17: procurement shall follow thresholds and documentation requirements; high-value requests require the appropriate approving authority.",
      rule_meta: JSON.stringify({ high_value_threshold_peso: 50000 }),
    },
    {
      rule_code: "RULE-007",
      rule_name: "Request Completeness",
      category: "Institutional Requirement",
      description: "The request must carry complete requester information and a clear purpose.",
      policy_basis: "Property & Supplies Office - Requisition Policy (PSO-RP-2026) Sec. 2.2: complete requester and purpose information is required before a request can be processed.",
      rule_meta: "{}",
    },
  ];
  const ins = db.prepare(
    "INSERT INTO tbl_req_rule (rule_code, rule_name, category, description, policy_basis, rule_meta) VALUES (?, ?, ?, ?, ?, ?)"
  );
  for (const r of seedRules) ins.run(r.rule_code, r.rule_name, r.category, r.description, r.policy_basis, r.rule_meta);
}

export default db;