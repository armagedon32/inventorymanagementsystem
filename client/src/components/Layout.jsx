import { NavLink, Outlet, useLocation } from "react-router-dom";
import { useAuth } from "../context/AuthContext";

const isAdmin = (user) => user?.role === "Admin";

export default function Layout() {
  const { user, logout } = useAuth();
  const location = useLocation();
  const pageTitle = location.pathname
    .split("/")
    .filter(Boolean)
    .map((s) => s[0].toUpperCase() + s.slice(1))
    .join(" / ") || "Dashboard";

  const navItems = [
    { to: "/", label: "Dashboard", icon: "▤", end: true },
    { to: "/forecasting", label: "Demand Forecasting", icon: "△" },
    ...(isAdmin(user)
      ? [{ to: "/users", label: "User Management", icon: "◈" }]
      : []),
    {
      section: "Stock Inventory",
      items: [
        { to: "/stock", label: "Supplies / Stock In", icon: "▤" },
        { to: "/stock/add", label: "Supply Registration", icon: "✚" },
        { to: "/categories", label: "Categories", icon: "☰" },
      ],
    },
    {
      section: "Asset Management",
      items: [
        { to: "/assets", label: "Assets", icon: "◉" },
        { to: "/assets/add", label: "Asset Registration", icon: "✚" },
      ],
    },
    { to: "/change-password", label: "Change Password", icon: "⚿" },
  ];

  return (
    <div className="wrapper">
      <aside className="sidebar">
        <div className="brand">
          <img src="/logo.svg" alt="Logo" />
          <div>
            PROPERTY &amp; SUPPLIES OFFICE
            <div style={{ fontSize: "0.65rem", fontWeight: 400, letterSpacing: 1 }}>
              Inventory System
            </div>
          </div>
        </div>

        <div className="user-panel">
          <img className="avatar" src={user?.photo || "/logo.svg"} alt="User" />
          <div className="info">
            <span>{user?.fullname}</span>
            <small>{user?.role}</small>
          </div>
        </div>

        <ul className="nav-links">
          <li className="nav-section">Main Navigation</li>
          {navItems.map((item) =>
            item.items ? (
              <li key={item.section}>
                <div className="nav-header">{item.section}</div>
                {item.items.map((i) => (
                  <NavLink key={i.to} to={i.to} className={({ isActive }) => (isActive ? "active" : "")}>
                    <span className="icon">{i.icon}</span>
                    {i.label}
                  </NavLink>
                ))}
              </li>
            ) : (
              <li key={item.to}>
                <NavLink to={item.to} end={item.end} className={({ isActive }) => (isActive ? "active" : "")}>
                  <span className="icon">{item.icon}</span>
                  {item.label}
                </NavLink>
              </li>
            )
          )}
        </ul>

        <div className="account-actions">
          <NavLink to="/change-password">
            <span>⚿</span> Change Password
          </NavLink>
          <a className="logout" onClick={logout}>
            <span>⏻</span> Logout
          </a>
        </div>
      </aside>

      <div className="main">
        <div className="navbar">
          <div className="page-title">{pageTitle}</div>
          <div style={{ fontSize: "0.85rem", color: "#333" }}>
            {user?.role} · {user?.useremail}
          </div>
        </div>
        <div className="content">
          <Outlet />
        </div>
      </div>
    </div>
  );
}