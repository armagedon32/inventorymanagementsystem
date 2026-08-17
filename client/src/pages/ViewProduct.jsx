import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { api } from "../api/client";

export default function ViewProduct() {
  const { id } = useParams();
  const [product, setProduct] = useState(null);
  const [error, setError] = useState("");

  useEffect(() => {
    api.get(`/products/${id}`).then(setProduct).catch((e) => setError(e.message));
  }, [id]);

  if (error) return <div className="alert alert-error">{error}</div>;
  if (!product) return <div className="empty">Loading...</div>;

  const rows = [
    ["Barcode", product.barcode],
    ["Item Name", product.name],
    ["Brand", product.brand],
    ["Acquisition Type", product.acquisition_type],
    ["Category", product.category_name],
    ["Description", product.description],
    ["Current Stock", product.stock],
    ["Reorder Level", product.reorder_level],
    ["Unit Cost", product.unit_cost != null ? "₱" + Number(product.unit_cost).toFixed(2) : "₱0.00"],
    ["Date Added", product.date_added],
  ];

  return (
    <div className="card">
      <div className="card-header">
        <h5>{product.name} - Details</h5>
        <div className="flex">
          <Link to={`/products/${id}/history`} className="btn btn-dark btn-sm">History</Link>
          <Link to={`/products/${id}/stock-in`} className="btn btn-info btn-sm">Stock In</Link>
          <Link to={`/products/${id}/stock-out`} className="btn btn-primary btn-sm">Issue</Link>
          <Link to={`/products/${id}/edit`} className="btn btn-success btn-sm">Edit</Link>
          <Link to="/products" className="btn btn-light btn-sm">Back</Link>
        </div>
      </div>
      <div className="card-body">
        <div className="form-grid">
          {rows.map(([label, value]) => (
            <div className="form-group" key={label}>
              <label>{label}</label>
              <input className="form-control" value={value ?? ""} readOnly />
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}