import { useEffect, useState } from "react";
import { Navigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import { api } from "../api/client";

export default function Admin() {
  const { user } = useAuth();
  const isAdmin = user?.role === "Admin";

  const [tos, setTos] = useState("");
  const [oicProperty, setOicProperty] = useState("");
  const [oicPresident, setOicPresident] = useState("");
  const [error, setError] = useState("");
  const [msg, setMsg] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    api.get("/settings").then((s) => {
      setTos(s.terms_of_service || "");
      setOicProperty(s.oic_property || "");
      setOicPresident(s.oic_president || "");
    }).catch((e) => setError(e.message));
  }, []);

  async function handleSave(e) {
    e.preventDefault();
    setError("");
    setMsg("");
    setLoading(true);
    try {
      await api.put("/settings", { terms_of_service: tos, oic_property: oicProperty, oic_president: oicPresident });
      setMsg("System settings updated.");
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  if (!isAdmin) return <Navigate to="/" replace />;

  return (
    <div className="card">
      <div className="card-header">
        <h5>Admin Dashboard - System Settings</h5>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}

        <h6 style={{ margin: "0 0 0.5rem" }}>Terms of Service</h6>
        <p className="text-muted" style={{ fontSize: "0.85rem", marginTop: 0 }}>
          This is the Terms of Service content shown to users. Update the text and click Save Settings.
        </p>
        <form onSubmit={handleSave}>
          <div className="form-group">
            <label>Terms of Service Content</label>
            <textarea
              className="form-control"
              rows="14"
              value={tos}
              onChange={(e) => setTos(e.target.value)}
              placeholder="Enter the Terms of Service here..."
            />
          </div>

          <h6 style={{ margin: "1.25rem 0 0.5rem" }}>Document Signatories (used on printed forms)</h6>
          <div className="form-grid">
            <div className="form-group">
              <label>OIC - Property &amp; Supplies</label>
              <input className="form-control" value={oicProperty} onChange={(e) => setOicProperty(e.target.value)} />
            </div>
            <div className="form-group">
              <label>College President</label>
              <input className="form-control" value={oicPresident} onChange={(e) => setOicPresident(e.target.value)} />
            </div>
          </div>

          <div className="flex between mt-4">
            <span className="text-muted" style={{ fontSize: "0.85rem" }}>Admin only</span>
            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? "Saving..." : "Save Settings"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}