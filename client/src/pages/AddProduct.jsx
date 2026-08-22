import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { api, API_URL } from "../api/client";

const OFFICES = [
  "Library Office",
  "Registrar Office",
  "College President/MIS Office",
  "Faculty Office",
  "Deans Office",
  "Clinic Office",
  "NSTP Office",
  "OSA ADMISSION & SCHOLARSHIP Office",
  "Guidance Office",
  "Academic Affairs Office",
  "Property Custodian Office",
  "Research Office",
  "Planning Office",
  "Maintenance Office",
  "Head Sports Athletics Office",
  "Utility Office",
  "AVR Internet Laboratory Office",
  "Guard House",
  "Computer Laboratory",
  "Administrator Office",
  "Kitchen Laboratory",
  "Bartending",
  "Tourism",
  "Housekeeping",
  "Front Office",
  "Food and Beverage",
];

export default function AddProduct({ type = "Stock" }) {
  const isAsset = type === "Asset";
  const navigate = useNavigate();
  const [categories, setCategories] = useState([]);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [importMsg, setImportMsg] = useState("");
  const [importResult, setImportResult] = useState(null);
  const [form, setForm] = useState({
    barcode: "",
    name: "",
    brand: "",
    acquisition_type: "Purchased",
    category: "",
    description: "",
    stock: 0,
    reorder_level: 0,
    unit_cost: 0,
    unit: "pcs",
    product_type: type,
    serial_number: "",
    condition: "Good",
    assigned_to: "",
    department: "",
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
      navigate(isAsset ? "/assets" : "/stock");
    } catch (e) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  }

  async function downloadTemplate() {
    try {
      const res = await fetch(`${API_URL}/products/import-template/stock`, {
        headers: { Authorization: `Bearer ${localStorage.getItem("custodian_token")}` },
      });
      const blob = await res.blob();
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = "supply-import-template.xlsx";
      a.click();
      URL.revokeObjectURL(url);
    } catch {
      setError("Failed to download template.");
    }
  }

  async function handleBulkImport(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    setError("");
    setImportMsg("Importing...");
    setImportResult(null);
    try {
      const base64 = await new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result.split(",")[1]);
        reader.onerror = reject;
        reader.readAsDataURL(file);
      });
      const result = await api.post("/products/import/stock", { csv: base64 });
      setImportResult(result);
      setImportMsg(`Imported: ${result.imported}, Skipped: ${result.skipped}`);
      if (result.errors?.length) {
        setImportMsg((m) => m + "\n" + result.errors.join("\n"));
      }
    } catch (err) {
      setError(err.message);
      setImportMsg("");
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>Supply Registration</h5>
        <div className="flex" style={{ gap: "8px" }}>
          <button type="button" className="btn btn-light btn-sm" onClick={downloadTemplate}>
            ⬇ Download Excel Template
          </button>
          <label className="btn btn-primary btn-sm" style={{ cursor: "pointer" }}>
            ⬆ Import Excel
            <input type="file" accept=".xlsx,.xls" style={{ display: "none" }} onChange={handleBulkImport} />
          </label>
        </div>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {importMsg && <div className="alert alert-success" style={{ whiteSpace: "pre-wrap" }}>{importMsg}</div>}
        {importResult && importResult.imported > 0 && (
          <div className="alert alert-success">
            Successfully imported {importResult.imported} item(s).
            {importResult.skipped > 0 && ` ${importResult.skipped} row(s) skipped.`}
          </div>
        )}
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
            {isAsset && (
            <div className="form-group">
              <label>Asset Tag / Barcode (blank = auto)</label>
              <input className="form-control" value={form.barcode} onChange={(e) => set("barcode", e.target.value)} />
            </div>
            )}
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
            {isAsset && (
            <div className="form-group">
              <label>Acquisition Type</label>
              <select
                className="form-select"
                value={form.acquisition_type}
                onChange={(e) => set("acquisition_type", e.target.value)}
              >
                <option>Purchased</option>
                <option>Donated</option>
                <option>Created</option>
              </select>
            </div>
            )}
            <div className="form-group">
              <label>Description</label>
              <input className="form-control" value={form.description} onChange={(e) => set("description", e.target.value)} />
            </div>
            <div className="form-group">
              <label>Unit</label>
              <select className="form-select" value={form.unit} onChange={(e) => set("unit", e.target.value)}>
                {["pcs", "box", "ream", "pack", "bottle", "set", "unit", "liter", "kg", "pair"].map((u) => (
                  <option key={u}>{u}</option>
                ))}
              </select>
            </div>

            {isAsset ? (
              <>
                <div className="form-group">
                  <label>Serial Number *</label>
                  <input className="form-control" value={form.serial_number} onChange={(e) => set("serial_number", e.target.value)} required />
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
              <>
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
              </>
            )}

            {isAsset && (
            <div className="form-group">
              <label>Office *</label>
              <select className="form-select" value={form.department} onChange={(e) => set("department", e.target.value)} required>
                <option value="">-- Select --</option>
                {OFFICES.map((d) => (
                  <option key={d}>{d}</option>
                ))}
              </select>
            </div>
            )}
          </div>

          <div className="flex between mt-4">
            <button type="button" className="btn btn-light" onClick={() => navigate(-1)}>Cancel</button>
            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? "Saving..." : isAsset ? "Save Asset" : "Save Supply"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}