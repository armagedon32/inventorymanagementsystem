import { useEffect, useState } from "react";
import { Link, useParams, useNavigate } from "react-router-dom";
import { api } from "../api/client";
import { useAuth } from "../context/AuthContext";
import { printDoc } from "../utils/print";

export default function IncidentView() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const isAdmin = user?.role === "Admin";
  const [inc, setInc] = useState(null);
  const [settings, setSettings] = useState({});
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");

  useEffect(() => {
    api.get(`/incidents/${id}`).then(setInc).catch((e) => setError(e.message));
    api.get("/settings").then(setSettings).catch(() => {});
  }, [id]);

  async function handleResolve() {
    if (!window.confirm(`Mark ${inc.report_number} as resolved?`)) return;
    setMsg("");
    setError("");
    try {
      await api.post(`/incidents/${inc.id}/resolve`);
      setMsg("Incident resolved.");
      api.get(`/incidents/${id}`).then(setInc);
    } catch (e) {
      setError(e.message);
    }
  }

  async function handleDelete() {
    if (!window.confirm(`Delete ${inc.report_number}?`)) return;
    try {
      await api.del(`/incidents/${inc.id}`);
      navigate("/incidents", { replace: true });
    } catch (e) {
      setError(e.message);
    }
  }

  function handlePrint() {
    printDoc({
      title: "INCIDENT REPORT",
      docNo: inc.report_number,
      meta: [
        ["Report No.", inc.report_number],
        ["Reported By", inc.reported_by],
        ["Office", inc.office_name || "—"],
        ["Incident Date", `${inc.incident_date || "—"} ${inc.incident_time ? `(${inc.incident_time})` : ""}`],
        ["Description", inc.description],
        ["Extent of Damage", inc.extent_of_damage || "—"],
        ["Status", inc.status],
      ],
      columns: [
        { label: "Qty", key: "qty" },
        { label: "Inventory No.", key: "inventory_no" },
        { label: "Property / Item", key: "item" },
        { label: "Serial No.", key: "serial" },
        { label: "Location", key: "location" },
        { label: "Last Borrower", key: "borrower" },
      ],
      items: inc.items.map((it) => ({
        qty: it.quantity ?? "—",
        inventory_no: it.inventory_no || "—",
        item: it.asset_name || "—",
        serial: it.serial_number || "—",
        location: it.location || "—",
        borrower: it.last_borrower || "—",
      })),
      signLeft: settings.oic_property || "MARITES MENDIGORIN",
      signRight: settings.oic_president || "DR. ROSELY H. AGUSTIN",
      signLeftTitle: "Reported by:",
      signRightTitle: "Noted by:",
    });
  }

  if (error && !inc) return <div className="alert alert-error">{error}</div>;
  if (!inc) return <div className="empty">Loading...</div>;

  const rows = [
    ["Report No.", inc.report_number],
    ["Status", inc.status],
    ["Reported By", inc.reported_by],
    ["Office", inc.office_name || "—"],
    ["Incident Date", `${inc.incident_date || "—"} ${inc.incident_time ? `(${inc.incident_time})` : ""}`],
    ["Description", inc.description],
    ["Extent of Damage", inc.extent_of_damage || "—"],
  ];

  return (
    <div className="card">
      <div className="card-header">
        <h5>Incident Report - {inc.report_number}</h5>
        <div className="flex">
          <button className="btn btn-info btn-sm" onClick={handlePrint}>🖨 Print Report</button>
          {inc.status === "Open" && (
            <button className="btn btn-success btn-sm" onClick={handleResolve}>✓ Mark Resolved</button>
          )}
          {isAdmin && <button className="btn btn-dark btn-sm" onClick={handleDelete}>🗑 Delete</button>}
          <Link to="/incidents" className="btn btn-light btn-sm">Back</Link>
        </div>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}
        <div className="form-grid">
          {rows.map(([label, value]) => (
            <div className="form-group" key={label}>
              <label>{label}</label>
              <textarea className="form-control" value={value ?? ""} readOnly rows={label === "Description" || label === "Extent of Damage" ? 3 : 1} />
            </div>
          ))}
        </div>

        <h6 style={{ margin: "1rem 0 0.5rem" }}>Property Items Involved</h6>
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Inventory No.</th>
                <th>Item</th>
                <th>Serial No.</th>
                <th>Qty</th>
                <th>Location</th>
                <th>Last Borrower</th>
              </tr>
            </thead>
            <tbody>
              {inc.items.map((it, i) => (
                <tr key={it.id}>
                  <td>{i + 1}</td>
                  <td>{it.inventory_no || "—"}</td>
                  <td><strong>{it.asset_name || "—"}</strong></td>
                  <td>{it.serial_number || "—"}</td>
                  <td>{it.quantity ?? "—"}</td>
                  <td>{it.location || "—"}</td>
                  <td>{it.last_borrower || "—"}</td>
                </tr>
              ))}
              {inc.items.length === 0 && <tr><td colSpan={7} className="empty">No items.</td></tr>}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}