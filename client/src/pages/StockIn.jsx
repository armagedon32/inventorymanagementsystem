import { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { api } from "../api/client";

export default function StockIn() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [product, setProduct] = useState(null);
  const [quantity, setQuantity] = useState(1);
  const [remarks, setRemarks] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    api.get(`/products/${id}`).then(setProduct).catch((e) => setError(e.message));
  }, [id]);

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      await api.post(`/products/${id}/stock-in`, { quantity: Number(quantity), remarks });
      navigate(`/products/${id}`, { replace: true });
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
        <h5>Restock - {product.name}</h5>
      </div>
      <div className="card-body">
        <div className="alert alert-success" style={{ background: "#e8f5e9", borderRadius: 12 }}>
          Current stock: <strong>{product.stock}</strong> · Reorder level: {product.reorder_level}
        </div>
        {error && <div className="alert alert-error">{error}</div>}
        <form onSubmit={handleSubmit}>
          <div className="form-grid">
            <div className="form-group">
              <label>Quantity to Add *</label>
              <input
                type="number"
                min="1"
                required
                className="form-control"
                value={quantity}
                onChange={(e) => setQuantity(Number(e.target.value))}
              />
            </div>
            <div className="form-group">
              <label>Remarks</label>
              <input className="form-control" value={remarks} onChange={(e) => setRemarks(e.target.value)} placeholder="e.g. new delivery" />
            </div>
          </div>
          <div className="flex between mt-4">
            <button type="button" className="btn btn-light" onClick={() => navigate(-1)}>Cancel</button>
            <button type="submit" className="btn btn-info" disabled={loading}>
              {loading ? "Processing..." : "Add Stock"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}