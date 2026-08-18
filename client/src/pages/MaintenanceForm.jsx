import { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { api } from "../api/client";

const empty = { item_name: "", office: "", brand: "", serial_number: "", maintenance_task: "", frequency_days: 0, next_maintenance_date: "" };

export default function MaintenanceForm() {
  const { id } = useParams();
  const navigate = useNavigate();
  const editing = Boolean(id);
  const [form, setForm] = useState(empty);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!editing) return;
    api.get(`/maintenance`).then((rows) => {
      const m = rows.find((r) => r.id === Number(id));
      if (m) setForm({
        item_name: m.item_name, office: m.office || "", brand: m.brand || "",
        serial_number: m.serial_number || "", maintenance_task: m.maintenance_task || "",
        frequency_days: m.frequency_days || 0, next_maintenance_date: m.next_maintenance_date || "",
      });
    }).catch((e) => setError(e.message));
  }, [id, editing]);

  const set = (k, v) => setForm((f) => ({ ...f, [k]: v }));

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      const body = { ...form, frequency_days: Number(form.frequency_days) || 0 };
      if (editing) await api.put(`/maintenance/${id}`, body);
      else await api.post("/maintenance", body);
      navigate("/maintenance", { replace: true });
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>{editing ? "Edit Maintenance Record" : "New Maintenance Record"}</h5>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        <form onSubmit={handleSubmit}>
          <div className="form-grid">
            <div className="form-group">
              <label>Item Name *</label>
              <input className="form-control" value={form.item_name} onChange={(e) => set("item_name", e.target.value)} required />
            </div>
            <div className="form-group">
              <label>Office</label>
              <input className="form-control" value={form.office} onChange={(e) => set("office", e.target.value)} />
            </div>
            <div className="form-group">
              <label>Brand</label>
              <input className="form-control" value={form.brand} onChange={(e) => set("brand", e.target.value)} />
            </div>
            <div className="form-group">
              <label>Serial Number</label>
              <input className="form-control" value={form.serial_number} onChange={(e) => set("serial_number", e.target.value)} />
            </div>
            <div className="form-group">
              <label>Maintenance Task</label>
              <input className="form-control" value={form.maintenance_task} onChange={(e) => set("maintenance_task", e.target.value)} placeholder='e.g. "Lens cleaning and lamp check"' />
            </div>
            <div className="form-group">
              <label>Frequency (days)</label>
              <input type="number" min="0" className="form-control" value={form.frequency_days} onChange={(e) => set("frequency_days", e.target.value)} />
            </div>
            <div className="form-group">
              <label>Next Maintenance Date</label>
              <input type="date" className="form-control" value={form.next_maintenance_date} onChange={(e) => set("next_maintenance_date", e.target.value)} />
            </div>
          </div>
          <div className="flex between mt-4">
            <button type="button" className="btn btn-light" onClick={() => navigate(-1)}>Cancel</button>
            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? "Saving..." : editing ? "Save Changes" : "Create Record"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}