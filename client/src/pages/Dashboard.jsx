import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { api } from "../api/client";
import { useAuth } from "../context/AuthContext";
import {
  BarChart,
  Bar,
  PieChart,
  Pie,
  Cell,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
  ResponsiveContainer,
} from "recharts";

const PIE_COLORS = ["#4e73df", "#1cc88a", "#36b9cc", "#f6c23e", "#e74a3b", "#858796", "#2e59d9"];

export default function Dashboard() {
  const { user } = useAuth();
  const [data, setData] = useState(null);
  const [error, setError] = useState("");

  useEffect(() => {
    api
      .get("/dashboard/summary")
      .then(setData)
      .catch((e) => setError(e.message));
  }, []);

  if (error) return <div className="alert alert-error">{error}</div>;
  if (!data) return <div className="empty">Loading dashboard...</div>;

  return (
    <div>
      <div className="row mb-3">
        <div className="col">
          <Link to="/forecasting">
            <div className="card-box blue">
              <i className="bg-icon">▤</i>
              <h3>{data.totalProducts}</h3>
              <p>Total Products</p>
            </div>
          </Link>
        </div>
        <div className="col">
          <div className="card-box green">
            <i className="bg-icon">▦</i>
            <h3>{data.totalStock}</h3>
            <p>Total Stock Quantity</p>
          </div>
        </div>
        <div className="col">
          <Link to="/products">
            <div className="card-box orange">
              <i className="bg-icon">!</i>
              <h3>{data.lowStock}</h3>
              <p>Low Stock Items</p>
            </div>
          </Link>
        </div>
        <div className="col">
          <Link to="/products">
            <div className="card-box red">
              <i className="bg-icon">×</i>
              <h3>{data.outOfStock}</h3>
              <p>Out of Stock</p>
            </div>
          </Link>
        </div>
      </div>

      <div className="row mb-3">
        {user?.role === "Admin" && (
          <div className="col">
            <Link to="/users">
              <div className="card-box gray">
                <i className="bg-icon">◈</i>
                <h3>{data.totalUsers}</h3>
                <p>Total Users</p>
              </div>
            </Link>
          </div>
        )}
        <div className="col">
          <Link to="/products">
            <div className="card-box blue">
              <i className="bg-icon">↘</i>
              <h3>{data.totalStockout}</h3>
              <p>Stock Out (30 days)</p>
            </div>
          </Link>
        </div>
      </div>

      <div className="row">
        <div className="col">
          <div className="chart-box">
            <h5>Acquisition Distribution</h5>
            <div style={{ height: 300 }}>
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={data.acquisition}
                    dataKey="total"
                    nameKey="acquisition_type"
                    innerRadius="55%"
                    outerRadius="80%"
                    label
                  >
                    {data.acquisition.map((_, i) => (
                      <Cell key={i} fill={PIE_COLORS[i % PIE_COLORS.length]} />
                    ))}
                  </Pie>
                  <Tooltip />
                </PieChart>
              </ResponsiveContainer>
            </div>
          </div>
        </div>

        <div className="col">
          <div className="chart-box">
            <h5>Category Distribution</h5>
            <div style={{ height: 300 }}>
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={data.categories}
                    dataKey="total"
                    nameKey="category_name"
                    innerRadius="55%"
                    outerRadius="80%"
                    label
                  >
                    {data.categories.map((_, i) => (
                      <Cell key={i} fill={PIE_COLORS[i % PIE_COLORS.length]} />
                    ))}
                  </Pie>
                  <Tooltip />
                </PieChart>
              </ResponsiveContainer>
            </div>
          </div>
        </div>
      </div>

      <div className="row">
        <div className="col">
          <div className="chart-box">
            <h5>Stock Out - Last 7 Days</h5>
            <div style={{ height: 260 }}>
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={data.last7Stockout}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="day" />
                  <YAxis allowDecimals={false} />
                  <Tooltip />
                  <Bar dataKey="total" name="Qty" fill="#1b5e20" radius={[6, 6, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </div>
        </div>

        <div className="col">
          <div className="chart-box">
            <h5>Recent Activity</h5>
            <table>
              <thead>
                <tr>
                  <th>User</th>
                  <th>Action</th>
                  <th>When</th>
                </tr>
              </thead>
              <tbody>
                {data.recentActivity.map((a) => (
                  <tr key={a.id}>
                    <td>{a.fullname || "System"}</td>
                    <td>{a.action}</td>
                    <td>{a.date_created}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}