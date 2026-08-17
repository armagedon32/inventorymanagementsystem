import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { api } from "../api/client";

export default function Products() {
  const [products, setProducts] = useState([]);
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");

  useEffect(() => {
    load();
  }, []);

  function load() {
    api
      .get("/products")
      .then(setProducts)
      .catch((e) => setError(e.message));
  }

  async function handleDelete(p) {
    if (!window.confirm(`Archive product "${p.name}"?`)) return;
    try {
      await api.del(`/products/${p.pid}`);
      setMsg(`Product "${p.name}" archived.`);
      load();
    } catch (e) {
      setError(e.message);
    }
  }

  return (
    <div className="card">
      <div className="card-header">
        <h5>Supplies</h5>
        <div className="flex">
          <span className="text-muted" style={{ fontSize: "0.85rem" }}>
            {products.length} item(s)
          </span>
          <Link to="/products/add" className="btn btn-primary btn-sm">
            ✚ New Supply
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
                <th>Barcode</th>
                <th>Name</th>
                <th>Brand</th>
                <th>Acq. Type</th>
                <th>Category</th>
                <th>Description</th>
                <th>Stock</th>
                <th>Reorder</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {products.map((p) => {
                const low = p.stock > 0 && p.stock <= p.reorder_level;
                const out = p.stock === 0;
                return (
                  <tr key={p.pid} className={low || out ? "low-stock" : ""}>
                    <td>{p.barcode}</td>
                    <td><strong>{p.name}</strong></td>
                    <td>{p.brand}</td>
                    <td>{p.acquisition_type}</td>
                    <td>{p.category_name}</td>
                    <td>{p.description}</td>
                    <td><strong>{p.stock}</strong></td>
                    <td>{p.reorder_level}</td>
                    <td>
                      {out ? (
                        <span className="badge badge-danger">Out</span>
                      ) : low ? (
                        <span className="badge badge-warn">Low</span>
                      ) : (
                        <span className="badge badge-ok">OK</span>
                      )}
                    </td>
                    <td>
                      <div className="btn-group">
                        <Link to={`/products/${p.pid}`} className="btn btn-warning btn-sm" title="View">
                          👁
                        </Link>
                        <Link to={`/products/${p.pid}/edit`} className="btn btn-success btn-sm" title="Edit">
                          ✎
                        </Link>
                        <Link to={`/products/${p.pid}/stock-in`} className="btn btn-info btn-sm" title="Restock">
                          ⬆ Stock In
                        </Link>
                        <Link to={`/products/${p.pid}/stock-out`} className="btn btn-primary btn-sm" title="Issue">
                          ⬇ Issue
                        </Link>
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
                  <td colSpan={10} className="empty">No supplies yet. Click "New Supply".</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}