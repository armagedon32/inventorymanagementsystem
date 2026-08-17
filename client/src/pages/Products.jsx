import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { api } from "../api/client";

export default function Products({ type = "Stock" }) {
  const isAsset = type === "Asset";
  const base = isAsset ? "/assets" : "/stock";
  const [products, setProducts] = useState([]);
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");

  useEffect(() => {
    load();
  }, [type]);

  function load() {
    api
      .get(`/products?type=${type}`)
      .then(setProducts)
      .catch((e) => setError(e.message));
  }

  async function handleDelete(p) {
    if (!window.confirm(`Archive "${p.name}"?`)) return;
    try {
      await api.del(`/products/${p.pid}`);
      setMsg(`"${p.name}" archived.`);
      load();
    } catch (e) {
      setError(e.message);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>{isAsset ? "Asset Management" : "Supplies / Stock Inventory"}</h5>
        <div className="flex">
          <span className="text-muted" style={{ fontSize: "0.85rem" }}>
            {products.length} item(s)
          </span>
          <Link to={`${base}/add`} className="btn btn-primary btn-sm">
            ✚ {isAsset ? "New Asset" : "New Supply"}
          </Link>
        </div>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}

        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>{isAsset ? "Asset Tag" : "Barcode"}</th>
                <th>Name</th>
                <th>Brand</th>
                {!isAsset && <th>Acq. Type</th>}
                <th>Category</th>
                {isAsset && <th>Serial No.</th>}
                {isAsset && <th>Condition</th>}
                {isAsset && <th>Assigned To</th>}
                {!isAsset && <th>Description</th>}
                {!isAsset && <th>Stock</th>}
                {!isAsset && <th>Reorder</th>}
                {!isAsset && <th>Status</th>}
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {products.map((p) => {
                const low = p.stock > 0 && p.stock <= p.reorder_level;
                const out = p.stock === 0;
                return (
                  <tr key={p.pid} className={!isAsset && (low || out) ? "low-stock" : ""}>
                    <td>{p.barcode || "—"}</td>
                    <td><strong>{p.name}</strong></td>
                    <td>{p.brand}</td>
                    {!isAsset && <td>{p.acquisition_type}</td>}
                    <td>{p.category_name}</td>
                    {isAsset && <td>{p.serial_number || "—"}</td>}
                    {isAsset && (
                      <td>
                        <span className={`badge ${p.condition === "Good" ? "badge-ok" : p.condition === "Needs Repair" ? "badge-warn" : "badge-danger"}`}>
                          {p.condition || "Good"}
                        </span>
                      </td>
                    )}
                    {isAsset && <td>{p.assigned_to || "—"}</td>}
                    {!isAsset && <td>{p.description}</td>}
                    {!isAsset && <td><strong>{p.stock}</strong></td>}
                    {!isAsset && <td>{p.reorder_level}</td>}
                    {!isAsset && (
                      <td>
                        {out ? (
                          <span className="badge badge-danger">Out</span>
                        ) : low ? (
                          <span className="badge badge-warn">Low</span>
                        ) : (
                          <span className="badge badge-ok">OK</span>
                        )}
                      </td>
                    )}
                    <td>
                      <div className="btn-group">
                        <Link to={`${base}/${p.pid}`} className="btn btn-warning btn-sm" title="View">
                          👁
                        </Link>
                        <Link to={`${base}/${p.pid}/edit`} className="btn btn-success btn-sm" title="Edit">
                          ✎
                        </Link>
                        {!isAsset && (
                          <>
                            <Link to={`${base}/${p.pid}/stock-in`} className="btn btn-info btn-sm" title="Restock">
                              ⬆ Stock In
                            </Link>
                            <Link to={`${base}/${p.pid}/stock-out`} className="btn btn-primary btn-sm" title="Issue">
                              ⬇ Issue
                            </Link>
                          </>
                        )}
                        <button className="btn btn-danger btn-sm" title="Archive" onClick={() => handleDelete(p)}>
                          🗑
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })}
              {products.length === 0 && (
                <tr>
                  <td colSpan={isAsset ? 8 : 10} className="empty">
                    No {isAsset ? "assets" : "supplies"} yet. Click "{isAsset ? "New Asset" : "New Supply"}".
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}