import { useEffect, useState } from "react";
import { api } from "../api/client";

export default function Archive({ type = "Asset" }) {
  const [products, setProducts] = useState([]);
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");
  const [query, setQuery] = useState("");

  useEffect(() => {
    load();
  }, [type]);

  function load() {
    api
      .get(`/products/archived?type=${type}`)
      .then(setProducts)
      .catch((e) => setError(e.message));
  }

  async function handleRestore(p) {
    if (!window.confirm(`Restore "${p.name}"?`)) return;
    try {
      await api.put(`/products/${p.pid}/restore`);
      setMsg(`"${p.name}" restored.`);
      load();
    } catch (e) {
      setError(e.message);
    }
  }

  async function handlePermanentDelete(p) {
    if (!window.confirm(`PERMANENTLY delete "${p.name}"? This cannot be undone!`)) return;
    try {
      await api.del(`/products/${p.pid}/permanent`);
      setMsg(`"${p.name}" permanently deleted.`);
      load();
    } catch (e) {
      setError(e.message);
    }
  }

  async function handleDeleteAllArchived() {
    if (!window.confirm(`PERMANENTLY delete ALL archived ${type.toLowerCase()}s? This cannot be undone!`)) return;
    try {
      const result = await api.del(`/products/archived/all?type=${type}`);
      setMsg(`Permanently deleted ${result.deleted} item(s).`);
      load();
    } catch (e) {
      setError(e.message);
    }
  }

  async function handleRestoreAll() {
    if (!window.confirm(`Restore ALL archived ${type.toLowerCase()}s?`)) return;
    try {
      for (const p of products) {
        await api.put(`/products/${p.pid}/restore`);
      }
      setMsg(`Restored ${products.length} item(s).`);
      load();
    } catch (e) {
      setError(e.message);
    }
  }

  const isAsset = type === "Asset";
  const visible = query.trim()
    ? products.filter((p) =>
        [p.name, p.brand, p.barcode, p.serial_number, p.category_name]
          .filter(Boolean)
          .some((v) => String(v).toLowerCase().includes(query.toLowerCase()))
      )
    : products;

  return (
    <div className="card">
      <div className="card-header">
        <h5>{isAsset ? "Asset Archive" : "Supply Archive"}</h5>
        <div className="flex">
          <span className="text-muted" style={{ fontSize: "0.85rem" }}>
            {products.length} archived item(s)
          </span>
          <input
            className="form-control"
            style={{ maxWidth: 260 }}
            placeholder="Search..."
            value={query}
            onChange={(e) => setQuery(e.target.value)}
          />
          {products.length > 0 && (
            <>
              <button className="btn btn-sm" onClick={handleRestoreAll}>
                ↩ Restore All
              </button>
              <button className="btn btn-danger btn-sm" onClick={handleDeleteAllArchived}>
                🗑 Permanent Delete All
              </button>
            </>
          )}
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
                <th>Category</th>
                {isAsset && <th>Serial No.</th>}
                <th>Unit</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {visible.map((p) => (
                <tr key={p.pid}>
                  <td>{p.barcode || "—"}</td>
                  <td><strong>{p.name}</strong></td>
                  <td>{p.brand}</td>
                  <td>{p.category_name}</td>
                  {isAsset && <td>{p.serial_number || "—"}</td>}
                  <td>{p.unit || "pcs"}</td>
                  <td>
                    <div className="btn-group">
                      <button className="btn btn-success btn-sm" title="Restore" onClick={() => handleRestore(p)}>
                        ↩ Restore
                      </button>
                      <button className="btn btn-danger btn-sm" title="Permanent Delete" onClick={() => handlePermanentDelete(p)}>
                        🗑 Delete
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
              {products.length === 0 && (
                <tr>
                  <td colSpan={isAsset ? 7 : 6} className="empty">
                    No archived {isAsset ? "assets" : "supplies"}.
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
