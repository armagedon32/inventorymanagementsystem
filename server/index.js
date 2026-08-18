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
import forecasting from "./src/routes/forecasting.js";
import { requireAuth } from "./src/middleware/auth.js";
import db from "./src/db.js";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const app = express();
const PORT = process.env.PORT || 5000;

app.use(cors());
app.use(express.json({ limit: "10mb" }));

const UPLOADS_DIR = path.join(__dirname, "uploads");
if (!fs.existsSync(UPLOADS_DIR)) fs.mkdirSync(UPLOADS_DIR, { recursive: true });
app.use("/uploads", express.static(UPLOADS_DIR));

app.get("/api/health", (req, res) => res.json({ status: "ok" }));

app.use("/api/auth", authRoutes);
app.use("/api/dashboard", dashboardRoutes);
app.use("/api/products", productRoutes);
app.use("/api/users", userRoutes);
app.use("/api/requisitions", requisitionRoutes);
app.use("/api", reservationRoutes);
app.use("/api/reports", reportRoutes);

app.get("/api/forecasting", requireAuth, forecasting);

app.get("/api/settings", (req, res) => {
  const s = db.prepare("SELECT * FROM settings WHERE id = 1").get();
  res.json({
    oic_property: s?.oic_property || "",
    oic_president: s?.oic_president || "",
  });
});

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