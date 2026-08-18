import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { api } from "../api/client";
import { useAuth } from "../context/AuthContext";

const statusBadge = (s) => {
  if (s === "Approved") return <span className="badge badge-ok">{s}</span>;
  if (s === "Rejected") return <span className="badge badge-danger">{s}</span>;
  return <span className="badge badge-warn">{s}</span>;
};

export default function Requisitions() {
  const { user } = useAuth();
  const isAdmin = user?.role === "Admin";
  const [reqs, setReqs] = useState([]);
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");

  useEffect(() => {
    load();
  }, []);

  function load() {
    api.get("/requisitions").then(setReqs).catch((e) => setError(e.message));
  }

  async function handleApprove(r) {
    if (!window.confirm(`Approve requisition ${r.req_no} for "${r.purpose}"? Stock will be auto-deducted.`)) return;
    setMsg("");
    setError("");
    try {
      await api.post(`/requisitions/${r.id}/approve`);
      setMsg(`${r.req_no} approved.`);
      load();
    } catch (e) {
      setError(e.message);
    }
  }

  async function handleReject(r) {
    const reason = window.prompt(`Reject requisition ${r.req_no}? Enter a reason:`, "");
    if (reason === null || !reason.trim()) return;
    setMsg("");
    setError("");
    try {
      await api.post(`/requisitions/${r.id}/reject`, { reason: reason.trim() });
      setMsg(`${r.req_no} rejected.`);
      load();
    } catch (e) {
      setError(e.message);
    }
  }

  async function handleDelete(r) {
    if (!window.confirm(`Delete requisition ${r.req_no}? This will hide it permanently.`)) return;
    setMsg("");
    setError("");
    try {
      await api.del(`/requisitions/${r.id}`);
      setMsg(`${r.req_no} deleted.`);
      load();
    } catch (e) {
      setError(e.message);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>Requisitions</h5>
        <div className="flex">
          <span className="text-muted" style={{ fontSize: "0.85rem" }}>
            {reqs.length} record(s)
          </span>
          <Link to="/requisitions/new" className="btn btn-primary btn-sm">✚ New Requisition</Link>
        </div>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Requisition No.</th>
                <th>Purpose</th>
                <th>Requested By</th>
                <th>Date</th>
                <th>Items</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {reqs.map((r) => (
                <tr key={r.id}>
                  <td><strong>{r.req_no}</strong></td>
                  <td>{r.purpose}</td>
                  <td>{r.requested_name || "—"}</td>
                  <td>{r.date_created}</td>
                  <td>
                    <span className="badge badge-ok">{r.item_count}</span>
                  </td>
                  <td>{statusBadge(r.status)}</td>
                  <td>
                    <div className="btn-group">
                      <Link to={`/requisitions/${r.id}`} className="btn btn-warning btn-sm" title="View">👁</Link>
                      {isAdmin && r.status === "Pending" && (
                        <>
                          <button className="btn btn-success btn-sm" title="Approve" onClick={() => handleApprove(r)}>✓</button>
                          <button className="btn btn-danger btn-sm" title="Reject" onClick={() => handleReject(r)}>✗</button>
                        </>
                      )}
                      {isAdmin && (
                        <button className="btn btn-dark btn-sm" title="Delete" onClick={() => handleDelete(r)}>🗑</button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
              {reqs.length === 0 && (
                <tr>
                  <td colSpan={7} className="empty">No requisitions yet. Click "New Requisition".</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}