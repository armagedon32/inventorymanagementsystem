import { useEffect, useState } from "react";
import { api } from "../api/client";
import { useAuth } from "../context/AuthContext";
import Modal from "../components/Modal";

const empty = { fullname: "", contact: "", email: "", assigned_dept: "" };

export default function Instructors() {
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
    api.get("/master/instructors").then(setRows).catch((e) => setError(e.message));
  }

  function openAdd() {
    setEditing(null);
    setForm(empty);
    setShow(true);
  }

  function openEdit(i) {
    setEditing(i);
    setForm({ fullname: i.fullname, contact: i.contact || "", email: i.email || "", assigned_dept: i.assigned_dept || "" });
    setShow(true);
  }

  async function handleSave(e) {
    e.preventDefault();
    setError("");
    setMsg("");
    try {
      if (editing) await api.put(`/master/instructors/${editing.id}`, form);
      else await api.post("/master/instructors", form);
      setMsg(editing ? "Instructor updated." : "Instructor added.");
      setShow(false);
      load();
    } catch (err) {
      setError(err.message);
    }
  }

  async function handleDelete(i) {
    if (!window.confirm(`Delete instructor "${i.fullname}"?`)) return;
    setError("");
    setMsg("");
    try {
      await api.del(`/master/instructors/${i.id}`);
      setMsg("Instructor deleted.");
      load();
    } catch (err) {
      setError(err.message);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>Instructors</h5>
        <div className="flex">
          <span className="text-muted" style={{ fontSize: "0.85rem" }}>{rows.length} record(s)</span>
          {isAdmin && <button className="btn btn-primary btn-sm" onClick={openAdd}>✚ New Instructor</button>}
        </div>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Full Name</th>
                <th>Contact</th>
                <th>Email</th>
                <th>Department</th>
                {isAdmin && <th>Actions</th>}
              </tr>
            </thead>
            <tbody>
              {rows.map((i) => (
                <tr key={i.id}>
                  <td><strong>{i.fullname}</strong></td>
                  <td>{i.contact || "—"}</td>
                  <td>{i.email || "—"}</td>
                  <td>{i.assigned_dept || "—"}</td>
                  {isAdmin && (
                    <td>
                      <div className="btn-group">
                        <button className="btn btn-warning btn-sm" title="Edit" onClick={() => openEdit(i)}>✎</button>
                        <button className="btn btn-danger btn-sm" title="Delete" onClick={() => handleDelete(i)}>🗑</button>
                      </div>
                    </td>
                  )}
                </tr>
              ))}
              {rows.length === 0 && <tr><td colSpan={5} className="empty">No instructors yet.</td></tr>}
            </tbody>
          </table>
        </div>
      </div>

      {show && (
        <Modal title={editing ? "Edit Instructor" : "New Instructor"} onClose={() => setShow(false)}>
          {error && <div className="alert alert-error">{error}</div>}
          <form onSubmit={handleSave}>
            <div className="form-grid">
              <div className="form-group">
                <label>Full Name *</label>
                <input className="form-control" value={form.fullname} onChange={(e) => setForm({ ...form, fullname: e.target.value })} required />
              </div>
              <div className="form-group">
                <label>Contact</label>
                <input className="form-control" value={form.contact} onChange={(e) => setForm({ ...form, contact: e.target.value })} />
              </div>
              <div className="form-group">
                <label>Email</label>
                <input className="form-control" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
              </div>
              <div className="form-group">
                <label>Department</label>
                <input className="form-control" value={form.assigned_dept} onChange={(e) => setForm({ ...form, assigned_dept: e.target.value })} />
              </div>
            </div>
            <div className="flex between mt-4">
              <button type="button" className="btn btn-light" onClick={() => setShow(false)}>Cancel</button>
              <button type="submit" className="btn btn-primary">{editing ? "Save Changes" : "Add Instructor"}</button>
            </div>
          </form>
        </Modal>
      )}
    </div>
  );
}