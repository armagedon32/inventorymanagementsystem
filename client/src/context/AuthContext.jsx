import { createContext, useContext, useEffect, useState, useCallback } from "react";
import { api, authStore } from "../api/client";
import { Navigate } from "react-router-dom";

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(authStore.getUser());
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (authStore.getToken() && !authStore.getUser()) {
      api
        .get("/auth/me")
        .then(setUser)
        .catch(() => authStore.clear())
        .finally(() => setLoading(false));
    } else {
      setLoading(false);
    }
  }, []);

  const login = useCallback(async (identifier, password, captchaToken, captchaAnswer) => {
    const data = await api.post("/auth/login", { identifier, password, captchaToken, captchaAnswer });
    authStore.setSession(data.token, data.user);
    setUser(data.user);
    return data.user;
  }, []);

  const logout = useCallback(async () => {
    try {
      await api.post("/auth/logout", {});
    } catch {
      /* ignore */
    }
    authStore.clear();
    setUser(null);
  }, []);

  const updateUser = useCallback((next) => {
    const token = authStore.getToken();
    if (token) authStore.setSession(token, next);
    setUser(next);
  }, []);

  if (loading) return null;

  return <AuthContext.Provider value={{ user, login, logout, updateUser }}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  return useContext(AuthContext);
}

export function ProtectedRoute({ children }) {
  const { user } = useAuth();
  if (!user) return <Navigate to="/login" replace />;
  return children;
}