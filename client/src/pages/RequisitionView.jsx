import { useEffect, useState } from "react";
import { Link, useParams, useNavigate } from "react-router-dom";
import { api } from "../api/client";
import { useAuth } from "../context/AuthContext";

const statusBadge = (s) => {
  if (s === "Approved") return <span className="badge badge-ok">{s}</span>;
  if (s === "Rejected") return <span className="badge badge-danger">{s}</span>;
  return <span className="badge badge-warn">{s}</span>;
};

export default function RequisitionView() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const isAdmin = user?.role === "Admin";
  const [req, setReq] = useState(null);
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    load();
  }, [id]);

  function load() {
    api.get(`/requisitions/${id}`).then(setReq).catch((e) => setError(e.message));
  }

  async function handleApprove() {
    if (!window.confirm(`Approve ${req.req_no}? Stock will be auto-deducted.`)) return;
    setMsg("");
    setError("");
    setLoading(true);
    try {
      await api.post(`/requisitions/${req.id}/approve`);
      setMsg(`${req.req_no} approved.`);
      load();
    } catch (e) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  }

  async function handleReject() {
    const reason = window.prompt(`Reject ${req.req_no}? Enter a reason:`, "");
    if (reason === null || !reason.trim()) return;
    setMsg("");
    setError("");
    setLoading(true);
    try {
      await api.post(`/requisitions/${req.id}/reject`, { reason: reason.trim() });
      setMsg(`${req.req_no} rejected.`);
      load();
    } catch (e) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  }

  async function handleDelete() {
    if (!window.confirm(`Delete ${req.req_no}? This will hide it permanently.`)) return;
    setMsg("");
    setError("");
    try {
      await api.del(`/requisitions/${req.id}`);
      navigate("/requisitions", { replace: true });
    } catch (e) {
      setError(e.message);
    }
  }

  if (error && !req) return <div className="alert alert-error">{error}</div>;
  if (!req) return <div className="empty">Loading...</div>;

  const rows = [
    ["Requisition No.", req.req_no],
    ["Status", req.status],
    ["Requested By", req.requested_name || "—"],
    ["Date Created", req.date_created],
    ["Purpose", req.purpose],
    ["Date Processed", req.date_processed || "—"],
    ["Processed By", req.processed_name || "—"],
    ...(req.status === "Rejected" && req.reject_reason ? [["Reject Reason", req.reject_reason]] : []),
  ];

  return (
    <div className="card">
      <div className="card-header">
        <h5>Requisition Details - {req.req_no}</h5>
        <div className="flex">
          {isAdmin && req.status === "Pending" && (
            <>
              <button className="btn btn-success btn-sm" onClick={handleApprove} disabled={loading}>✓ Approve</button>
              <button className="btn btn-danger btn-sm" onClick={handleReject} disabled={loading}>✗ Reject</button>
            </>
          )}
          {isAdmin && <button className="btn btn-dark btn-sm" onClick={handleDelete}>🗑 Delete</button>}
          <Link to="/requisitions" className="btn btn-light btn-sm">Back</Link>
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

        <h6 style={{ margin: "1rem 0 0.5rem" }}>Requested Items</h6>
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Item Name</th>
                <th>Brand</th>
                <th>Quantity</th>
                <th>Available</th>
                <th>Unit</th>
              </tr>
            </thead>
            <tbody>
              {req.items.map((it, i) => (
                <tr key={it.id}>
                  <td>{i + 1}</td>
                  <td><strong>{it.product_name}</strong></td>
                  <td>{it.brand}</td>
                  <td>{it.quantity}</td>
                  <td>{it.current_stock}</td>
                  <td>{it.unit || "pcs"}</td>
                </tr>
              ))}
              {req.items.length === 0 && (
                <tr>
                  <td colSpan={6} className="empty">No items.</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}