import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { api } from "../api/client";

export default function NewPtr() {
  const navigate = useNavigate();
  const [offices, setOffices] = useState([]);
  const [assets, setAssets] = useState([]);
  const [fromOffice, setFromOffice] = useState("");
  const [toOffice, setToOffice] = useState("");
  const [transferDate, setTransferDate] = useState(new Date().toISOString().slice(0, 10));
  const [remarks, setRemarks] = useState("");
  const [lines, setLines] = useState([{ asset_id: "" }]);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    api.get("/master/offices").then(setOffices).catch((e) => setError(e.message));
    api.get("/products?type=Asset").then(setAssets).catch((e) => setError(e.message));
  }, []);

  const sourceAssets = assets.filter((a) => a.office_id && Number(a.office_id) === Number(fromOffice));
  const targetOffices = offices.filter((o) => o.id !== Number(fromOffice));

  function setLine(idx, value) {
    setLines((ls) => ls.map((l, i) => (i === idx ? { asset_id: value } : l)));
  }

  function addLine() {
    setLines((ls) => [...ls, { asset_id: "" }]);
  }

  function removeLine(idx) {
    setLines((ls) => (ls.length > 1 ? ls.filter((_, i) => i !== idx) : ls));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      const items = lines.filter((l) => l.asset_id).map((l) => ({ asset_id: Number(l.asset_id) }));
      if (items.length === 0) {
        setError("Select at least one asset to transfer.");
        setLoading(false);
        return;
      }
      const r = await api.post("/ptr", {
        from_office: Number(fromOffice),
        to_office: Number(toOffice),
        transfer_date: transferDate,
        remarks,
        items,
      });
      navigate(`/ptr/${r.id}`, { replace: true });
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>New PTR (Property Transfer Receipt)</h5>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        <form onSubmit={handleSubmit}>
          <div className="form-grid">
            <div className="form-group">
              <label>From Office *</label>
              <select
                className="form-select"
                value={fromOffice}
                onChange={(e) => { setFromOffice(e.target.value); setLines([{ asset_id: "" }]); }}
                required
              >
                <option value="">-- Select Source Office --</option>
                {offices.map((o) => (
                  <option key={o.id} value={o.id}>{o.office_name}</option>
                ))}
              </select>
            </div>
            <div className="form-group">
              <label>To Office *</label>
              <select className="form-select" value={toOffice} onChange={(e) => setToOffice(e.target.value)} required>
                <option value="">-- Select Destination Office --</option>
                {targetOffices.map((o) => (
                  <option key={o.id} value={o.id}>{o.office_name}</option>
                ))}
              </select>
            </div>
            <div className="form-group">
              <label>Transfer Date</label>
              <input type="date" className="form-control" value={transferDate} onChange={(e) => setTransferDate(e.target.value)} />
            </div>
          </div>
          <div className="form-group">
            <label>Remarks</label>
            <input className="form-control" value={remarks} onChange={(e) => setRemarks(e.target.value)} />
          </div>

          <h6 style={{ margin: "1rem 0 0.5rem" }}>Assets to Transfer (owned by source office)</h6>
          {!fromOffice && <div className="text-muted" style={{ fontSize: "0.85rem", marginBottom: "8px" }}>Select a source office first.</div>}
          {lines.map((line, i) => (
            <div className="form-grid" key={i} style={{ alignItems: "end" }}>
              <div className="form-group grow">
                <label>Asset</label>
                <select className="form-select" value={line.asset_id} onChange={(e) => setLine(i, e.target.value)} required disabled={!fromOffice}>
                  <option value="">-- Select Asset --</option>
                  {sourceAssets.map((a) => (
                    <option key={a.pid} value={a.pid}>
                      {a.name} ({a.serial_number || a.barcode}) — {a.stock} unit(s)
                    </option>
                  ))}
                </select>
              </div>
              <div className="form-group">
                <button type="button" className="btn btn-danger btn-sm" onClick={() => removeLine(i)} title="Remove line">✕</button>
              </div>
            </div>
          ))}
          <button type="button" className="btn btn-light btn-sm" onClick={addLine} disabled={!fromOffice}>✚ Add Another Asset</button>

          <div className="flex between mt-4">
            <button type="button" className="btn btn-light" onClick={() => navigate(-1)}>Cancel</button>
            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? "Submitting..." : "Submit PTR"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}