import { useEffect, useState } from "react";
import { Link, useParams, useNavigate } from "react-router-dom";
import { api } from "../api/client";
import { useAuth } from "../context/AuthContext";
import { printDoc } from "../utils/print";

export default function PtrView() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const isAdmin = user?.role === "Admin";
  const [ptr, setPtr] = useState(null);
  const [settings, setSettings] = useState({});
  const [error, setError] = useState("");

  useEffect(() => {
    api.get(`/ptr/${id}`).then(setPtr).catch((e) => setError(e.message));
    api.get("/settings").then(setSettings).catch(() => {});
  }, [id]);

  async function handleDelete() {
    if (!window.confirm(`Delete ${ptr.ptr_no}?`)) return;
    try {
      await api.del(`/ptr/${ptr.id}`);
      navigate("/ptr", { replace: true });
    } catch (e) {
      setError(e.message);
    }
  }

  function handlePrint() {
    printDoc({
      title: "PROPERTY TRANSFER RECEIPT",
      docNo: ptr.ptr_no,
      meta: [
        ["PTR No.", ptr.ptr_no],
        ["Transfer Date", ptr.transfer_date || "—"],
        ["From Office", ptr.from_office_name || "—"],
        ["To Office", ptr.to_office_name || "—"],
        ["Remarks", ptr.remarks || "—"],
      ],
      columns: [
        { label: "Qty", key: "qty" },
        { label: "Inventory No.", key: "inventory_no" },
        { label: "Property / Item", key: "item" },
        { label: "Serial No.", key: "serial" },
        { label: "Condition", key: "condition" },
      ],
      items: ptr.items.map((it) => ({
        qty: it.quantity ?? "—",
        inventory_no: it.inventory_no || "—",
        item: it.asset_name || it.description || "—",
        serial: it.serial_number || "—",
        condition: it.condition || "—",
      })),
      signLeft: settings.oic_property || "MARITES MENDIGORIN",
      signRight: settings.oic_president || "DR. ROSELY H. AGUSTIN",
      signLeftTitle: "Released by:",
      signRightTitle: "Received by:",
    });
  }

  if (error && !ptr) return <div className="alert alert-error">{error}</div>;
  if (!ptr) return <div className="empty">Loading...</div>;

  const rows = [
    ["PTR No.", ptr.ptr_no],
    ["Transfer Date", ptr.transfer_date || "—"],
    ["From Office", ptr.from_office_name || "—"],
    ["To Office", ptr.to_office_name || "—"],
    ["Remarks", ptr.remarks || "—"],
  ];

  return (
    <div className="card">
      <div className="card-header">
        <h5>PTR Details - {ptr.ptr_no}</h5>
        <div className="flex">
          <button className="btn btn-info btn-sm" onClick={handlePrint}>🖨 Print PTR</button>
          {isAdmin && <button className="btn btn-dark btn-sm" onClick={handleDelete}>🗑 Delete</button>}
          <Link to="/ptr" className="btn btn-light btn-sm">Back</Link>
        </div>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        <div className="form-grid">
          {rows.map(([label, value]) => (
            <div className="form-group" key={label}>
              <label>{label}</label>
              <input className="form-control" value={value ?? ""} readOnly />
            </div>
          ))}
        </div>

        <h6 style={{ margin: "1rem 0 0.5rem" }}>Transferred Items</h6>
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Inventory No.</th>
                <th>Item</th>
                <th>Serial No.</th>
                <th>Quantity</th>
                <th>Condition</th>
              </tr>
            </thead>
            <tbody>
              {ptr.items.map((it, i) => (
                <tr key={it.id}>
                  <td>{i + 1}</td>
                  <td>{it.inventory_no || "—"}</td>
                  <td><strong>{it.asset_name || it.description || "—"}</strong></td>
                  <td>{it.serial_number || "—"}</td>
                  <td>{it.quantity ?? "—"}</td>
                  <td>{it.condition || "—"}</td>
                </tr>
              ))}
              {ptr.items.length === 0 && <tr><td colSpan={6} className="empty">No items.</td></tr>}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}