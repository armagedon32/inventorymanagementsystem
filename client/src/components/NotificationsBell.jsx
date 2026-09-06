import { useEffect, useRef, useState } from "react";
import { useNavigate } from "react-router-dom";
import { api } from "../api/client";

function timeAgo(dateStr) {
  const d = new Date(dateStr);
  if (isNaN(d)) return "";
  const s = Math.floor((Date.now() - d.getTime()) / 1000);
  if (s < 60) return "just now";
  if (s < 3600) return `${Math.floor(s / 60)}m ago`;
  if (s < 86400) return `${Math.floor(s / 3600)}h ago`;
  return `${Math.floor(s / 86400)}d ago`;
}

export default function NotificationsBell() {
  const [open, setOpen] = useState(false);
  const [items, setItems] = useState([]);
  const [unread, setUnread] = useState(0);
  const boxRef = useRef(null);
  const navigate = useNavigate();

  const load = async () => {
    try {
      const data = await api.get("/notifications");
      setItems(data.items || []);
      setUnread(data.unread || 0);
    } catch {
      /* ignore transient */
    }
  };

  useEffect(() => {
    load();
    const timer = setInterval(load, 45000);
    return () => clearInterval(timer);
  }, []);

  useEffect(() => {
    const onClick = (e) => {
      if (boxRef.current && !boxRef.current.contains(e.target)) setOpen(false);
    };
    document.addEventListener("mousedown", onClick);
    return () => document.removeEventListener("mousedown", onClick);
  }, []);

  const openNotification = async (n) => {
    setOpen(false);
    if (!n.is_read) {
      try { await api.post(`/notifications/${n.id}/read`, {}); } catch { /* ignore */ }
      setUnread((u) => Math.max(0, u - 1));
      setItems((arr) => arr.map((x) => (x.id === n.id ? { ...x, is_read: 1 } : x)));
    }
    if (n.link) navigate(n.link);
  };

  const markAll = async () => {
    try { await api.post("/notifications/read-all", {}); } catch { /* ignore */ }
    setUnread(0);
    setItems((arr) => arr.map((x) => ({ ...x, is_read: 1 })));
  };

  return (
    <div className="notif-box" style={{ position: "relative", display: "inline-block" }} ref={boxRef}>
      <button
        className="notif-bell"
        onClick={() => setOpen((o) => !o)}
        title="Notifications"
        style={{
          background: "none",
          border: "none",
          cursor: "pointer",
          fontSize: "1.25rem",
          position: "relative",
          marginRight: "1rem",
        }}
      >
        🔔
        {unread > 0 && (
          <span
            style={{
              position: "absolute",
              top: "-6px",
              right: "-8px",
              background: "#e11d48",
              color: "#fff",
              borderRadius: "10px",
              fontSize: "0.68rem",
              lineHeight: "1",
              padding: "3px 5px",
              minWidth: "16px",
            }}
          >
            {unread > 99 ? "99+" : unread}
          </span>
        )}
      </button>

      {open && (
        <div
          className="notif-dropdown"
          style={{
            position: "absolute",
            right: 0,
            top: "calc(100% + 8px)",
            width: 340,
            maxWidth: "80vw",
            background: "#fff",
            border: "1px solid #e5e7eb",
            borderRadius: 10,
            boxShadow: "0 8px 24px rgba(0,0,0,.15)",
            zIndex: 1000,
            overflow: "hidden",
          }}
        >
          <div
            style={{
              display: "flex",
              justifyContent: "space-between",
              alignItems: "center",
              padding: "10px 14px",
              borderBottom: "1px solid #e5e7eb",
              background: "#fafafa",
            }}
          >
            <strong style={{ fontSize: ".85rem" }}>Notifications</strong>
            {unread > 0 && (
              <button
                onClick={markAll}
                style={{ background: "none", border: "none", color: "#2563eb", cursor: "pointer", fontSize: ".78rem" }}
              >
                Mark all as read
              </button>
            )}
          </div>
          <div style={{ maxHeight: 360, overflowY: "auto" }}>
            {items.length === 0 && (
              <div style={{ padding: 18, color: "#6b7280", fontSize: ".85rem", textAlign: "center" }}>
                No notifications yet.
              </div>
            )}
            {items.map((n) => (
              <button
                key={n.id}
                onClick={() => openNotification(n)}
                style={{
                  display: "block",
                  width: "100%",
                  textAlign: "left",
                  background: n.is_read ? "#fff" : "#eff6ff",
                  border: "none",
                  borderBottom: "1px solid #f3f4f6",
                  padding: "10px 14px",
                  cursor: "pointer",
                }}
              >
                <div style={{ fontWeight: n.is_read ? 400 : 600, fontSize: ".85rem", color: "#111" }}>
                  {n.title}
                </div>
                <div style={{ fontSize: ".78rem", color: "#4b5563", marginTop: 2 }}>{n.message}</div>
                <div style={{ fontSize: ".72rem", color: "#9ca3af", marginTop: 4 }}>
                  {timeAgo(n.date_created)}
                </div>
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}