import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { api } from "../api/client";

export default function NewRis() {
  const navigate = useNavigate();
  const [assets, setAssets] = useState([]);
  const [offices, setOffices] = useState([]);
  const [form, setForm] = useState({
    last_name: "", first_name: "", mi_name: "", cp_number: "", position: "",
    event_name: "", event_date: "", start_datetime: "", end_datetime: "",
  });
  const [lines, setLines] = useState([{ asset_id: "", quantity: 1, borrowed_from: "" }]);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    api.get("/products?type=Asset").then((rows) => setAssets(rows.filter((a) => a.stock > 0 && !a.office_id))).catch((e) => setError(e.message));
    api.get("/master/offices").then(setOffices).catch((e) => setError(e.message));
  }, []);

  const set = (k, v) => setForm((f) => ({ ...f, [k]: v }));

  function setLine(idx, field, value) {
    setLines((ls) => ls.map((l, i) => (i === idx ? { ...l, [field]: field === "quantity" ? Number(value) : value } : l)));
  }

  function addLine() {
    setLines((ls) => [...ls, { asset_id: "", quantity: 1, borrowed_from: "" }]);
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
        .filter((l) => l.asset_id && Number(l.quantity) > 0)
        .map((l) => ({ asset_id: Number(l.asset_id), quantity: Number(l.quantity), borrowed_from: l.borrowed_from ? Number(l.borrowed_from) : null }));
      if (items.length === 0) {
        setError("Select at least one asset with a valid quantity.");
        setLoading(false);
        return;
      }
      const r = await api.post("/ris", { ...form, items });
      navigate(`/ris/${r.id}`, { replace: true });
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>New RIS (Requisition &amp; Issue Slip)</h5>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        <form onSubmit={handleSubmit}>
          <h6 style={{ margin: "0 0 0.5rem" }}>Borrower Information</h6>
          <div className="form-grid">
            <div className="form-group">
              <label>Last Name *</label>
              <input className="form-control" value={form.last_name} onChange={(e) => set("last_name", e.target.value)} required />
            </div>
            <div className="form-group">
              <label>First Name *</label>
              <input className="form-control" value={form.first_name} onChange={(e) => set("first_name", e.target.value)} required />
            </div>
            <div className="form-group">
              <label>Middle Initial</label>
              <input className="form-control" value={form.mi_name} onChange={(e) => set("mi_name", e.target.value)} />
            </div>
            <div className="form-group">
              <label>Contact No.</label>
              <input className="form-control" value={form.cp_number} onChange={(e) => set("cp_number", e.target.value)} />
            </div>
            <div className="form-group">
              <label>Position</label>
              <input className="form-control" value={form.position} onChange={(e) => set("position", e.target.value)} />
            </div>
          </div>

          <h6 style={{ margin: "1rem 0 0.5rem" }}>Event Details</h6>
          <div className="form-grid">
            <div className="form-group">
              <label>Event Name *</label>
              <input className="form-control" value={form.event_name} onChange={(e) => set("event_name", e.target.value)} required />
            </div>
            <div className="form-group">
              <label>Event Date</label>
              <input type="date" className="form-control" value={form.event_date} onChange={(e) => set("event_date", e.target.value)} />
            </div>
            <div className="form-group">
              <label>Start Date/Time</label>
              <input type="datetime-local" className="form-control" value={form.start_datetime} onChange={(e) => set("start_datetime", e.target.value)} />
            </div>
            <div className="form-group">
              <label>Return Due</label>
              <input type="datetime-local" className="form-control" value={form.end_datetime} onChange={(e) => set("end_datetime", e.target.value)} />
            </div>
          </div>

          <h6 style={{ margin: "1rem 0 0.5rem" }}>Property Items</h6>
          {lines.map((line, i) => (
            <div className="form-grid" key={i} style={{ alignItems: "end" }}>
              <div className="form-group">
                <label>Asset</label>
                <select className="form-select" value={line.asset_id} onChange={(e) => setLine(i, "asset_id", e.target.value)} required>
                  <option value="">-- Select Asset --</option>
                  {assets.map((a) => (
                    <option key={a.pid} value={a.pid}>
                      {a.name} ({a.serial_number || a.barcode}) — {a.stock} unit(s)
                    </option>
                  ))}
                </select>
              </div>
              <div className="form-group">
                <label>Quantity *</label>
                <input type="number" min="1" className="form-control" value={line.quantity} onChange={(e) => setLine(i, "quantity", e.target.value)} required />
              </div>
              <div className="form-group">
                <label>Borrowed From</label>
                <select className="form-select" value={line.borrowed_from} onChange={(e) => setLine(i, "borrowed_from", e.target.value)}>
                  <option value="">-- Office --</option>
                  {offices.map((o) => (
                    <option key={o.id} value={o.id}>{o.office_name}</option>
                  ))}
                </select>
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
              {loading ? "Submitting..." : "Submit RIS"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}