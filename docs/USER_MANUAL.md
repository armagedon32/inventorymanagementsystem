# User Manual — Property and Supplies Office Inventory System

**System name:** Property & Supplies Office Inventory System (Inventory and Requisitions System with Asset Tracking and Data Analytics)
**Applies to:** v1.0 (deployed build, September 2026)

---

## 1. System Overview

The system computerizes the Property and Supplies Office's four core operations:

1. **Stock inventory** — recording of supplies in (receiving) and out (issuance), with reorder-level alerts.
2. **Requisitions** — online request-and-approval workflow that automatically deducts and issues stock.
3. **Asset tracking** — registration, assignment, returns (RIS), property transfer (PTR), disposal, incidents, and maintenance of office assets.
4. **Data analytics** — 48 months of issuance history with an LSTM demand-forecasting module and reporting.

### 1.1 Hardware and software requirements

- Modern web browser (Chrome, Edge, Firefox, or Safari), up to date.
- Internet connection. No client installation is required; the system runs on a web server (AWS EC2).
- Administrator workstation recommended minimum: 4 GB RAM, 1080p display.

### 1.2 Accessing the system

- Primary (AWS, HTTPS): `https://knsinventorysystem.site/`
- Alternate: `https://www.knsinventorysystem.site/`
- Mirror (Railway): `https://server-production-df0e.up.railway.app/`

