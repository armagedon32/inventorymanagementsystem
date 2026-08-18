import { Routes, Route, Navigate, useLocation, useParams } from "react-router-dom";
import { useAuth, ProtectedRoute } from "./context/AuthContext";
import Layout from "./components/Layout";
import Login from "./pages/Login";
import Dashboard from "./pages/Dashboard";
import Products from "./pages/Products";
import AddProduct from "./pages/AddProduct";
import EditProduct from "./pages/EditProduct";
import ViewProduct from "./pages/ViewProduct";
import AssignAsset from "./pages/AssignAsset";
import StockIn from "./pages/StockIn";
import StockOut from "./pages/StockOut";
import StockHistory from "./pages/StockHistory";
import Users from "./pages/Users";
import Categories from "./pages/Categories";
import Requisitions from "./pages/Requisitions";
import NewRequisition from "./pages/NewRequisition";
import RequisitionView from "./pages/RequisitionView";
import Forecasting from "./pages/Forecasting";
import ChangePassword from "./pages/ChangePassword";

function StockRedirect() {
  const location = useLocation();
  return <Navigate to={location.pathname.replace(/^\/products/, "/stock") + location.search} replace />;
}

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
        <Route path="/stock" element={<Products type="Stock" />} />
        <Route path="/stock/add" element={<AddProduct type="Stock" />} />
        <Route path="/stock/:id/edit" element={<EditProduct type="Stock" />} />
        <Route path="/stock/:id" element={<ViewProduct type="Stock" />} />
        <Route path="/stock/:id/stock-in" element={<StockIn />} />
        <Route path="/stock/:id/stock-out" element={<StockOut />} />
        <Route path="/stock/:id/history" element={<StockHistory />} />
        <Route path="/assets" element={<Products type="Asset" />} />
        <Route path="/assets/add" element={<AddProduct type="Asset" />} />
        <Route path="/assets/:id/edit" element={<EditProduct type="Asset" />} />
        <Route path="/assets/:id/assign" element={<AssignAsset />} />
        <Route path="/assets/:id" element={<ViewProduct type="Asset" />} />
        <Route path="/products" element={<Navigate to="/stock" replace />} />
        <Route path="/products/*" element={<StockRedirect />} />
        <Route path="/users" element={<Users />} />
        <Route path="/categories" element={<Categories />} />
        <Route path="/requisitions" element={<Requisitions />} />
        <Route path="/requisitions/new" element={<NewRequisition />} />
        <Route path="/requisitions/:id" element={<RequisitionView />} />
        <Route path="/forecasting" element={<Forecasting />} />
        <Route path="/change-password" element={<ChangePassword />} />
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}