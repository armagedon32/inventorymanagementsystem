import { useEffect, useState } from "react";
import { Link, useParams, useNavigate } from "react-router-dom";
import { api } from "../api/client";

export default function ReservationView() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [res, setRes] = useState(null);
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    api.get(`/reservations/${id}`).then(setRes).catch((e) => setError(e.message));
  }, [id]);

  async function handleCancel() {
    if (!window.confirm(`Cancel reservation for "${res.event_name}"?`)) return;
    setMsg("");
    setError("");
    setLoading(true);
    try {
      await api.post(`/reservations/${res.id}/cancel`);
      setMsg("Reservation cancelled.");
      api.get(`/reservations/${id}`).then(setRes).catch(() => {});
    } catch (e) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  }

  if (error && !res) return <div className="alert alert-error">{error}</div>;
  if (!res) return <div className="empty">Loading...</div>;

  const rows = [
    ["Room", res.room_name],
    ["Room Location", res.location || "—"],
    ["Capacity", res.capacity ? `${res.capacity} seats` : "—"],
    ["Event Name", res.event_name],
    ["Purpose", res.purpose || "—"],
    ["Start Time", String(res.start_time).replace("T", " ")],
    ["End Time", String(res.end_time).replace("T", " ")],
    ["Reserved By", res.reserved_name || "—"],
    ["Status", res.status],
    ["Date Created", res.date_created],
  ];

  return (
    <div className="card">
      <div className="card-header">
        <h5>Reservation Details - {res.event_name}</h5>
        <div className="flex">
          {res.status !== "Cancelled" && (
            <button className="btn btn-danger btn-sm" onClick={handleCancel} disabled={loading}>✗ Cancel Reservation</button>
          )}
          <Link to="/reservations" className="btn btn-light btn-sm">Back</Link>
        </div>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}
        <div className="form-grid">
          {rows.map(([label, value]) => (
            <div className="form-group" key={label}>
              <label>{label}</label>
              <input className="form-control" value={value ?? ""} readOnly />
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}