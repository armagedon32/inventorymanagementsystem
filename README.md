# Property and Supplies Office - Inventory Management System

Converted from the legacy PHP (XAMPP/MySQL) system into a modern **React + Node/Express + SQLite** full-stack app.
No XAMPP, no MySQL, no third-party servers needed — SQLite runs inside Node.

> **Live deployment:** https://server-production-df0e.up.railway.app
> (Admin: `superadmin` / `admin123`)

## Features (Core Modules)

- **Authentication** — login (email/username), JWT session, change password, role-based access
  (Admin / Intern / Student Assistant)
- **Dashboard** — live summary cards + charts (acquisition, category, 7-day stock out) + recent activity log
- **Supplies (Products)** — registration, list, view, edit, archive, auto-generated barcodes
- **Stock Management** — Stock In (restock), Stock Out (issuance to office/instructor), full history log
- **User Management** (Admin only) — create / edit / archive users
- **Demand Forecasting (the algorithm)** — RNN-LSTM (Input → Hidden LSTM → Dense → Forecast Output),
  trained on monthly issuance history (seasonal cycle: June enrollment peak, 2nd-term peak in January,
  summer drop); per-item forecast + suggested reorder quantity + status (OK / Low / Out) + MAE/RMSE/MAPE
  backtest metrics

## Default Accounts

| Role               | Username      | Password      |
|--------------------|---------------|---------------|
| Admin              | `superadmin`  | `admin123`    |
| Intern             | `intern`      | `intern123`   |
| Student Assistant  | `assistant`   | `assistant123`|

## Tech Stack

- **Frontend:** React 18 + Vite + React Router + Recharts
- **Backend:** Node.js + Express (REST API)
- **Database:** SQLite via `better-sqlite3` (single file: `server/data/custodian.db`)
- **Auth:** JSON Web Token (JWT) + bcrypt password hashing

## Project Structure

```
react/
├── package.json          # npm workspaces + shared scripts
├── server/               # Express + SQLite backend
│   ├── index.js          # entry point (serves API + built client)
│   ├── seed.js           # create admin + demo data
│   ├── src/db.js         # SQLite schema + connection
│   ├── src/middleware/auth.js
│   └── src/routes/       # auth, products, users, dashboard, forecasting
└── client/               # Vite + React frontend
    └── src/pages/        # Login, Dashboard, Products, StockIn/Out, Users, Forecasting...
```

## Local Development (Hot Reload)

```bash
cd react
npm install
npm run seed          # first time only: creates DB + admin + sample data
npm run dev           # runs API (:5000) + Vite dev server (:5173) together
```

Then open **http://localhost:5173** (Vite dev server proxies `/api` to the API).

- `npm run dev:server` — API only
- `npm run dev:client` — frontend only

## Production Mode (Single Port)

```bash
cd react
npm install
npm run seed
npm run build         # builds React into client/dist
npm start             # Express serves the built app + API on :5000
```

Open **http://localhost:5000**

## Deploy to Railway

1. Push this `react/` folder to a Git repository (or connect directly to your repo).
2. In Railway, create a **New Project → Deploy from GitHub repo**.
3. Set **Root Directory** to `react` (or `server` for just the backend).
4. Set these environment variables in Railway (Settings → Variables):
   - `NODE_ENV=production`
   - `JWT_SECRET=<a long random string>`
   - `PORT` (Railway auto-injects this; don't set it manually)
5. Build command (root = `react`): `npm install && npm run build`
6. Start command: `npm start`
7. Railway gives you a public `*.up.railway.app` URL.

> **Important for persistent data on Railway:** Railway's filesystem is ephemeral. The SQLite file
> lives in `server/data/custodian.db`. To keep data between restarts, add a **Volume** mounted at
> `/app/server/data` (or run `npm run seed` once via Railway CLI after first deploy).

## Forecasting Algorithm

Located at `server/src/routes/forecasting.js` (implementation in `server/src/ml/lstm.js`).

- Input: monthly issuance aggregates from `tbl_stockout` (every Stock Out event is logged with product,
  quantity, date). Input features = scaled monthly quantity + one-hot month-of-year.
- Method: **RNN-LSTM** (Input Layer → Hidden LSTM Layer → Dense Layer → Forecast Output Layer).
  Windows of `SEQUENCE_LENGTH = 12` months ("Previous 12 months → Forecast Month 13") predict the next
  month's demand, trained with MSE loss + Adam. Backtest on the last `VAL_SPLIT = 3` windows yields
  MAE / RMSE / MAPE per item and per classification category.
- Fallback: products with fewer than `MIN_HISTORY_MONTHS = 12` months of history are marked
  "Insufficient Training Data" and use a recent-average fallback so reorder suggestions still show.
- Output per product:
  - `forecast_monthly` = next month's LSTM forecast (rounded)
  - `future` = recursive multi-month ahead forecast (`HORIZON_MONTHS = 3`)
  - `suggested_reorder` = `max(0, forecast * LEAD_TIME_MONTHS - current_stock)`
  - `status` = Out of Stock / Low Stock / OK (based on `stock` vs `reorder_level`)
  - `model_status` = Trained / Insufficient Training Data
- Acceptance: `MAPE_ACCEPTANCE = 20%` (≤ 20% → `metrics_within_acceptance`).
- Results are cached in memory and rebuilt automatically when stock-in/out data changes.

## API Reference (abbreviated)

| Method | Endpoint                          | Access   | Description                     |
|--------|-----------------------------------|----------|---------------------------------|
| POST   | `/api/auth/login`                 | public   | Login → `{token, user}`         |
| GET    | `/api/auth/me`                    | auth     | Current user                    |
| POST   | `/api/auth/change-password`       | auth     | Change password                 |
| POST   | `/api/auth/logout`                | auth     | Logout + activity log           |
| GET    | `/api/dashboard/summary`          | auth     | Stats + charts data             |
| GET    | `/api/products`                   | auth     | List supplies                   |
| POST   | `/api/products`                   | auth     | Create supply                   |
| GET    | `/api/products/:id`               | auth     | Get one supply                  |
| PUT    | `/api/products/:id`               | auth     | Update supply                   |
| DELETE | `/api/products/:id`               | auth     | Archive supply                  |
| POST   | `/api/products/:id/stock-in`      | auth     | Restock                         |
| POST   | `/api/products/:id/stock-out`     | auth     | Issue stock (validates stock)   |
| GET    | `/api/products/:id/history`       | auth     | Stock in/out history            |
| GET    | `/api/products/meta/categories`   | auth     | Categories list                 |
| GET    | `/api/forecasting`                | auth     | Forecast + recommendations      |
| GET/POST/PUT/DELETE | `/api/users[...]`          | admin    | User management                 |

## Known Limitations (Phase 1 - Core)

- The original system's Property, RIS, RSE, PTR, Facility, Incident, and Maintenance modules are
  **not yet converted** — planned for the next phase.
- No image upload UI yet (schema column exists; endpoints can be added later).
- Barcode printing (original used FPDF) will be re-implemented with a browser-based printable view.
