import { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { api } from "../api/client";

export default function EditProduct({ type = "Stock" }) {
  const isAsset = type === "Asset";
  const base = isAsset ? "/assets" : "/stock";
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
          unit_cost: p.unit_cost || 0,
          unit: p.unit || "pcs",
          product_type: p.product_type || type,
          serial_number: p.serial_number || "",
          condition: p.condition || "Good",
          assigned_to: p.assigned_to || "",
          department: p.department || "",
        })
      )
      .catch((e) => setError(e.message));
  }, [id, type]);

  function set(field, value) {
    setForm((f) => ({ ...f, [field]: value }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      await api.put(`/products/${id}`, form);
      navigate(`${base}/${id}`);
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
        <h5>{isAsset ? "Edit Asset" : "Edit Supply"}</h5>
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
              <label>{isAsset ? "Asset Tag / Barcode" : "Barcode"}</label>
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
                <option>Created</option>
              </select>
            </div>
            <div className="form-group">
              <label>Description</label>
              <input className="form-control" value={form.description} onChange={(e) => set("description", e.target.value)} />
            </div>
            <div className="form-group">
              <label>Unit</label>
              <select className="form-select" value={form.unit || "pcs"} onChange={(e) => set("unit", e.target.value)}>
                {["pcs", "box", "ream", "pack", "bottle", "set", "unit", "liter", "kg", "pair"].map((u) => (
                  <option key={u}>{u}</option>
                ))}
              </select>
            </div>

            {isAsset ? (
              <>
                <div className="form-group">
                  <label>Serial Number</label>
                  <input className="form-control" value={form.serial_number} onChange={(e) => set("serial_number", e.target.value)} />
                </div>
                <div className="form-group">
                  <label>Condition</label>
                  <select className="form-select" value={form.condition} onChange={(e) => set("condition", e.target.value)}>
                    <option>Good</option>
                    <option>Fair</option>
                    <option>Needs Repair</option>
                    <option>For Disposal</option>
                  </select>
                </div>
                <div className="form-group">
                  <label>Assigned To (Office / Person)</label>
                  <input className="form-control" value={form.assigned_to} onChange={(e) => set("assigned_to", e.target.value)} />
                </div>
                <div className="form-group">
                  <label>Quantity</label>
                  <input
                    type="number"
                    min="0"
                    className="form-control"
                    value={form.stock}
                    onChange={(e) => set("stock", Number(e.target.value))}
                  />
                </div>
              </>
            ) : (
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
            )}

            <div className="form-group">
              <label>Office</label>
              <select className="form-select" value={form.department} onChange={(e) => set("department", e.target.value)}>
                <option value="">-- Select --</option>
                {["Library Office","Registrar Office","College President/MIS Office","Faculty Office","Deans Office","Clinic Office","NSTP Office","OSA ADMISSION & SCHOLARSHIP Office","Guidance Office","Academic Affairs Office","Property Custodian Office","Research Office","Planning Office","Maintenance Office","Head Sports Athletics Office","Utility Office","AVR Internet Laboratory Office","Guard House","Computer Laboratory","Administrator Office","Kitchen Laboratory","Bartending","Tourism","Housekeeping","Front Office","Food and Beverage"].map((d) => (
                  <option key={d}>{d}</option>
                ))}
              </select>
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