/**
 * Stock demand forecasting — RNN-LSTM (per dissertation §2.27).
 *
 * Architecture: Input Layer -> Hidden LSTM Layer -> Dense Layer -> Forecast Output Layer.
 * - Input features: scaled monthly issuance quantity + one-hot month-of-year (the
 *   dissertation lists "historical issuance quantity / date of issuance / monthly
 *   consumption / semester & summer demand" as input variables).
 * - Windows of `SEQUENCE_LENGTH` months predict the next month (MSE loss, Adam).
 * - Backtest on the last `VAL_SPLIT` windows produces MAE / RMSE / MAPE.
 * - If a product has fewer than MIN_HISTORY_MONTHS of history (or too few windows), the
 *   model reports "Insufficient Training Data" (per the ML Lab manual) and falls back to a
 *   naive recent-average so reorder recommendations remain available.
 * - Results are cached in memory and rebuilt only when stock-in/out data changes.
 */

import db from "../db.js";
import { LSTMForecaster } from "../ml/lstm.js";
import { logActivity } from "../activity.js";

const SEQUENCE_LENGTH = 12;      // "Previous 12 months -> Forecast Month 13"
const MIN_HISTORY_MONTHS = 12;   // minimum monthly points to train
const MIN_TRAIN_SAMPLES = 3;     // minimum windows to train
const HORIZON_MONTHS = 3;        // forecast horizon
const LEAD_TIME_MONTHS = 1;      // months between ordering and arrival
const HIDDEN_SIZE = 6;
const EPOCHS = 150;
const LR = 0.02;
const VAL_SPLIT = 3;             // last N windows held out for MAE/RMSE/MAPE backtest
const MAPE_ACCEPTANCE = 20;      // manuscript acceptance threshold (<= 20%)

let cache = null;
let cacheKey = null;

function monthKey(dateStr) {
  return String(dateStr).slice(0, 7); // "YYYY-MM"
}

function monthIndexOf(dateStr) {
  const s = String(dateStr);
  return s.length >= 7 ? Number(s.slice(5, 7)) - 1 : 0; // 0 = January
}

function dataVersion() {
  const so = db.prepare("SELECT COUNT(*) c, COALESCE(MAX(id),0) m FROM tbl_stockout WHERE is_archived = 0").get();
  const si = db.prepare("SELECT COUNT(*) c, COALESCE(MAX(id),0) m FROM tbl_stockin WHERE is_archived = 0").get();
  return `so${so.c}-${so.m}|si${si.c}-${si.m}`;
}

/** pid -> [{ date, value }] sorted chronologically */
function demandSeries() {
  const rows = db
    .prepare(
      `SELECT product_id, quantity, stockout_date
       FROM tbl_stockout
       WHERE is_archived = 0
       ORDER BY stockout_date`
    )
    .all();

  const map = {};
  const byMonth = {};
  for (const r of rows) {
    const key = monthKey(r.stockout_date);
    const mi = monthIndexOf(r.stockout_date);
    if (!key) continue;
    if (!byMonth[r.product_id]) byMonth[r.product_id] = {};
    if (!byMonth[r.product_id][key]) byMonth[r.product_id][key] = { value: 0, mi };
    byMonth[r.product_id][key].value += Number(r.quantity);
  }
  for (const pid of Object.keys(byMonth)) {
    map[pid] = Object.entries(byMonth[pid])
      .sort(([a], [b]) => (a < b ? -1 : 1))
      .map(([date, v]) => ({ date, value: v.value, mi: v.mi }));
  }
  return map;
}

function minMax(arr) {
  let lo = Infinity, hi = -Infinity;
  for (const v of arr) {
    lo = Math.min(lo, v);
    hi = Math.max(hi, v);
  }
  return { lo, hi, span: hi - lo || 1 };
}

function scale(v, m) {
  return (v - m.lo) / m.span;
}

function unscale(v, m) {
  return v * m.span + m.lo;
}

/** [scaled demand, one-hot month (12 features)] */
function inputFor(value, mi, scaler) {
  const feat = new Array(13).fill(0);
  feat[0] = scale(value, scaler);
  feat[1 + mi] = 1;
  return feat;
}

/** MAE, RMSE, MAPE between denormalized predictions and actuals. */
function evalMetrics(preds, actuals) {
  if (preds.length === 0) return null;
  let mae = 0, rmse = 0, mape = 0;
  for (let i = 0; i < preds.length; i++) {
    const d = Math.abs(preds[i] - actuals[i]);
    mae += d;
    rmse += d * d;
    mape += d / Math.max(Math.abs(actuals[i]), 1);
  }
  mae /= preds.length;
  rmse = Math.sqrt(rmse / preds.length);
  mape = (mape / preds.length) * 100;
  return { mae: round2(mae), rmse: round2(rmse), mape: round2(mape) };
}

