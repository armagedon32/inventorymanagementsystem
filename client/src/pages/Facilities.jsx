import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { api } from "../api/client";
import { useAuth } from "../context/AuthContext";

const statusBadge = (s) => {
  if (s === "Returned") return <span className="badge badge-ok">{s}</span>;
  if (s === "Cancelled") return <span className="badge badge-danger">{s}</span>;
  if (s === "Issued") return <span className="badge badge-info">{s}</span>;
  return <span className="badge badge-warn">{s}</span>;
};

export default function Facilities() {
  const { user } = useAuth();
  const isAdmin = user?.role === "Admin";
  const [rows, setRows] = useState([]);
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");

  useEffect(() => { load(); }, []);

  function load() {
    api.get("/facilities").then(setRows).catch((e) => setError(e.message));
  }

  async function handleDelete(f) {
    if (!window.confirm(`Delete facility request ${f.request_no}? Outstanding equipment will be restored.`)) return;
    setMsg("");
    setError("");
    try {
      await api.del(`/facilities/${f.id}`);
      setMsg(`${f.request_no} deleted.`);
      load();
    } catch (e) {
      setError(e.message);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>Facility &amp; Equipment Requests</h5>
        <div className="flex">
          <span className="text-muted" style={{ fontSize: "0.85rem" }}>{rows.length} record(s)</span>
          <Link to="/facilities/new" className="btn btn-primary btn-sm">✚ New Request</Link>
        </div>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Request No.</th>
                <th>Office / Org</th>
                <th>Requesting Name</th>
                <th>Event</th>
                <th>Facility</th>
                <th>Participants</th>
                <th>Equipment</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((f) => (
                <tr key={f.id}>
                  <td><strong>{f.request_no}</strong></td>
                  <td>{f.office_or_org || "—"}</td>
                  <td>{f.requesting_name || "—"}</td>
                  <td>{f.event_name}</td>
                  <td>{f.room_name || "—"}</td>
                  <td>{f.num_participants}</td>
                  <td><span className="badge badge-ok">{f.equip_count}</span></td>
                  <td>{statusBadge(f.status)}</td>
                  <td>
                    <div className="btn-group">
                      <Link to={`/facilities/${f.id}`} className="btn btn-warning btn-sm" title="View">👁</Link>
                      {isAdmin && (
                        <button className="btn btn-dark btn-sm" title="Delete" onClick={() => handleDelete(f)}>🗑</button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
              {rows.length === 0 && <tr><td colSpan={9} className="empty">No facility requests yet. Click "New Request".</td></tr>}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}