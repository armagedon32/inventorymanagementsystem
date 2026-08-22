import { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { api } from "../api/client";

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

export default function AssignAsset() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [asset, setAsset] = useState(null);
  const [offices, setOffices] = useState([]);
  const [instructors, setInstructors] = useState([]);
  const [officeId, setOfficeId] = useState("");
  const [instructorId, setInstructorId] = useState("");
  const [department, setDepartment] = useState("");
  const [remarks, setRemarks] = useState("");
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    api.get(`/products/${id}`).then((a) => {
      setAsset(a);
      setDepartment(a.department || "");
    }).catch((e) => setError(e.message));
    api.get("/users/offices").then(setOffices).catch(() => {});
    api.get("/users/instructors").then(setInstructors).catch(() => {});
  }, [id]);

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      await api.post(`/products/${id}/assign`, {
        office_id: officeId || null,
        instructor_id: instructorId || null,
        department: department || null,
        remarks,
      });
      navigate(`/assets/${id}`, { replace: true });
    } catch (e) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  }

  async function handleUnassign() {
    if (!window.confirm(`Unassign ${asset.name} (${asset.serial_number || "no serial"})? The assignment history will be kept.`)) return;
    setError("");
    setMsg("");
    try {
      await api.post(`/products/${id}/unassign`);
      setMsg(`${asset.name} unassigned.`);
      api.get(`/products/${id}`).then((a) => {
        setAsset(a);
        setDepartment(a.department || "");
      });
    } catch (e) {
      setError(e.message);
    }
  }

  if (error && !asset) return <div className="alert alert-error">{error}</div>;
  if (!asset) return <div className="empty">Loading...</div>;

  return (
    <div className="card">
      <div className="card-header">
        <h5>Assign Asset - {asset.name}</h5>
      </div>
      <div className="card-body">
        <div className="alert alert-warning" style={{ background: "#fff3cd", borderRadius: 12, color: "#856404" }}>
          <strong>Unit:</strong> {asset.serial_number || "—"} (Serial No.) &nbsp;•&nbsp; <strong>Tag:</strong> {asset.barcode || "—"}
          <br />
          {asset.assigned_to
            ? `Currently assigned to: <strong>${asset.assigned_to}</strong>${asset.assigned_date ? ` (since ${asset.assigned_date})` : ""}`
            : "This asset is not assigned yet."}
        </div>
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}
        <form onSubmit={handleSubmit}>
          <div className="form-grid">
            <div className="form-group">
              <label>Assign To (Office)</label>
              <select className="form-select" value={officeId} onChange={(e) => setOfficeId(Number(e.target.value) || "")}>
                <option value="">-- Select Office --</option>
                {offices.map((o) => (
                  <option key={o.id} value={o.id}>{o.office_name}</option>
                ))}
              </select>
            </div>
            <div className="form-group">
              <label>Personnel / Employee</label>
              <select className="form-select" value={instructorId} onChange={(e) => setInstructorId(Number(e.target.value) || "")}>
                <option value="">-- Select --</option>
                {instructors.map((i) => (
                  <option key={i.id} value={i.id}>{i.fullname}</option>
                ))}
              </select>
            </div>
            <div className="form-group">
              <label>Office *</label>
              <select className="form-select" value={department} onChange={(e) => setDepartment(e.target.value)} required>
                <option value="">-- Select --</option>
                {OFFICES.map((d) => (
                  <option key={d}>{d}</option>
                ))}
              </select>
            </div>
            <div className="form-group">
              <label>Remarks</label>
              <input className="form-control" value={remarks} onChange={(e) => setRemarks(e.target.value)} placeholder="e.g. assigned for Comlab use" />
            </div>
          </div>
          <div className="flex between mt-4">
            <div className="flex">
              {asset.assigned_to && (
                <button type="button" className="btn btn-danger" onClick={handleUnassign}>↩ Unassign</button>
              )}
              <button type="button" className="btn btn-light" onClick={() => navigate(-1)}>Cancel</button>
            </div>
            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? "Saving..." : "Assign Asset"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}