function round2(n) {
  return Math.round(n * 100) / 100;
}

function naiveForecast(series) {
  const recent = series.slice(-6);
  return Math.round(recent.reduce((a, b) => a + b, 0) / Math.max(recent.length, 1));
}

/** Train LSTM for one product given monthly points [{date,value,mi}]. */
function trainProduct(points) {
  const values = points.map((p) => p.value);
  const scaler = minMax(values);
  const normValues = values.map((v) => scale(v, scaler));
  const inputs = points.map((p, i) => inputFor(normValues[i], p.mi, scaler));

  // windows of SEQUENCE_LENGTH feature-vectors -> scalar next-month target
  const samples = [];
  const targets = [];
  for (let i = 0; i + SEQUENCE_LENGTH < inputs.length; i++) {
    samples.push(inputs.slice(i, i + SEQUENCE_LENGTH));
    targets.push(normValues[i + SEQUENCE_LENGTH]);
  }

  if (points.length < MIN_HISTORY_MONTHS || samples.length < MIN_TRAIN_SAMPLES) {
    const forecast = naiveForecast(values);
    return {
      forecast,
      future: [],
      metrics: null,
      meta: {
        model_status: "Insufficient Training Data",
        reason: "Requires at least " + MIN_HISTORY_MONTHS + " months of issuance history",
        history_months: points.length,
        train_samples: samples.length,
      },
    };
  }

  const nTrain = Math.max(samples.length - VAL_SPLIT, MIN_TRAIN_SAMPLES);
  const trainS = samples.slice(0, nTrain);
  const trainT = targets.slice(0, nTrain);
  const valS = samples.slice(nTrain);
  const valT = targets.slice(nTrain);
  const valActuals = valT.slice(0, valT.length);

  const t0 = Date.now();
  const model = new LSTMForecaster({ inputSize: 13, hiddenSize: HIDDEN_SIZE });
  const loss = model.fit(trainS, trainT, { epochs: EPOCHS, lr: LR, clip: 5 });
  const trainTimeMs = Date.now() - t0;

  // backtest metrics (denormalized)
  const preds = valS.map((s) => unscale(model.predict(s), scaler));
  const actuals = valActuals.map((v) => unscale(v, scaler));
  const metrics = evalMetrics(preds, actuals);

  // multi-step future forecast (recursive, feeding predictions + next month's one-hot back)
  const future = [];
  let curInputs = inputs.slice(-SEQUENCE_LENGTH);
  let mi = points[points.length - 1].mi;
  for (let s = 0; s < HORIZON_MONTHS; s++) {
    const yScaled = model.predict(curInputs);
    const v = Math.max(0, Math.round(unscale(yScaled, scaler)));
    future.push(v);
    mi = (mi + 1) % 12;
    curInputs = curInputs.slice(1).concat([inputFor(yScaled, mi, scaler)]);
  }

  return {
    forecast: future[0] || naiveForecast(values),
    future,
    metrics,
    meta: {
      model_status: "Trained",
      history_months: points.length,
      train_samples: trainS.length,
      val_samples: valS.length,
      sequence_length: SEQUENCE_LENGTH,
      hidden_size: HIDDEN_SIZE,
      epochs: EPOCHS,
      final_loss: Math.round(loss * 1000) / 1000,
      train_time_ms: trainTimeMs,
    },
  };
}

function buildPerProductForecast(seriesMap) {
  const products = db
    .prepare(
      `SELECT p.*, c.category AS category_name
       FROM tbl_product p
       LEFT JOIN tbl_category c ON p.category = c.catid
       WHERE p.is_archived = 0 AND p.product_type = 'Stock'`
    )
    .all();

  return products.map((p) => {
    const points = seriesMap[p.pid] || [];
    const values = points.map((x) => x.value);
    const currentStock = Number(p.stock);

    let forecast, future, metrics, meta;
    if (points.length === 0) {
      forecast = 0;
      future = [];
      metrics = null;
      meta = { model_status: "Insufficient Training Data", reason: "No issuance history", history_months: 0, train_samples: 0 };
    } else {
      const r = trainProduct(points);
      forecast = r.forecast;
      future = r.future;
      metrics = r.metrics;
      meta = r.meta;
    }

    const suggestedRestock = Math.max(0, forecast * LEAD_TIME_MONTHS - currentStock);

    let status = "OK";
    if (currentStock === 0) status = "Out of Stock";
    else if (currentStock <= Number(p.reorder_level)) status = "Low Stock";

    const nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);

    return {
      pid: p.pid,
      name: p.name,
      brand: p.brand,
      category_name: p.category_name,
      current_stock: currentStock,
      reorder_level: Number(p.reorder_level),
      forecast_monthly: forecast,
      future,
      suggested_reorder: Math.round(suggestedRestock),
      status,
      model_status: meta.model_status,
      metrics,
      model_meta: meta,
      history: points.slice(-6).map((d) => ({ month: d.date, demand: d.value })),
      forecast_month: nextMonth.toISOString().slice(0, 7),
    };
  });
}

