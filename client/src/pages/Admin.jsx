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
  const [backupError, setBackupError] = useState("");
  const [restoreError, setRestoreError] = useState("");
  const [restoreSuccess, setRestoreSuccess] = useState(false);
  const [backupLoading, setBackupLoading] = useState(false);
  const [restoreLoading, setRestoreLoading] = useState(false);
  const [selectedFile, setSelectedFile] = useState(null);

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

  const handleBackup = async () => {
    setBackupLoading(true);
    setBackupError("");
    try {
      api.initiateBackup();
      // The backup will trigger a file download, show success message after short delay
      setBackupLoading(false);
      // We'll rely on the browser download, but could show a message
      setMsg("Backup download started - check your downloads folder.");
    } catch (err) {
      setBackupError(err.message);
    } finally {
      setBackupLoading(false);
    }
  };

  const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file && (file.type === "application/x-sqlite3" || file.name.endsWith(".db"))) {
      setSelectedFile(file);
    }
  };

  const handleRestore = async () => {
    if (!selectedFile) {
      setRestoreError("Please select a .db backup file first");
      return;
    }
    setRestoreLoading(true);
    setRestoreError("");
    setRestoreSuccess(false);
    try {
      const formData = new FormData();
      formData.append("backup", selectedFile);
      const res = await fetch(`${API_URL}/api/restore`, {
        method: "POST",
        body: formData,
        headers: {
          Authorization: `Bearer ${user?.token || localStorage.getItem("custodian_token")}`
        }
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Restore failed");
      setRestoreSuccess(true);
      setRestoreError("Database restored successfully. Server restart required.");
      setSelectedFile(null);
    } catch (err) {
      setRestoreError(err.message);
    } finally {
      setRestoreLoading(false);
    }
  };

  if (!isAdmin) return <Navigate to="/" replace />;

  return (
    <div className="card">
      <div className="card-header">
        <h5>Admin Dashboard - System Settings</h5>
      </div>
      <div className="card-body">
        {error && <div className="alert alert-error">{error}</div>}
        {msg && <div className="alert alert-success">{msg}</div>}

        {/* Backup/Restore Section */}
        <div className="mt-4 p-3 bg-light rounded">
          <h6><i className="fas fa-database me-2"></i> Database Backup & Restore</h6>
          <p className="text-muted small mb-2">Create backups of your database before making major changes.</p>
          
          <div className="row g-2">
            <div className="col-6">
              <button 
                onClick={handleBackup} 
                className={`btn btn-outline-secondary w-100 ${backupLoading ? "disabled" : ""}`}
                disabled={backupLoading}
              >
                <i className="fas fa-download me-2"></i> Backup Database
                {backupLoading && <span className="spinner-border spinner-border-sm align-bottom"></span>}
              </button>
            </div>
            <div className="col-6">
              <button 
                onClick={() => document.getElementById('restoreFile').click()} 
                className={`btn btn-outline-secondary w-100 ${restoreLoading ? "disabled" : ""}`}
                disabled={restoreLoading}
              >
                <i className="fas fa-upload me-2"></i> Restore Database
              </button>
            </div>
          </div>

          {/* Restore file input (hidden) */}
          <input 
            type="file" 
            id="restoreFile" 
            accept=".db" 
            onChange={handleFileChange} 
            style={{ display: 'none' }}
          />

          {/* Restore result */}
          {restoreSuccess && (
            <div className="alert alert-success mt-3">
              <i className="fas fa-check-circle me-2"></i>
              <strong>Success:</strong> Database restored! Please restart the application.
            </div>
          )}

          {restoreError && (
            <div className="alert alert-error mt-3">
              <i className="fas fa-exclamation-triangle me-2"></i>
              <strong>Error:</strong> {restoreError}
            </div>
          )}
        </div>

        <h6 style={{ margin: "1.25rem 0 0.5rem" }}>Terms of Service</h6>
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
              <label>OIC - Property & Supplies</label>
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