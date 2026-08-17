import { useEffect, useRef, useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import { api } from "../api/client";

const CAPTCHA_SCRIPT = "https://www.google.com/recaptcha/api.js?render=explicit";

export default function Login() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const [identifier, setIdentifier] = useState("");
  const [password, setPassword] = useState("");
  const [showPass, setShowPass] = useState(false);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [captchaState, setCaptchaState] = useState({ enabled: false, ready: false, failed: false });
  const widgetId = useRef(null);
  const captchaEl = useRef(null);

  useEffect(() => {
    let cancelled = false;

    async function loadCaptcha() {
      try {
        const cfg = await api.get("/auth/captcha-config");
        if (cancelled || !cfg.enabled || !cfg.siteKey) return;
        setCaptchaState((s) => ({ ...s, enabled: true }));

        if (!window.grecaptcha) {
          await new Promise((resolve, reject) => {
            const script = document.createElement("script");
            script.src = CAPTCHA_SCRIPT;
            script.async = true;
            script.onload = resolve;
            script.onerror = () => reject(new Error("script load failed"));
            document.head.appendChild(script);
          });
        }

        window.grecaptcha.ready(() => {
          if (cancelled) return;
          widgetId.current = window.grecaptcha.render(captchaEl.current, {
            sitekey: cfg.siteKey,
            theme: "dark",
          });
          setCaptchaState((s) => ({ ...s, ready: true }));
        });
      } catch {
        if (!cancelled) setCaptchaState((s) => ({ ...s, failed: true }));
      }
    }

    loadCaptcha();
    return () => {
      cancelled = true;
    };
  }, []);

  function resetCaptcha() {
    if (widgetId.current != null && window.grecaptcha) {
      window.grecaptcha.reset(widgetId.current);
    }
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");

    let captchaToken;
    if (captchaState.enabled) {
      try {
        captchaToken = window.grecaptcha.getResponse(widgetId.current);
      } catch {
        captchaToken = "";
      }
      if (!captchaToken) {
        setError("Please complete the CAPTCHA to continue.");
        return;
      }
    }

    setLoading(true);
    try {
      const user = await login(identifier, password, captchaToken);
      navigate("/", { replace: true });
    } catch (err) {
      setError(err.message);
      resetCaptcha();
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
          {captchaState.failed && (
            <div className="login-error">
              CAPTCHA could not be loaded. Please check your internet connection and refresh.
            </div>
          )}

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

            {captchaState.enabled && (
              <div className="recaptcha-row">
                <div ref={captchaEl} />
              </div>
            )}

            <button type="submit" className="btn-login" disabled={loading}>
              {loading ? "Signing in..." : "LOGIN"}
            </button>

            <span className="forgot-password">Forgot Password?</span>
          </form>
        </div>
      </div>
    </div>
  );
}