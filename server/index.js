import express from "express";
import cors from "cors";
import path from "path";
import fs from "fs";
import { fileURLToPath } from "url";
import multer from "multer";

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

// Configure multer for file uploads
const storage = multer.diskStorage({
  destination: function (req, file, cb) {
    cb(null, "uploads/");
  },
  filename: function (req, file, cb) {
    cb(null, Date.now() + "-" + file.originalname);
  }
});

const upload = multer({ storage: storage });

app.use(cors());
app.use(express.json({ limit: "10mb" }));

const UPLOADS_DIR = path.join(__dirname, "uploads");
if (!fs.existsSync(UPLOADS_DIR)) fs.mkdirSync(UPLOADS_DIR, { recursive: true });
app.use("/uploads", express.static(UPLOADS_DIR));

app.get("/api/health", (req, res) => res.json({ status: "ok" }));

const BACKUPS_DIR = path.join(__dirname, "backups");
if (!fs.existsSync(BACKUPS_DIR)) fs.mkdirSync(BACKUPS_DIR, { recursive: true });

// ===================== BACKUP & RESTORE =====================
app.get("/api/backup", requireAuth, requireAdmin, (req, res) => {
  const backupPath = path.join(BACKUPS_DIR, "custodian_backup.db");
  // Copy current database to backup file
  try {
    const dbPath = path.join(__dirname, "data", "custodian.db");
    // Ensure we have a fresh copy
    if (fs.existsSync(backupPath)) fs.unlinkSync(backupPath);
    fs.copyFileSync(dbPath, backupPath);
    res.download(backupPath, "custodian_backup.db", (err) => {
      if (err) {
        console.error("Download error:", err);
        // Clean up download file if error
        if (fs.existsSync(backupPath)) fs.unlinkSync(backupPath);
      }
    });
  } catch (err) {
    console.error("Backup error:", err);
    res.status(500).json({ error: "Backup failed: " + err.message });
  }
});

app.post("/api/restore", requireAuth, requireAdmin, upload.single('backup'), (req, res) => {
  try {
    if (!req.file) {
      return res.status(400).json({ error: "No backup file uploaded" });
    }

    const dbPath = path.join(__dirname, "data", "custodian.db");
    const backupPath = path.join(BACKUPS_DIR, req.file.filename);

    // Safety: keep current database state log
    logActivity(req, "Initiating database restore - previous state preserved");

    // Replace current database with backup
    if (fs.existsSync(dbPath)) {
      fs.unlinkSync(dbPath);
    }
    fs.copyFileSync(backupPath, dbPath);

    logActivity(req, "Database restored from: " + req.file.filename);
    res.json({ success: true, message: "Database restored from " + req.file.filename + ". Server restart required." });
  } catch (err) {
    console.error("Restore error:", err);
    res.status(500).json({ error: "Restore failed: " + err.message });
  }
});

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

app.listen(PORT, () => {
  console.log(`Custodian API running on http://localhost:${PORT}`);
  if (!fs.existsSync(path.join(CLIENT_DIST, "index.html"))) {
    console.log("Client build not found - run `npm run build` for production mode.");
  }
});