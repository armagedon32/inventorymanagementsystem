import { useEffect, useState } from "react";
import { api } from "../api/client";

export default function Categories() {
  const [categories, setCategories] = useState([]);
  const [category, setCategory] = useState("");
  const [description, setDescription] = useState("");
  const [editing, setEditing] = useState(null);
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    load();
  }, []);

  function load() {
    api
      .get("/products/meta/categories")
      .then(setCategories)
      .catch((e) => setError(e.message));
  }

  function resetForm() {
    setCategory("");
    setDescription("");
    setEditing(null);
  }

  function startEdit(c) {
    setEditing(c);
    setCategory(c.category);
    setDescription(c.description || "");
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    setMsg("");
    setLoading(true);
    try {
      if (editing) {
        await api.put(`/products/meta/categories/${editing.catid}`, { category, description });
        setMsg(`Category "${category}" updated.`);
      } else {
        await api.post("/products/meta/categories", { category, description });
        setMsg(`Category "${category}" added.`);
      }
      resetForm();
      load();
    } catch (e) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  }

  async function handleArchive(c) {
    if (!window.confirm(`Archive category "${c.category}"? Items using it must be moved first.`)) return;
    setError("");
    setMsg("");
    try {
      await api.del(`/products/meta/categories/${c.catid}`);
      setMsg(`Category "${c.category}" archived.`);
      load();
    } catch (e) {
      setError(e.message);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>Category Management</h5>
        <span className="text-muted" style={{ fontSize: "0.85rem" }}>
          {categories.length} categor(ies)
        </span>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}

        <form onSubmit={handleSubmit} className="mb-3">
          <div className="form-grid">
            <div className="form-group">
              <label>Category Name *</label>
              <input
                className="form-control"
                value={category}
                onChange={(e) => setCategory(e.target.value)}
                placeholder='e.g. "Office Supply"'
                required
              />
            </div>
            <div className="form-group">
              <label>Description</label>
              <input
                className="form-control"
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                placeholder="e.g. Consumable office materials"
              />
            </div>
          </div>
          <div className="flex between mt-2">
            <div />
            <div className="flex" style={{ gap: "0.5rem" }}>
              {editing && (
                <button type="button" className="btn btn-light" onClick={resetForm}>
                  Cancel Edit
                </button>
              )}
              <button type="submit" className="btn btn-primary" disabled={loading}>
                {loading ? "Saving..." : editing ? "Update Category" : "Add Category"}
              </button>
            </div>
          </div>
        </form>

        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Category Name</th>
                <th>Description</th>
                <th>Items Used</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {categories.map((c, i) => (
                <tr key={c.catid}>
                  <td>{i + 1}</td>
                  <td>
                    <strong>{c.category}</strong>
                  </td>
                  <td>{c.description || "—"}</td>
                  <td>
                    <span className="badge badge-ok">{c.item_count}</span>
                  </td>
                  <td>
                    <div className="btn-group">
                      <button className="btn btn-success btn-sm" title="Edit" onClick={() => startEdit(c)}>
                        ✎
                      </button>
                      <button className="btn btn-danger btn-sm" title="Archive" onClick={() => handleArchive(c)}>
                        🗑
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
              {categories.length === 0 && (
                <tr>
                  <td colSpan={5} className="empty">
                    No categories yet. Add one above.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}