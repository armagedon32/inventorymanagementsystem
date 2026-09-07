import { useAuth } from "../context/AuthContext";

const DOCS = [
  { href: "/docs/index.html", icon: "◈", title: "Documentation Home", desc: "Landing page with all links below, system access, test accounts, and verified results." },
  { href: "/docs/USER_MANUAL.md", icon: "📘", title: "User's Manual", desc: "Step-by-step guide: login, inventory, stock-in/out, requisitions, RIS issuance & returns, assets, reports, ML Lab, settings." },
  { href: "/docs/IMPLEMENTATION_PLAN.md", icon: "📐", title: "System Implementation Plan", desc: "Phased deployment: infrastructure, data migration, LGU barcode integration, training, pilot, evaluation, maintenance." },
  { href: "/docs/SYSTEM_ACCESS_AND_EVALUATION.md", icon: "🔑", title: "System Access & Evaluation Guide", desc: "How to access the live system, test accounts per role, and what to evaluate per module." },
  { href: "/docs/ACTION_TAKEN.md", icon: "✅", title: "Action Taken (Panel Responses)", desc: "Consolidated replies to panel feedback — implemented system changes and manuscript revisions." },
  { href: "/docs/System_Overview_AVP.html", icon: "🎞", title: "System Overview — AVP Deck", desc: "Self-contained slide presentation of the system, modules, and verified evaluation results (usable as the video/AVP presentation)." },
];

const ACCOUNTS = [
  { username: "superadmin", password: "admin123", role: "Administrator", purpose: "Full access — inventory, assets, requisitions, reports, Demand Forecasting / ML Lab, audit logs, backups." },
  { username: "faculty", password: "intern123", role: "Faculty", purpose: "Submit requisitions, view assigned assets, reservations." },
  { username: "staff", password: "assistant123", role: "Non-Teaching Staff", purpose: "Submit requests, borrow/return items, reservations." },
];

const RESULTS = [
  { component: "Demand forecasting (RNN-LSTM)", result: "72/72 trained · 48 months (Sep 2022 – Aug 2026) · 3,704 issuance records · overall MAPE 19.41% (MAE 1.58, RMSE 2.69)", target: "≤ 20% ✓" },
  { component: "Training evidence", result: "Run history: timestamped retrains (~26 s per run), per-run metrics, per-item LSTM internals", target: "—" },
  { component: "Asset tracking (evaluation)", result: "Location 97.80% · Status updates 98.80% · Unrecorded assets 0% · Overall 96.80%", target: "≥ 95% ✓" },
  { component: "Transaction logging", result: "100% recorded, zero unmapped items (72 active stock supplies · 1,320 active assets)", target: "100% ✓" },
  { component: "Software quality (ISO/IEC 25010)", result: "Overall 4.48 (System Users 4.47 · IT Experts 4.53)", target: "≥ 3.50 ✓" },
];

export default function Documentation() {
  const { user } = useAuth();
  return (
    <div>
      <div className="card" style={{ marginBottom: 16 }}>
        <div className="card-header">
          <h4>System Documentation &amp; Evaluation</h4>
        </div>
        <div className="card-body">
          <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(240px, 1fr))", gap: 12 }}>
            {DOCS.map((d) => (
              <div key={d.href} className="card" style={{ margin: 0 }}>
                <div className="card-body" style={{ padding: 14 }}>
                  <div style={{ fontSize: "1.4rem" }}>{d.icon}</div>
                  <strong>{d.title}</strong>
                  <p className="text-muted" style={{ fontSize: "0.82rem", margin: "6px 0 10px" }}>
                    {d.desc}
                  </p>
                  <a href={d.href} target="_blank" rel="noopener noreferrer">
                    Open ↗
                  </a>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="card" style={{ marginBottom: 16 }}>
        <div className="card-header">
          <h4>Live System Access — {user?.role || ""}</h4>
        </div>
        <div className="card-body">
          <table>
            <thead>
              <tr>
                <th>Username</th>
                <th>Password</th>
                <th>Role</th>
                <th>Purpose</th>
              </tr>
            </thead>
            <tbody>
              {ACCOUNTS.map((a) => (
                <tr key={a.username}>
                  <td>
                    <code>{a.username}</code>
                  </td>
                  <td>
                    <code>{a.password}</code>
                  </td>
                  <td>{a.role}</td>
                  <td className="text-muted">{a.purpose}</td>
                </tr>
              ))}
            </tbody>
          </table>
          <p className="text-muted" style={{ fontSize: "0.82rem", marginTop: 10 }}>
            Each login requires a CAPTCHA. Sessions expire after 12 hours. URL:{" "}
            <a href="https://knsinventorysystem.site" target="_blank" rel="noopener noreferrer">
              https://knsinventorysystem.site
            </a>
          </p>
        </div>
      </div>

      <div className="card">
        <div className="card-header">
          <h4>Verified Results (Sept 6, 2026)</h4>
        </div>
        <div className="card-body">
          <table>
            <thead>
              <tr>
                <th>Component</th>
                <th>Result</th>
                <th>Acceptance</th>
              </tr>
            </thead>
            <tbody>
              {RESULTS.map((r) => (
                <tr key={r.component}>
                  <td>{r.component}</td>
                  <td>{r.result}</td>
                  <td>{r.target}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}