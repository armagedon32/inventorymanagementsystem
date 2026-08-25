export const API_URL = "/api";

const TOKEN_KEY = "custodian_token";
const USER_KEY = "custodian_user";

export const authStore = {
  getToken: () => localStorage.getItem(TOKEN_KEY),
  setSession: (token, user) => {
    localStorage.setItem(TOKEN_KEY, token);
    localStorage.setItem(USER_KEY, JSON.stringify(user));
  },
  getUser: () => {
    try {
      return JSON.parse(localStorage.getItem(USER_KEY));
    } catch {
      return null;
    }
  },
  clear: () => {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
  },
};

async function request(method, url, body) {
  const headers = { "Content-Type": "application/json" };
  const token = authStore.getToken();
  if (token) headers.Authorization = `Bearer ${token}`;

  const res = await fetch(`${API_URL}${url}`, {
    method,
    headers,
    body: body ? JSON.stringify(body) : undefined,
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    if (res.status === 401 && authStore.getToken()) {
      authStore.clear();
      window.location.href = "/login";
    }
    throw new Error(data.error || "Request failed");
  }
  return data;
}

export const api = {
  get: (url) => request("GET", url),
  post: (url, body) => request("POST", url, body),
  put: (url, body) => request("PUT", url, body),
  del: (url) => request("DELETE", url),
  // Backup & Restore functions
  // getBackup: () => request("GET", "/api/backup"), // Returns file download - handled separately
  restoreDatabase: (file) => {
    const formData = new FormData();
    formData.append("backup", file);
    return fetch(`${API_URL}/api/restore`, {
      method: "POST",
      body: formData,
      headers: {
        Authorization: `Bearer ${authStore.getToken()}`
      }
    }).then(async (res) => {
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Restore failed");
      return data;
    });
  },
  // Initiate database backup - triggers server-side backup and returns file
  initiateBackup: () => {
    // This triggers the backup on server and forces file download
    window.location.href = `${API_URL}/api/backup`;
  }
};