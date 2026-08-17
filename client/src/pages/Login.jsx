import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";

export default function Login() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const [identifier, setIdentifier] = useState("");
  const [password, setPassword] = useState("");
  const [showPass, setShowPass] = useState(false);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      const user = await login(identifier, password);
      navigate(user.role === "Admin" ? "/" : "/", { replace: true });
    } catch (err) {
      setError(err.message);
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