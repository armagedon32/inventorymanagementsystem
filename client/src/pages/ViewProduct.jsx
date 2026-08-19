import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { api } from "../api/client";

export default function ViewProduct({ type = "Stock" }) {
  const isAsset = type === "Asset";
  const base = isAsset ? "/assets" : "/stock";
  const { id } = useParams();
  const [product, setProduct] = useState(null);
  const [assignments, setAssignments] = useState([]);
  const [error, setError] = useState("");

  useEffect(() => {
    api.get(`/products/${id}`).then(setProduct).catch((e) => setError(e.message));
    if (isAsset) {
      api.get(`/products/${id}/assignments`).then(setAssignments).catch(() => {});
    }
  }, [id, isAsset]);

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
    rows.splice(6, 0, ["Department", product.department || "—"]);
    rows.splice(6, 0, ["Assigned To", product.assigned_to || "—"]);
    rows.splice(9, 0, ["Assigned Remarks", product.assigned_remarks || "—"]);
    rows.splice(10, 0, ["Date Assigned", product.assigned_date || "—"]);
  } else {
    rows.splice(6, 0, ["Department", product.department || "—"]);
    rows.splice(7, 0, ["Current Stock", product.stock]);
    rows.splice(8, 0, ["Reorder Level", product.reorder_level]);
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>{product.name} - Details</h5>
        <div className="flex">
          {isAsset ? (
            <Link to={`${base}/${id}/assign`} className="btn btn-dark btn-sm">⇄ Assign</Link>
          ) : (
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

        {isAsset && (
          <div className="mt-4">
            <h6 style={{ marginBottom: "0.5rem" }}>Assignment History</h6>
            <div className="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Serial No.</th>
                    <th>Assigned To</th>
                    <th>Department</th>
                    <th>Remarks</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  {assignments.map((a, i) => (
                    <tr key={a.id}>
                      <td>{i + 1}</td>
                      <td>{a.serial_number || product.serial_number || "—"}</td>
                      <td><strong>{a.assigned_to}</strong></td>
                      <td>{a.department || "—"}</td>
                      <td>{a.remarks || "—"}</td>
                      <td>{a.date_assigned}</td>
                    </tr>
                  ))}
                  {assignments.length === 0 && (
                    <tr>
                      <td colSpan={6} className="empty">No assignments yet.</td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}