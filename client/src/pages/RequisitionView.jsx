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
  const [evals, setEvals] = useState([]);
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");
  const [loading, setLoading] = useState(false);
  const [evaluating, setEvaluating] = useState(false);

  useEffect(() => {
    load();
  }, [id]);

  function load() {
    api.get(`/requisitions/${id}`).then(setReq).catch((e) => setError(e.message));
    api.get(`/requisitions/${id}/evaluations`).then(setEvals).catch(() => setEvals([]));
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

  async function handleEvaluate() {
    setMsg("");
    setError("");
    setEvaluating(true);
    try {
      const res = await api.post(`/requisitions/${req.id}/evaluate`);
      setEvals(await api.get(`/requisitions/${req.id}/evaluations`));
      setMsg(`Evaluation complete — recommendation: ${res.recommendation}.`);
    } catch (e) {
      setError(e.message);
    } finally {
      setEvaluating(false);
    }
  }

  function RecBadge({ value }) {
    if (value === "APPROVE") return <span className="badge badge-ok">APPROVE</span>;
    if (value === "REJECT") return <span className="badge badge-danger">REJECT</span>;
    return <span className="badge badge-warn">REVISE</span>;
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

        <div className="chart-box" style={{ marginTop: "1.5rem" }}>
          <div className="flex between" style={{ marginBottom: "0.75rem" }}>
            <h5 style={{ margin: 0 }}>Rule Evaluation / Recommendation Details</h5>
            {isAdmin && (
              <button className="btn btn-primary btn-sm" onClick={handleEvaluate} disabled={evaluating}>
                {evaluating ? "Evaluating..." : "⚖ Run Rule Evaluation"}
              </button>
            )}
          </div>
          {evals.length === 0 && (
            <div className="empty">No rule evaluation has been run for this requisition yet.</div>
          )}
          {evals.map((ev) => (
            <div key={ev.id} className="card" style={{ marginTop: "0.75rem", background: "var(--card-bg)" }}>
              <div className="card-header" style={{ flexWrap: "wrap", gap: 8 }}>
                <h6 style={{ margin: 0 }}>
                  <RecBadge value={ev.recommendation} /> Recommended {ev.recommendation}
                </h6>
                <span className="text-muted" style={{ fontSize: "0.78rem" }}>
                  {ev.eval_date} · by {ev.evaluated_by_name || "—"} · Passed {ev.passed} / Failed {ev.failed}
                </span>
              </div>
              <div className="card-body">
                <p style={{ margin: "0 0 0.75rem" }}>{ev.reason}</p>
                <div className="table-wrap">
                  <table>
                    <thead>
                      <tr>
                        <th>Rule</th>
                        <th>Category</th>
                        <th>Result</th>
                        <th>Detail</th>
                        <th>Policy Basis</th>
                      </tr>
                    </thead>
                    <tbody>
                      {ev.rules.map((rl, i) => (
                        <tr key={i}>
                          <td><strong>{rl.rule_name}</strong><div className="text-muted" style={{ fontSize: "0.72rem" }}>{rl.rule_code}</div></td>
                          <td>{rl.category}</td>
                          <td>
                            <span className={`badge ${rl.result === "PASS" ? "badge-ok" : "badge-danger"}`}>{rl.result}</span>
                          </td>
                          <td>{rl.detail}</td>
                          <td><span style={{ fontSize: "0.78rem" }}>{rl.policy_basis || "—"}</span></td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}