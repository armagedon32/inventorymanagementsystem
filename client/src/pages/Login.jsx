import { useCallback, useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import { api } from "../api/client";
import Modal from "../components/Modal";

export default function Login() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const [identifier, setIdentifier] = useState("");
  const [password, setPassword] = useState("");
  const [showPass, setShowPass] = useState(false);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [captcha, setCaptcha] = useState(null);
  const [captchaAnswer, setCaptchaAnswer] = useState("");
  const [tos, setTos] = useState("");
  const [showTos, setShowTos] = useState(false);

  const loadCaptcha = useCallback(async () => {
    try {
      const data = await api.get("/auth/captcha");
      setCaptcha(data);
      setCaptchaAnswer("");
    } catch {
      setCaptcha(null);
    }
  }, []);

  useEffect(() => {
    loadCaptcha();
  }, [loadCaptcha]);

  async function openTos() {
    setError("");
    try {
      const s = await api.get("/settings");
      setTos(s.terms_of_service || "No Terms of Service has been published yet.");
      setShowTos(true);
    } catch {
      setError("Could not load the Terms of Service.");
    }
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");

    if (!captcha) {
      setError("Could not load the CAPTCHA. Please try again.");
      return;
    }
    if (!captchaAnswer.trim()) {
      setError("Please answer the CAPTCHA to continue.");
      return;
    }

    setLoading(true);
    try {
      const user = await login(identifier, password, captcha.token, captchaAnswer);
      navigate("/", { replace: true });
    } catch (err) {
      setError(err.message);
      loadCaptcha();
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="login-page">
      <div className="login-box">
        <div className="login-card">
          <img src="/logo.svg" className="logo-floating" alt="Logo" />

          <div className="login-title">
            <b>PROPERTY AND SUPPLIES OFFICE</b>
            <span>Inventory Management System</span>
          </div>

          {error && <div className="login-error">{error}</div>}

          <form onSubmit={handleSubmit}>
            <div className="input-row">
              <input
                type="text"
                placeholder="Email / Username"
                value={identifier}
                onChange={(e) => setIdentifier(e.target.value)}
                required
              />
            </div>

            <div className="input-row">
              <input
                type={showPass ? "text" : "password"}
                placeholder="Password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
              />
              <span className="icon" onClick={() => setShowPass(!showPass)}>
                {showPass ? "🙈" : "👁"}
              </span>
            </div>

            <div className="captcha-box">
              <img
                src={captchaUrl}
                alt="CAPTCHA"
                className="captcha-image"
              />
              <button
                type="button"
                className="captcha-refresh"
                title="Refresh CAPTCHA"
                onClick={loadCaptcha}
              >
                ↻
              </button>
            </div>

            <button type="submit" className="btn-login" disabled={loading}>
              {loading ? "Signing in..." : "LOGIN"}
            </button>

            <div className="login-links">
              <span className="forgot-password">Forgot Password?</span>
              <span className="tos-link" onClick={openTos}>Terms of Service</span>
            </div>
          </form>
        </div>
      </div>

      {showTos && (
        <Modal title="Terms of Service" onClose={() => setShowTos(false)} wide>
          <div style={{ whiteSpace: "pre-wrap", fontSize: "0.9rem", maxHeight: "60vh", overflow: "auto" }}>{tos}</div>
          <div className="flex between mt-4">
            <button type="button" className="btn btn-light" onClick={() => setShowTos(false)}>Close</button>
          </div>
        </Modal>
      )}
    </div>
  );
}