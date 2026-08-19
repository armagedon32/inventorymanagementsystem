import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { api } from "../api/client";

export default function NewReservation() {
  const navigate = useNavigate();
  const [rooms, setRooms] = useState([]);
  const [bookedRooms, setBookedRooms] = useState([]);
  const [form, setForm] = useState({
    room_id: "",
    event_name: "",
    purpose: "",
    start_time: "",
    end_time: "",
  });
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    api.get("/rooms").then(setRooms).catch((e) => setError(e.message));
    api
      .get("/reservations")
      .then((rows) => {
        const d = new Date();
        const p = (n) => String(n).padStart(2, "0");
        const now = `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`;
        const active = rows.filter((r) => r.status !== "Cancelled" && r.start_time <= now && now < r.end_time);
        setBookedRooms(active.map((r) => r.room_id));
      })
      .catch((e) => setError(e.message));
  }, []);

  function set(field, value) {
    setForm((f) => ({ ...f, [field]: value }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      const r = await api.post("/reservations", {
        room_id: Number(form.room_id),
        event_name: form.event_name,
        purpose: form.purpose,
        start_time: form.start_time,
        end_time: form.end_time,
      });
      navigate(`/reservations/${r.id}`, { replace: true });
    } catch (e) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>New Room Reservation</h5>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        <form onSubmit={handleSubmit}>
          <div className="form-grid">
            <div className="form-group">
              <label>Room *</label>
              <select className="form-select" value={form.room_id} onChange={(e) => set("room_id", e.target.value)} required>
                <option value="">-- Select Room --</option>
                {rooms.map((r) => (
                  <option key={r.id} value={r.id} disabled={bookedRooms.includes(r.id)}>
                    {r.room_name}{r.location ? ` (${r.location})` : ""}{r.capacity ? ` - ${r.capacity} seats` : ""}
                    {bookedRooms.includes(r.id) ? " — Booked" : ""}
                  </option>
                ))}
              </select>
            </div>
            <div className="form-group">
              <label>Event Name *</label>
              <input className="form-control" value={form.event_name} onChange={(e) => set("event_name", e.target.value)} placeholder="e.g. Faculty Meeting" required />
            </div>
            <div className="form-group">
              <label>Purpose</label>
              <input className="form-control" value={form.purpose} onChange={(e) => set("purpose", e.target.value)} placeholder="e.g. Monthly staff meeting" />
            </div>
            <div className="form-group">
              <label>Start Date &amp; Time *</label>
              <input type="datetime-local" className="form-control" value={form.start_time} onChange={(e) => set("start_time", e.target.value)} required />
            </div>
            <div className="form-group">
              <label>End Date &amp; Time *</label>
              <input type="datetime-local" className="form-control" value={form.end_time} onChange={(e) => set("end_time", e.target.value)} required />
            </div>
          </div>
          <div className="flex between mt-4">
            <button type="button" className="btn btn-light" onClick={() => navigate(-1)}>Cancel</button>
            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? "Submitting..." : "Submit Reservation"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}