-- MySQL Schema Migration from SQLite (custodian.db -> RDS MySQL)
-- Run this on your Amazon RDS MySQL instance

-- ============================================================
-- TBL_USER (core users table)
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_user (
  userid INT PRIMARY KEY AUTO_INCREMENT,
  fullname TEXT NOT NULL,
  username TEXT NOT NULL,
  useremail TEXT NOT NULL,
  contact_number TEXT,
  course TEXT,
  major TEXT,
  year_level TEXT,
  department TEXT,
  userpassword TEXT NOT NULL,
  must_change_password TINYINT(1) DEFAULT 0,
  role TEXT NOT NULL DEFAULT 'Admin',
  photo TEXT,
  recovery_question TEXT,
  recovery_answer TEXT,
  is_archived INTEGER DEFAULT 0,
  address TEXT,
  department_user TEXT -- Note: added 'department' column migration
);

-- ============================================================
-- TBL_CATEGORY
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_category (
  catid INT PRIMARY KEY AUTO_INCREMENT,
  category TEXT NOT NULL,
  description TEXT,
  is_archived INTEGER DEFAULT 0
);

-- ============================================================
-- TBL_PRODUCT (important - has many columns from migrations)
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_product (
  pid INT PRIMARY KEY AUTO_INCREMENT,
  barcode TEXT NOT NULL,
  name TEXT NOT NULL,
  brand TEXT NOT NULL,
  acquisition_type TEXT NOT NULL,
  category INT NOT NULL,
  description TEXT NOT NULL,
  stock INTEGER DEFAULT 0,
  reorder_level INTEGER DEFAULT 0,
  unit_cost REAL DEFAULT 0,
  unit TEXT DEFAULT 'pcs',
  product_type TEXT DEFAULT 'Stock',
  serial_number TEXT,
  condition TEXT DEFAULT 'Good',
  assigned_to TEXT,
  assigned_remarks TEXT,
  assigned_date TEXT,
  date_added TEXT,
  image TEXT,
  department TEXT,        -- added during Railway migrations
  is_archived INTEGER DEFAULT 0
);

-- ============================================================
-- TBL_OFFICE
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_office (
  id INT PRIMARY KEY AUTO_INCREMENT,
  parent_id INT,
  office_name TEXT NOT NULL,
  address TEXT,
  contact TEXT,
  max_capacity INTEGER DEFAULT 0,
  instructor_id INT,
  is_archived INTEGER DEFAULT 0
);

-- ============================================================
-- TBL_INSTRUCTORS
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_instructors (
  id INT PRIMARY KEY AUTO_INCREMENT,
  fullname TEXT NOT NULL,
  contact TEXT,
  email TEXT,
  assigned_dept TEXT,
  is_archived INTEGER DEFAULT 0
);

-- ============================================================
-- TBL_STOCKIN
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_stockin (
  id INT PRIMARY KEY AUTO_INCREMENT,
  product_id INT NOT NULL,
  quantity INTEGER NOT NULL,
  remarks TEXT,
  stock_date TEXT DEFAULT (datetime('now','localtime'))
);

-- ============================================================
-- TBL_STOCKOUT
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_stockout (
  id INT PRIMARY KEY AUTO_INCREMENT,
  product_id INT NOT NULL,
  office_id INT,
  instructor_id INT,
  quantity INTEGER NOT NULL,
  stockout_date TEXT DEFAULT (datetime('now','localtime')),
  remarks TEXT,
  is_archived INTEGER DEFAULT 0
);

-- ============================================================
-- ACTIVITY_LOG
-- ============================================================
CREATE TABLE IF NOT EXISTS activity_log (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT,
  action TEXT,
  description TEXT,
  target_id INT,
  ip_address TEXT,
  date_created TEXT DEFAULT (datetime('now','localtime'))
);

-- ============================================================
-- TBL_ASSET_ASSIGNMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_asset_assignments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  asset_id INT NOT NULL,
  assigned_to TEXT NOT NULL,
  office_id INT,
  instructor_id INT,
  department TEXT,
  remarks TEXT,
  date_assigned TEXT DEFAULT (datetime('now','localtime')),
  is_archived INTEGER DEFAULT 0
);

-- ============================================================
-- TBL_REQUISITION
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_requisition (
  id INT PRIMARY KEY AUTO_INCREMENT,
  req_no TEXT NOT NULL,
  purpose TEXT NOT NULL,
  requested_by INT,
  status TEXT DEFAULT 'Pending',
  reject_reason TEXT,
  date_created TEXT DEFAULT (datetime('now','localtime')),
  date_processed TEXT,
  processed_by INT,
  is_archived INTEGER DEFAULT 0
);

-- ============================================================
-- TBL_REQUISITION_ITEM
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_requisition_item (
  id INT PRIMARY KEY AUTO_INCREMENT,
  requisition_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INTEGER NOT NULL,
  is_archived INTEGER DEFAULT 0
);

-- ============================================================
-- TBL_ROOM
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_room (
  id INT PRIMARY KEY AUTO_INCREMENT,
  room_name TEXT NOT NULL,
  capacity INTEGER DEFAULT 0,
  location TEXT,
  is_archived INTEGER DEFAULT 0
);

-- ============================================================
-- TBL_ROOM_RESERVATION
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_room_reservation (
  id INT PRIMARY KEY AUTO_INCREMENT,
  room_id INT NOT NULL,
  event_name TEXT NOT NULL,
  purpose TEXT,
  start_time TEXT NOT NULL,
  end_time TEXT NOT NULL,
  reserved_by INT,
  status TEXT DEFAULT 'Confirmed',
  date_created TEXT DEFAULT (datetime('now','localtime')),
  is_archived INTEGER DEFAULT 0
);

