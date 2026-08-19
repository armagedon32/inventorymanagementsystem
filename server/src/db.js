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
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  ris_id        INTEGER NOT NULL,
  asset_id      INTEGER NOT NULL,
  quantity      INTEGER NOT NULL,
  borrowed_from INTEGER,
  condition     TEXT DEFAULT 'Good',
  is_archived   INTEGER DEFAULT 0
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

export default db;