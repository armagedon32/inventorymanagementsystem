import { useEffect, useState, useMemo } from "react";
import { Link } from "react-router-dom";
import { api } from "../api/client";

const ROOM_COLORS = ["#2563eb", "#dc2626", "#16a34a", "#ea580c", "#7c3aed", "#0891b2", "#be185d", "#65a30d"];

const statusBadge = (s) => {
  if (s === "Cancelled") return <span className="badge badge-danger">{s}</span>;
  return <span className="badge badge-ok">{s}</span>;
};

function getDaysInMonth(year, month) {
  return new Date(year, month + 1, 0).getDate();
}

function getFirstDayOfWeek(year, month) {
  return new Date(year, month, 1).getDay();
}

function isSameDay(d1, d2) {
  return d1.getFullYear() === d2.getFullYear() && d1.getMonth() === d2.getMonth() && d1.getDate() === d2.getDate();
}

function parseDT(s) {
  if (!s) return null;
  const str = String(s).replace(" ", "T");
  const d = new Date(str);
  return isNaN(d) ? null : d;
}

export default function Reservations() {
  const [reservations, setReservations] = useState([]);
  const [statusFilter, setStatusFilter] = useState("");
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");

  const today = new Date();
  const [calYear, setCalYear] = useState(today.getFullYear());
  const [calMonth, setCalMonth] = useState(today.getMonth());
  const [selectedDate, setSelectedDate] = useState(null);

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

  const confirmed = useMemo(
    () => reservations.filter((r) => r.status !== "Cancelled"),
    [reservations]
  );

  const roomColorMap = useMemo(() => {
    const map = {};
    let idx = 0;
    for (const r of confirmed) {
      if (!map[r.room_name]) {
        map[r.room_name] = ROOM_COLORS[idx % ROOM_COLORS.length];
        idx++;
      }
    }
    return map;
  }, [confirmed]);

  function reservationsForDay(year, month, day) {
    const dayStart = new Date(year, month, day, 0, 0, 0);
    const dayEnd = new Date(year, month, day, 23, 59, 59);
    return confirmed.filter((r) => {
      const s = parseDT(r.start_time);
      const e = parseDT(r.end_time);
      if (!s || !e) return false;
      return s <= dayEnd && e >= dayStart;
    });
  }

  const daysInMonth = getDaysInMonth(calYear, calMonth);
  const firstDay = getFirstDayOfWeek(calYear, calMonth);
  const calendarDays = [];
  for (let i = 0; i < firstDay; i++) calendarDays.push(null);
  for (let d = 1; d <= daysInMonth; d++) calendarDays.push(d);

  const selectedReservations = selectedDate
    ? reservationsForDay(calYear, calMonth, selectedDate)
    : [];

  const MONTH_NAMES = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

  function prevMonth() {
    setSelectedDate(null);
    if (calMonth === 0) { setCalMonth(11); setCalYear((y) => y - 1); }
    else setCalMonth((m) => m - 1);
  }

  function nextMonth() {
    setSelectedDate(null);
    if (calMonth === 11) { setCalMonth(0); setCalYear((y) => y + 1); }
    else setCalMonth((m) => m + 1);
  }

  return (
    <div>
      {/* Calendar Card */}
      <div className="card" style={{ marginBottom: "1rem" }}>
        <div className="card-header">
          <h5>Reservation Calendar</h5>
          <div className="flex" style={{ gap: 8 }}>
            <button className="btn btn-sm" onClick={prevMonth}>← Prev</button>
            <span style={{ fontWeight: 600, minWidth: 160, textAlign: "center", lineHeight: "28px" }}>
              {MONTH_NAMES[calMonth]} {calYear}
            </span>
            <button className="btn btn-sm" onClick={nextMonth}>Next →</button>
            <button className="btn btn-sm" onClick={() => { setCalYear(today.getFullYear()); setCalMonth(today.getMonth()); setSelectedDate(today.getDate()); }}>Today</button>
          </div>
        </div>
        <div className="card-body" style={{ padding: "0.5rem" }}>
          {/* Room Legend */}
          <div style={{ display: "flex", flexWrap: "wrap", gap: 12, marginBottom: 10, fontSize: "0.78rem" }}>
            {Object.entries(roomColorMap).map(([name, color]) => (
              <span key={name} style={{ display: "flex", alignItems: "center", gap: 4 }}>
                <span style={{ width: 12, height: 12, borderRadius: 3, background: color, display: "inline-block" }} />
                {name}
              </span>
            ))}
          </div>

          {/* Day Headers */}
          <div style={{ display: "grid", gridTemplateColumns: "repeat(7, 1fr)", gap: 2, marginBottom: 4 }}>
            {["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].map((d) => (
              <div key={d} style={{ textAlign: "center", fontWeight: 600, fontSize: "0.75rem", color: "#666", padding: "4px 0" }}>{d}</div>
            ))}
          </div>

          {/* Calendar Grid */}
          <div style={{ display: "grid", gridTemplateColumns: "repeat(7, 1fr)", gap: 2 }}>
            {calendarDays.map((day, i) => {
              if (day === null) return <div key={`empty-${i}`} />;
              const dayRes = reservationsForDay(calYear, calMonth, day);
              const isToday = isSameDay(new Date(calYear, calMonth, day), today);
              const isSelected = selectedDate === day;
              return (
                <div
                  key={day}
                  onClick={() => setSelectedDate(isSelected ? null : day)}
                  style={{
                    border: isSelected ? "2px solid #2563eb" : isToday ? "2px solid #f59e0b" : "1px solid #e5e7eb",
                    borderRadius: 6,
                    padding: "4px 5px",
                    minHeight: 60,
                    cursor: "pointer",
                    background: isSelected ? "#eff6ff" : isToday ? "#fffbeb" : "#fff",
                    transition: "all 0.15s",
                  }}
                >
                  <div style={{ fontSize: "0.8rem", fontWeight: isToday || isSelected ? 700 : 500, marginBottom: 2 }}>
                    {isToday && <span style={{ color: "#f59e0b", marginRight: 2 }}>●</span>}
                    {day}
                  </div>
                  {dayRes.slice(0, 2).map((r) => (
                    <div
                      key={r.id}
                      title={`${r.room_name}: ${r.event_name} (${String(r.start_time).slice(11, 16)} - ${String(r.end_time).slice(11, 16)})`}
                      style={{
                        background: roomColorMap[r.room_name] || "#666",
                        color: "#fff",
                        fontSize: "0.62rem",
                        padding: "1px 4px",
                        borderRadius: 3,
                        marginBottom: 1,
                        overflow: "hidden",
                        textOverflow: "ellipsis",
                        whiteSpace: "nowrap",
                        lineHeight: "14px",
                      }}
                    >
                      {r.event_name}
                    </div>
                  ))}
                  {dayRes.length > 2 && (
                    <div style={{ fontSize: "0.6rem", color: "#888", textAlign: "center" }}>+{dayRes.length - 2} more</div>
                  )}
                </div>
              );
            })}
          </div>

          {/* Selected Day Detail */}
          {selectedDate && (
            <div style={{ marginTop: 12, padding: 10, background: "#f8fafc", borderRadius: 6, border: "1px solid #e2e8f0" }}>
              <div style={{ fontWeight: 600, marginBottom: 6 }}>
                {MONTH_NAMES[calMonth]} {selectedDate}, {calYear}
                <button className="btn btn-sm" style={{ marginLeft: 8, padding: "0 6px" }} onClick={() => setSelectedDate(null)}>✕</button>
              </div>
              {selectedReservations.length === 0 ? (
                <div style={{ color: "#888", fontSize: "0.85rem" }}>No reservations on this day.</div>
              ) : (
                <table style={{ width: "100%", fontSize: "0.82rem" }}>
                  <thead>
                    <tr>
                      <th style={{ textAlign: "left", padding: "4px 6px" }}>Room</th>
                      <th style={{ textAlign: "left", padding: "4px 6px" }}>Event</th>
                      <th style={{ textAlign: "left", padding: "4px 6px" }}>Time</th>
                      <th style={{ textAlign: "left", padding: "4px 6px" }}>By</th>
                    </tr>
                  </thead>
                  <tbody>
                    {selectedReservations.map((r) => (
                      <tr key={r.id}>
                        <td style={{ padding: "4px 6px" }}>
                          <span style={{ display: "inline-flex", alignItems: "center", gap: 4 }}>
                            <span style={{ width: 8, height: 8, borderRadius: 2, background: roomColorMap[r.room_name] || "#666", display: "inline-block" }} />
                            <strong>{r.room_name}</strong>
                          </span>
                        </td>
                        <td style={{ padding: "4px 6px" }}>{r.event_name}</td>
                        <td style={{ padding: "4px 6px" }}>
                          {String(r.start_time).replace("T", " ").slice(0, 16)} — {String(r.end_time).replace("T", " ").slice(0, 16)}
                        </td>
                        <td style={{ padding: "4px 6px" }}>{r.reserved_name || "—"}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>
          )}
        </div>
      </div>

      {/* Reservations Table */}
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
    </div>
  );
}
