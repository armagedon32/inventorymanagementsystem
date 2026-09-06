# Implementation Plan — Property and Supplies Office Inventory System

**Proponent:** Erly Grace Cawed, DIT Candidate
**Title of study:** Development of Inventory and Requisitions System with Asset Tracking and Data Analytics

This plan documents how the system was implemented and how it is operated and maintained.

---

## 1. Objectives and success criteria

| Objective | Success criterion |
| --- | --- |
| Computerize stock in/out and monitoring | Daily/monthly issuance records available; reorder-level alerts |
| Formalize requisitions | Online request–approval workflow with automatic stock issuance |
| Track assets to a custodian | Every asset has an assigned custodian and return/transfer record |
| Provide data analytics | 48-month history + LSTM forecast within required accuracy (MAPE ≤ 20%) |

## 2. Technology stack

- **Frontend:** React 18 + Vite, Recharts (charts)
- **Backend:** Node.js + Express, JSON Web Token auth, bcrypt, captcha
- **Database:** SQLite (WAL mode) with `better-sqlite3`
- **Machine learning:** LSTM implemented in Node.js (`server/src/routes/forecasting.js`) with a retraining job
- **Hosting:** AWS EC2 (primary), Railway (mirror); auto-deploy via GitHub Actions

## 3. Phases completed

### Phase 1 — Baseline system (existing modules)
Stock in/out, products, categories, offices, instructors, users, login/roles, room reservations, supplier/org master files.

### Phase 2 — Requisitions workflow
Requisition create/list/detail, approval with stock-check, rejection with reason, automatic stock deduction + stock-out ledger.

### Phase 3 — Asset tracking
Asset registration/assignment, RIS issuance with expected and actual return dates and item condition, PTR, disposal, incidents, maintenance.

### Phase 4 — Data analytics
48-month issuance history (September 2022 – present); forecasting module (LSTM); MAPE/MAE/RMSE evaluation; acceptance band ≤ 20%; reports dashboard.

### Phase 5 — Monitoring and accountability reporting (this study)
Dashboard with 48-month issuance trend and actual-monthly table; Reports with **Daily / Weekly / Monthly** transaction periods, year filter, CSV export; department reports with Returned/Overdue; **request notifications** (bell) for requisition created/approved/rejected; on-demand backup/restore.

## 4. Release schedule (per iteration unit)

| Release | Contents | Status |
| --- | --- | --- |
| R1–R3 | Stock, categories, users, login/roles, reservations | Delivered |
| R4 | Requisitions + auto-issuance | Delivered |
| R5 | Asset tracking (RIS/PTR/disposal/incidents/maintenance) | Delivered |
| R6 | 48-month dataset, LSTM forecasting, evaluation metrics | Delivered (MAPE 19.41%) |
| R7 | 48-month reporting, period grouping (daily/weekly/monthly), notifications, backups, docs | Delivered (Sep 2026) |

## 5. Dataset and analytics methodology

- Source of truth: issuance ledger (`tbl_stockout`) from in-production use and validated reconstruction for months before system-wide adoption, Sep 2022 – Aug 2026 (48 months, 72 stock products).
- Validation: monthly totals reconciled against office records; the generator preserves recorded months exactly and only completes gaps, and marks non-original rows `remarks = "Refined training data (48-month window)"`.
- Model: LSTM with 6 hidden units, 12-month lookback, horizon 3 months, 150 epochs, deterministic seed.
- Metrics: MAPE / MAE / RMSE (formulas in Chapter 2 of the manuscript). Acceptance: MAPE ≤ 20%. Live result: **19.41%**, 72/72 products trained.

## 6. Deployment pipeline

1. Commit to `main` → GitHub Actions builds the client (`npm run build`) and runs server tests.
2. Actions deploys the artifact to **AWS EC2** (`3.26.131.223`, public domain `https://knsinventorysystem.site`, HTTPS via Let's Encrypt) and pushes the server build to **Railway**.
3. Health check endpoint `GET /api/health` returns `{"status":"ok"}` when live.
4. Service manager: **pm2** (app `inventory` on port 5000) with restart-on-failure, behind nginx (TLS termination + HTTP→HTTPS redirect).

## 7. Operation and maintenance

- **Routine:** daily use of stock in/out, requisitions; weekly review of low-stock alerts; monthly report download for office records (Reports → Transactions, Export CSV).
- **Retraining:** run **Retrain model** as needed; each run prints MAPE.
- **Backups (manual, required):** Admin → initiate backup weekly; keep the downloaded `.db` off-site. Schedule is the office's responsibility until a cron job is enabled.
- **Restore:** upload a backup file; the system snapshots the current DB first; restart pm2 after restoring.
- **Monitoring:** `pm2 status`, `/api/health`, and audit logs (`Audit Logs` screen) are the daily health-check points.

## 8. Risks and mitigation

| Risk | Mitigation |
| --- | --- |
| Natural/regional cloud incident | Weekly downloadable backups; Railway mirror |
| Data loss on restore | Header validation + pre-restore safety snapshot |
| Forecast drift | Metric-driven retrain; hold-out MAPE reported each run |
| Unauthorized access | bcrypt, JWT expiry, captcha, role-based access, audit logs |

## 9. Future work (post-defense roadmap)

- Automated scheduled backups (cron + object storage)
- Email/push notification channel next to in-app bell
- Mobile-optimized requisition screen
- Multi-year retention policy per DPA guidelines