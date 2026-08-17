import { Routes, Route, Navigate } from "react-router-dom";
import { useAuth, ProtectedRoute } from "./context/AuthContext";
import Layout from "./components/Layout";
import Login from "./pages/Login";
import Dashboard from "./pages/Dashboard";
import Products from "./pages/Products";
import AddProduct from "./pages/AddProduct";
import EditProduct from "./pages/EditProduct";
import ViewProduct from "./pages/ViewProduct";
import StockIn from "./pages/StockIn";
import StockOut from "./pages/StockOut";
import StockHistory from "./pages/StockHistory";
import Users from "./pages/Users";
import Forecasting from "./pages/Forecasting";
import ChangePassword from "./pages/ChangePassword";

export default function App() {
  const { user } = useAuth();

  return (
    <Routes>
      <Route
        path="/login"
        element={user ? <Navigate to="/" replace /> : <Login />}
      />

      <Route
        element={
          <ProtectedRoute>
            <Layout />
          </ProtectedRoute>
        }
      >
        <Route path="/" element={<Dashboard />} />
        <Route path="/products" element={<Products />} />
        <Route path="/products/add" element={<AddProduct />} />
        <Route path="/products/:id/edit" element={<EditProduct />} />
        <Route path="/products/:id" element={<ViewProduct />} />
        <Route path="/products/:id/stock-in" element={<StockIn />} />
        <Route path="/products/:id/stock-out" element={<StockOut />} />
        <Route path="/products/:id/history" element={<StockHistory />} />
        <Route path="/users" element={<Users />} />
        <Route path="/forecasting" element={<Forecasting />} />
        <Route path="/change-password" element={<ChangePassword />} />
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}