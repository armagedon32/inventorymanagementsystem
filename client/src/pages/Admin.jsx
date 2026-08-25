import { useEffect, useState } from "react";
import { Navigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import { api, API_URL } from "../api/client";

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
    setMsg("");
    try {
      const res = await fetch(`${API_URL}/backup`, {
        headers: { Authorization: `Bearer ${localStorage.getItem("custodian_token")}` },
      });
      if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        throw new Error(data.error || "Backup failed");
      }
      const blob = await res.blob();
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `custodian_backup-${new Date().toISOString().slice(0, 10)}.db`;
      a.click();
      URL.revokeObjectURL(url);
      setMsg("Backup downloaded successfully. Check your downloads folder.");
    } catch (err) {
      setBackupError(err.message);
    } finally {
      setBackupLoading(false);
    }
  };

  const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    if (file.name.endsWith(".db")) {
      setSelectedFile(file);
      setRestoreError("");
    } else {
      setSelectedFile(null);
      setRestoreError("Invalid file. Please select a .db backup file.");
    }
    e.target.value = "";
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
      const base64 = await new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result.split(",")[1]);
        reader.onerror = reject;
        reader.readAsDataURL(selectedFile);
      });
      const res = await fetch(`${API_URL}/restore`, {
        method: "POST",
        body: JSON.stringify({ data: base64 }),
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${localStorage.getItem("custodian_token")}`
        }
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Restore failed");
      setRestoreSuccess(true);
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
          <h6>Database Backup &amp; Restore</h6>
          <p className="text-muted" style={{ fontSize: "0.85rem", marginBottom: 8 }}>
            Create backups of your database before making major changes.
          </p>
          
          <div className="row g-2">
            <div className="col-6">
              <button 
                type="button"
                onClick={handleBackup} 
                className={`btn btn-light btn-sm w-100 ${backupLoading ? "disabled" : ""}`}
                disabled={backupLoading}
              >
                ⬇ Backup Database
                {backupLoading && " ..."}
              </button>
            </div>
            <div className="col-6">
              <button 
                type="button"
                onClick={() => document.getElementById('restoreFile').click()} 
                className={`btn btn-light btn-sm w-100 ${restoreLoading ? "disabled" : ""}`}
                disabled={restoreLoading}
              >
                ⬆ Restore Database
                {restoreLoading && " ..."}
              </button>
            </div>
          </div>

          {/* Selected file + confirm restore */}
          {selectedFile && (
            <div className="mt-3 p-2" style={{ background: "#fff", border: "1px solid #ddd", borderRadius: 6 }}>
              <div style={{ fontSize: "0.85rem", marginBottom: 8 }}>
                Selected file: <strong>{selectedFile.name}</strong>
              </div>
              <button onClick={handleRestore} disabled={restoreLoading} className="btn btn-danger btn-sm">
                {restoreLoading ? "Restoring..." : "⚠ Confirm Restore (replaces current database)"}
              </button>
              <button
                type="button"
                className="btn btn-light btn-sm"
                style={{ marginLeft: 8 }}
                onClick={() => setSelectedFile(null)}
                disabled={restoreLoading}
              >
                Cancel
              </button>
            </div>
          )}

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
              <strong>Success:</strong> Database restored! Please restart the application (redeploy in Railway).
            </div>
          )}

          {restoreError && (
            <div className="alert alert-error mt-3">
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