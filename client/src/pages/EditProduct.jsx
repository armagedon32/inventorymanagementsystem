import { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { api } from "../api/client";

export default function EditProduct() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [categories, setCategories] = useState([]);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [form, setForm] = useState(null);

  useEffect(() => {
    api.get("/products/meta/categories").then(setCategories).catch((e) => setError(e.message));
    api
      .get(`/products/${id}`)
      .then((p) =>
        setForm({
          barcode: p.barcode,
          name: p.name,
          brand: p.brand,
          acquisition_type: p.acquisition_type,
          category: String(p.category),
          description: p.description,
          reorder_level: p.reorder_level,
        })
      )
      .catch((e) => setError(e.message));
  }, [id]);

  function set(field, value) {
    setForm((f) => ({ ...f, [field]: value }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      await api.put(`/products/${id}`, form);
      navigate(`/products/${id}`);
    } catch (e) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  }

  if (!form) return <div className="empty">Loading...</div>;

  return (
    <div className="card">
      <div className="card-header">
        <h5>Edit Supply</h5>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        <form onSubmit={handleSubmit}>
          <div className="form-grid">
            <div className="form-group">
              <label>Item Name *</label>
              <input className="form-control" value={form.name} onChange={(e) => set("name", e.target.value)} required />
            </div>
            <div className="form-group">
              <label>Brand</label>
              <input className="form-control" value={form.brand} onChange={(e) => set("brand", e.target.value)} />
            </div>
            <div className="form-group">
              <label>Barcode</label>
              <input className="form-control" value={form.barcode} onChange={(e) => set("barcode", e.target.value)} />
            </div>
            <div className="form-group">
              <label>Category *</label>
              <select className="form-select" value={form.category} onChange={(e) => set("category", e.target.value)} required>
                <option value="">-- Select --</option>
                {categories.map((c) => (
                  <option key={c.catid} value={c.catid}>{c.category}</option>
                ))}
              </select>
            </div>
            <div className="form-group">
              <label>Acquisition Type</label>
              <select className="form-select" value={form.acquisition_type} onChange={(e) => set("acquisition_type", e.target.value)}>
                <option>Purchased</option>
                <option>Donated</option>
              </select>
            </div>
            <div className="form-group">
              <label>Description</label>
              <input className="form-control" value={form.description} onChange={(e) => set("description", e.target.value)} />
            </div>
            <div className="form-group">
              <label>Reorder Level</label>
              <input
                type="number"
                min="0"
                className="form-control"
                value={form.reorder_level}
                onChange={(e) => set("reorder_level", Number(e.target.value))}
              />
            </div>
          </div>

          <div className="flex between mt-4">
            <button type="button" className="btn btn-light" onClick={() => navigate(-1)}>Cancel</button>
            <button type="submit" className="btn btn-success" disabled={loading}>
              {loading ? "Saving..." : "Save Changes"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}