import { useState } from "react";
import { useAuth } from "../context/AuthContext";
import { api } from "../api/client";

export default function Settings() {
  const { user, updateUser, logout } = useAuth();

  const [profile, setProfile] = useState({
    fullname: user?.fullname || "",
    useremail: user?.useremail || "",
    contact_number: user?.contact_number || "",
    address: user?.address || "",
    department: user?.department || "Admin/Staff",
  });
  const [photo, setPhoto] = useState(user?.photo || "");
  const [photoFile, setPhotoFile] = useState(null);

  const [pw, setPw] = useState({ currentPassword: "", newPassword: "", confirm: "" });

  const [pError, setPError] = useState("");
  const [pMsg, setPMsg] = useState("");
  const [pLoading, setPLoading] = useState(false);
  const [wError, setWError] = useState("");
  const [wMsg, setWMsg] = useState("");
  const [wLoading, setWLoading] = useState(false);

  const setP = (k, v) => setProfile((f) => ({ ...f, [k]: v }));

  function onPhotoChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    if (!file.type.startsWith("image/")) {
      setPError("Please select an image file.");
      return;
    }
    const reader = new FileReader();
    reader.onload = () => {
      setPhoto(reader.result);
      setPhotoFile(reader.result);
      setPError("");
    };
    reader.readAsDataURL(file);
  }

  async function handleProfileSave(e) {
    e.preventDefault();
    setPError("");
    setPMsg("");
    setPLoading(true);
    try {
      const body = { ...profile };
      if (photoFile) body.photo = photoFile;
      const res = await api.put("/auth/profile", body);
      updateUser(res.user);
      setPhotoFile(null);
      setPMsg("Profile updated successfully.");
    } catch (err) {
      setPError(err.message);
    } finally {
      setPLoading(false);
    }
  }

  async function handlePasswordChange(e) {
    e.preventDefault();
    setWError("");
    setWMsg("");
    if (pw.newPassword.length < 6) {
      setWError("New password must be at least 6 characters.");
      return;
    }
    if (pw.newPassword !== pw.confirm) {
      setWError("New password and confirmation do not match.");
      return;
    }
    setWLoading(true);
    try {
      await api.post("/auth/change-password", { currentPassword: pw.newPassword ? pw.currentPassword : undefined, newPassword: pw.newPassword });
      setWMsg("Password changed successfully. Please log in again.");
      setTimeout(() => {
        logout();
        window.location.href = "/login";
      }, 1500);
    } catch (err) {
      setWError(err.message);
    } finally {
      setWLoading(false);
    }
  }

  const showStudentFields = false;

  return (
    <div className="form-grid" style={{ alignItems: "start" }}>
      <div className="card" style={{ maxWidth: 640 }}>
        <div className="card-header">
          <h5>Profile Settings</h5>
        </div>
        <div className="card-body">
          {pError && <div className="alert alert-error">{pError}</div>}
          {pMsg && <div className="alert alert-success">{pMsg}</div>}
          <form onSubmit={handleProfileSave}>
            <div className="flex" style={{ marginBottom: "16px" }}>
              <img className="img-thumb" style={{ width: 72, height: 72, borderRadius: "50%" }} src={photo || "/logo.svg"} alt="Avatar" />
              <div>
                <label style={{ fontWeight: 600 }}>Profile Photo</label>
                <input type="file" accept="image/*" className="form-control" onChange={onPhotoChange} />
                <small className="text-muted">PNG, JPG or WEBP (max 5 MB)</small>
              </div>
            </div>
            <div className="form-grid">
              <div className="form-group">
                <label>Full Name *</label>
                <input className="form-control" value={profile.fullname} onChange={(e) => setP("fullname", e.target.value)} required />
              </div>
              <div className="form-group">
                <label>Email *</label>
                <input type="email" className="form-control" value={profile.useremail} onChange={(e) => setP("useremail", e.target.value)} required />
              </div>
              <div className="form-group">
                <label>Phone / Contact No.</label>
                <input className="form-control" value={profile.contact_number} onChange={(e) => setP("contact_number", e.target.value)} />
              </div>
              <div className="form-group">
                <label>Address</label>
                <input className="form-control" value={profile.address} onChange={(e) => setP("address", e.target.value)} />
              </div>
              {!showStudentFields && (
                <div className="form-group">
                  <label>Department</label>
                  <select className="form-select" value={profile.department} onChange={(e) => setP("department", e.target.value)}>
                    {["Admin/Staff", "HM", "BED", "TED", "CSD"].map((d) => (
                      <option key={d} value={d}>{d}</option>
                    ))}
                  </select>
                </div>
              )}
            </div>
            <div className="flex between mt-4">
              <span className="text-muted" style={{ fontSize: "0.85rem" }}>Username: <strong>{user?.username}</strong></span>
              <button type="submit" className="btn btn-primary" disabled={pLoading}>
                {pLoading ? "Saving..." : "Save Profile"}
              </button>
            </div>
          </form>
        </div>
      </div>

      <div className="card" style={{ maxWidth: 520 }}>
        <div className="card-header">
          <h5>Change Password</h5>
        </div>
        <div className="card-body">
          {wError && <div className="alert alert-error">{wError}</div>}
          {wMsg && <div className="alert alert-success">{wMsg}</div>}
          <form onSubmit={handlePasswordChange}>
            <div className="form-group">
              <label>Current Password</label>
              <input type="password" className="form-control" value={pw.currentPassword} onChange={(e) => setPw({ ...pw, currentPassword: e.target.value })} required />
            </div>
            <div className="form-group">
              <label>New Password</label>
              <input type="password" className="form-control" value={pw.newPassword} onChange={(e) => setPw({ ...pw, newPassword: e.target.value })} required />
            </div>
            <div className="form-group">
              <label>Confirm New Password</label>
              <input type="password" className="form-control" value={pw.confirm} onChange={(e) => setPw({ ...pw, confirm: e.target.value })} required />
            </div>
            <button type="submit" className="btn btn-primary" disabled={wLoading}>
              {wLoading ? "Updating..." : "Update Password"}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}