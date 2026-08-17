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
  date_added      TEXT,
  image           TEXT,
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
  date_created TEXT DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS settings (
  id INTEGER PRIMARY KEY CHECK (id = 1),
  oic_property TEXT,
  oic_president TEXT
);
`);

// Migrations for existing databases
const productCols = db.prepare("PRAGMA table_info(tbl_product)").all().map((c) => c.name);
if (!productCols.includes("unit_cost")) {
  db.exec("ALTER TABLE tbl_product ADD COLUMN unit_cost REAL DEFAULT 0");
  db.exec("UPDATE tbl_product SET unit_cost = 25 WHERE unit_cost = 0");
}

export default db;