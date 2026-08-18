import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { api } from "../api/client";

const statusBadge = (s) => {
  if (s === "Cancelled") return <span className="badge badge-danger">{s}</span>;
  return <span className="badge badge-ok">{s}</span>;
};

export default function Reservations() {
  const [reservations, setReservations] = useState([]);
  const [statusFilter, setStatusFilter] = useState("");
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");

  useEffect(() => {
    load();
  }, [statusFilter]);

  function load() {
    api
      .get(`/reservations${statusFilter ? `?status=${statusFilter}` : ""}`)
      .then(setReservations)
      .catch((e) => setError(e.message));
  }

  async function handleCancel(r) {
    if (!window.confirm(`Cancel reservation for "${r.event_name}" in ${r.room_name}?`)) return;
    setMsg("");
    setError("");
    try {
      await api.post(`/reservations/${r.id}/cancel`);
      setMsg(`Reservation for "${r.event_name}" cancelled.`);
      load();
    } catch (e) {
      setError(e.message);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>Conference Room Reservations</h5>
        <div className="flex">
          <select
            className="form-select"
            style={{ width: "auto" }}
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
          >
            <option value="">All Status</option>
            <option value="Confirmed">Confirmed</option>
            <option value="Cancelled">Cancelled</option>
          </select>
          <span className="text-muted" style={{ fontSize: "0.85rem" }}>
            {reservations.length} record(s)
          </span>
          <Link to="/reservations/new" className="btn btn-primary btn-sm">✚ New Reservation</Link>
        </div>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Room</th>
                <th>Event Name</th>
                <th>Purpose</th>
                <th>Start</th>
                <th>End</th>
                <th>Reserved By</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {reservations.map((r) => (
                <tr key={r.id}>
                  <td><strong>{r.room_name}</strong></td>
                  <td>{r.event_name}</td>
                  <td>{r.purpose || "—"}</td>
                  <td>{String(r.start_time).replace("T", " ")}</td>
                  <td>{String(r.end_time).replace("T", " ")}</td>
                  <td>{r.reserved_name || "—"}</td>
                  <td>{statusBadge(r.status)}</td>
                  <td>
                    <div className="btn-group">
                      <Link to={`/reservations/${r.id}`} className="btn btn-warning btn-sm" title="View">👁</Link>
                      {r.status !== "Cancelled" && (
                        <button className="btn btn-danger btn-sm" title="Cancel" onClick={() => handleCancel(r)}>✗</button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
              {reservations.length === 0 && (
                <tr>
                  <td colSpan={8} className="empty">No reservations found.</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}