import { useEffect, useState } from "react";
import { api } from "../api/client";
import { useAuth } from "../context/AuthContext";
import Modal from "../components/Modal";

const empty = { supplier_name: "", contact: "", address: "" };

export default function Suppliers() {
  const { user } = useAuth();
  const isAdmin = user?.role === "Admin";
  const [rows, setRows] = useState([]);
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");
  const [show, setShow] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState(empty);

  useEffect(() => { load(); }, []);

  function load() {
    api.get("/master/suppliers").then(setRows).catch((e) => setError(e.message));
  }

  function openAdd() {
    setEditing(null);
    setForm(empty);
    setShow(true);
  }

  function openEdit(s) {
    setEditing(s);
    setForm({ supplier_name: s.supplier_name, contact: s.contact || "", address: s.address || "" });
    setShow(true);
  }

  async function handleSave(e) {
    e.preventDefault();
    setError("");
    setMsg("");
    try {
      if (editing) await api.put(`/master/suppliers/${editing.sup_id}`, form);
      else await api.post("/master/suppliers", form);
      setMsg(editing ? "Supplier updated." : "Supplier added.");
      setShow(false);
      load();
    } catch (err) {
      setError(err.message);
    }
  }

  async function handleDelete(s) {
    if (!window.confirm(`Delete supplier "${s.supplier_name}"?`)) return;
    setError("");
    setMsg("");
    try {
      await api.del(`/master/suppliers/${s.sup_id}`);
      setMsg("Supplier deleted.");
      load();
    } catch (err) {
      setError(err.message);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>Suppliers</h5>
        <div className="flex">
          <span className="text-muted" style={{ fontSize: "0.85rem" }}>{rows.length} record(s)</span>
          {isAdmin && <button className="btn btn-primary btn-sm" onClick={openAdd}>✚ New Supplier</button>}
        </div>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Supplier Name</th>
                <th>Contact</th>
                <th>Address</th>
                <th>Items</th>
                {isAdmin && <th>Actions</th>}
              </tr>
            </thead>
            <tbody>
              {rows.map((s) => (
                <tr key={s.sup_id}>
                  <td><strong>{s.supplier_name}</strong></td>
                  <td>{s.contact || "—"}</td>
                  <td>{s.address || "—"}</td>
                  <td><span className="badge badge-ok">{s.item_count}</span></td>
                  {isAdmin && (
                    <td>
                      <div className="btn-group">
                        <button className="btn btn-warning btn-sm" title="Edit" onClick={() => openEdit(s)}>✎</button>
                        <button className="btn btn-danger btn-sm" title="Delete" onClick={() => handleDelete(s)}>🗑</button>
                      </div>
                    </td>
                  )}
                </tr>
              ))}
              {rows.length === 0 && <tr><td colSpan={5} className="empty">No suppliers yet.</td></tr>}
            </tbody>
          </table>
        </div>
      </div>

      {show && (
        <Modal title={editing ? "Edit Supplier" : "New Supplier"} onClose={() => setShow(false)}>
          {error && <div className="alert alert-error">{error}</div>}
          <form onSubmit={handleSave}>
            <div className="form-grid">
              <div className="form-group">
                <label>Supplier Name *</label>
                <input className="form-control" value={form.supplier_name} onChange={(e) => setForm({ ...form, supplier_name: e.target.value })} required />
              </div>
              <div className="form-group">
                <label>Contact</label>
                <input className="form-control" value={form.contact} onChange={(e) => setForm({ ...form, contact: e.target.value })} />
              </div>
              <div className="form-group">
                <label>Address</label>
                <input className="form-control" value={form.address} onChange={(e) => setForm({ ...form, address: e.target.value })} />
              </div>
            </div>
            <div className="flex between mt-4">
              <button type="button" className="btn btn-light" onClick={() => setShow(false)}>Cancel</button>
              <button type="submit" className="btn btn-primary">{editing ? "Save Changes" : "Add Supplier"}</button>
            </div>
          </form>
        </Modal>
      )}
    </div>
  );
}