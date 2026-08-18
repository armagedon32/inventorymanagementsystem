import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { api } from "../api/client";

export default function NewIncident() {
  const navigate = useNavigate();
  const [offices, setOffices] = useState([]);
  const [assets, setAssets] = useState([]);
  const [form, setForm] = useState({
    reported_by: "", office: "", incident_date: "", incident_time: "",
    description: "", extent_of_damage: "",
  });
  const [lines, setLines] = useState([{ asset_id: "", quantity: 1, location: "", last_borrower: "" }]);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    api.get("/master/offices").then(setOffices).catch((e) => setError(e.message));
    api.get("/products?type=Asset").then(setAssets).catch((e) => setError(e.message));
  }, []);

  const set = (k, v) => setForm((f) => ({ ...f, [k]: v }));

  function setLine(idx, field, value) {
    setLines((ls) => ls.map((l, i) => (i === idx ? { ...l, [field]: field === "quantity" ? Number(value) : value } : l)));
  }

  function addLine() {
    setLines((ls) => [...ls, { asset_id: "", quantity: 1, location: "", last_borrower: "" }]);
  }

  function removeLine(idx) {
    setLines((ls) => (ls.length > 1 ? ls.filter((_, i) => i !== idx) : ls));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      const items = lines
        .filter((l) => l.asset_id || l.location || l.last_borrower)
        .map((l) => ({
          asset_id: l.asset_id ? Number(l.asset_id) : null,
          quantity: Number(l.quantity) || 1,
          location: l.location,
          last_borrower: l.last_borrower,
        }));
      if (items.length === 0) {
        setError("Add at least one property item involved.");
        setLoading(false);
        return;
      }
      const r = await api.post("/incidents", { ...form, office: form.office ? Number(form.office) : null, items });
      navigate(`/incidents/${r.id}`, { replace: true });
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>New Incident Report</h5>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        <form onSubmit={handleSubmit}>
          <div className="form-grid">
            <div className="form-group">
              <label>Reported By *</label>
              <input className="form-control" value={form.reported_by} onChange={(e) => set("reported_by", e.target.value)} required />
            </div>
            <div className="form-group">
              <label>Office</label>
              <select className="form-select" value={form.office} onChange={(e) => set("office", e.target.value)}>
                <option value="">-- Office --</option>
                {offices.map((o) => (
                  <option key={o.id} value={o.id}>{o.office_name}</option>
                ))}
              </select>
            </div>
            <div className="form-group">
              <label>Incident Date</label>
              <input type="date" className="form-control" value={form.incident_date} onChange={(e) => set("incident_date", e.target.value)} />
            </div>
            <div className="form-group">
              <label>Incident Time</label>
              <input type="time" className="form-control" value={form.incident_time} onChange={(e) => set("incident_time", e.target.value)} />
            </div>
          </div>
          <div className="form-grid">
            <div className="form-group">
              <label>Description *</label>
              <textarea className="form-control" rows="3" value={form.description} onChange={(e) => set("description", e.target.value)} required />
            </div>
            <div className="form-group">
              <label>Extent of Damage</label>
              <textarea className="form-control" rows="3" value={form.extent_of_damage} onChange={(e) => set("extent_of_damage", e.target.value)} />
            </div>
          </div>

          <h6 style={{ margin: "1rem 0 0.5rem" }}>Property Items Involved</h6>
          {lines.map((line, i) => (
            <div className="form-grid" key={i} style={{ alignItems: "end" }}>
              <div className="form-group">
                <label>Asset</label>
                <select className="form-select" value={line.asset_id} onChange={(e) => setLine(i, "asset_id", e.target.value)}>
                  <option value="">-- Select Asset (optional) --</option>
                  {assets.map((a) => (
                    <option key={a.pid} value={a.pid}>
                      {a.name} ({a.serial_number || a.barcode})
                    </option>
                  ))}
                </select>
              </div>
              <div className="form-group">
                <label>Qty</label>
                <input type="number" min="1" className="form-control" value={line.quantity} onChange={(e) => setLine(i, "quantity", e.target.value)} />
              </div>
              <div className="form-group">
                <label>Location</label>
                <input className="form-control" value={line.location} onChange={(e) => setLine(i, "location", e.target.value)} />
              </div>
              <div className="form-group">
                <label>Last Borrower</label>
                <input className="form-control" value={line.last_borrower} onChange={(e) => setLine(i, "last_borrower", e.target.value)} />
              </div>
              <div className="form-group">
                <button type="button" className="btn btn-danger btn-sm" onClick={() => removeLine(i)} title="Remove line">✕</button>
              </div>
            </div>
          ))}
          <button type="button" className="btn btn-light btn-sm" onClick={addLine}>✚ Add Another Item</button>

          <div className="flex between mt-4">
            <button type="button" className="btn btn-light" onClick={() => navigate(-1)}>Cancel</button>
            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? "Submitting..." : "Submit Report"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}