import { useEffect, useState } from "react";
import { api } from "../api/client";
import { useAuth } from "../context/AuthContext";
import Modal from "../components/Modal";

const empty = { office_name: "", parent_id: "", address: "", contact: "", max_capacity: 0 };

export default function Offices() {
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
    api.get("/master/offices").then(setRows).catch((e) => setError(e.message));
  }

  function openAdd() {
    setEditing(null);
    setForm(empty);
    setShow(true);
  }

  function openEdit(o) {
    setEditing(o);
    setForm({
      office_name: o.office_name,
      parent_id: o.parent_id || "",
      address: o.address || "",
      contact: o.contact || "",
      max_capacity: o.max_capacity || 0,
    });
    setShow(true);
  }

  async function handleSave(e) {
    e.preventDefault();
    setError("");
    setMsg("");
    try {
      const body = { ...form, parent_id: form.parent_id ? Number(form.parent_id) : null, max_capacity: Number(form.max_capacity) || 0 };
      if (editing) await api.put(`/master/offices/${editing.id}`, body);
      else await api.post("/master/offices", body);
      setMsg(editing ? "Office updated." : "Office added.");
      setShow(false);
      load();
    } catch (err) {
      setError(err.message);
    }
  }

  async function handleDelete(o) {
    if (!window.confirm(`Delete office "${o.office_name}"? Assets assigned to it will be unlinked.`)) return;
    setError("");
    setMsg("");
    try {
      await api.del(`/master/offices/${o.id}`);
      setMsg("Office deleted.");
      load();
    } catch (err) {
      setError(err.message);
    }
  }

  const parents = rows.filter((o) => !editing || o.id !== editing.id);

  return (
    <div className="card">
      <div className="card-header">
        <h5>Offices / Sections</h5>
        <div className="flex">
          <span className="text-muted" style={{ fontSize: "0.85rem" }}>{rows.length} record(s)</span>
          {isAdmin && <button className="btn btn-primary btn-sm" onClick={openAdd}>✚ New Office</button>}
        </div>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Office / Section</th>
                <th>Parent</th>
                <th>OIC / Address</th>
                <th>Contact</th>
                <th>Capacity</th>
                <th>Assets</th>
                {isAdmin && <th>Actions</th>}
              </tr>
            </thead>
            <tbody>
              {rows.map((o) => (
                <tr key={o.id}>
                  <td><strong>{o.office_name}</strong></td>
                  <td>{o.parent_name || "—"}</td>
                  <td>{o.address || "—"}</td>
                  <td>{o.contact || "—"}</td>
                  <td>{o.max_capacity || "—"}</td>
                  <td><span className="badge badge-ok">{o.asset_count}</span></td>
                  {isAdmin && (
                    <td>
                      <div className="btn-group">
                        <button className="btn btn-warning btn-sm" title="Edit" onClick={() => openEdit(o)}>✎</button>
                        <button className="btn btn-danger btn-sm" title="Delete" onClick={() => handleDelete(o)}>🗑</button>
                      </div>
                    </td>
                  )}
                </tr>
              ))}
              {rows.length === 0 && <tr><td colSpan={7} className="empty">No offices yet.</td></tr>}
            </tbody>
          </table>
        </div>
      </div>

      {show && (
        <Modal title={editing ? "Edit Office" : "New Office"} onClose={() => setShow(false)}>
          {error && <div className="alert alert-error">{error}</div>}
          <form onSubmit={handleSave}>
            <div className="form-grid">
              <div className="form-group">
                <label>Office / Section Name *</label>
                <input className="form-control" value={form.office_name} onChange={(e) => setForm({ ...form, office_name: e.target.value })} required />
              </div>
              <div className="form-group">
                <label>Parent Office</label>
                <select className="form-select" value={form.parent_id} onChange={(e) => setForm({ ...form, parent_id: e.target.value })}>
                  <option value="">— None —</option>
                  {parents.map((o) => (
                    <option key={o.id} value={o.id}>{o.office_name}</option>
                  ))}
                </select>
              </div>
              <div className="form-group">
                <label>OIC / Address</label>
                <input className="form-control" value={form.address} onChange={(e) => setForm({ ...form, address: e.target.value })} />
              </div>
              <div className="form-group">
                <label>Contact</label>
                <input className="form-control" value={form.contact} onChange={(e) => setForm({ ...form, contact: e.target.value })} />
              </div>
              <div className="form-group">
                <label>Max Capacity</label>
                <input type="number" min="0" className="form-control" value={form.max_capacity} onChange={(e) => setForm({ ...form, max_capacity: e.target.value })} />
              </div>
            </div>
            <div className="flex between mt-4">
              <button type="button" className="btn btn-light" onClick={() => setShow(false)}>Cancel</button>
              <button type="submit" className="btn btn-primary">{editing ? "Save Changes" : "Add Office"}</button>
            </div>
          </form>
        </Modal>
      )}
    </div>
  );
}