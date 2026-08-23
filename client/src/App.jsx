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
import Reservations from "./pages/Reservations";
import NewReservation from "./pages/NewReservation";
import ReservationView from "./pages/ReservationView";
import Reports from "./pages/Reports";
import Forecasting from "./pages/Forecasting";
import DepartmentReport from "./pages/DepartmentReport";
import AssetTracking from "./pages/AssetTracking";
import Settings from "./pages/Settings";
import Admin from "./pages/Admin";
import AuditLogs from "./pages/AuditLogs";
import Archive from "./pages/Archive";

function StockRedirect() {
  const location = useLocation();
  return <Navigate to={location.pathname.replace(/^\/products/, "/stock") + location.search} replace />;
}

function AdminRoute({ children }) {
  const { user } = useAuth();
  if (user?.role !== "Admin") return <Navigate to="/" replace />;
  return children;
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
        <Route path="/stock" element={<AdminRoute><Products type="Stock" /></AdminRoute>} />
        <Route path="/stock/archive" element={<AdminRoute><Archive type="Stock" /></AdminRoute>} />
        <Route path="/stock/add" element={<AdminRoute><AddProduct type="Stock" /></AdminRoute>} />
        <Route path="/stock/:id/edit" element={<AdminRoute><EditProduct type="Stock" /></AdminRoute>} />
        <Route path="/stock/:id" element={<AdminRoute><ViewProduct type="Stock" /></AdminRoute>} />
        <Route path="/stock/:id/stock-in" element={<AdminRoute><StockIn /></AdminRoute>} />
        <Route path="/stock/:id/stock-out" element={<AdminRoute><StockOut /></AdminRoute>} />
        <Route path="/stock/:id/history" element={<AdminRoute><StockHistory /></AdminRoute>} />
        <Route path="/assets" element={<AdminRoute><Products type="Asset" /></AdminRoute>} />
        <Route path="/assets/archive" element={<AdminRoute><Archive type="Asset" /></AdminRoute>} />
        <Route path="/assets/add" element={<AdminRoute><AddProduct type="Asset" /></AdminRoute>} />
        <Route path="/assets/:id/edit" element={<AdminRoute><EditProduct type="Asset" /></AdminRoute>} />
        <Route path="/assets/:id/assign" element={<AdminRoute><AssignAsset /></AdminRoute>} />
        <Route path="/assets/:id" element={<AdminRoute><ViewProduct type="Asset" /></AdminRoute>} />
        <Route path="/products" element={<Navigate to="/stock" replace />} />
        <Route path="/products/*" element={<StockRedirect />} />
        <Route path="/users" element={<AdminRoute><Users /></AdminRoute>} />
        <Route path="/categories" element={<AdminRoute><Categories /></AdminRoute>} />
        <Route path="/requisitions" element={<Requisitions />} />
        <Route path="/requisitions/new" element={<NewRequisition />} />
        <Route path="/requisitions/:id" element={<RequisitionView />} />
        <Route path="/reservations" element={<Reservations />} />
        <Route path="/reservations/new" element={<NewReservation />} />
        <Route path="/reservations/:id" element={<ReservationView />} />
        <Route path="/reports" element={<AdminRoute><Reports /></AdminRoute>} />
        <Route path="/forecasting" element={<AdminRoute><Forecasting /></AdminRoute>} />
        <Route path="/department-reports" element={<AdminRoute><DepartmentReport /></AdminRoute>} />
        <Route path="/asset-tracking" element={<AdminRoute><AssetTracking /></AdminRoute>} />
        <Route path="/settings" element={<Settings />} />
        <Route path="/admin" element={<AdminRoute><Admin /></AdminRoute>} />
        <Route path="/audit-logs" element={<AdminRoute><AuditLogs /></AdminRoute>} />
        <Route path="/change-password" element={<Navigate to="/settings" replace />} />
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}