import { useEffect, useState } from "react";
import { Link, useParams, useNavigate } from "react-router-dom";
import { api } from "../api/client";
import { useAuth } from "../context/AuthContext";
import { printDoc } from "../utils/print";

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

  useEffect(() => {
    api.get(`/ris/${id}`).then(setRis).catch((e) => setError(e.message));
    api.get("/settings").then(setSettings).catch(() => {});
  }, [id]);

  async function handleReturn() {
    if (!window.confirm(`Mark ${ris.ris_no} as returned? Asset units will be restored.`)) return;
    setMsg("");
    setError("");
    try {
      await api.post(`/ris/${ris.id}/return`);
      setMsg(`${ris.ris_no} returned.`);
      api.get(`/ris/${id}`).then(setRis);
    } catch (e) {
      setError(e.message);
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
      ["Contact No.", ris.cp_number || "—"],
      ["Event", ris.event_name],
      ["Event Date", ris.event_date || "—"],
      ["Borrowed At", ris.start_datetime || "—"],
      ["Return Due", ris.end_datetime || "—"],
      ["Status", ris.status],
      ...(ris.return_date ? [["Returned At", ris.return_date]] : []),
    ];
    printDoc({
      title: "REQUISITION AND ISSUE SLIP",
      docNo: ris.ris_no,
      meta,
      columns: [
        { label: "Qty", key: "qty" },
        { label: "Inventory No.", key: "inventory_no" },
        { label: "Property / Item", key: "item" },
        { label: "Serial No.", key: "serial" },
        { label: "Borrowed From", key: "from" },
      ],
      items: ris.items.map((it) => ({
        qty: it.quantity,
        inventory_no: it.inventory_no || "—",
        item: it.asset_name || "—",
        serial: it.serial_number || "—",
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
          {ris.status === "Borrowed" && (
            <button className="btn btn-success btn-sm" onClick={handleReturn}>↩ Mark Returned</button>
          )}
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
                  <td>{it.borrowed_from_name || "—"}</td>
                </tr>
              ))}
              {ris.items.length === 0 && <tr><td colSpan={6} className="empty">No items.</td></tr>}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}