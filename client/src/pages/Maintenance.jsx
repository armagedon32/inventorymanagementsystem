import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { api } from "../api/client";
import { useAuth } from "../context/AuthContext";

export default function Maintenance() {
  const { user } = useAuth();
  const isAdmin = user?.role === "Admin";
  const [rows, setRows] = useState([]);
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");

  useEffect(() => { load(); }, []);

  function load() {
    api.get("/maintenance").then(setRows).catch((e) => setError(e.message));
  }

  const dueBadge = (d) => {
    if (d == null) return "—";
    if (d <= 0) return <span className="badge badge-danger">Due {Math.abs(d)}d ago</span>;
    if (d <= 7) return <span className="badge badge-warn">In {d}d</span>;
    return <span className="badge badge-ok">In {d}d</span>;
  };

  async function handleComplete(m) {
    if (!window.confirm(`Mark maintenance for "${m.item_name}" as completed? Next schedule will be computed.`)) return;
    setMsg("");
    setError("");
    try {
      await api.post(`/maintenance/${m.id}/complete`);
      setMsg(`${m.maintenance_code} completed.`);
      load();
    } catch (e) {
      setError(e.message);
    }
  }

  async function handleDelete(m) {
    if (!window.confirm(`Delete maintenance record ${m.maintenance_code}?`)) return;
    setMsg("");
    setError("");
    try {
      await api.del(`/maintenance/${m.id}`);
      setMsg(`${m.maintenance_code} deleted.`);
      load();
    } catch (e) {
      setError(e.message);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>Maintenance Records</h5>
        <div className="flex">
          <span className="text-muted" style={{ fontSize: "0.85rem" }}>{rows.length} record(s)</span>
          <Link to="/maintenance/new" className="btn btn-primary btn-sm">✚ New Maintenance</Link>
        </div>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Code</th>
                <th>Item</th>
                <th>Office</th>
                <th>Brand</th>
                <th>Serial No.</th>
                <th>Task</th>
                <th>Last Done</th>
                <th>Next Due</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((m) => (
                <tr key={m.id}>
                  <td><strong>{m.maintenance_code}</strong></td>
                  <td>{m.item_name}</td>
                  <td>{m.office || "—"}</td>
                  <td>{m.brand || "—"}</td>
                  <td>{m.serial_number || "—"}</td>
                  <td>{m.maintenance_task || "—"}</td>
                  <td>{m.previous_maintenance_date || "—"}</td>
                  <td>{m.next_maintenance_date ? `${m.next_maintenance_date} ${dueBadge(m.days_before_due)}` : "—"}</td>
                  <td>
                    <div className="btn-group">
                      <Link to={`/maintenance/${m.id}/edit`} className="btn btn-warning btn-sm" title="Edit">✎</Link>
                      {isAdmin && (
                        <>
                          <button className="btn btn-success btn-sm" title="Mark Done" onClick={() => handleComplete(m)}>✓</button>
                          <button className="btn btn-dark btn-sm" title="Delete" onClick={() => handleDelete(m)}>🗑</button>
                        </>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
              {rows.length === 0 && <tr><td colSpan={9} className="empty">No maintenance records yet. Click "New Maintenance".</td></tr>}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}