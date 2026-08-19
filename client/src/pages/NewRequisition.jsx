import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { api } from "../api/client";

export default function NewRequisition() {
  const navigate = useNavigate();
  const [products, setProducts] = useState([]);
  const [purpose, setPurpose] = useState("");
  const [lines, setLines] = useState([{ product_id: "", quantity: 1 }]);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    api.get("/products?type=Stock").then((rows) => setProducts(rows.filter((p) => p.stock > 0))).catch((e) => setError(e.message));
  }, []);

  function maxQty(productId) {
    const p = products.find((x) => x.pid === Number(productId));
    return p ? p.stock : 1;
  }

  function setLine(idx, field, value) {
    setLines((ls) => ls.map((l, i) => (i === idx ? { ...l, [field]: field === "quantity" ? Number(value) : value } : l)));
  }

  function handleQuantityChange(idx, value) {
    const v = Number(value);
    const max = maxQty(lines[idx].product_id);
    setLines((ls) => ls.map((l, i) => (i === idx ? { ...l, quantity: Math.min(v, max) } : l)));
  }

  function addLine() {
    setLines((ls) => [...ls, { product_id: "", quantity: 1 }]);
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
        .filter((l) => l.product_id && Number(l.quantity) > 0)
        .map((l) => ({ product_id: Number(l.product_id), quantity: Number(l.quantity) }));
      if (items.length === 0) {
        setError("Select at least one item with a valid quantity.");
        setLoading(false);
        return;
      }
      const r = await api.post("/requisitions", { purpose, items });
      navigate(`/requisitions/${r.id}`, { replace: true });
    } catch (e) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>New Requisition</h5>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        <form onSubmit={handleSubmit}>
          <div className="form-grid">
            <div className="form-group">
              <label>Purpose *</label>
              <input
                className="form-control"
                value={purpose}
                onChange={(e) => setPurpose(e.target.value)}
                placeholder='e.g. "Office supplies for enrollment period"'
                required
              />
            </div>
          </div>

          <h6 style={{ margin: "1rem 0 0.5rem" }}>Requested Items</h6>
          {lines.map((line, i) => (
            <div className="form-grid" key={i} style={{ alignItems: "end" }}>
              <div className="form-group">
                <label>Item</label>
                <select
                  className="form-select"
                  value={line.product_id}
                  onChange={(e) => setLine(i, "product_id", e.target.value)}
                  required
                >
                  <option value="">-- Select Item --</option>
                  {products.map((p) => (
                    <option key={p.pid} value={p.pid}>
                      {p.name}{p.brand ? ` (${p.brand})` : ""} — {p.stock} {p.unit || "pcs"} available
                    </option>
                  ))}
                </select>
              </div>
              <div className="form-group">
                <label>Quantity * (max {maxQty(line.product_id)})</label>
                <input
                  type="number"
                  min="1"
                  max={maxQty(line.product_id)}
                  className="form-control"
                  value={line.quantity}
                  onChange={(e) => handleQuantityChange(i, e.target.value)}
                  required
                />
              </div>
              <div className="form-group">
                <button type="button" className="btn btn-danger btn-sm" onClick={() => removeLine(i)} title="Remove line">
                  ✕
                </button>
              </div>
            </div>
          ))}
          <button type="button" className="btn btn-light btn-sm" onClick={addLine}>✚ Add Another Item</button>

          <div className="flex between mt-4">
            <button type="button" className="btn btn-light" onClick={() => navigate(-1)}>Cancel</button>
            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? "Submitting..." : "Submit Requisition"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}