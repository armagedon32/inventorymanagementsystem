import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { api } from "../api/client";
import { exportCSV, exportPDF } from "../utils/export";

const STATUS_BADGE = {
  Available: "badge-ok",
  Assigned: "badge-warn",
  Borrowed: "badge-info",
  Overdue: "badge-danger",
  "In Maintenance": "badge-warn",
  "For Disposal": "badge-dark",
};

function pct(n) {
  return `${n}%`;
}

function UtilizationBar({ percent }) {
  const color = percent >= 70 ? "#10b981" : percent >= 40 ? "#f59e0b" : "#ef4444";
  return (
    <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
      <div style={{ flex: 1, height: 8, background: "#e5e7eb", borderRadius: 4 }}>
        <div style={{ width: `${Math.min(percent, 100)}%`, height: 8, background: color, borderRadius: 4 }} />
      </div>
      <span style={{ fontSize: "0.85rem", minWidth: 44, textAlign: "right" }}>{pct(percent)}</span>
    </div>
  );
}

function MiniTable({ title, headers, rows, empty }) {
  return (
    <div className="col">
      <div className="chart-box">
        <h5>{title}</h5>
        <div className="table-wrap" style={{ maxHeight: 320 }}>
          <table>
            <thead>
              <tr>{headers.map((h) => <th key={h}>{h}</th>)}</tr>
            </thead>
            <tbody>
              {rows.map((r, i) => (
                <tr key={i}>
                  <td><strong>{r[0]}</strong></td>
                  <td>{r[1]}</td>
                  <td>{r[2]}</td>
                  <td><UtilizationBar percent={r[3]} /></td>
                </tr>
              ))}
              {rows.length === 0 && <tr><td colSpan={4} className="empty">{empty}</td></tr>}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

export default function AssetTracking() {
  const [data, setData] = useState(null);
  const [error, setError] = useState("");
  const [days, setDays] = useState(90);
  const [query, setQuery] = useState("");
  const [showHistory, setShowHistory] = useState({});

  useEffect(() => {
    api.get(`/asset-tracking?days=${days}`).then(setData).catch((e) => setError(e.message));
  }, [days]);

  function handleExport(format) {
    if (!data) return;
    const headers = [
      { label: "Asset Tag", key: "barcode" },
      { label: "Name", key: "name" },
      { label: "Serial No.", key: "serial_number" },
      { label: "Office", key: "department" },
      { label: "Category", key: "category_name" },
      { label: "Condition", key: "condition" },
      { label: "Status", key: "status" },
      { label: "Status Detail", key: "status_detail" },
      { label: "In Use", key: "__in_use" },
    ];
    const rows = data.assets.map((a) => ({ ...a, __in_use: a.in_use ? "Yes" : "No" }));
    const filename = `asset-tracking-${new Date().toISOString().slice(0, 10)}`;
    if (format === "pdf") exportPDF({ title: "Asset Tracking & Utilization Report", filename: `${filename}.pdf`, headers, rows });
    else exportCSV({ filename: `${filename}.csv`, headers, rows });
  }

  if (error) return <div className="alert alert-error">{error}</div>;
  if (!data) return <div className="empty">Loading asset tracking...</div>;

  const u = data.utilization;

  const visible = query.trim()
    ? data.assets.filter((a) =>
        [a.name, a.barcode, a.serial_number, a.department, a.category_name, a.assigned_to]
          .filter(Boolean)
          .some((v) => String(v).toLowerCase().includes(query.toLowerCase()))
      )
    : data.assets;

  return (
    <div>
      <div className="card">
        <div className="card-header">
          <h5>Asset Tracking &amp; Utilization</h5>
          <div className="flex">
            <label style={{ fontSize: "0.85rem" }}>Period:</label>
            <select className="form-select" style={{ maxWidth: 120 }} value={days} onChange={(e) => setDays(Number(e.target.value))}>
              <option value={30}>30 days</option>
              <option value={90}>90 days</option>
              <option value={180}>180 days</option>
              <option value={365}>365 days</option>
            </select>
            <input className="form-control" style={{ maxWidth: 240 }} placeholder="Search asset / serial / dept..." value={query} onChange={(e) => setQuery(e.target.value)} />
            <button className="btn btn-sm" title="Export to Excel (CSV)" onClick={() => handleExport("excel")}>⤓ Excel</button>
            <button className="btn btn-sm" title="Export to PDF (print)" onClick={() => handleExport("pdf")}>⤓ PDF</button>
            <Link to="/assets" className="btn btn-light btn-sm">Assets</Link>
          </div>
        </div>
        <div className="card-body">
          {error && <div className="alert alert-error">{error}</div>}

          <div className="row mb-3">
            <div className="col">
              <div className="card-box purple">
                <h3>{u.overall.percent}%</h3>
                <p>Overall Utilization ({u.overall.in_use}/{u.overall.total} in use)</p>
              </div>
            </div>
            <div className="col">
              <div className="card-box blue">
                <h3>{u.time_based.percent}%</h3>
                <p style={{ textAlign: 'left' }}>Time-Based Utilization ({u.time_based.in_use_days}/{u.time_based.total_asset_days} asset-days)</p>
              </div>
            </div>
            <div className="col">
              <div className="card-box green">
                <h3>{data.status_counts.Available}</h3>
                <p>Available</p>
              </div>
            </div>
            <div className="col">
              <div className="card-box orange">
                <h3>{data.status_counts.Assigned}</h3>
                <p>Assigned</p>
              </div>
            </div>
            <div className="col">
              <div className="card-box red">
                <h3>{data.status_counts.Borrowed + data.status_counts.Overdue}</h3>
                <p>Borrowed / Overdue</p>
              </div>
            </div>
          </div>

          <div className="row mb-3">
            <div className="col">
              <div className="chart-box">
                <h5>Status Distribution</h5>
                <div className="table-wrap">
                  <table>
                    <tbody>
                      {Object.entries(data.status_counts).map(([k, v]) => (
                        <tr key={k}>
                          <td><strong>{k}</strong></td>
                          <td>{v}</td>
                          <td><UtilizationBar percent={data.total_assets ? Math.round((v / data.total_assets) * 10000) / 100 : 0} /></td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <div className="row">
            <MiniTable
              title="Utilization by Department"
              headers={["Department", "In Use", "Total", "%"]}
              rows={u.by_department.map((d) => [d.department, `${d.in_use}/${d.total}`, d.total, d.percent])}
              empty="No departments yet."
            />
            <MiniTable
              title="Utilization by Category"
              headers={["Category", "In Use", "Total", "%"]}
              rows={u.by_category.map((c) => [c.category, `${c.in_use}/${c.total}`, c.total, c.percent])}
              empty="No categories yet."
            />
            <MiniTable
              title="Utilization by Office"
              headers={["Office", "In Use", "Total", "%"]}
              rows={u.by_office.map((o) => [o.office, `${o.in_use}/${o.total}`, o.total, o.percent])}
              empty="No offices yet."
            />
          </div>

          <h6 style={{ margin: "1.5rem 0 0.5rem" }}>Asset Status ({visible.length} of {data.total_assets})</h6>
          <div className="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Tag</th>
                  <th>Asset</th>
                  <th>Serial No.</th>
                  <th>Dept</th>
                  <th>Category</th>
                  <th>Status</th>
                  <th>In Use</th>
                  <th>Util. Days ({data.period_days})</th>
                  <th>History</th>
                </tr>
              </thead>
              <tbody>
                {visible.map((a) => (
                  <tr key={a.pid}>
                    <td>{a.barcode || "—"}</td>
                    <td><Link to={`/assets/${a.pid}`}><strong>{a.name}</strong></Link></td>
                    <td>{a.serial_number || "—"}</td>
                    <td>{a.department || "—"}</td>
                    <td>{a.category_name}</td>
                    <td>
                      <span className={`badge ${STATUS_BADGE[a.status] || "badge-light"}`}>{a.status}</span>
                      {a.status_detail && <div className="muted" style={{ fontSize: "0.75rem" }}>{a.status_detail}</div>}
                    </td>
                    <td>{a.in_use ? "Yes" : "No"}</td>
                    <td>{a.utilization_days}</td>
                    <td>
                      <button
                        className="btn btn-sm"
                        onClick={() => setShowHistory((s) => ({ ...s, [a.pid]: !s[a.pid] }))}
                      >
                        {showHistory[a.pid] ? "Hide" : "Show"}
                      </button>
                      {showHistory[a.pid] && (
                        <ul style={{ marginTop: 6, paddingLeft: 18, fontSize: "0.8rem" }}>
                          {a.history.map((h, i) => (
                            <li key={i}><strong>{h.type}</strong> ({h.date}) — {h.detail}</li>
                          ))}
                          {a.history.length === 0 && <li className="empty">No recorded movements.</li>}
                        </ul>
                      )}
                    </td>
                  </tr>
                ))}
                {visible.length === 0 && <tr><td colSpan={9} className="empty">No assets match your search.</td></tr>}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}