import express from "express";
import cors from "cors";
import path from "path";
import fs from "fs";
import { fileURLToPath } from "url";

import authRoutes from "./src/routes/auth.js";
import productRoutes from "./src/routes/products.js";
import userRoutes from "./src/routes/users.js";
import dashboardRoutes from "./src/routes/dashboard.js";
import requisitionRoutes from "./src/routes/requisitions.js";
import reservationRoutes from "./src/routes/reservations.js";
import reportRoutes from "./src/routes/reports.js";
import forecasting, { retrainForecasting } from "./src/routes/forecasting.js";
import masterRoutes from "./src/routes/master.js";
import { requireAuth, requireAdmin } from "./src/middleware/auth.js";
import db from "./src/db.js";
import { logActivity } from "./src/activity.js";
import activityRoutes from "./src/routes/activity.js";
import departmentReportRoutes from "./src/routes/department-report.js";
import assetTrackingRoutes from "./src/routes/asset-tracking.js";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const app = express();
const PORT = process.env.PORT || 5000;

app.use(cors());

// ===================== BACKUP & RESTORE =====================
// Registered before the global JSON parser so restore can accept larger bodies.

app.get("/api/backup", requireAuth, requireAdmin, (req, res) => {
  try {
    const BACKUPS_DIR = path.join(__dirname, "backups");
    if (!fs.existsSync(BACKUPS_DIR)) fs.mkdirSync(BACKUPS_DIR, { recursive: true });
    const backupPath = path.join(BACKUPS_DIR, "custodian_backup.db");
    const dbPath = path.join(__dirname, "data", "custodian.db");

    // Flush WAL into the main db file before copying
    try { db.pragma("wal_checkpoint(TRUNCATE)"); } catch {}

    if (fs.existsSync(backupPath)) fs.unlinkSync(backupPath);
    fs.copyFileSync(dbPath, backupPath);
    res.download(backupPath, `custodian_backup-${new Date().toISOString().slice(0, 10)}.db`, (err) => {
      if (err && !res.headersSent) {
        console.error("Download error:", err);
        res.status(500).json({ error: "Backup download failed." });
      }
    });
  } catch (err) {
    console.error("Backup error:", err);
    res.status(500).json({ error: "Backup failed: " + err.message });
  }
});

app.post("/api/restore", requireAuth, requireAdmin, express.json({ limit: "100mb" }), (req, res) => {
  const tmpPath = path.join(__dirname, "data", "custodian.db.restore-tmp");
  try {
    const { data } = req.body || {};
    if (!data || !String(data).trim()) {
      return res.status(400).json({ error: "No backup file content received." });
    }

    const buf = Buffer.from(String(data), "base64");

    // Validate SQLite file header before touching the live database
    const SQLITE_HEADER = Buffer.from("SQLite format 3\0");
    if (buf.length < 100 || !buf.subarray(0, 16).equals(SQLITE_HEADER)) {
      return res.status(400).json({ error: "Invalid backup file — not a valid SQLite database." });
    }

    const dbPath = path.join(__dirname, "data", "custodian.db");

    // Flush pending WAL writes of the CURRENT database first
    try { db.pragma("wal_checkpoint(TRUNCATE)"); } catch {}

    fs.writeFileSync(tmpPath, buf);

    // Swap files; remove stale WAL/SHM so they can't corrupt the new file
    for (const f of [dbPath, dbPath + "-wal", dbPath + "-shm"]) {
      if (fs.existsSync(f)) fs.unlinkSync(f);
    }
    fs.renameSync(tmpPath, dbPath);

    logActivity(req, "Database restored from uploaded backup file");
    res.json({ success: true, message: "Database restored successfully. Server restart required." });
  } catch (err) {
    console.error("Restore error:", err);
    if (fs.existsSync(tmpPath)) {
      try { fs.unlinkSync(tmpPath); } catch {}
    }
    res.status(500).json({ error: "Restore failed: " + err.message });
  }
});

app.use(express.json({ limit: "10mb" }));

const UPLOADS_DIR = path.join(__dirname, "uploads");
if (!fs.existsSync(UPLOADS_DIR)) fs.mkdirSync(UPLOADS_DIR, { recursive: true });
app.use("/uploads", express.static(UPLOADS_DIR));

app.get("/api/health", (req, res) => res.json({ status: "ok" }));

app.get("/api/settings", (req, res) => {
  const s = db.prepare("SELECT * FROM settings WHERE id = 1").get();
  res.json({
    oic_property: s?.oic_property || "",
    oic_president: s?.oic_president || "",
    terms_of_service: s?.terms_of_service || "",
  });
});

app.put("/api/settings", requireAuth, requireAdmin, (req, res) => {
  const { oic_property, oic_president, terms_of_service } = req.body || {};
  const exists = db.prepare("SELECT id FROM settings WHERE id = 1").get();
  if (!exists) {
    db.prepare(
      "INSERT INTO settings (id, oic_property, oic_president, terms_of_service) VALUES (1, ?, ?, ?)"
    ).run(oic_property || "", oic_president || "", terms_of_service || "");
  } else {
    const s = db.prepare("SELECT * FROM settings WHERE id = 1").get();
    db.prepare(
      "UPDATE settings SET oic_property = ?, oic_president = ?, terms_of_service = ? WHERE id = 1"
    ).run(
      oic_property !== undefined ? oic_property : s.oic_property || "",
      oic_president !== undefined ? oic_president : s.oic_president || "",
      terms_of_service !== undefined ? terms_of_service : s.terms_of_service || ""
    );
  }
  logActivity(req, "Updated System Settings");
  res.json({ success: true });
});

app.use("/api/auth", authRoutes);
app.use("/api/dashboard", dashboardRoutes);
app.use("/api/products", productRoutes);
app.use("/api/users", userRoutes);
app.use("/api/requisitions", requisitionRoutes);
app.use("/api", reservationRoutes);
app.use("/api/reports", reportRoutes);
app.use("/api/master", masterRoutes);
app.use("/api/activity", activityRoutes);
app.use("/api/department-report", departmentReportRoutes);
app.use("/api/asset-tracking", assetTrackingRoutes);

app.get("/api/forecasting", requireAuth, forecasting);
app.post("/api/forecasting/retrain", requireAuth, retrainForecasting);

// Serve the built React app in production
const CLIENT_DIST = path.join(__dirname, "..", "client", "dist");
if (fs.existsSync(CLIENT_DIST)) {
  app.use(express.static(CLIENT_DIST));
  app.get(/^(?!\/api\/).*/, (req, res) => {
    res.sendFile(path.join(CLIENT_DIST, "index.html"));
  });
}

// 404 for unmatched API routes
app.use("/api", (req, res) => res.status(404).json({ error: "Not found" }));

// Global error handler — always respond with JSON, never HTML
app.use((err, req, res, next) => {
  console.error("Unhandled error:", err);
  if (res.headersSent) return next(err);
  res.status(err.status || err.statusCode || 500).json({ error: err.message || "Server error" });
});

app.listen(PORT, () => {
  console.log(`Custodian API running on http://localhost:${PORT}`);
  if (!fs.existsSync(path.join(CLIENT_DIST, "index.html"))) {
    console.log("Client build not found - run `npm run build` for production mode.");
  }
});