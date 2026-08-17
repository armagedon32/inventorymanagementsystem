import { useEffect, useState } from "react";
import { api } from "../api/client";
import { useAuth } from "../context/AuthContext";

const emptyForm = {
  fullname: "",
  username: "",
  useremail: "",
  contact_number: "",
  course: "",
  major: "",
  year_level: "",
  role: "Intern",
  password: "",
};

export default function Users() {
  const { user: currentUser } = useAuth();
  const [users, setUsers] = useState([]);
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");
  const [form, setForm] = useState(emptyForm);
  const [editing, setEditing] = useState(null);
  const [showForm, setShowForm] = useState(false);

  useEffect(() => {
    load();
  }, []);

  function load() {
    api.get("/users").then(setUsers).catch((e) => setError(e.message));
  }

  function set(field, value) {
    setForm((f) => ({ ...f, [field]: value }));
  }

  function startEdit(u) {
    setEditing(u.userid);
    setForm({
      fullname: u.fullname,
      username: u.username,
      useremail: u.useremail,
      contact_number: u.contact_number || "",
      course: u.course || "",
      major: u.major || "",
      year_level: u.year_level || "",
      role: u.role,
      password: "",
    });
    setShowForm(true);
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    setMsg("");
    try {
      if (editing) {
        await api.put(`/users/${editing}`, form);
        setMsg("User updated.");
      } else {
        await api.post("/users", form);
        setMsg("User created.");
      }
      setForm(emptyForm);
      setEditing(null);
      setShowForm(false);
      load();
    } catch (e) {
      setError(e.message);
    }
  }

  async function handleDelete(u) {
    if (!window.confirm(`Archive user "${u.fullname}"?`)) return;
    try {
      await api.del(`/users/${u.userid}`);
      setMsg("User archived.");
      load();
    } catch (e) {
      setError(e.message);
    }
  }

  return (
    <div>
      {error && <div className="alert alert-error">{error}</div>}
      {msg && <div className="alert alert-success">{msg}</div>}

      <div className="card mb-4">
        <div className="card-header">
          <h5>User Management</h5>
          <button className="btn btn-primary btn-sm" onClick={() => { setShowForm(!showForm); setEditing(null); setForm(emptyForm); }}>
            {showForm && !editing ? "Hide Form" : "➕ New User"}
          </button>
        </div>
        <div className="card-body">
          <div className="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Fullname</th>
                  <th>Username</th>
                  <th>Email</th>
                  <th>Contact</th>
                  <th>Course</th>
                  <th>Year</th>
                  <th>Role</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {users.map((u) => (
                  <tr key={u.userid}>
                    <td><strong>{u.fullname}</strong></td>
                    <td>{u.username}</td>
                    <td>{u.useremail}</td>
                    <td>{u.contact_number || ""}</td>
                    <td>{u.course || ""}</td>
                    <td>{u.year_level || ""}</td>
                    <td><span className="badge badge-ok">{u.role}</span></td>
                    <td>
                      <div className="btn-group">
                        <button className="btn btn-success btn-sm" onClick={() => startEdit(u)}>✎ Edit</button>
                        {currentUser?.userid !== u.userid && (
                          <button className="btn btn-danger btn-sm" onClick={() => handleDelete(u)}>🗑</button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {showForm && (
        <div className="card">
          <div className="card-header">
            <h5>{editing ? "Edit User" : "New User"}</h5>
          </div>
          <div className="card-body">
            <form onSubmit={handleSubmit}>
              <div className="form-grid-3">
                <div className="form-group">
                  <label>Fullname *</label>
                  <input className="form-control" value={form.fullname} onChange={(e) => set("fullname", e.target.value)} required />
                </div>
                <div className="form-group">
                  <label>Username *</label>
                  <input className="form-control" value={form.username} onChange={(e) => set("username", e.target.value)} required />
                </div>
                <div className="form-group">
                  <label>Email *</label>
                  <input type="email" className="form-control" value={form.useremail} onChange={(e) => set("useremail", e.target.value)} required />
                </div>
                <div className="form-group">
                  <label>Contact Number</label>
                  <input className="form-control" value={form.contact_number} onChange={(e) => set("contact_number", e.target.value)} />
                </div>
                <div className="form-group">
                  <label>Course</label>
                  <input className="form-control" value={form.course} onChange={(e) => set("course", e.target.value)} />
                </div>
                <div className="form-group">
                  <label>Major</label>
                  <input className="form-control" value={form.major} onChange={(e) => set("major", e.target.value)} />
                </div>
                <div className="form-group">
                  <label>Year Level</label>
                  <input className="form-control" value={form.year_level} onChange={(e) => set("year_level", e.target.value)} />
                </div>
                <div className="form-group">
                  <label>Role *</label>
                  <select className="form-select" value={form.role} onChange={(e) => set("role", e.target.value)}>
                    <option>Admin</option>
                    <option>Intern</option>
                    <option>Student Assistant</option>
                  </select>
                </div>
                <div className="form-group">
                  <label>{editing ? "New Password (leave blank to keep)" : "Password *"}</label>
                  <input
                    type="password"
                    className="form-control"
                    value={form.password}
                    onChange={(e) => set("password", e.target.value)}
                    required={!editing}
                  />
                </div>
              </div>
              <div className="flex between mt-4">
                <button type="button" className="btn btn-light" onClick={() => { setShowForm(false); setEditing(null); setForm(emptyForm); }}>
                  Cancel
                </button>
                <button type="submit" className="btn btn-primary">{editing ? "Save Changes" : "Create User"}</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}