import { useEffect, useState } from "react";
import { Link, useParams, useNavigate } from "react-router-dom";
import { api } from "../api/client";
import { useAuth } from "../context/AuthContext";
import { printDoc } from "../utils/print";

const RETURN_OPTIONS = ["Excellent", "Good", "Slightly Damaged", "Broken", "Missing"];

const statusBadge = (s) => {
  if (s === "Returned") return <span className="badge badge-ok">{s}</span>;
  if (s === "Overdue") return <span className="badge badge-danger">{s}</span>;
  return <span className="badge badge-warn">{s}</span>;
};

export default function RisView() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const isAdmin = user?.role === "Admin";
  const [ris, setRis] = useState(null);
  const [settings, setSettings] = useState({});
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");
  const [returns, setReturns] = useState([]);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    api.get(`/ris/${id}`).then((r) => {
      setRis(r);
      setReturns(r.items.map((it) => ({ id: it.id, return_condition: "", return_remarks: "" })));
    }).catch((e) => setError(e.message));
    api.get("/settings").then(setSettings).catch(() => {});
  }, [id]);

  function setReturn(itemId, field, value) {
    setReturns((prev) => prev.map((r) => (r.id === itemId ? { ...r, [field]: value } : r)));
  }

  async function handleReturn() {
    const incomplete = returns.filter((r) => !r.return_condition);
    if (incomplete.length > 0) {
      setError("Select the Condition Upon Return for every item before confirming the return.");
      return;
    }
    if (ris.status !== "Borrowed") return;
    if (!window.confirm(`Confirm return of ${ris.ris_no}? Conditions will be saved and asset units restored to stock.`)) return;
    setSaving(true);
    setMsg("");
    setError("");
    try {
      await api.post(`/ris/${ris.id}/return`, { items: returns });
      setMsg(`${ris.ris_no} returned — conditions recorded.`);
      const r = await api.get(`/ris/${id}`);
      setRis(r);
      setReturns(r.items.map((it) => ({ id: it.id, return_condition: "", return_remarks: "" })));
    } catch (e) {
      setError(e.message);
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete() {
    if (!window.confirm(`Delete ${ris.ris_no}? Outstanding units will be restored to stock.`)) return;
    try {
      await api.del(`/ris/${ris.id}`);
      navigate("/ris", { replace: true });
    } catch (e) {
      setError(e.message);
    }
  }

  function handlePrint() {
    const meta = [
      ["RIS No.", ris.ris_no],
      ["Borrower", `${ris.last_name}, ${ris.first_name} ${ris.mi_name || ""}`.trim()],
      ["Position", ris.position || "—"],
      ["Department", ris.department || "—"],
      ["Contact No.", ris.cp_number || "—"],
      ["Event", ris.event_name],
      ["Event Date", ris.event_date || "—"],
      ["Borrowed At", ris.start_datetime || "—"],
      ["Return Due", ris.end_datetime || "—"],
      ["Status", ris.status],
      ...(ris.return_date ? [["Returned At", ris.return_date]] : []),
    ];
    const returned = ris.status === "Returned";
    printDoc({
      title: "REQUISITION AND ISSUE SLIP",
      docNo: ris.ris_no,
      meta,
      columns: [
        { label: "Qty", key: "qty" },
        { label: "Inventory No.", key: "inventory_no" },
        { label: "Property / Item", key: "item" },
        { label: "Serial No.", key: "serial" },
        { label: "Condition", key: "condition" },
        ...(returned ? [{ label: "Cond. Upon Return", key: "return_condition" }] : []),
        ...(returned ? [{ label: "Return Remarks", key: "return_remarks" }] : []),
        { label: "Borrowed From", key: "from" },
      ],
      items: ris.items.map((it) => ({
        qty: it.quantity,
        inventory_no: it.inventory_no || "—",
        item: it.asset_name || "—",
        serial: it.serial_number || "—",
        condition: it.condition || "Good",
        return_condition: it.return_condition || "—",
        return_remarks: it.return_remarks || "—",
        from: it.borrowed_from_name || "—",
      })),
      signLeft: settings.oic_property || "MARITES MENDIGORIN",
      signRight: settings.oic_president || "DR. ROSELY H. AGUSTIN",
      signLeftTitle: "Issued by:",
      signRightTitle: "Approved by:",
    });
  }

  if (error && !ris) return <div className="alert alert-error">{error}</div>;
  if (!ris) return <div className="empty">Loading...</div>;

  const rows = [
    ["RIS No.", ris.ris_no],
    ["Borrower", `${ris.last_name}, ${ris.first_name} ${ris.mi_name || ""}`.trim()],
    ["Position", ris.position || "—"],
    ["Department", ris.department || "—"],
    ["Contact No.", ris.cp_number || "—"],
    ["Event", ris.event_name],
    ["Event Date", ris.event_date || "—"],
    ["Borrowed At", ris.start_datetime || "—"],
    ["Return Due", ris.end_datetime || "—"],
    ["Status", ris.status],
    ...(ris.return_date ? [["Returned At", ris.return_date]] : []),
  ];

  return (
    <div className="card">
      <div className="card-header">
        <h5>RIS Details - {ris.ris_no}</h5>
        <div className="flex">
          <button className="btn btn-info btn-sm" onClick={handlePrint}>🖨 Print RIS</button>
          {isAdmin && <button className="btn btn-dark btn-sm" onClick={handleDelete}>🗑 Delete</button>}
          <Link to="/ris" className="btn btn-light btn-sm">Back</Link>
        </div>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}
        <div className="form-grid">
          {rows.map(([label, value]) => (
            <div className="form-group" key={label}>
              <label>{label}</label>
              <input className="form-control" value={value ?? ""} readOnly />
            </div>
          ))}
        </div>

        <h6 style={{ margin: "1rem 0 0.5rem" }}>Borrowed Items</h6>
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Inventory No.</th>
                <th>Item</th>
                <th>Serial No.</th>
                <th>Quantity</th>
                <th>Condition (Issued)</th>
                <th>Condition Upon Return</th>
                <th>Return Remarks</th>
                <th>Borrowed From</th>
              </tr>
            </thead>
            <tbody>
              {ris.items.map((it, i) => (
                <tr key={it.id}>
                  <td>{i + 1}</td>
                  <td>{it.inventory_no || "—"}</td>
                  <td><strong>{it.asset_name}</strong></td>
                  <td>{it.serial_number || "—"}</td>
                  <td>{it.quantity}</td>
                  <td>{it.condition || "Good"}</td>
                  <td>
                    {ris.status === "Returned" ? (
                      it.return_condition ? (
                        <span className={`badge ${
                          it.return_condition === "Excellent" || it.return_condition === "Good" ? "badge-ok" : "badge-warn"
                        }`}>{it.return_condition}</span>
                      ) : (
                        "—"
                      )
                    ) : (
                      "—"
                    )}
                  </td>
                  <td>{it.return_remarks || "—"}</td>
                  <td>{it.borrowed_from_name || "—"}</td>
                </tr>
              ))}
              {ris.items.length === 0 && <tr><td colSpan={9} className="empty">No items.</td></tr>}
            </tbody>
          </table>
        </div>

        {ris.status === "Borrowed" && (
          <div className="card" style={{ marginTop: "1rem", background: "var(--card-bg)" }}>
            <div className="card-header">
              <h5>Process Return — Condition Upon Return *</h5>
              <span className="text-muted" style={{ fontSize: "0.78rem" }}>
                Required before items are restored to stock. Select the condition and enter remarks for damage or loss.
              </span>
            </div>
            <div className="card-body">
              <div className="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>Item</th>
                      <th>Qty</th>
                      <th>Condition Upon Return *</th>
                      <th>Return Remarks</th>
                    </tr>
                  </thead>
                  <tbody>
                    {ris.items.map((it, i) => {
                      const r = returns.find((x) => x.id === it.id) || {};
                      return (
                        <tr key={it.id}>
                          <td>
                            <strong>{it.asset_name}</strong>
                            <div className="text-muted" style={{ fontSize: "0.78rem" }}>
                              {it.inventory_no || "—"} · {it.serial_number || "—"}
                            </div>
                          </td>
                          <td>{it.quantity}</td>
                          <td>
                            <select
                              className="form-control"
                              value={r.return_condition || ""}
                              onChange={(e) => setReturn(it.id, "return_condition", e.target.value)}
                            >
                              <option value="">— Select —</option>
                              {RETURN_OPTIONS.map((c) => (
                                <option key={c} value={c}>{c}</option>
                              ))}
                            </select>
                          </td>
                          <td>
                            <input
                              className="form-control"
                              placeholder="Optional damage / loss details"
                              value={r.return_remarks || ""}
                              maxLength={500}
                              onChange={(e) => setReturn(it.id, "return_remarks", e.target.value)}
                            />
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
              <div className="flex" style={{ marginTop: "1rem", justifyContent: "flex-end", gap: 10 }}>
                <button
                  className="btn btn-success"
                  onClick={handleReturn}
                  disabled={saving}
                >
                  {saving ? "Saving..." : "✓ Confirm Return &amp; Restore Stock"}
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}