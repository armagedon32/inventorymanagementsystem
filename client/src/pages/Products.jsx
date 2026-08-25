import { useEffect, useState, useMemo } from "react";
import { Link } from "react-router-dom";
import { api, API_URL } from "../api/client";
import { exportCSV, exportPDF } from "../utils/export";
import { useAuth } from "../context/AuthContext";

export default function Products({ type = "Stock" }) {
  const { user } = useAuth();
  const isSuperAdmin = user?.username === "superadmin";
  const isAdmin = user?.role === "Admin";
  const isAsset = type === "Asset";
  const base = isAsset ? "/assets" : "/stock";
  const [products, setProducts] = useState([]);
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");
  const [query, setQuery] = useState("");
  const [importMsg, setImportMsg] = useState("");
  const [importResult, setImportResult] = useState(null);
  const [page, setPage] = useState(1);
  const pageSize = 50;

  useEffect(() => {
    load();
  }, [type]);

  function load() {
    api
      .get(`/products?type=${type}`)
      .then(setProducts)
      .catch((e) => setError(e.message));
  }

  async function downloadTemplate() {
    try {
      const endpoint = isAsset ? "/products/import-template" : "/products/import-template/stock";
      const filename = isAsset ? "asset-import-template.xlsx" : "supply-import-template.xlsx";
      const res = await fetch(`${API_URL}${endpoint}`, {
        headers: { Authorization: `Bearer ${localStorage.getItem("custodian_token")}` },
      });
      const blob = await res.blob();
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = filename;
      a.click();
      URL.revokeObjectURL(url);
    } catch {
      setError("Failed to download template.");
    }
  }

  async function handleBulkImport(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    setError("");
    setImportMsg("Importing...");
    setImportResult(null);
    try {
      const base64 = await new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result.split(",")[1]);
        reader.onerror = reject;
        reader.readAsDataURL(file);
      });
      const endpoint = isAsset ? "/products/import" : "/products/import/stock";
      const result = await api.post(endpoint, { csv: base64 });
      setImportResult(result);
      setImportMsg(`Imported: ${result.imported}, Skipped: ${result.skipped}`);
      if (result.errors?.length) {
        setImportMsg((m) => m + "\n" + result.errors.join("\n"));
      }
      load();
    } catch (err) {
      setError(err.message);
      setImportMsg("");
    }
  }

  async function handleDeleteAll() {
    if (!window.confirm(`Delete ALL ${isAsset ? "assets" : "supplies"}? This cannot be undone.`)) return;
    try {
      const result = await api.del(`/products/all?type=${type}`);
      setMsg(`Deleted ${result.deleted} item(s).`);
      load();
    } catch (e) {
      setError(e.message);
    }
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

  const statusOf = (p) => {
    if (p.stock === 0) return "Out of Stock";
    if (p.stock <= p.reorder_level) return "Low";
    return "OK";
  };

  const exportColumns = isAsset
    ? [
        { label: "Asset Tag", key: "barcode" },
        { label: "Name", key: "name" },
        { label: "Brand", key: "brand" },
        { label: "Category", key: "category_name" },
        { label: "Serial No.", key: "serial_number" },
        { label: "Condition", key: "condition" },
        { label: "Assigned To", key: "assigned_to" },
        { label: "Unit", key: "unit" },
        { label: "Quantity", key: "stock" },
        { label: "Unit Cost", key: "unit_cost" },
      ]
    : [
        { label: "Barcode", key: "barcode" },
        { label: "Name", key: "name" },
        { label: "Brand", key: "brand" },
        { label: "Acquisition Type", key: "acquisition_type" },
        { label: "Category", key: "category_name" },
        { label: "Description", key: "description" },
        { label: "Unit", key: "unit" },
        { label: "Stock", key: "stock" },
        { label: "Reorder Level", key: "reorder_level" },
        { label: "Unit Cost", key: "unit_cost" },
        { label: "Status", key: "__status" },
      ];

  const exportRows = isAsset
    ? products
    : products.map((p) => ({ ...p, __status: statusOf(p) }));

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return products;
    return products.filter((p) =>
      [p.name, p.brand, p.barcode, p.serial_number, p.assigned_to, p.category_name]
        .filter(Boolean)
        .some((v) => String(v).toLowerCase().includes(q))
    );
  }, [products, query]);

  useEffect(() => {
    setPage(1);
  }, [query]);

  const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
  const safePage = Math.min(page, totalPages);
  const visible = filtered.slice((safePage - 1) * pageSize, safePage * pageSize);

  function getPageNumbers(current, total) {
    const maxButtons = 7;
    if (total <= maxButtons) return Array.from({ length: total }, (_, i) => i + 1);
    let start = Math.max(1, current - 3);
    const end = Math.min(total, start + maxButtons - 1);
    start = Math.max(1, end - maxButtons + 1);
    const nums = [];
    if (start > 1) nums.push(1, "…");
    for (let i = start; i <= end; i++) nums.push(i);
    if (end < total) nums.push("…", total);
    return nums;
  }
  const pageNumbers = getPageNumbers(safePage, totalPages);

  function handleExport(format) {
    const title = isAsset ? "Asset Management List" : "Stock Inventory List";
    const filename = `${isAsset ? "assets" : "stock-inventory"}-${new Date().toISOString().slice(0, 10)}`;
    if (format === "pdf") exportPDF({ title, filename: `${filename}.pdf`, headers: exportColumns, rows: exportRows });
    else exportCSV({ filename: `${filename}.csv`, headers: exportColumns, rows: exportRows });
  }

  async function fixAssetStock() {
    if (!confirm("Set stock = 1 for all assets with stock = 0?")) return;
    try {
      const r = await api.get("/products/fix-asset-stock");
      alert(`Fixed ${r.updated} asset(s).`);
      load();
    } catch (e) {
      setError(e.message);
    }
  }

  async function fixDuplicateBarcodes() {
    if (!confirm("Archive all duplicates and items with -A suffix? Only original copies will remain.")) return;
    try {
      const r = await api.get("/products/fix-duplicate-barcodes");
      alert(`Barcode dupes: ${r.barcodeDupes}, Serial dupes: ${r.serialDupes}, Dash-A items: ${r.dashAItems}. Archived ${r.deleted} total.`);
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
          <input
            className="form-control"
            style={{ maxWidth: 260 }}
            placeholder={isAsset ? "Search serial / tag / assigned..." : "Search name / barcode..."}
            value={query}
            onChange={(e) => setQuery(e.target.value)}
          />
          {isAdmin && isAsset && (
            <>
              <button type="button" className="btn btn-sm" onClick={downloadTemplate}>
                ⬇ Template
              </button>
              <label className="btn btn-sm" style={{ cursor: "pointer" }}>
                ⬆ Import
                <input type="file" accept=".xlsx,.xls" style={{ display: "none" }} onChange={handleBulkImport} />
              </label>
            </>
          )}
          {isAdmin && (
            <>
              <button className="btn btn-sm" title="Export to Excel (CSV)" onClick={() => handleExport("excel")}>
                ⤓ Excel
              </button>
              <button className="btn btn-sm" title="Export to PDF (print)" onClick={() => handleExport("pdf")}>
                ⤓ PDF
              </button>
            </>
          )}
          {isSuperAdmin && products.length > 0 && isAsset && (
            <>
              <button className="btn btn-warning btn-sm" title="Set stock=1 for all assets with stock=0" onClick={fixAssetStock}>
                🔧 Fix Stock
              </button>
              <button className="btn btn-warning btn-sm" title="Fix duplicate barcodes" onClick={fixDuplicateBarcodes}>
                🔧 Fix Dupes
              </button>
            </>
          )}
          {isAdmin && products.length > 0 && (
            <button className="btn btn-danger btn-sm" title="Delete All" onClick={handleDeleteAll}>
              🗑 Delete All
            </button>
          )}
          {isAdmin && (
            <Link to={`${base}/add`} className="btn btn-primary btn-sm">
              ✚ {isAsset ? "New Asset" : "New Supply"}
            </Link>
          )}
        </div>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}
        {importMsg && <div className="alert alert-success" style={{ whiteSpace: "pre-wrap" }}>{importMsg}</div>}
        {importResult && importResult.imported > 0 && (
          <div className="alert alert-success">
            Successfully imported {importResult.imported} item(s).
            {importResult.skipped > 0 && ` ${importResult.skipped} row(s) skipped.`}
          </div>
        )}

        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                {isAsset && <th>Asset Tag</th>}
                <th>Name</th>
                <th>Brand</th>
                <th>Category</th>
                <th>Unit</th>
                {isAsset && <th>Serial No.</th>}
                {isAsset && <th>Condition</th>}
                {isAsset && <th>Assigned To</th>}
                {isAsset && <th>Office</th>}
                {!isAsset && <th>Description</th>}
                {!isAsset && <th>Stock</th>}
                {!isAsset && <th>Reorder</th>}
                {!isAsset && <th>Status</th>}
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {visible.map((p) => {
                const low = p.stock > 0 && p.stock <= p.reorder_level;
                const out = p.stock === 0;
                return (
                  <tr key={p.pid} className={!isAsset && (low || out) ? "low-stock" : ""}>
                    {isAsset && <td>{p.barcode || "—"}</td>}
                    <td><strong>{p.name}</strong></td>
                    <td>{p.brand}</td>
                    <td>{p.category_name}</td>
                    <td>{p.unit || "pcs"}</td>
                    {isAsset && <td>{p.serial_number || "—"}</td>}
                    {isAsset && (
                      <td>
                        <span className={`badge ${p.condition === "Good" ? "badge-ok" : p.condition === "Needs Repair" ? "badge-warn" : "badge-danger"}`}>
                          {p.condition || "Good"}
                        </span>
                      </td>
                    )}
                    {isAsset && <td>{p.assigned_to || "—"}</td>}
                    {isAsset && <td>{p.department || "—"}</td>}
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
                        {isAdmin && (
                          <>
                            <Link to={`${base}/${p.pid}/edit`} className="btn btn-success btn-sm" title="Edit">
                              ✎
                            </Link>
                            {isAsset && (
                              <Link to={`${base}/${p.pid}/assign`} className="btn btn-dark btn-sm" title="Assign">
                                ⇄ Assign
                              </Link>
                            )}
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
                          </>
                        )}
                      </div>
                    </td>
                  </tr>
                );
              })}
              {filtered.length === 0 && (
                <tr>
                  <td colSpan={isAsset ? 10 : 11} className="empty">
                    {query.trim()
                      ? `No ${isAsset ? "assets" : "supplies"} match your search.`
                      : `No ${isAsset ? "assets" : "supplies"} yet. Click "${isAsset ? "New Asset" : "New Supply"}".`}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        <div
          className="flex between"
          style={{ marginTop: 12, alignItems: "center", flexWrap: "wrap", gap: 8 }}
        >
          <span className="text-muted" style={{ fontSize: "0.85rem" }}>
            {filtered.length === 0
              ? "No items"
              : `Showing ${(safePage - 1) * pageSize + 1}–${Math.min(safePage * pageSize, filtered.length)} of ${filtered.length} item(s)`}
          </span>
          <div className="flex" style={{ gap: 4 }}>
            <button
              type="button"
              className="btn btn-light btn-sm"
              disabled={safePage === 1}
              onClick={() => setPage(1)}
              title="First page"
            >
              «
            </button>
            <button
              type="button"
              className="btn btn-light btn-sm"
              disabled={safePage === 1}
              onClick={() => setPage(safePage - 1)}
            >
              ‹ Prev
            </button>
            {pageNumbers.map((n, i) =>
              n === "…" ? (
                <span key={`ellipsis-${i}`} className="text-muted" style={{ padding: "0 4px", lineHeight: "28px" }}>
                  …
                </span>
              ) : (
                <button
                  key={n}
                  type="button"
                  className={`btn btn-sm ${n === safePage ? "btn-primary" : "btn-light"}`}
                  onClick={() => setPage(n)}
                >
                  {n}
                </button>
              )
            )}
            <button
              type="button"
              className="btn btn-light btn-sm"
              disabled={safePage >= totalPages}
              onClick={() => setPage(safePage + 1)}
            >
              Next ›
            </button>
            <button
              type="button"
              className="btn btn-light btn-sm"
              disabled={safePage >= totalPages}
              onClick={() => setPage(totalPages)}
              title="Last page"
            >
              »
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
