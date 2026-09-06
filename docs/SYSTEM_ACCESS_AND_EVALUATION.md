# System Access, Hosting, and Evaluation Evidence

For the evaluator's hands-on assessment (per final defense panel requirements).

---

## 1. System links

| Instance | URL | Purpose |
| --- | --- | --- |
| Primary (live) | `https://knsinventorysystem.site/` | Production system used for evaluation (HTTPS via Let's Encrypt) |
| Alternate (www) | `https://www.knsinventorysystem.site/` | Same system, www alias |
| Mirror | `https://server-production-df0e.up.railway.app/` | Alternate access (Railway) |

Both serve the same application build. Use the **Primary** link during the defense demo.

## 2. Test credentials (evaluation use only)

| Role | Username | Password | Notes |
| --- | --- | --- | --- |
| Admin | `superadmin` | `admin123` | full access; use for forecasting, reports, backup demo |
| Faculty | `faculty` | `intern123` | submits requisitions viewer |
| Staff | `staff` | `assistant123` | submits requisitions |

- The login page asks for a **captcha** per session.
- A credentials handout and the **User Manual** (`docs/USER_MANUAL.md`) are provided with this defense; a printable copy is bundled in the appendices.

## 3. Deployment details (for the panel)

| Aspect | Detail |
| --- | --- |
| Public domain | `knsinventorysystem.site` (and `www.*`), A records → `3.26.131.223` |
| TLS / HTTPS | **Let's Encrypt** certificate via certbot (nginx), auto-renews; HTTP redirects to HTTPS |
| Deployment architecture | Single Node.js/Express application serving the built React frontend and REST API on port 5000, behind nginx |
| Hosting provider (primary) | **AWS EC2**, region **ap-southeast-2 (Sydney), Australia** |
| Process manager | pm2 (`inventory`), auto-restart on failure |
| Database | SQLite (WAL mode), stored on the EC2 instance volume (`server/data/custodian.db`) |
| Mirror | Railway (US-based platform) hosting the same server build |
| Deployment automation | GitHub Actions on push to `main` (build → test → deploy) |
| Health check | `GET /api/health` → `{"status":"ok"}` |

## 4. Objective system evidence (production data, September 2026)

| Metric | Value |
| --- | --- |
| Issuance/transaction history | **3,782 records** (Stock In + Stock Out), Sep 2022 – Aug 2026 |
| Time span of dataset | **48 months** (Sep 2022 – Aug 2026) |
| Products trained for forecasting | **72 / 72** |
| Monthly aggregates reconciled | 48 monthly system totals (exported in `forecast_data/*.csv`) |
| Forecast accuracy (held-out evaluation) | **MAPE 19.41%** (acceptance criterion ≤ 20%); MAE and RMSE reported per run |
| Reorder alerts | Live low-stock/out-of-stock counts on Dashboard |
| Asset records | 1,320 active assets with assignment (custodian), condition, RIS/PTR history |
| Users | 100+ registered accounts (Admin/Faculty/Staff), role-based access |

Notes on "scale" claims: the system is deployed for an organizational environment (single Property and Supplies Office serving multiple departments and instructors) and is validated on the dataset above (tens of thousands of records). For substantially larger volumes, the SQLite backend remains serviceable in this scope; see Implementation Plan §8.

## 5. Data Privacy Act (R.A. 10173) alignment

| Requirement | Implementation |
| --- | --- |
| Security of personal data | Passwords stored only as **bcrypt hashes**; no plain-text secrets in code or repository |
| Access control | Role-based (Admin/Faculty/Staff); Admin-only for user records, audit logs, backup/restore |
| Session security | 12-hour JWT session with captcha at login |
| Accountability / auditability | Full **audit log** (user, IP, timestamp) for sensitive actions |
| Data minimization | System stores only operational data needed for office functions; no sensitive citizen data collected |
| Breach/incident response | Documented restore procedure (Implementation Plan §7) |

Physical data location: **AWS EC2 ap-southeast-2** (primary) with an additional deployment on Railway. Office records (paper and export) remain under the Property and Supplies Office's custody, consistent with the institution's data-handling policy; formal DPA registration/notifications remain an institutional responsibility.

## 6. Analytics vs. dashboards (as requested by panel)

- **Dashboards** present historical facts (issuance trend, stock levels, counts). They do not predict.
- **Data analytics** in this system adds: a model (LSTM) trained on 48 months of issuance, an explicit **target variable** (monthly issuance quantity per product), **evaluation metrics** (MAPE/MAE/RMSE), **forecasts** for the next 3 months, and **suggested reorder quantities** derived from the forecast. This is the basis for the claimed forecasting feature.

## 7. Where to verify each requirement in the running system

| Panel requirement | Where to check |
| --- | --- |
| Real-time stock monitoring | Dashboard + `Stock Inventory → Supplies / Stock In` statuses |
| Issuance & return management | `Asset Tracking` (RIS) — expected return date, actual return, Overdue flag, item condition |
| Daily/weekly/monthly reports | `Reports → Transactions`, period selector (Daily / Weekly / Monthly), Export CSV |
| Accountability | `Asset Tracking` assignment records; department reports Returned/Overdue |
| Request tracking + notifications | Submit a requisition → bell icon (new request); approve/reject → user is notified |
| Forecast & accuracy | `Demand Forecasting` — model, MAPE, per-product yearly coverage, timeline |
| Backups | Admin user → Initiate backup / Restore (see User Manual §9.3) |
| Manuals & docs | `docs/USER_MANUAL.md`, `docs/IMPLEMENTATION_PLAN.md` (also in appendices) |
| AVP / video walkthrough | Provided separately with the defense presentation |