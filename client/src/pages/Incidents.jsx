import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { api } from "../api/client";
import { useAuth } from "../context/AuthContext";

const statusBadge = (s) =>
  s === "Resolved" ? <span className="badge badge-ok">{s}</span> : <span className="badge badge-warn">{s}</span>;

export default function Incidents() {
  const { user } = useAuth();
  const isAdmin = user?.role === "Admin";
  const [rows, setRows] = useState([]);
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");

  useEffect(() => { load(); }, []);

  function load() {
    api.get("/incidents").then(setRows).catch((e) => setError(e.message));
  }

  async function handleDelete(r) {
    if (!window.confirm(`Delete incident report ${r.report_number}?`)) return;
    setMsg("");
    setError("");
    try {
      await api.del(`/incidents/${r.id}`);
      setMsg(`${r.report_number} deleted.`);
      load();
    } catch (e) {
      setError(e.message);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>Incident Reports</h5>
        <div className="flex">
          <span className="text-muted" style={{ fontSize: "0.85rem" }}>{rows.length} record(s)</span>
          <Link to="/incidents/new" className="btn btn-primary btn-sm">✚ New Incident Report</Link>
        </div>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Report No.</th>
                <th>Reported By</th>
                <th>Office</th>
                <th>Incident Date</th>
                <th>Description</th>
                <th>Items</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((r) => (
                <tr key={r.id}>
                  <td><strong>{r.report_number}</strong></td>
                  <td>{r.reported_by}</td>
                  <td>{r.office_name || "—"}</td>
                  <td>{r.incident_date || "—"} {r.incident_time ? `(${r.incident_time})` : ""}</td>
                  <td>{r.description}</td>
                  <td><span className="badge badge-ok">{r.item_count}</span></td>
                  <td>{statusBadge(r.status)}</td>
                  <td>
                    <div className="btn-group">
                      <Link to={`/incidents/${r.id}`} className="btn btn-warning btn-sm" title="View">👁</Link>
                      {isAdmin && (
                        <button className="btn btn-dark btn-sm" title="Delete" onClick={() => handleDelete(r)}>🗑</button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
              {rows.length === 0 && <tr><td colSpan={8} className="empty">No incident reports yet. Click "New Incident Report".</td></tr>}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}