-- ============================================================
-- SETTINGS
-- ============================================================
CREATE TABLE IF NOT EXISTS settings (
  id INT PRIMARY KEY CHECK (id = 1),
  oic_property TEXT,
  oic_president TEXT,
  terms_of_service TEXT
);

-- ============================================================
-- TBL_SUPPLIER
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_supplier (
  sup_id INT PRIMARY KEY AUTO_INCREMENT,
  supplier_name TEXT NOT NULL,
  contact TEXT,
  address TEXT,
  is_archived INTEGER DEFAULT 0
);

-- ============================================================
-- TBL_ORGANIZATION
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_organization (
  id INT PRIMARY KEY AUTO_INCREMENT,
  org_name TEXT NOT NULL,
  president TEXT,
  org_logo TEXT,
  is_archived INTEGER DEFAULT 0
);

-- ============================================================
-- TBL_RIS_HEADER
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_ris_header (
  id INT PRIMARY KEY AUTO_INCREMENT,
  ris_no TEXT NOT NULL,
  last_name TEXT NOT NULL,
  first_name TEXT NOT NULL,
  mi_name TEXT,
  cp_number TEXT,
  position TEXT,
  event_name TEXT,
  event_date TEXT,
  start_datetime TEXT,
  end_datetime TEXT,
  department TEXT,
  is_returned INTEGER DEFAULT 0,
  return_date TEXT,
  is_archived INTEGER DEFAULT 0
);

-- ============================================================
-- TBL_RIS_ITEMS
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_ris_items (
  id INT PRIMARY KEY AUTO_INCREMENT,
  ris_id INT NOT NULL,
  asset_id INT NOT NULL,
  quantity INTEGER NOT NULL,
  condition TEXT DEFAULT 'Good',
  is_archived INTEGER DEFAULT 0
);

-- ============================================================
-- TBL_PTR_HEADER
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_ptr_header (
  id INT PRIMARY KEY AUTO_INCREMENT,
  ptr_no TEXT NOT NULL,
  transfer_date TEXT,
  from_office INT,
  to_office INT,
  remarks TEXT,
  is_archived INTEGER DEFAULT 0
);

-- ============================================================
-- TBL_PTR_ITEMS
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_ptr_items (
  id INT PRIMARY KEY AUTO_INCREMENT,
  ptr_id INT NOT NULL,
  asset_id INT NOT NULL,
  inventory_no TEXT,
  description TEXT,
  quantity INTEGER,
  is_archived INTEGER DEFAULT 0
);

-- ============================================================
-- TBL_DISPOSAL
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_disposal (
  id INT PRIMARY KEY AUTO_INCREMENT,
  dis_no TEXT NOT NULL,
  asset_id INT,
  item_name TEXT,
  inventory_no TEXT,
  office_id INT,
  quantity INTEGER,
  remarks TEXT,
  disposed_by INT,
  disposed_at TEXT DEFAULT (datetime('now','localtime')),
  is_archived INTEGER DEFAULT 0
);

-- ============================================================
-- TBL_INCIDENT_REPORTS
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_incident_reports (
  id INT PRIMARY KEY AUTO_INCREMENT,
  report_number TEXT NOT NULL,
  reported_by TEXT,
  office INT,
  incident_date TEXT,
  incident_time TEXT,
  description TEXT,
  extent_of_damage TEXT,
  status TEXT DEFAULT 'Open',
  is_archived INTEGER DEFAULT 0
);

-- ============================================================
-- TBL_INCIDENT_ITEMS
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_incident_items (
  id INT PRIMARY KEY AUTO_INCREMENT,
  incident_id INT NOT NULL,
  asset_id INT,
  quantity INTEGER DEFAULT 1,
  serial_number TEXT,
  location TEXT,
  last_borrower TEXT,
  is_archived INTEGER DEFAULT 0
);

-- ============================================================
-- TBL_MAINTENANCE_REPORTS
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_maintenance_reports (
  id INT PRIMARY KEY AUTO_INCREMENT,
  item_name TEXT NOT NULL,
  office TEXT,
  brand TEXT,
  serial_number TEXT,
  maintenance_code TEXT,
  maintenance_task TEXT,
  frequency_days INTEGER DEFAULT 0,
  previous_maintenance_date TEXT,
  next_maintenance_date TEXT,
  is_archived INTEGER DEFAULT 0
);

-- ============================================================
-- TBL_FACILITY_HEADER
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_facility_header (
  id INT PRIMARY KEY AUTO_INCREMENT,
  request_no TEXT NOT NULL,
  office_or_org TEXT,
  requesting_name TEXT,
  contact_no TEXT,
  address TEXT,
  date_of_filing TEXT,
  event_name TEXT NOT NULL,
  num_participants INTEGER DEFAULT 0,
  start_datetime TEXT,
  end_datetime TEXT,
  facility_id INT,
  status TEXT DEFAULT 'Pending',
  is_archived INTEGER DEFAULT 0
);

-- ============================================================
-- TBL_FACILITY_EQUIPMENT
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_facility_equipment (
  id INT PRIMARY KEY AUTO_INCREMENT,
  facility_request_id INT NOT NULL,
  asset_id INT,
  quantity INTEGER DEFAULT 1,
  item_name TEXT,
  is_archived INTEGER DEFAULT 0
);