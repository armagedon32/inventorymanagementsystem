import { useEffect, useState } from "react";
import { api } from "../api/client";
import { useAuth } from "../context/AuthContext";
import Modal from "../components/Modal";

export default function Disposal() {
  const { user } = useAuth();
  const isAdmin = user?.role === "Admin";
  const [rows, setRows] = useState([]);
  const [assets, setAssets] = useState([]);
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");
  const [show, setShow] = useState(false);
  const [form, setForm] = useState({ asset_id: "", quantity: 1, remarks: "" });
  const [loading, setLoading] = useState(false);

  useEffect(() => { load(); }, []);

  function load() {
    api.get("/disposal").then(setRows).catch((e) => setError(e.message));
  }

  function openAdd() {
    setError("");
    setForm({ asset_id: "", quantity: 1, remarks: "" });
    setShow(true);
    api.get("/products?type=Asset").then((rows) => setAssets(rows.filter((a) => a.stock > 0 && a.condition !== "Disposed"))).catch((e) => setError(e.message));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      await api.post("/disposal", { asset_id: Number(form.asset_id), quantity: Number(form.quantity), remarks: form.remarks });
      setMsg("Disposal recorded.");
      setShow(false);
      load();
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  async function handleReverse(d) {
    if (!window.confirm(`Reverse disposal ${d.dis_no}? ${d.quantity} unit(s) will be restored to stock.`)) return;
    setError("");
    setMsg("");
    try {
      await api.del(`/disposal/${d.id}`);
      setMsg("Disposal reversed.");
      load();
    } catch (err) {
      setError(err.message);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>Property Disposal (Tax Disposal)</h5>
        <div className="flex">
          <span className="text-muted" style={{ fontSize: "0.85rem" }}>{rows.length} record(s)</span>
          {isAdmin && <button className="btn btn-primary btn-sm" onClick={openAdd}>✚ New Disposal</button>}
        </div>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Disposal No.</th>
                <th>Item</th>
                <th>Inventory No.</th>
                <th>Office</th>
                <th>Qty</th>
                <th>Remarks</th>
                <th>Disposed At</th>
                {isAdmin && <th>Actions</th>}
              </tr>
            </thead>
            <tbody>
              {rows.map((d) => (
                <tr key={d.id}>
                  <td><strong>{d.dis_no}</strong></td>
                  <td>{d.item_name}</td>
                  <td>{d.inventory_no || "—"}</td>
                  <td>{d.office_name || "—"}</td>
                  <td>{d.quantity}</td>
                  <td>{d.remarks || "—"}</td>
                  <td>{d.disposed_at}</td>
                  {isAdmin && (
                    <td>
                      <div className="btn-group">
                        <button className="btn btn-warning btn-sm" title="Reverse" onClick={() => handleReverse(d)}>↩</button>
                      </div>
                    </td>
                  )}
                </tr>
              ))}
              {rows.length === 0 && <tr><td colSpan={8} className="empty">No disposal records yet.</td></tr>}
            </tbody>
          </table>
        </div>
      </div>

      {show && (
        <Modal title="New Disposal" onClose={() => setShow(false)}>
          {error && <div className="alert alert-error">{error}</div>}
          <form onSubmit={handleSubmit}>
            <div className="form-grid">
              <div className="form-group">
                <label>Asset *</label>
                <select className="form-select" value={form.asset_id} onChange={(e) => setForm({ ...form, asset_id: e.target.value })} required>
                  <option value="">-- Select Asset --</option>
                  {assets.map((a) => (
                    <option key={a.pid} value={a.pid}>
                      {a.name} ({a.serial_number || a.barcode}) — {a.stock} unit(s)
                    </option>
                  ))}
                </select>
              </div>
              <div className="form-group">
                <label>Quantity *</label>
                <input type="number" min="1" className="form-control" value={form.quantity} onChange={(e) => setForm({ ...form, quantity: e.target.value })} required />
              </div>
            </div>
            <div className="form-group">
              <label>Remarks</label>
              <textarea className="form-control" rows="2" value={form.remarks} onChange={(e) => setForm({ ...form, remarks: e.target.value })} placeholder='e.g. "Beyond economic repair"' />
            </div>
            <div className="flex between mt-4">
              <button type="button" className="btn btn-light" onClick={() => setShow(false)}>Cancel</button>
              <button type="submit" className="btn btn-primary" disabled={loading}>{loading ? "Saving..." : "Record Disposal"}</button>
            </div>
          </form>
        </Modal>
      )}
    </div>
  );
}