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
import Organizations from "./pages/Organizations";
import Offices from "./pages/Offices";
import Instructors from "./pages/Instructors";
import DepartmentReport from "./pages/DepartmentReport";
import AssetTracking from "./pages/AssetTracking";
import Ptrs from "./pages/Ptrs";
import NewPtr from "./pages/NewPtr";
import PtrView from "./pages/PtrView";
import Disposal from "./pages/Disposal";
import Incidents from "./pages/Incidents";
import NewIncident from "./pages/NewIncident";
import IncidentView from "./pages/IncidentView";
import Maintenance from "./pages/Maintenance";
import MaintenanceForm from "./pages/MaintenanceForm";
import Facilities from "./pages/Facilities";
import NewFacility from "./pages/NewFacility";
import FacilityView from "./pages/FacilityView";
import Settings from "./pages/Settings";
import Admin from "./pages/Admin";
import AuditLogs from "./pages/AuditLogs";
import Archive from "./pages/Archive";

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
        <Route path="/stock/archive" element={<Archive type="Stock" />} />
        <Route path="/stock/add" element={<AddProduct type="Stock" />} />
        <Route path="/stock/:id/edit" element={<EditProduct type="Stock" />} />
        <Route path="/stock/:id" element={<ViewProduct type="Stock" />} />
        <Route path="/stock/:id/stock-in" element={<StockIn />} />
        <Route path="/stock/:id/stock-out" element={<StockOut />} />
        <Route path="/stock/:id/history" element={<StockHistory />} />
        <Route path="/assets" element={<Products type="Asset" />} />
        <Route path="/assets/archive" element={<Archive type="Asset" />} />
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
        <Route path="/reservations" element={<Reservations />} />
        <Route path="/reservations/new" element={<NewReservation />} />
        <Route path="/reservations/:id" element={<ReservationView />} />
        <Route path="/reports" element={<Reports />} />
        <Route path="/forecasting" element={<Forecasting />} />
        <Route path="/organizations" element={<Organizations />} />
        <Route path="/offices" element={<Offices />} />
        <Route path="/instructors" element={<Instructors />} />
        <Route path="/department-reports" element={<DepartmentReport />} />
        <Route path="/asset-tracking" element={<AssetTracking />} />
        <Route path="/ptr" element={<Ptrs />} />
        <Route path="/ptr/new" element={<NewPtr />} />
        <Route path="/ptr/:id" element={<PtrView />} />
        <Route path="/disposal" element={<Disposal />} />
        <Route path="/incidents" element={<Incidents />} />
        <Route path="/incidents/new" element={<NewIncident />} />
        <Route path="/incidents/:id" element={<IncidentView />} />
        <Route path="/maintenance" element={<Maintenance />} />
        <Route path="/maintenance/new" element={<MaintenanceForm />} />
        <Route path="/maintenance/:id/edit" element={<MaintenanceForm />} />
        <Route path="/facilities" element={<Facilities />} />
        <Route path="/facilities/new" element={<NewFacility />} />
        <Route path="/facilities/:id" element={<FacilityView />} />
        <Route path="/settings" element={<Settings />} />
        <Route path="/admin" element={<Admin />} />
        <Route path="/audit-logs" element={<AuditLogs />} />
        <Route path="/change-password" element={<Navigate to="/settings" replace />} />
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}