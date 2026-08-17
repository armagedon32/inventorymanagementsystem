import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { api } from "../api/client";

export default function AddProduct() {
  const navigate = useNavigate();
  const [categories, setCategories] = useState([]);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [form, setForm] = useState({
    barcode: "",
    name: "",
    brand: "",
    acquisition_type: "Purchased",
    category: "",
    description: "",
    stock: 0,
    reorder_level: 0,
  });

  useEffect(() => {
    api.get("/products/meta/categories").then(setCategories).catch((e) => setError(e.message));
  }, []);

  function set(field, value) {
    setForm((f) => ({ ...f, [field]: value }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      await api.post("/products", form);
      navigate("/products");
    } catch (e) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>Supply Registration</h5>
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
              <label>Barcode (leave blank to auto-generate)</label>
              <input className="form-control" value={form.barcode} onChange={(e) => set("barcode", e.target.value)} />
            </div>
            <div className="form-group">
              <label>Category *</label>
              <select
                className="form-select"
                value={form.category}
                onChange={(e) => set("category", e.target.value)}
                required
              >
                <option value="">-- Select --</option>
                {categories.map((c) => (
                  <option key={c.catid} value={c.catid}>{c.category}</option>
                ))}
              </select>
            </div>
            <div className="form-group">
              <label>Acquisition Type</label>
              <select
                className="form-select"
                value={form.acquisition_type}
                onChange={(e) => set("acquisition_type", e.target.value)}
              >
                <option>Purchased</option>
                <option>Donated</option>
              </select>
            </div>
            <div className="form-group">
              <label>Description</label>
              <input className="form-control" value={form.description} onChange={(e) => set("description", e.target.value)} />
            </div>
            <div className="form-group">
              <label>Initial Stock</label>
              <input
                type="number"
                min="0"
                className="form-control"
                value={form.stock}
                onChange={(e) => set("stock", Number(e.target.value))}
              />
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
            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? "Saving..." : "Save Supply"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}