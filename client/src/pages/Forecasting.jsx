import { useEffect, useState } from "react";
import { api } from "../api/client";
import {
  LineChart,
  Line,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
  Legend,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
} from "recharts";

const PIE_COLORS = ["#2563eb", "#f59e0b", "#ef4444", "#0ea5e9", "#64748b"];

export default function Forecasting() {
  const [data, setData] = useState(null);
  const [error, setError] = useState("");
  const [filter, setFilter] = useState("all");
  const [retraining, setRetraining] = useState(false);
  const [msg, setMsg] = useState("");

  useEffect(() => {
    api.get("/forecasting").then(setData).catch((e) => setError(e.message));
  }, []);

  async function retrain() {
    setRetraining(true);
    setError("");
    setMsg("");
    try {
      const d = await api.post("/forecasting/retrain");
      setData(d);
      setMsg("Model retrained successfully.");
    } catch (e) {
      setError(e.message);
    } finally {
      setRetraining(false);
    }
  }

  if (error) return <div className="alert alert-error">{error}</div>;
  if (!data) return <div className="empty">Computing RNN-LSTM forecast...</div>;

  const rows = data.perProduct
    .filter((p) => (filter === "all" ? true : p.status.toLowerCase().includes(filter.toLowerCase())))
    .sort((a, b) => b.suggested_reorder - a.suggested_reorder);

  const statusData = ["OK", "Low Stock", "Out of Stock"].map((s) => ({
    name: s,
    value: data.perProduct.filter((p) => p.status === s).length,
  }));

  const chartData = data.perProduct
    .filter((p) => p.forecast_monthly > 0 || p.suggested_reorder > 0)
    .slice(0, 12)
    .map((p) => ({
      name: p.name,
      "Forecast / mo": p.forecast_monthly,
      "Suggested Order": p.suggested_reorder,
      "Current Stock": p.current_stock,
    }));

  const m = data.metrics;
  const acc = data.metrics_within_acceptance;

  return (
    <div>
      {msg && <div className="alert alert-success">{msg}</div>}
      {/* ML Lab - model status card */}
      <div className="card">
        <div className="card-header">
          <h5>Machine Learning Lab — RNN-LSTM Demand Forecaster</h5>
          <button type="button" className="btn btn-primary btn-sm" onClick={retrain} disabled={retraining}>
            {retraining ? "Retraining..." : "⟳ Retrain Model"}
          </button>
        </div>
        <div className="card-body">
          <div className="text-muted" style={{ fontSize: "0.8rem", marginBottom: 12 }}>
            LSTM · {data.sequence_length}-month sequence · {data.hidden_size} hidden units ·{" "}
            {data.epochs} epochs · generated {new Date(data.generated_at).toLocaleString()}
          </div>
          <div className="row">
            <div className="col">
              <div className="card-box blue">
                <h3>{data.model.status}</h3>
                <p>Model Status</p>
              </div>
            </div>
            <div className="col">
              <div className="card-box green">
                <h3>{data.model.trained_products}</h3>
                <p style={{ marginBottom: 0 }}>Trained Items</p>
                <small className="text-muted">sufficient history</small>
              </div>
            </div>
            <div className="col">
              <div className="card-box orange">
                <h3>{data.model.insufficient_products}</h3>
                <p style={{ marginBottom: 0 }}>Insufficient Training Data</p>
                <small className="text-muted">below {data.sequence_length}-month minimum</small>
              </div>
            </div>
            <div className="col">
              <div className="card-box red">
                <h3>{m ? `${m.mape}%` : "—"}</h3>
                <p style={{ marginBottom: 0 }}>MAPE ≤ {data.mape_acceptance}%</p>
                {m && (
                  <small style={{ color: acc ? "#155724" : "#721c24" }}>
                    {acc ? "Target Achieved" : "Above acceptance"}
                  </small>
                )}
              </div>
            </div>
          </div>

          {m && (
            <div className="mt-3">
              <h6>Backtest Accuracy (held-out validation):</h6>
              <div className="flex" style={{ flexWrap: "wrap" }}>
                <span>
                  <strong>MAE:</strong> {m.mae} units
                </span>
                <span>
                  <strong>RMSE:</strong> {m.rmse} units
                </span>
                <span>
                  <strong>MAPE:</strong> {m.mape}%
                </span>
                <span>
                  <strong>Acceptance (≤ {data.mape_acceptance}%):</strong>{" "}
                  {acc ? (
                    <span className="badge badge-ok">Target Achieved</span>
                  ) : (
                    <span className="badge badge-danger">Above ceiling</span>
                  )}
                </span>
              </div>
              <div className="mt-2">
                <small className="text-muted">
                  Computing time: {data.build_ms} ms (results cached until stock-in/out data changes). Per the
                  dissertation, error metrics (MAE/RMSE/MAPE) are validated on historical issuance windows and
                  must stay at or below 20% MAPE across seasonal demand cycles.
                </small>
              </div>
            </div>
          )}
        </div>
      </div>

      <div className="card">
        <div className="card-header">
          <h5>Demand Forecasting &amp; Reorder Recommendation</h5>
          <span className="text-muted" style={{ fontSize: "0.8rem" }}>
            Algorithm: RNN-LSTM (deep learning) · {data.horizon_months}-month horizon ·{" "}
            {data.lead_time_months}-month lead time
          </span>
        </div>
        <div className="card-body">
          <p className="text-muted" style={{ fontSize: "0.9rem", lineHeight: 1.6 }}>
            The <strong>RNN-LSTM</strong> (Input Layer → Hidden LSTM Layer → Dense Layer → Forecast Output)
            is trained on each item's historical <em>issuance</em> using the <strong>MSE loss</strong>. It
            learns the <strong>seasonal demand</strong> pattern — the consumption surge during enrollment
            (June) and second term (January), and the drop during summer — through the input, forget, and
            output gates of the LSTM cell. The <strong>Suggested Order</strong> is the forecasted demand
            multiplied by the lead time, minus the current stock.
          </p>

          <div className="row mt-3">
            <div className="col">
              <div className="card-box blue">
                <h3>{data.totals.totalForecast}</h3>
                <p>Total Forecasted Monthly Demand</p>
              </div>
            </div>
            <div className="col">
              <div className="card-box green">
                <h3>{data.totals.totalSuggested}</h3>
                <p>Total Suggested Reorder Qty</p>
              </div>
            </div>
            <div className="col">
              <div className="card-box orange">
                <h3>{data.totals.actionNeeded}</h3>
                <p>Items Needing Restock</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="row">
        <div className="col">
          <div className="chart-box">
            <h5>Monthly Demand Timeline (36-month issuance history)</h5>
            <div style={{ height: 280 }}>
              <ResponsiveContainer width="100%" height="100%">
                <LineChart data={data.timeline}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="month" />
                  <YAxis allowDecimals={false} />
                  <Tooltip />
                  <Legend />
                  <Line type="monotone" dataKey="demand" name="Qty issued" stroke="#2563eb" strokeWidth={2} />
                </LineChart>
              </ResponsiveContainer>
            </div>
          </div>
        </div>
        <div className="col">
          <div className="chart-box">
            <h5>Stock Status Distribution</h5>
            <div style={{ height: 280 }}>
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie data={statusData} dataKey="value" nameKey="name" innerRadius="55%" outerRadius="80%" label>
                    {statusData.map((_, i) => (
                      <Cell key={i} fill={PIE_COLORS[i % PIE_COLORS.length]} />
                    ))}
                  </Pie>
                  <Tooltip />
                </PieChart>
              </ResponsiveContainer>
            </div>
          </div>
        </div>
      </div>

      {chartData.length > 0 && (
        <div className="row">
          <div className="col">
            <div className="chart-box">
              <h5>Forecast vs Current Stock (top items)</h5>
              <div style={{ height: 320 }}>
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={chartData}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="name" interval={0} angle={-30} textAnchor="end" height={60} />
                    <YAxis allowDecimals={false} />
                    <Tooltip />
                    <Legend />
                    <Bar dataKey="Forecast / mo" fill="#10b981" radius={[4, 4, 0, 0]} />
                    <Bar dataKey="Current Stock" fill="#2563eb" radius={[4, 4, 0, 0]} />
                    <Bar dataKey="Suggested Order" fill="#ef4444" radius={[4, 4, 0, 0]} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </div>
          </div>
        </div>
      )}

      {data.categoryMetrics && data.categoryMetrics.length > 0 && (
        <div className="card">
          <div className="card-header">
            <h5>Forecasting Accuracy by Inventory Classification (MAE / RMSE / MAPE)</h5>
          </div>
          <div className="card-body">
            <div className="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Inventory Classification</th>
                    <th>MAE (units)</th>
                    <th>RMSE (units)</th>
                    <th>MAPE</th>
                    <th>Performance Threshold (≤ {data.mape_acceptance}%)</th>
                  </tr>
                </thead>
                <tbody>
                  {data.categoryMetrics.map((c, i) => (
                    <tr key={i}>
                      <td>{c.category}</td>
                      <td>{c.mae}</td>
                      <td>{c.rmse}</td>
                      <td>{c.mape}%</td>
                      <td>
                        {c.mape <= data.mape_acceptance ? (
                          <span className="badge badge-ok">Target Achieved</span>
                        ) : (
                          <span className="badge badge-danger">Above ceiling</span>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      <div className="card">
        <div className="card-header">
          <h5>Per-Item Recommendations</h5>
          <select className="form-select" style={{ width: 180 }} value={filter} onChange={(e) => setFilter(e.target.value)}>
            <option value="all">All items</option>
            <option value="out">Out of stock</option>
            <option value="low">Low stock</option>
            <option value="ok">Healthy stock</option>
          </select>
        </div>
        <div className="card-body">
          <div className="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Item</th>
                  <th>Category</th>
                  <th>Current Stock</th>
                  <th>Monthly Demand (hist)</th>
                  <th>Forecast (next mo)</th>
                  <th>Suggested Order</th>
                  <th>Forecast Model</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((p) => (
                  <tr key={p.pid}>
                    <td>
                      <strong>{p.name}</strong> {p.brand && <span className="text-muted">· {p.brand}</span>}
                    </td>
                    <td>{p.category_name}</td>
                    <td>{p.current_stock}</td>
                    <td>
                      {p.history.length > 0
                        ? p.history.map((h) => `${h.month.slice(5)}:${h.demand}`).join(", ")
                        : <span className="text-muted">—</span>}
                    </td>
                    <td><strong>{p.forecast_monthly}</strong></td>
                    <td>
                      {p.suggested_reorder > 0 ? (
                        <strong style={{ color: "#dc2626" }}>{p.suggested_reorder}</strong>
                      ) : (
                        "—"
                      )}
                    </td>
                    <td>
                      {p.model_status === "Trained" ? (
                        <span title={p.metrics ? `MAE ${p.metrics.mae} · RMSE ${p.metrics.rmse} · MAPE ${p.metrics.mape}%` : "model trained"}>
                          <span className="badge badge-ok">LSTM</span>{" "}
                          <small className="text-muted">{p.metrics ? `${p.metrics.mape}%` : ""}</small>
                        </span>
                      ) : (
                        <span className="badge badge-warn" title="Continue recording transactions to meet the 12-month minimum">
                          Insufficient Data
                        </span>
                      )}
                    </td>
                    <td>
                      {p.status === "Out of Stock" ? (
                        <span className="badge badge-danger">{p.status}</span>
                      ) : p.status === "Low Stock" ? (
                        <span className="badge badge-warn">{p.status}</span>
                      ) : (
                        <span className="badge badge-ok">{p.status}</span>
                      )}
                    </td>
                  </tr>
                ))}
                {rows.length === 0 && (
                  <tr>
                    <td colSpan={8} className="empty">No items match the current filter.</td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}