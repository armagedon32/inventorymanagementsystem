import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { api } from "../api/client";

export default function NewFacility() {
  const navigate = useNavigate();
  const [rooms, setRooms] = useState([]);
  const [orgs, setOrgs] = useState([]);
  const [assets, setAssets] = useState([]);
  const [form, setForm] = useState({
    office_or_org: "", requesting_name: "", contact_no: "", address: "",
    date_of_filing: new Date().toISOString().slice(0, 10),
    event_name: "", num_participants: 0, start_datetime: "", end_datetime: "", facility_id: "",
  });
  const [lines, setLines] = useState([{ asset_id: "", quantity: 1 }]);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    api.get("/rooms").then(setRooms).catch((e) => setError(e.message));
    api.get("/master/organizations").then(setOrgs).catch((e) => setError(e.message));
    api.get("/products?type=Asset").then(setAssets).catch((e) => setError(e.message));
  }, []);

  const set = (k, v) => setForm((f) => ({ ...f, [k]: v }));

  function setLine(idx, field, value) {
    setLines((ls) => ls.map((l, i) => (i === idx ? { ...l, [field]: field === "quantity" ? Number(value) : value } : l)));
  }

  function addLine() {
    setLines((ls) => [...ls, { asset_id: "", quantity: 1 }]);
  }

  function removeLine(idx) {
    setLines((ls) => (ls.length > 1 ? ls.filter((_, i) => i !== idx) : ls));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      const equipment = lines
        .filter((l) => l.asset_id && Number(l.quantity) > 0)
        .map((l) => ({ asset_id: Number(l.asset_id), quantity: Number(l.quantity) }));
      const r = await api.post("/facilities", {
        ...form,
        facility_id: Number(form.facility_id),
        num_participants: Number(form.num_participants) || 0,
        equipment,
      });
      navigate(`/facilities/${r.id}`, { replace: true });
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>New Facility &amp; Equipment Request</h5>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        <form onSubmit={handleSubmit}>
          <h6 style={{ margin: "0 0 0.5rem" }}>Requesting Party</h6>
          <div className="form-grid">
            <div className="form-group">
              <label>Office / Organization</label>
              <input className="form-control" list="org-list" value={form.office_or_org} onChange={(e) => set("office_or_org", e.target.value)} />
              <datalist id="org-list">
                {orgs.map((o) => <option key={o.id} value={o.org_name} />)}
              </datalist>
            </div>
            <div className="form-group">
              <label>Requesting Name</label>
              <input className="form-control" value={form.requesting_name} onChange={(e) => set("requesting_name", e.target.value)} />
            </div>
            <div className="form-group">
              <label>Contact No.</label>
              <input className="form-control" value={form.contact_no} onChange={(e) => set("contact_no", e.target.value)} />
            </div>
            <div className="form-group">
              <label>Address</label>
              <input className="form-control" value={form.address} onChange={(e) => set("address", e.target.value)} />
            </div>
            <div className="form-group">
              <label>Date of Filing</label>
              <input type="date" className="form-control" value={form.date_of_filing} onChange={(e) => set("date_of_filing", e.target.value)} />
            </div>
          </div>

          <h6 style={{ margin: "1rem 0 0.5rem" }}>Event &amp; Facility</h6>
          <div className="form-grid">
            <div className="form-group">
              <label>Event Name *</label>
              <input className="form-control" value={form.event_name} onChange={(e) => set("event_name", e.target.value)} required />
            </div>
            <div className="form-group">
              <label>Facility / Room *</label>
              <select className="form-select" value={form.facility_id} onChange={(e) => set("facility_id", e.target.value)} required>
                <option value="">-- Select Room --</option>
                {rooms.map((r) => (
                  <option key={r.id} value={r.id}>{r.room_name} ({r.location || "—"})</option>
                ))}
              </select>
            </div>
            <div className="form-group">
              <label>No. of Participants</label>
              <input type="number" min="0" className="form-control" value={form.num_participants} onChange={(e) => set("num_participants", e.target.value)} />
            </div>
            <div className="form-group">
              <label>Start Date/Time</label>
              <input type="datetime-local" className="form-control" value={form.start_datetime} onChange={(e) => set("start_datetime", e.target.value)} />
            </div>
            <div className="form-group">
              <label>End Date/Time</label>
              <input type="datetime-local" className="form-control" value={form.end_datetime} onChange={(e) => set("end_datetime", e.target.value)} />
            </div>
          </div>

          <h6 style={{ margin: "1rem 0 0.5rem" }}>Borrowed Equipment</h6>
          {lines.map((line, i) => (
            <div className="form-grid" key={i} style={{ alignItems: "end" }}>
              <div className="form-group grow">
                <label>Asset</label>
                <select className="form-select" value={line.asset_id} onChange={(e) => setLine(i, "asset_id", e.target.value)}>
                  <option value="">-- Select Asset --</option>
                  {assets.map((a) => (
                    <option key={a.pid} value={a.pid}>
                      {a.name} ({a.serial_number || a.barcode}) — {a.stock} unit(s)
                    </option>
                  ))}
                </select>
              </div>
              <div className="form-group">
                <label>Quantity</label>
                <input type="number" min="1" className="form-control" value={line.quantity} onChange={(e) => setLine(i, "quantity", e.target.value)} />
              </div>
              <div className="form-group">
                <button type="button" className="btn btn-danger btn-sm" onClick={() => removeLine(i)} title="Remove line">✕</button>
              </div>
            </div>
          ))}
          <button type="button" className="btn btn-light btn-sm" onClick={addLine}>✚ Add Equipment</button>

          <div className="flex between mt-4">
            <button type="button" className="btn btn-light" onClick={() => navigate(-1)}>Cancel</button>
            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? "Submitting..." : "Submit Request"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}