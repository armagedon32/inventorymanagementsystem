import { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { api } from "../api/client";

export default function StockOut() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [product, setProduct] = useState(null);
  const [offices, setOffices] = useState([]);
  const [instructors, setInstructors] = useState([]);
  const [quantity, setQuantity] = useState(1);
  const [officeId, setOfficeId] = useState("");
  const [instructorId, setInstructorId] = useState("");
  const [remarks, setRemarks] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    api.get(`/products/${id}`).then(setProduct).catch((e) => setError(e.message));
    api.get("/users/offices").then(setOffices).catch(() => {});
    api.get("/users/instructors").then(setInstructors).catch(() => {});
  }, [id]);

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      await api.post(`/products/${id}/stock-out`, {
        quantity: Number(quantity),
        office_id: officeId || null,
        instructor_id: instructorId || null,
        remarks,
      });
      navigate(`/stock/${id}`, { replace: true });
    } catch (e) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  }

  if (error && !product) return <div className="alert alert-error">{error}</div>;
  if (!product) return <div className="empty">Loading...</div>;

  return (
    <div className="card">
      <div className="card-header">
        <h5>Stock Out / Issue - {product.name}</h5>
      </div>
      <div className="card-body">
        <div className="alert alert-warning" style={{ background: "#fff3cd", borderRadius: 12, color: "#856404" }}>
          Available stock: <strong>{product.stock}</strong>
        </div>
        {error && <div className="alert alert-error">{error}</div>}
        <form onSubmit={handleSubmit}>
          <div className="form-grid">
            <div className="form-group">
              <label>Quantity to Issue *</label>
              <input
                type="number"
                min="1"
                max={product.stock}
                required
                className="form-control"
                value={quantity}
                onChange={(e) => setQuantity(Number(e.target.value))}
              />
            </div>
            <div className="form-group">
              <label>Issued To (Office)</label>
              <select className="form-select" value={officeId} onChange={(e) => setOfficeId(Number(e.target.value) || "")}>
                <option value="">-- Select Office --</option>
                {offices.map((o) => (
                  <option key={o.id} value={o.id}>{o.office_name}</option>
                ))}
              </select>
            </div>
            <div className="form-group">
              <label>Instructor / Personnel</label>
              <select className="form-select" value={instructorId} onChange={(e) => setInstructorId(Number(e.target.value) || "")}>
                <option value="">-- Select --</option>
                {instructors.map((i) => (
                  <option key={i.id} value={i.id}>{i.fullname}</option>
                ))}
              </select>
            </div>
            <div className="form-group">
              <label>Remarks</label>
              <input className="form-control" value={remarks} onChange={(e) => setRemarks(e.target.value)} placeholder="e.g. for enrollment" />
            </div>
          </div>
          <div className="flex between mt-4">
            <button type="button" className="btn btn-light" onClick={() => navigate(-1)}>Cancel</button>
            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? "Processing..." : "Issue Stock"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}