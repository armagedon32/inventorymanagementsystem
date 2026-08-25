<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="x-ua-compatible" content="ie=edge">

  <title>PROPERTY | CUSTODIAN </title>

  <!-- Google Font: Source Sans Pro -->
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">

  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">

  <!-- Select2 -->
  <link rel="stylesheet" href="../plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">

  <!-- iCheck for checkboxes and radio inputs -->
  <link rel="stylesheet" href="../plugins/icheck-bootstrap/icheck-bootstrap.min.css">

  <!-- Theme style -->
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">

  <!-- DataTables -->
  <link rel="stylesheet" href="../plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../plugins/datatables-responsive/css/responsive.bootstrap4.min.css">

  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="../plugins/sweetalert2/sweetalert2.min.css">

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

  <!-- USER PANEL ALIGNMENT AND COLOR FIX -->
  <style>
    /* Full height layout with fixed elements */
    html, body {
      height: 100%;
      overflow: hidden; /* Prevent body from scrolling */
    }

    .wrapper {
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    /* Fixed Header */
    .main-header {
      position: fixed;
      top: 0;
      right: 0;
      left: 250px; /* sidebar width */
      z-index: 1030;
      margin-left: 0 !important;
      background-color: #ffffff !important;
      border-bottom: 1px solid #dee2e6;
    }

    /* Fixed Sidebar */
    .main-sidebar {
      height: 100vh !important;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1031;
      background-color: #80d384 !important;
      overflow-y: auto;
    }

    /* Content Wrapper - This is the only part that scrolls */
    .content-wrapper {
      margin-left: 250px !important;
      margin-top: 57px; /* header height */
      margin-bottom: 57px; /* footer height */
      height: calc(100vh - 114px); /* Viewport height minus header and footer */
      overflow-y: auto;
      background-color: #f4f6f9;
    }

    /* Fixed Footer */
    .main-footer {
      position: fixed;
      bottom: 0;
      right: 0;
      left: 250px;
      z-index: 1029;
      margin-left: 0 !important;
      background-color: #ffffff !important;
      color: #1b5e20 !important;
      border-top: 1px solid #dee2e6;
      padding: 15px;
      height: 57px;
    }

    /* Navbar icon color */
    .main-header .nav-link {
      color: #1b5e20 !important;
    }

    /* Responsive adjustments for collapsed sidebar */
    .sidebar-collapse .main-header,
    .sidebar-collapse .main-footer {
      left: 4.6rem; /* collapsed width */
    }
    .sidebar-collapse .content-wrapper {
      margin-left: 4.6rem !important;
    }

    /* Sidebar nav links */
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

    /* User panel info */
    .user-panel .info {
      display: flex;
      flex-direction: column;
      padding-left: 15px;
      padding-top: 10px; /* Further adjusted vertical position */
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

    /* User photo style */
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

    /* Brand logo */
    .brand-link {
      background-color: #1b5e20 !important;
      color: #ffffff !important;
    }
    .brand-link:hover {
      background-color: #388e3c !important;
      color: #ffffff !important;
    }

    /* User panel buttons */
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
          <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>

        <li class="nav-item d-none d-sm-inline-block">
          <!-- <a href="dashboard.php" class="nav-link"><b>Home</b></a> -->
        </li>
      </ul>
      <!-- Right navbar links -->
      <ul class="navbar-nav ml-auto">
      </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar elevation-4">

      <!-- Brand Logo -->
      <!-- <a href="dashboard.php" class="brand-link">
        <img src="../dist/img/logo-150x150.png" alt="Logo" class="brand-image img-circle elevation-3"
          style="opacity: .8">
        <span class="brand-text font-weight-light">Property Custodian</span>
      </a> -->

      <!-- Sidebar -->
      <div class="sidebar">

        <!-- User Panel -->
        <div class="user-panel mt-3 pb-3 mb-0">
          <div class="d-flex align-items-center px-2">
            <div class="image">
              <?php
              // Proactively fetch photo if missing or empty in session
              if (!isset($_SESSION['photo']) || empty($_SESSION['photo'])) {
                  include_once 'connectdb.php';
                  $stmt = $pdo->prepare("SELECT photo FROM tbl_user WHERE userid = :id");
                  $stmt->execute([':id' => $_SESSION['userid']]);
                  $userPhoto = $stmt->fetchColumn();
                  if ($userPhoto) {
                      $_SESSION['photo'] = $userPhoto;
                  }
              }

              if(!empty($_SESSION['photo'])): ?>
                <img src="../ui/uploads/<?php echo $_SESSION['photo']; ?>" class="img-circle elevation-2" alt="User Image">
              <?php else: ?>
                <img src="../dist/img/user.jpg" class="img-circle elevation-2" alt="User Image">
              <?php endif; ?>
            </div>
            <div class="info">
              <span><?php echo $_SESSION['fullname']; ?></span>
              <small><i class="fas fa-user-shield mr-1"></i><?php echo $_SESSION['role']; ?></small>
            </div>
          </div>

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

            <li class="nav-item">
              <a href="dashboard.php" class="nav-link">
                <i class="nav-icon fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
              </a>
            </li>

            <li class="nav-item">
              <a href="office.php" class="nav-link">
                <i class="nav-icon fas fa-building"></i>
                <p>Offices / Departments</p>
              </a>
            </li>

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

            <!--<li class="nav-item has-treeview">
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
                   </ul> -->
                <!-- <li class="nav-item">
                  <a href="incidentrep.php" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Incident Report</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="maintenancerep.php" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Maintenance Report</p>
                  </a>
                </li> -->
            </li>

           
                
          </ul>
        </nav>
        <!-- /.sidebar-menu -->

      </div>
      <!-- /.sidebar -->
    </aside>