import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { api } from "../api/client";

export default function ViewProduct({ type = "Stock" }) {
  const isAsset = type === "Asset";
  const base = isAsset ? "/assets" : "/stock";
  const { id } = useParams();
  const [product, setProduct] = useState(null);
  const [error, setError] = useState("");

  useEffect(() => {
    api.get(`/products/${id}`).then(setProduct).catch((e) => setError(e.message));
  }, [id]);

  if (error) return <div className="alert alert-error">{error}</div>;
  if (!product) return <div className="empty">Loading...</div>;

  const rows = [
    ["Asset Tag / Barcode", product.barcode],
    ["Item Name", product.name],
    ["Brand", product.brand],
    ["Acquisition Type", product.acquisition_type],
    ["Category", product.category_name],
    ["Description", product.description],
    ["Unit", product.unit || "pcs"],
    ["Unit Cost", product.unit_cost != null ? "₱" + Number(product.unit_cost).toFixed(2) : "₱0.00"],
    ["Date Added", product.date_added],
  ];

  if (isAsset) {
    rows.splice(6, 0, ["Serial Number", product.serial_number || "—"]);
    rows.splice(6, 0, ["Condition", product.condition || "Good"]);
    rows.splice(6, 0, ["Quantity", product.stock]);
    rows.splice(6, 0, ["Assigned To", product.assigned_to || "—"]);
  } else {
    rows.splice(6, 0, ["Current Stock", product.stock]);
    rows.splice(7, 0, ["Reorder Level", product.reorder_level]);
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>{product.name} - Details</h5>
        <div className="flex">
          {!isAsset && (
            <>
              <Link to={`${base}/${id}/history`} className="btn btn-dark btn-sm">History</Link>
              <Link to={`${base}/${id}/stock-in`} className="btn btn-info btn-sm">Stock In</Link>
              <Link to={`${base}/${id}/stock-out`} className="btn btn-primary btn-sm">Issue</Link>
            </>
          )}
          <Link to={`${base}/${id}/edit`} className="btn btn-success btn-sm">Edit</Link>
          <Link to={base} className="btn btn-light btn-sm">Back</Link>
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