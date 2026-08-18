import { useEffect, useState } from "react";
import { api } from "../api/client";
import { useAuth } from "../context/AuthContext";
import Modal from "../components/Modal";

const empty = { org_name: "", president: "" };

export default function Organizations() {
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
    api.get("/master/organizations").then(setRows).catch((e) => setError(e.message));
  }

  function openAdd() {
    setEditing(null);
    setForm(empty);
    setShow(true);
  }

  function openEdit(o) {
    setEditing(o);
    setForm({ org_name: o.org_name, president: o.president || "" });
    setShow(true);
  }

  async function handleSave(e) {
    e.preventDefault();
    setError("");
    setMsg("");
    try {
      if (editing) await api.put(`/master/organizations/${editing.id}`, form);
      else await api.post("/master/organizations", form);
      setMsg(editing ? "Organization updated." : "Organization added.");
      setShow(false);
      load();
    } catch (err) {
      setError(err.message);
    }
  }

  async function handleDelete(o) {
    if (!window.confirm(`Delete organization "${o.org_name}"?`)) return;
    setError("");
    setMsg("");
    try {
      await api.del(`/master/organizations/${o.id}`);
      setMsg("Organization deleted.");
      load();
    } catch (err) {
      setError(err.message);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>Organizations</h5>
        <div className="flex">
          <span className="text-muted" style={{ fontSize: "0.85rem" }}>{rows.length} record(s)</span>
          {isAdmin && <button className="btn btn-primary btn-sm" onClick={openAdd}>✚ New Organization</button>}
        </div>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Organization</th>
                <th>President</th>
                <th>Facility Use</th>
                {isAdmin && <th>Actions</th>}
              </tr>
            </thead>
            <tbody>
              {rows.map((o) => (
                <tr key={o.id}>
                  <td><strong>{o.org_name}</strong></td>
                  <td>{o.president || "—"}</td>
                  <td><span className="badge badge-ok">{o.use_count}</span></td>
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
              {rows.length === 0 && <tr><td colSpan={4} className="empty">No organizations yet.</td></tr>}
            </tbody>
          </table>
        </div>
      </div>

      {show && (
        <Modal title={editing ? "Edit Organization" : "New Organization"} onClose={() => setShow(false)}>
          {error && <div className="alert alert-error">{error}</div>}
          <form onSubmit={handleSave}>
            <div className="form-grid">
              <div className="form-group">
                <label>Organization Name *</label>
                <input className="form-control" value={form.org_name} onChange={(e) => setForm({ ...form, org_name: e.target.value })} required />
              </div>
              <div className="form-group">
                <label>President / Adviser</label>
                <input className="form-control" value={form.president} onChange={(e) => setForm({ ...form, president: e.target.value })} />
              </div>
            </div>
            <div className="flex between mt-4">
              <button type="button" className="btn btn-light" onClick={() => setShow(false)}>Cancel</button>
              <button type="submit" className="btn btn-primary">{editing ? "Save Changes" : "Add Organization"}</button>
            </div>
          </form>
        </Modal>
      )}
    </div>
  );
}