import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { api } from "../api/client";
import { useAuth } from "../context/AuthContext";

const statusBadge = (s) => {
  if (s === "Returned") return <span className="badge badge-ok">{s}</span>;
  if (s === "Overdue") return <span className="badge badge-danger">{s}</span>;
  return <span className="badge badge-warn">{s}</span>;
};

export default function Ris() {
  const { user } = useAuth();
  const isAdmin = user?.role === "Admin";
  const [rows, setRows] = useState([]);
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");
  const [seeding, setSeeding] = useState(false);

  useEffect(() => { load(); }, []);

  function load() {
    api.get("/ris").then(setRows).catch((e) => setError(e.message));
  }

  async function seedRis() {
    if (!confirm("Generate 50 sample RIS transactions using users from User Management?")) return;
    setSeeding(true);
    setError("");
    setMsg("");
    try {
      const r = await api.get("/ris/seed");
      setMsg(`Created ${r.created} RIS transactions from ${r.users} users and ${r.assets} assets.`);
      load();
    } catch (e) {
      setError(e.message);
    } finally {
      setSeeding(false);
    }
  }

  async function handleReturn(r) {
    if (!window.confirm(`Mark RIS ${r.ris_no} as returned? Asset units will be restored.`)) return;
    setMsg("");
    setError("");
    try {
      await api.post(`/ris/${r.id}/return`);
      setMsg(`${r.ris_no} returned.`);
      load();
    } catch (e) {
      setError(e.message);
    }
  }

  async function handleDelete(r) {
    if (!window.confirm(`Delete RIS ${r.ris_no}? Outstanding units will be restored to stock.`)) return;
    setMsg("");
    setError("");
    try {
      await api.del(`/ris/${r.id}`);
      setMsg(`${r.ris_no} deleted.`);
      load();
    } catch (e) {
      setError(e.message);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>RIS / Borrowed Property</h5>
        <div className="flex">
          <span className="text-muted" style={{ fontSize: "0.85rem" }}>{rows.length} record(s)</span>
          {isAdmin && (
            <button className="btn btn-warning btn-sm" onClick={seedRis} disabled={seeding}>
              {seeding ? "Seeding..." : "📊 Seed Data"}
            </button>
          )}
          <Link to="/ris/new" className="btn btn-primary btn-sm">✚ New RIS</Link>
        </div>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>RIS No.</th>
                <th>Borrower</th>
                <th>Event</th>
                <th>Date</th>
                <th>Return Due</th>
                <th>Items</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((r) => (
                <tr key={r.id}>
                  <td><strong>{r.ris_no}</strong></td>
                  <td>{r.last_name}, {r.first_name}</td>
                  <td>{r.event_name}</td>
                  <td>{r.event_date || "—"}</td>
                  <td>{r.end_datetime || "—"}</td>
                  <td><span className="badge badge-ok">{r.item_count}</span></td>
                  <td>{statusBadge(r.status)}</td>
                  <td>
                    <div className="btn-group">
                      <Link to={`/ris/${r.id}`} className="btn btn-warning btn-sm" title="View">👁</Link>
                      {r.status === "Borrowed" && (
                        <button className="btn btn-success btn-sm" title="Mark Returned" onClick={() => handleReturn(r)}>↩</button>
                      )}
                      {isAdmin && (
                        <button className="btn btn-dark btn-sm" title="Delete" onClick={() => handleDelete(r)}>🗑</button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
              {rows.length === 0 && <tr><td colSpan={8} className="empty">No RIS records yet. Click "New RIS".</td></tr>}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}