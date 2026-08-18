import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { api } from "../api/client";
import { useAuth } from "../context/AuthContext";

export default function Ptrs() {
  const { user } = useAuth();
  const isAdmin = user?.role === "Admin";
  const [rows, setRows] = useState([]);
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");

  useEffect(() => { load(); }, []);

  function load() {
    api.get("/ptr").then(setRows).catch((e) => setError(e.message));
  }

  async function handleDelete(p) {
    if (!window.confirm(`Delete PTR ${p.ptr_no}? Asset ownership transfer will be removed from the record.`)) return;
    setMsg("");
    setError("");
    try {
      await api.del(`/ptr/${p.id}`);
      setMsg(`${p.ptr_no} deleted.`);
      load();
    } catch (e) {
      setError(e.message);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>Property Transfer Receipts (PTR)</h5>
        <div className="flex">
          <span className="text-muted" style={{ fontSize: "0.85rem" }}>{rows.length} record(s)</span>
          <Link to="/ptr/new" className="btn btn-primary btn-sm">✚ New PTR</Link>
        </div>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>PTR No.</th>
                <th>Transfer Date</th>
                <th>From</th>
                <th>To</th>
                <th>Remarks</th>
                <th>Items</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((p) => (
                <tr key={p.id}>
                  <td><strong>{p.ptr_no}</strong></td>
                  <td>{p.transfer_date || "—"}</td>
                  <td>{p.from_office_name || "—"}</td>
                  <td>{p.to_office_name || "—"}</td>
                  <td>{p.remarks || "—"}</td>
                  <td><span className="badge badge-ok">{p.item_count}</span></td>
                  <td>
                    <div className="btn-group">
                      <Link to={`/ptr/${p.id}`} className="btn btn-warning btn-sm" title="View">👁</Link>
                      {isAdmin && (
                        <button className="btn btn-dark btn-sm" title="Delete" onClick={() => handleDelete(p)}>🗑</button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
              {rows.length === 0 && <tr><td colSpan={7} className="empty">No PTR records yet. Click "New PTR".</td></tr>}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}