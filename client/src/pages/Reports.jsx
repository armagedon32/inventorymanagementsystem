import { useEffect, useState } from "react";
import { api } from "../api/client";
import { exportCSV } from "../utils/export";
import {
  BarChart,
  Bar,
  PieChart,
  Pie,
  Cell,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
  ResponsiveContainer,
  Legend,
} from "recharts";

const PIE_COLORS = ["#2563eb", "#10b981", "#0ea5e9", "#f59e0b", "#ef4444", "#64748b", "#6366f1", "#84cc16"];

const REPORTS = {
  inventory: "Inventory",
  assets: "Assets",
  requisitions: "Requisitions",
  transactions: "Transactions",
};

const peso = (n) =>
  "₱" + Number(n || 0).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export default function Reports() {
  const [type, setType] = useState("inventory");
  const [data, setData] = useState(null);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    setLoading(true);
    setError("");
    api
      .get(`/reports/${type}`)
      .then(setData)
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false));
  }, [type]);

  function statCards() {
    const s = data.stats;
    switch (type) {
      case "inventory":
        return [
          { label: "Total Items", value: s.totalItems, cls: "blue" },
          { label: "Total Stock Qty", value: s.totalStock, cls: "green" },
          { label: "Inventory Value", value: peso(s.totalValue), cls: "purple" },
          { label: "Low / Out of Stock", value: `${s.low} / ${s.outOfStock}`, cls: "orange" },
        ];
      case "assets":
        return [
          { label: "Total Assets", value: s.totalAssets, cls: "purple" },
          { label: "Asset Value", value: peso(s.totalValue), cls: "green" },
          { label: "Assigned", value: s.assigned, cls: "blue" },
          { label: "Unassigned", value: s.unassigned, cls: "orange" },
        ];
      case "requisitions":
        return [
          { label: "Total Requisitions", value: s.total, cls: "blue" },
          { label: "Pending", value: s.pending, cls: "orange" },
          { label: "Approved", value: s.approved, cls: "green" },
          { label: "Rejected", value: s.rejected, cls: "red" },
        ];
      case "transactions":
        return [
          { label: "Total Transactions", value: s.totalTransactions, cls: "blue" },
          { label: "Stock In", value: s.totalIn, cls: "green" },
          { label: "Stock Out", value: s.totalOut, cls: "orange" },
          { label: "Net (In - Out)", value: s.net, cls: s.net >= 0 ? "blue" : "red" },
        ];
      default:
        return [];
    }
  }

  function renderChart() {
    if (type === "inventory" || type === "transactions") {
      if (type === "inventory") {
        return (
          <BarChart data={data.chart}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" />
            <YAxis allowDecimals={false} />
            <Tooltip />
            <Bar dataKey="value" name="Items" fill="#2563eb" radius={[6, 6, 0, 0]} />
          </BarChart>
        );
      }
      return (
        <BarChart data={data.chart}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="month" />
          <YAxis allowDecimals={false} />
          <Tooltip />
          <Legend />
          <Bar dataKey="in" name="Stock In" fill="#10b981" radius={[6, 6, 0, 0]} />
          <Bar dataKey="out" name="Stock Out" fill="#f59e0b" radius={[6, 6, 0, 0]} />
        </BarChart>
      );
    }
    return (
      <PieChart>
        <Pie data={data.chart} dataKey="value" nameKey="name" innerRadius="50%" outerRadius="75%" label>
          {data.chart.map((_, i) => (
            <Cell key={i} fill={PIE_COLORS[i % PIE_COLORS.length]} />
          ))}
        </Pie>
        <Tooltip />
        <Legend />
      </PieChart>
    );
  }

  function handleExport() {
    exportCSV({
      filename: `${type}-report-${new Date().toISOString().slice(0, 10)}.csv`,
      headers: data.headers,
      rows: data.rows,
    });
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>Reports</h5>
        <div className="flex">
          <button className="btn btn-sm" onClick={handleExport} disabled={!data}>
            ⤓ Export CSV
          </button>
        </div>
      </div>
      <div className="card-body">
        <div className="flex" style={{ gap: "0.5rem", marginBottom: "1rem", flexWrap: "wrap" }}>
          {Object.entries(REPORTS).map(([key, label]) => (
            <button
              key={key}
              className={`btn btn-sm ${type === key ? "btn-primary" : "btn-light"}`}
              onClick={() => setType(key)}
            >
              {label}
            </button>
          ))}
        </div>

        {error && <div className="alert alert-error">{error}</div>}
        {loading && <div className="empty">Loading report...</div>}
        {!loading && data && (
          <>
            <div className="row mb-3">
              {statCards().map((c) => (
                <div className="col" key={c.label}>
                  <div className={`card-box ${c.cls}`}>
                    <h3 style={{ fontSize: c.value.length > 12 ? "1.2rem" : "1.5rem" }}>{c.value}</h3>
                    <p>{c.label}</p>
                  </div>
                </div>
              ))}
            </div>

            <div className="chart-box mb-3">
              <h5>{REPORTS[type]} - Summary Chart</h5>
              <div style={{ height: 280 }}>
                <ResponsiveContainer width="100%" height="100%">
                  {renderChart()}
                </ResponsiveContainer>
              </div>
            </div>

            <div className="chart-box">
              <h5>{REPORTS[type]} - Details ({data.rows.length} records)</h5>
              <div className="table-wrap">
                <table>
                  <thead>
                    <tr>
                      {data.headers.map((h) => (
                        <th key={h.key}>{h.label}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {data.rows.map((r, i) => (
                      <tr key={i}>
                        {data.headers.map((h) => {
                          const v = r[h.key];
                          const shown =
                            ["unit_cost", "value"].includes(h.key) && v != null
                              ? "₱" + Number(v).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                              : v ?? "—";
                          return <td key={h.key}>{shown}</td>;
                        })}
                      </tr>
                    ))}
                    {data.rows.length === 0 && (
                      <tr>
                        <td colSpan={data.headers.length} className="empty">No records for this report.</td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          </>
        )}
      </div>
    </div>
  );
}