import { useEffect, useState } from "react";
import { Navigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import { api } from "../api/client";

export default function AuditLogs() {
  const { user } = useAuth();
  const isAdmin = user?.role === "Admin";

  const [rows, setRows] = useState([]);
  const [total, setTotal] = useState(0);
  const [users, setUsers] = useState([]);
  const [q, setQ] = useState("");
  const [userId, setUserId] = useState("");
  const [date, setDate] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (isAdmin) api.get("/users").then(setUsers).catch(() => {});
  }, [isAdmin]);

  useEffect(() => {
    if (!isAdmin) return;
    setLoading(true);
    setError("");
    const params = new URLSearchParams();
    if (q.trim()) params.set("q", q.trim());
    if (userId) params.set("user_id", userId);
    if (date) params.set("date", date);
    api
      .get(`/activity?${params.toString()}`)
      .then((d) => { setRows(d.rows); setTotal(d.total); })
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false));
  }, [isAdmin, q, userId, date]);

  if (!isAdmin) return <Navigate to="/" replace />;

  return (
    <div className="card">
      <div className="card-header">
        <h5>Audit Logs <span className="badge badge-ok">{total}</span></h5>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}

        <div className="form-grid" style={{ marginBottom: "16px" }}>
          <div className="form-group">
            <label>Search</label>
            <input
              className="form-control"
              value={q}
              onChange={(e) => setQ(e.target.value)}
              placeholder="Search action, description, target ID or user..."
            />
          </div>
          <div className="form-group">
            <label>User</label>
            <select className="form-select" value={userId} onChange={(e) => setUserId(e.target.value)}>
              <option value="">-- All Users --</option>
              {users.map((u) => (
                <option key={u.userid} value={u.userid}>{u.fullname}</option>
              ))}
            </select>
          </div>
          <div className="form-group">
            <label>Date</label>
            <input type="date" className="form-control" value={date} onChange={(e) => setDate(e.target.value)} />
          </div>
        </div>

        {loading ? (
          <div className="text-muted">Loading...</div>
        ) : (
          <div className="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Timestamp</th>
                  <th>User</th>
                  <th>Action</th>
                  <th>Target ID</th>
                  <th>Description</th>
                  <th>IP Address</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((r) => (
                  <tr key={r.id}>
                    <td style={{ whiteSpace: "nowrap" }}>{r.date_created}</td>
                    <td><strong>{r.user_name || "System"}</strong></td>
                    <td>{r.action}</td>
                    <td>{r.target_id ?? "—"}</td>
                    <td>{r.description || "—"}</td>
                    <td>{r.ip_address || "—"}</td>
                  </tr>
                ))}
                {rows.length === 0 && (
                  <tr><td colSpan={6} className="empty">No activity found.</td></tr>
                )}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}