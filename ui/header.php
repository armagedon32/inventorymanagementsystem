<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="x-ua-compatible" content="ie=edge">

  <title>PROPERTY | CUSTODIAN </title>

  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <link rel="stylesheet" href="../plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../plugins/sweetalert2/sweetalert2.min.css">
  <link rel="stylesheet" href="../plugins/toastr/toastr.min.css">

  <script>
    if (window.history.replaceState) {
      window.history.replaceState(null, null, window.location.href);
    }
    
    // Refresh selections and clear forms when navigating back
    window.addEventListener("pageshow", function (event) {
      var historyTraversal = event.persisted || 
                             (typeof window.performance != "undefined" && 
                              window.performance.navigation.type === 2);
      if (historyTraversal) {
        // Find all forms and reset them
        var forms = document.getElementsByTagName('form');
        for (var i = 0; i < forms.length; i++) {
          forms[i].reset();
        }
        // If there are specific dynamic selects that need manual clearing
        var itemSelects = document.querySelectorAll('select[id="itemSelect"], select[id="instructorSelect"]');
        itemSelects.forEach(function(select) {
          var defaultOption = select.querySelector('option[value=""]');
          select.innerHTML = '';
          if (defaultOption) {
            select.appendChild(defaultOption);
          } else {
            var opt = document.createElement('option');
            opt.value = '';
            opt.text = '-- Select --';
            select.appendChild(opt);
          }
        });
      }
    });
  </script>

  <style>
    html, body {
      height: 100%;
      overflow: hidden;
    }
    .wrapper {
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    .main-header {
      position: fixed;
      top: 0;
      right: 0;
      left: 300px; /* Adjusted to new sidebar width */
      z-index: 1030;
      margin-left: 0 !important;
      background-color: #ffffff !important;
      border-bottom: 1px solid #dee2e6;
    }
    .main-sidebar {
      height: 100vh !important;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1031;
      background-color: #80d384 !important;
      overflow-y: auto;
      width: 300px; /* Increased width */
    }
    .content-wrapper {
      margin-left: 300px !important; /* Adjusted to new sidebar width */
      margin-top: 57px;
      margin-bottom: 57px;
      height: calc(100vh - 114px);
      overflow-y: auto;
      background-color: #f4f6f9;
    }
    .main-footer {
      position: fixed;
      bottom: 0;
      right: 0;
      left: 300px; /* Adjusted to new sidebar width */
      z-index: 1029;
      margin-left: 0 !important;
      background-color: #ffffff !important;
      color: #1b5e20 !important;
      border-top: 1px solid #dee2e6;
      padding: 15px;
      height: 57px;
    }
    .main-header .nav-link {
      color: #1b5e20 !important;
    }
    .sidebar-collapse .main-header,
    .sidebar-collapse .main-footer {
      left: 4.6rem;
    }
    .sidebar-collapse .content-wrapper {
      margin-left: 4.6rem !important;
    }
    .main-sidebar .nav-link {
      color: #ffffff;
    }
    .main-sidebar .nav-link:hover {
      background-color: #388e3c !important;
      color: #ffffff !important;
    }
    .main-sidebar .nav-link.active {
      background-color: #1b5e20 !important;
      color: #ffffff !important;
    }
    .user-panel .info {
      display: flex;
      flex-direction: column;
      padding-left: 15px;
      padding-top: 10px;
      line-height: 1.1;
    }
    .user-panel .info span {
      color: #1b5e20 !important;
      font-weight: 700;
      font-size: 1.15rem;
      display: block;
      letter-spacing: 0.1px;
      margin-bottom: 2px;
    }
    .user-panel .info small {
      color: #1b5e20;
      font-weight: 600;
      display: block;
      text-transform: uppercase;
      font-size: 0.7rem;
      opacity: 0.75;
      letter-spacing: 0.8px;
    }
    .user-panel .image img {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border: 2px solid #ffffff;
      box-shadow: 0 3px 6px rgba(0,0,0,0.12);
      transition: transform 0.3s ease;
    }
    .user-panel .image img:hover {
      transform: scale(1.05);
    }
    .brand-link {
      background-color: #1b5e20 !important;
      color: #ffffff !important;
    }
    .brand-link:hover {
      background-color: #388e3c !important;
      color: #ffffff !important;
    }
    .user-actions {
      padding: 0 10px;
      margin-top: 40px;
    }
    .btn-account {
      border-radius: 8px;
      padding: 8px 12px;
      font-size: 0.85rem;
      font-weight: 600;
      text-align: left;
      transition: all 0.3s ease;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      border: 1px solid rgba(255,255,255,0.2);
    }
    .btn-password {
      background-color: rgba(255,255,255,0.15);
      color: #ffffff !important;
    }
    .btn-password:hover {
      background-color: rgba(255,255,255,0.25);
      transform: translateX(5px);
    }
    .btn-logout {
      background-color: #d32f2f;
      color: #ffffff !important;
      border: none;
    }
    .btn-logout:hover {
      background-color: #b71c1c;
      transform: translateX(5px);
      box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }
  </style>
</head>

<body class="hold-transition sidebar-mini">
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" href="#" role="button"><i class=></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block"></li>
    </ul>
    <ul class="navbar-nav ml-auto"></ul>
  </nav>

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar elevation-4">
    <div class="sidebar">

      <!-- User Panel -->
      <div class="user-panel mt-3 pb-3 mb-0">
        <div class="d-flex align-items-center px-2">
          <div class="image">
            <script>
              window.currentUserId = "<?php echo $_SESSION['userid']; ?>";
            </script>
            <?php
            if (!isset($_SESSION['photo']) || empty($_SESSION['photo'])) {
                include_once 'connectdb.php';
                $stmt = $pdo->prepare("SELECT photo FROM tbl_user WHERE userid = :id");
                $stmt->execute([':id' => $_SESSION['userid']]);
                $userPhoto = $stmt->fetchColumn();
                if ($userPhoto) {
                    $_SESSION['photo'] = $userPhoto;
                }
            }
            if (!empty($_SESSION['photo'])): ?>
              <img id="sidebarUserPhoto" src="../ui/uploads/<?php echo $_SESSION['photo']; ?>" class="img-circle elevation-2" alt="User Image">
            <?php else: ?>
              <img id="sidebarUserPhoto" src="../dist/img/user.jpg" class="img-circle elevation-2" alt="User Image">
            <?php endif; ?>
          </div>
          <div class="info">
            <span id="sidebarUserFullname"><?php echo $_SESSION['fullname']; ?></span>
            <small id="sidebarUserRole"><i class="fas fa-user-shield mr-1"></i><?php echo $_SESSION['role']; ?></small>
          </div>
        </div>

        <?php
        $stmtOIC = $pdo->query("SELECT id, office_name, address FROM tbl_office WHERE (office_name LIKE '%Property%' OR office_name LIKE '%President%') AND is_archived = 0");
        $oics = $stmtOIC->fetchAll(PDO::FETCH_ASSOC);
        $prop_oic = "MARITES MENDIGORIN";
        $pres_oic = "DR. ROSELY H. AGUSTIN";
        foreach ($oics as $o) {
            if (stripos($o['office_name'], 'Property') !== false && !empty($o['address'])) $prop_oic = $o['address'];
            if (stripos($o['office_name'], 'President') !== false && !empty($o['address'])) $pres_oic = $o['address'];
        }
        ?>
        <script>
          window.globalOIC = {
              property: "<?= addslashes($prop_oic) ?>",
              president: "<?= addslashes($pres_oic) ?>"
          };
        </script>

        <div class="user-actions mt-4">
          <a href="changepassword.php" class="btn-account btn-password">
            <i class="fas fa-user-lock mr-2"></i> Change Password
          </a>
          <a href="logout.php" class="btn-account btn-logout">
            <i class="fas fa-sign-out-alt mr-2"></i> Logout
          </a>
        </div>
        <hr style="border-top: 1px solid rgba(255,255,255,0.3); margin: 0.5rem 0.8rem 0.2rem;">
      </div>
      <!-- /.user-panel -->

      <!-- Sidebar Menu -->
      <nav class="mt-0">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

          <li class="nav-header" style="color: #1b5e20; font-weight: 800; font-size: 0.75rem; padding: 0 1rem; text-transform: uppercase; letter-spacing: 1px;">Main Navigation</li>

          <li class="nav-item">
            <a href="dashboard.php" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>

          <?php if ($_SESSION['role'] !== 'Student Assistant' && $_SESSION['role'] !== 'Intern'): ?>
          <li class="nav-item">
            <?php
            $stmtCount = $pdo->query("SELECT COUNT(*) FROM password_reset_requests WHERE status = 'pending'");
            $pendingCount = $stmtCount->fetchColumn();
            ?>
            <a href="registration.php" class="nav-link">
              <i class="nav-icon fas fa-users-cog"></i>
              <p>
                User Management
                <?php if ($pendingCount > 0): ?>
                  <span class="badge badge-warning right"><?php echo $pendingCount; ?></span>
                <?php endif; ?>
              </p>
            </a>
          </li>
          <?php endif; ?>

          <li class="nav-item">
            <a href="office.php" class="nav-link">
              <i class="nav-icon fas fa-building"></i>
              <p>Offices / Departments</p>
            </a>
          </li>

          <!-- Inventory -->
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-boxes"></i>
              <p>
                Inventory
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="category.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Category</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="addproduct.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Supply Registration</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="addproperty.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Property Registration</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="propertylist.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Properties Inventory</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="ptr_record.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Transfer Property</p>
                </a>
              </li>
            </ul>
          </li>

          <!-- Stock Management -->
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-exchange-alt"></i>
              <p>
                Stock Management
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="productlist.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Stock In</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="stockout.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Stock Out</p>
                </a>
              </li>
            </ul>
          </li>

          <?php if ($_SESSION['role'] !== 'Student Assistant' && $_SESSION['role'] !== 'Intern'): ?>
          <!-- Issuance / PAR -->
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-file-signature"></i>
              <p>
                Issuance / PAR
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="rse.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>RSE</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="ris.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>RIS</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="facility.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>RF</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="ptr.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>PTR</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="incidentrep.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Incident Form</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="maintenancerep.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Maintenance Form</p>
                </a>
              </li>
            </ul>
          </li>
          <?php endif; ?>

          <?php if ($_SESSION['role'] !== 'Student Assistant' && $_SESSION['role'] !== 'Intern'): ?>
          <!-- Reports -->
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-chart-bar"></i>
              <p>
                Reports
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="incidentrecord.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Incident Report</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="maintenancerecords.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Maintenance Report</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="rseslip.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>RSE Slip</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="borrowed.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>RI Slip</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="facility_record.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>RF Slip</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="disposal.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Disposal Item</p>
                </a>
              </li>
            </ul>
          </li>
          <?php endif; ?>

          <?php if ($_SESSION['role'] !== 'Student Assistant' && $_SESSION['role'] !== 'Intern'): ?>
          <li class="nav-header" style="color: #1b5e20; font-weight: 800; font-size: 0.75rem; padding: 1.5rem 1rem 0.5rem; text-transform: uppercase; letter-spacing: 1px;">Settings</li>

          <li class="nav-item">
            <a href="utilities.php" class="nav-link">
              <i class="nav-icon fas fa-tools"></i>
              <p>Utilities</p>
            </a>
          </li>
          <?php endif; ?>

        </ul>
      </nav>
      <!-- /.sidebar-menu -->

    </div>
    <!-- /.sidebar -->
  </aside>

</html>