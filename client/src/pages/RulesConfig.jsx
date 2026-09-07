import { useEffect, useState } from "react";
import { api } from "../api/client";

export default function RulesConfig() {
  const [rules, setRules] = useState([]);
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");
  const [savingId, setSavingId] = useState(null);

  useEffect(() => { load(); }, []);

  function load() {
    api.get("/requisitions/rules").then(setRules).catch((e) => setError(e.message));
  }

  function set(key, field, value) {
    setRules((rs) => rs.map((r) => (r.id === key ? { ...r, [field]: value } : r)));
  }

  async function handleSave(r) {
    setSavingId(r.id);
    setError("");
    setMsg("");
    try {
      const rule_meta = { ...(r.rule_meta || {}) };
      await api.put(`/requisitions/rules/${r.id}`, {
        rule_name: r.rule_name,
        description: r.description,
        policy_basis: r.policy_basis,
        rule_meta,
        is_enabled: r.is_enabled ? 1 : 0,
      });
      setMsg(`${r.rule_code} saved.`);
    } catch (e) {
      setError(e.message);
    } finally {
      setSavingId(null);
    }
  }

  const metaField = (r, key) => {
    const cols = ["max_qty_per_item_per_month", "high_value_threshold_peso"];
    if (!cols.includes(key)) return null;
    const label = key === "max_qty_per_item_per_month" ? "Allocation Limit (qty/item/month)" : "High-Value Threshold (₱)";
    return (
      <div className="form-group" style={{ marginTop: "0.5rem" }}>
        <label>{label}</label>
        <input
          type="number"
          className="form-control"
          value={(r.rule_meta && r.rule_meta[key]) ?? ""}
          onChange={(e) =>
            set(r.id, "rule_meta", { ...(r.rule_meta || {}), [key]: Number(e.target.value) || 0 })
          }
        />
      </div>
    );
  };

  return (
    <div className="card">
      <div className="card-header">
        <h5>Requisition Rule Configuration</h5>
        <span className="text-muted" style={{ fontSize: "0.8rem" }}>
          Rules are evaluated against each submitted requisition and traced to their institutional / procurement policy basis.
        </span>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}
        {rules.length === 0 && <div className="empty">No rules configured.</div>}
        {rules.map((r) => (
          <div className="card" key={r.id} style={{ marginBottom: "1rem", background: "var(--card-bg)" }}>
            <div className="card-header" style={{ flexWrap: "wrap", gap: 8 }}>
              <h6 style={{ margin: 0 }}>
                {r.rule_code} — {r.rule_name}
                <span className="badge" style={{ marginLeft: 8 }}>{r.category}</span>
              </h6>
              <label className="flex" style={{ gap: 6, alignItems: "center", fontSize: "0.85rem" }}>
                <input
                  type="checkbox"
                  checked={r.is_enabled === 1 || r.is_enabled === true}
                  onChange={(e) => set(r.id, "is_enabled", e.target.checked ? 1 : 0)}
                />
                Enabled
              </label>
            </div>
            <div className="card-body">
              <div className="form-grid">
                <div className="form-group">
                  <label>Rule Name</label>
                  <input className="form-control" value={r.rule_name || ""} onChange={(e) => set(r.id, "rule_name", e.target.value)} />
                </div>
                <div className="form-group">
                  <label>Category</label>
                  <input className="form-control" value={r.category || ""} onChange={(e) => set(r.id, "category", e.target.value)} />
                </div>
              </div>
              <div className="form-group">
                <label>Description</label>
                <textarea className="form-control" rows={2} value={r.description || ""} onChange={(e) => set(r.id, "description", e.target.value)} />
              </div>
              <div className="form-group">
                <label>Policy Basis (Institutional / Procurement Policy Reference)</label>
                <textarea className="form-control" rows={2} value={r.policy_basis || ""} onChange={(e) => set(r.id, "policy_basis", e.target.value)} />
              </div>
              {metaField(r, "max_qty_per_item_per_month")}
              {metaField(r, "high_value_threshold_peso")}
              <div className="flex" style={{ marginTop: "0.75rem", justifyContent: "flex-end" }}>
                <button className="btn btn-primary btn-sm" onClick={() => handleSave(r)} disabled={savingId === r.id}>
                  {savingId === r.id ? "Saving..." : "Save Rule"}
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}