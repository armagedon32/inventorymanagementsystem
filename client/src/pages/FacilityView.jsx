import { useEffect, useState } from "react";
import { Link, useParams, useNavigate } from "react-router-dom";
import { api } from "../api/client";
import { useAuth } from "../context/AuthContext";
import { printDoc } from "../utils/print";

const statusBadge = (s) => {
  if (s === "Returned") return <span className="badge badge-ok">{s}</span>;
  if (s === "Cancelled") return <span className="badge badge-danger">{s}</span>;
  if (s === "Issued") return <span className="badge badge-info">{s}</span>;
  return <span className="badge badge-warn">{s}</span>;
};

export default function FacilityView() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const isAdmin = user?.role === "Admin";
  const [fac, setFac] = useState(null);
  const [settings, setSettings] = useState({});
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");

  useEffect(() => {
    api.get(`/facilities/${id}`).then(setFac).catch((e) => setError(e.message));
    api.get("/settings").then(setSettings).catch(() => {});
  }, [id]);

  async function run(action) {
    setMsg("");
    setError("");
    try {
      await api.post(`/facilities/${fac.id}/${action}`);
      setMsg(`Request ${action === "approve" ? "approved (equipment issued)" : action === "return" ? "marked returned (equipment restored)" : "cancelled"}.`);
      api.get(`/facilities/${id}`).then(setFac);
    } catch (e) {
      setError(e.message);
    }
  }

  async function handleDelete() {
    if (!window.confirm(`Delete ${fac.request_no}?`)) return;
    try {
      await api.del(`/facilities/${fac.id}`);
      navigate("/facilities", { replace: true });
    } catch (e) {
      setError(e.message);
    }
  }

  function handlePrint() {
    printDoc({
      title: "FACILITY USE REQUEST FORM",
      docNo: fac.request_no,
      meta: [
        ["Request No.", fac.request_no],
        ["Office / Organization", fac.office_or_org || "—"],
        ["Requesting Name", fac.requesting_name || "—"],
        ["Contact No.", fac.contact_no || "—"],
        ["Address", fac.address || "—"],
        ["Date of Filing", fac.date_of_filing || "—"],
        ["Event", fac.event_name],
        ["Facility", fac.room?.room_name || "—"],
        ["No. of Participants", fac.num_participants],
        ["Start", fac.start_datetime || "—"],
        ["End", fac.end_datetime || "—"],
        ["Status", fac.status],
      ],
      columns: [
        { label: "Qty", key: "qty" },
        { label: "Inventory No.", key: "inventory_no" },
        { label: "Equipment", key: "item" },
        { label: "Description", key: "desc" },
      ],
      items: fac.equip.map((e) => ({
        qty: e.quantity ?? "—",
        inventory_no: e.inventory_no || "—",
        item: e.asset_name || e.item_name || "—",
        desc: e.description || "—",
      })),
      signLeft: settings.oic_property || "MARITES MENDIGORIN",
      signRight: settings.oic_president || "DR. ROSELY H. AGUSTIN",
      signLeftTitle: "Requested by:",
      signRightTitle: "Approved by:",
    });
  }

  if (error && !fac) return <div className="alert alert-error">{error}</div>;
  if (!fac) return <div className="empty">Loading...</div>;

  const rows = [
    ["Request No.", fac.request_no],
    ["Status", statusBadge(fac.status)],
    ["Office / Organization", fac.office_or_org || "—"],
    ["Requesting Name", fac.requesting_name || "—"],
    ["Contact No.", fac.contact_no || "—"],
    ["Address", fac.address || "—"],
    ["Date of Filing", fac.date_of_filing || "—"],
    ["Event", fac.event_name],
    ["Facility", fac.room ? `${fac.room.room_name} (${fac.room.location || "—"}, cap ${fac.room.capacity})` : "—"],
    ["No. of Participants", fac.num_participants],
    ["Start", fac.start_datetime || "—"],
    ["End", fac.end_datetime || "—"],
  ];

  return (
    <div className="card">
      <div className="card-header">
        <h5>Facility Request - {fac.request_no}</h5>
        <div className="flex">
          <button className="btn btn-info btn-sm" onClick={handlePrint}>🖨 Print Form</button>
          {isAdmin && fac.status === "Pending" && (
            <button className="btn btn-success btn-sm" onClick={() => run("approve")}>✓ Approve / Issue</button>
          )}
          {isAdmin && fac.status === "Issued" && (
            <button className="btn btn-success btn-sm" onClick={() => run("return")}>↩ Mark Returned</button>
          )}
          {isAdmin && fac.status === "Pending" && (
            <button className="btn btn-danger btn-sm" onClick={() => run("cancel")}>✗ Cancel</button>
          )}
          {isAdmin && <button className="btn btn-dark btn-sm" onClick={handleDelete}>🗑 Delete</button>}
          <Link to="/facilities" className="btn btn-light btn-sm">Back</Link>
        </div>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}
        <div className="form-grid">
          {rows.map(([label, value]) => (
            <div className="form-group" key={label}>
              <label>{label}</label>
              {typeof value === "object" ? <div>{value}</div> : <input className="form-control" value={value ?? ""} readOnly />}
            </div>
          ))}
        </div>

        <h6 style={{ margin: "1rem 0 0.5rem" }}>Borrowed Equipment</h6>
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Inventory No.</th>
                <th>Equipment</th>
                <th>Description</th>
                <th>Quantity</th>
              </tr>
            </thead>
            <tbody>
              {fac.equip.map((e, i) => (
                <tr key={e.id}>
                  <td>{i + 1}</td>
                  <td>{e.inventory_no || "—"}</td>
                  <td><strong>{e.asset_name || e.item_name || "—"}</strong></td>
                  <td>{e.description || "—"}</td>
                  <td>{e.quantity ?? "—"}</td>
                </tr>
              ))}
              {fac.equip.length === 0 && <tr><td colSpan={5} className="empty">No equipment requested.</td></tr>}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}