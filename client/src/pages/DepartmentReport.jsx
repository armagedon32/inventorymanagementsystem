import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { api } from "../api/client";

const DEPARTMENTS = ["Admin/Staff", "HM", "BED", "TED", "CSD"];

export default function DepartmentReport() {
  const [dept, setDept] = useState("");
  const [data, setData] = useState(null);
  const [error, setError] = useState("");

  useEffect(() => {
    if (!dept) {
      setData(null);
      return;
    }
    api
      .get(`/department-report?dept=${encodeURIComponent(dept)}`)
      .then(setData)
      .catch((e) => setError(e.message));
  }, [dept]);

  return (
    <div>
      <div className="card">
        <div className="card-header">
          <h5>Department Report</h5>
          <div style={{ minWidth: 240 }}>
            <select className="form-select" value={dept} onChange={(e) => { setDept(e.target.value); setError(""); }}>
              <option value="">-- Select Department --</option>
              {DEPARTMENTS.map((d) => (
                <option key={d}>{d}</option>
              ))}
            </select>
          </div>
        </div>
        <div className="card-body">
          {error && <div className="alert alert-error">{error}</div>}
          {!dept && <div className="empty">Select a department to view requested/released assets and inventories.</div>}
          {data && (
            <>
              <h6 style={{ margin: "0 0 0.5rem" }}>Requested Assets (RIS) — {data.requested.length}</h6>
              <div className="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>RIS No.</th>
                      <th>Borrower</th>
                      <th>Event</th>
                      <th>Borrowed At</th>
                      <th>Return Due</th>
                      <th>Status</th>
                      <th>Items</th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.requested.map((r) => (
                      <tr key={r.id}>
                        <td><Link to={`/ris/${r.id}`}>{r.ris_no}</Link></td>
                        <td>{r.last_name}, {r.first_name}</td>
                        <td>{r.event_name}</td>
                        <td>{r.start_datetime || "—"}</td>
                        <td>{r.end_datetime || "—"}</td>
                        <td>{r.status}</td>
                        <td>
                          <ul style={{ margin: 0, paddingLeft: 18 }}>
                            {r.items.map((it) => (
                              <li key={it.id}>
                                {it.asset_name} × {it.quantity} — {it.condition || "Good"}
                              </li>
                            ))}
                          </ul>
                        </td>
                      </tr>
                    ))}
                    {data.requested.length === 0 && <tr><td colSpan={7} className="empty">No requested assets.</td></tr>}
                  </tbody>
                </table>
              </div>

              <h6 style={{ margin: "1.5rem 0 0.5rem" }}>Released Assets (Approved Requisitions) — {data.released.length}</h6>
              <div className="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>Req. No.</th>
                      <th>Requested By</th>
                      <th>Purpose</th>
                      <th>Date Processed</th>
                      <th>Items</th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.released.map((r) => (
                      <tr key={r.id}>
                        <td><Link to={`/requisitions/${r.id}`}>{r.req_no}</Link></td>
                        <td>{r.requested_by_name || "—"}</td>
                        <td>{r.purpose}</td>
                        <td>{r.date_processed || "—"}</td>
                        <td>
                          <ul style={{ margin: 0, paddingLeft: 18 }}>
                            {r.items.map((it) => (
                              <li key={it.id}>{it.product_name} × {it.quantity} {it.unit}</li>
                            ))}
                          </ul>
                        </td>
                      </tr>
                    ))}
                    {data.released.length === 0 && <tr><td colSpan={5} className="empty">No released assets.</td></tr>}
                  </tbody>
                </table>
              </div>

              <h6 style={{ margin: "1.5rem 0 0.5rem" }}>Inventories — {data.inventories.length}</h6>
              <div className="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>Inventory No.</th>
                      <th>Item</th>
                      <th>Type</th>
                      <th>Serial No.</th>
                      <th>Condition</th>
                      <th>Stock</th>
                      <th>Assigned To</th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.inventories.map((p) => (
                      <tr key={p.pid}>
                        <td>{p.inventory_no || "—"}</td>
                        <td><strong>{p.name}</strong> <span className="muted">({p.category_name})</span></td>
                        <td>{p.product_type}</td>
                        <td>{p.serial_number || "—"}</td>
                        <td>{p.condition || "—"}</td>
                        <td>{p.stock} {p.unit}</td>
                        <td>{p.assigned_to || "—"}</td>
                      </tr>
                    ))}
                    {data.inventories.length === 0 && <tr><td colSpan={7} className="empty">No inventories.</td></tr>}
                  </tbody>
                </table>
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
}