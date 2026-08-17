import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { api } from "../api/client";

export default function StockHistory() {
  const { id } = useParams();
  const [product, setProduct] = useState(null);
  const [history, setHistory] = useState([]);
  const [error, setError] = useState("");

  useEffect(() => {
    api.get(`/products/${id}`).then(setProduct).catch((e) => setError(e.message));
    api.get(`/products/${id}/history`).then(setHistory).catch((e) => setError(e.message));
  }, [id]);

  if (error && !product) return <div className="alert alert-error">{error}</div>;

  return (
    <div className="card">
      <div className="card-header">
        <h5>Stock History - {product?.name}</h5>
        <Link to={`/products/${id}`} className="btn btn-light btn-sm">Back</Link>
      </div>
      <div className="card-body">
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Type</th>
                <th>Quantity</th>
                <th>Date</th>
                <th>Remarks</th>
              </tr>
            </thead>
            <tbody>
              {history.map((h) => (
                <tr key={`${h.type}-${h.id}`}>
                  <td>
                    {h.type === "in" ? (
                      <span className="badge badge-ok">Stock In</span>
                    ) : (
                      <span className="badge badge-danger">Stock Out</span>
                    )}
                  </td>
                  <td>{h.type === "in" ? `+${h.quantity}` : `-${h.quantity}`}</td>
                  <td>{h.date}</td>
                  <td>{h.remarks || ""}</td>
                </tr>
              ))}
              {history.length === 0 && (
                <tr>
                  <td colSpan={4} className="empty">No movement recorded yet.</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}