function buildTimeline() {
  const rows = db
    .prepare(
      `SELECT stockout_date, SUM(quantity) AS total
       FROM tbl_stockout
       WHERE is_archived = 0
       GROUP BY date(stockout_date)
       ORDER BY date(stockout_date)`
    )
    .all();

  const byMonth = {};
  for (const r of rows) {
    const key = monthKey(r.stockout_date);
    if (!key) continue;
    byMonth[key] = (byMonth[key] || 0) + Number(r.total);
  }
  return Object.entries(byMonth)
    .sort(([a], [b]) => (a < b ? -1 : 1))
    .map(([month, demand]) => ({ month, demand }));
}

function build() {
  const seriesMap = demandSeries();
  const t0 = Date.now();
  const perProduct = buildPerProductForecast(seriesMap);
  const buildMs = Date.now() - t0;
  const timeline = buildTimeline();

  const totalForecast = perProduct.reduce((s, p) => s + p.forecast_monthly, 0);
  const totalSuggested = perProduct.reduce((s, p) => s + p.suggested_reorder, 0);
  const actionNeeded = perProduct.filter((p) => p.suggested_reorder > 0).length;

  const trained = perProduct.filter((p) => p.metrics);
  const overall = trained.length
    ? {
        mae: round2(trained.reduce((s, p) => s + p.metrics.mae, 0) / trained.length),
        rmse: round2(trained.reduce((s, p) => s + p.metrics.rmse, 0) / trained.length),
        mape: round2(trained.reduce((s, p) => s + p.metrics.mape, 0) / trained.length),
      }
    : null;

  // per-category metrics (mirrors the manuscript's classification table)
  const catMap = {};
  for (const p of trained) {
    if (!catMap[p.category_name]) catMap[p.category_name] = { mae: 0, rmse: 0, mape: 0, n: 0 };
    catMap[p.category_name].mae += p.metrics.mae;
    catMap[p.category_name].rmse += p.metrics.rmse;
    catMap[p.category_name].mape += p.metrics.mape;
    catMap[p.category_name].n += 1;
  }
  const categoryMetrics = Object.entries(catMap).map(([category, v]) => ({
    category,
    mae: round2(v.mae / v.n),
    rmse: round2(v.rmse / v.n),
    mape: round2(v.mape / v.n),
    items: v.n,
  }));

  return {
    algorithm: "RNN-LSTM",
    lead_time_months: LEAD_TIME_MONTHS,
    horizon_months: HORIZON_MONTHS,
    sequence_length: SEQUENCE_LENGTH,
    hidden_size: HIDDEN_SIZE,
    epochs: EPOCHS,
    mape_acceptance: MAPE_ACCEPTANCE,
    generated_at: new Date().toISOString(),
    build_ms: buildMs,
    model: {
      trained_products: trained.length,
      insufficient_products: perProduct.length - trained.length,
      total_products: perProduct.length,
      status: trained.length > 0 ? "Trained" : "Insufficient Training Data",
    },
    metrics: overall,
    metrics_within_acceptance: overall ? overall.mape <= MAPE_ACCEPTANCE : false,
    categoryMetrics,
    totals: { totalForecast, totalSuggested, actionNeeded },
    perProduct,
    timeline,
  };
}

export default function forecasting(req, res) {
  const key = dataVersion();
  if (req.query.force === "1" || !cache || cacheKey !== key) {
    cache = build();
    cacheKey = key;
  }
  res.json({ ...cache, retrained: req.query.force === "1" });
}

export function retrainForecasting(req, res) {
  cache = build();
  cacheKey = dataVersion();
  logActivity(req, "Retrained Forecasting Model", "Manual retrain requested from the ML Lab");
  res.json({ ...cache, retrained: true });
}