All connections to the primary site are encrypted with HTTPS (Let's Encrypt certificate, automatically renewed).

### 1.3 Roles

| Role | Capabilities |
| --- | --- |
| **Admin** | Full access: stock, assets, requisitions approval, forecasting, reports, user management, audit logs, backups, settings. |
| **Faculty** | Submit requisitions, view stock, monitor their own requests. |
| **Staff** | Submit requisitions, view stock, reservation requests. |

### 1.4 Test credentials

| Role | Username | Password |
| --- | --- | --- |
| Admin | `superadmin` | `admin123` |
| Faculty | `faculty` | `intern123` |
| Staff | `staff` | `assistant123` |

The login page requires a **captcha** each session. The system uses bcrypt password hashing, JSON Web Token sessions, and audit logging.

---

## 2. Dashboard

- Presents **key current-period figures** (total products, total assets, low/out-of-stock counts, pending requisitions, recent issuances).
- **Monthly issuance trend (48 months)** chart shows real recorded issuance from September 2022 to present.
- **View actual monthly data (48 months)** button opens the raw per-month issuance table.

---

## 3. Stock Inventory (Supplies)

### 3.1 Supplies / Stock In — `Stock Inventory → Supplies / Stock In`
Displays all stock products with barcode, name, brand, category, current stock, reorder level, and status.
- **Status** is computed automatically: `In Stock`, `Low Stock` (at or below reorder level), or `Out of Stock`.
- Buttons: **Edit**, **Stock In** (add quantity), **Stock Out** (issue quantity), **History** (full issuance/restock timeline), **Archive**.

### 3.2 Supply Registration — `Stock Inventory → Supply Registration`
Creates a new stock item: barcode, name, brand, acquisition type, category, description, unit, unit cost, reorder level, department.

### 3.3 Categories — `Stock Inventory → Categories`
Maintains product categories used across stock and forecasting.

### 3.4 Stock In (receiving)
Select a product → enter quantity + date → stock increase is recorded in the `tbl_stockin` ledger (shown in Reports → Transactions).

### 3.5 Stock Out (issuance)
Select a product → enter quantity + office/instructor recipient → stock is reduced and a `tbl_stockout` record is created. Automated approvals also create these records.

---

## 4. Requisitions

1. Go to **Requisitions → New Requisition**.
2. Enter the **purpose** and add line items (product, quantity). Quantities may not exceed the current stock.
3. Submit → the request gets a number (`REQ-yyyymmdd-###`) and is set to **Pending**.
4. Admins are notified of the new request (bell icon in the upper-right).
5. Approve → the system automatically deducts stock and creates the stock-out issuance record; the requester is notified.
6. Reject → the system requires a **reason**; the requester is notified with the reason.

The requester can track the status (Pending / Approved / Rejected) from the list screen and receive notifications of status changes.

---

## 5. Asset Tracking

Module: **Asset Tracking** (Admin).

### 5.1 Registration and assignment
Assets are registered with serial/inventory numbers and can be **assigned to an office, department, instructor, or person** (custodian). Each assignment records who is accountable, the date, and remarks — satisfying accountability per item.

### 5.2 Receipt and Issuance (RIS)
- A **Requisition & Issue Slip (RIS)** borrows assets for an event with an **expected return date (`end_datetime`)**.
- The system tracks **actual return** (`return_date`), and items past the expected return are flagged **Overdue**.
- **Condition** of each borrowed item is recorded (`Good`, `Fair`, `Damaged`, etc.).

### 5.3 Property Transfer (PTR)
Transfers asset custody between offices via **Property Transfer Report (PTR)** with inventory numbers.

### 5.4 Disposal, Incidents, Maintenance
Records of disposed assets, incident reports, and scheduled maintenance with next-due dates.

### 5.5 Reports (Department Reports)
Department-level summaries include **Returned / Overdue** counts for accountability monitoring.

---

## 6. Demand Forecasting (Admin)

Module: **Demand Forecasting**.

### 6.1 Data window
Uses **48 months of real issuance history (September 2022 – present)** across all stock products. See the **Historical Data Coverage by Year** table per product.

### 6.2 Model
- **Algorithm:** LSTM (Long Short-Term Memory neural network).
- Hyperparameters: 6 hidden units, sequence window 12 months, 150 training epochs, 3-month forecast horizon.
- **Accuracy:** measured against held-out actuals via **MAPE (Mean Absolute Percentage Error)**, plus RMSE and MAE. Acceptance criterion: MAPE ≤ 20%. The deployed system reports **MAPE 19.41%** (within the accepted band).

### 6.3 Reading a forecast
- Timeline chart shows past actuals and the next 3 months' forecast.
- Suggested reorder quantity = forecast demand in the horizon, expressed in the product's unit.
- Products with fewer than 12 months of data are flagged **insufficient data**.

### 6.4 Retraining
Click **Retrain model** after importing/recording new transaction data. A successful run shows 72/72 products trained and prints the MAPE.

---

## 7. Reports

Admin only. Tabs:

| Tab | Content |
| --- | --- |
| **Inventory** | Snapshot: item counts, total stock, low/out-of-stock; per-category distribution. |
| **Assets** | Total assets, assigned vs. unassigned, condition distribution. |
| **Requisitions** | Totals and Pending/Approved/Rejected breakdown. |
| **Transactions** | Full stock-in/stock-out ledger. Summary chart with **Daily / Weekly / Monthly** periods (48-month monthly window as well), a **Year filter**, and **Export CSV**. |

CSV export downloads the filtered detail rows.

---

## 8. Room Reservations

Request and track room/venue reservations with date/time and participant counts.

---

## 9. Administration

### 9.1 User management
Create users (Admin/Faculty/Staff), reset passwords, archive accounts.

### 9.2 Audit logs
Every sensitive action (login, create/approve/reject requisition, restore database, product/asset changes) is recorded with the acting user, IP address, and timestamp.

### 9.3 Backup and restore
- **Backup:** from the client, "Initiate backup" downloads `custodian_backup-YYYY-MM-DD.db` (server flushes the WAL before copying). Only Admins can do this. Store backup files off-site.
- **Restore:** upload a previously downloaded `.db` file. The server validates the SQLite header, creates a `pre_restore_<timestamp>.db` safety snapshot on the server, and only then replaces the live database. A server restart is required after restore.

### 9.4 Account settings
Change your own profile, password, and security questions.

---

## 10. Troubleshooting

| Symptom | Cause / action |
| --- | --- |
| Blank page or "Something went wrong" | Clear browser cache, **hard refresh** (Ctrl+F5). If it persists, note the exact error message and report it. The app includes an error boundary that shows the message. |
| "Unauthorized" / login loop | Session expired (12 h). Log in again; the captcha is required per session. |
| Cannot approve a requisition | Stock became insufficient for the request; top up first via Stock In. |
| Forecast shows "insufficient data" | Fewer than 12 months of records for that product; keep recording transactions. |
| Restore says server restart required | The admin must restart the pm2 process (see Implementation Plan, section 7). |

---

## 11. Data protection notes

- Passwords stored as **bcrypt hashes**, never plain text.
- Sessions are **JWTs** expiring after 12 hours.
- Only **Admin** accounts can view user records, audit logs, and perform backups/restores.
- The database and uploaded files live on the dedicated AWS EC2 server; backups are downloadable for off-site custody. See `SYSTEM_ACCESS_AND_EVALUATION.md` §5 for the Data Privacy Act alignment points.