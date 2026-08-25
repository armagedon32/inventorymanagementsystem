<?php
include_once 'connectdb.php';
session_start();

// ===================== LOGIN CHECK =====================
if (!isset($_SESSION['useremail']) || !in_array($_SESSION['role'], ["Admin","Intern","Student Assistant"])) {
    header('Location: ../index.php');
    exit();
}

// ===================== PASSWORD CHANGE CHECK =====================
if (isset($_SESSION['must_change_password'])) {
    header('Location: ../reset_change_password.php');
    exit();
}

// ===================== HEADERS =====================
if($_SESSION['role'] == "Admin"){
    include_once "header.php";
}else{
    include_once "headeruser.php";
}

// ===================== LOGIN SUCCESS ALERT =====================
$showLoginAlert = false;
if (isset($_SESSION['login_success'])) {
    $showLoginAlert = true;
    unset($_SESSION['login_success']); // remove so it only shows once
}

/* ===============================
   DASHBOARD SUMMARY
================================ */

// Total Products
$total_products = $pdo->query("SELECT COUNT(*) FROM tbl_product")->fetchColumn();

// Total Stock
$total_stock = $pdo->query("SELECT IFNULL(SUM(stock),0) FROM tbl_product")->fetchColumn();

// Low Stock
$low_stock_count = $pdo->query("
SELECT COUNT(*) FROM tbl_product 
WHERE stock <= reorder_level AND stock > 0
")->fetchColumn();

// Out of Stock
$out_of_stock_count = $pdo->query("
SELECT COUNT(*) FROM tbl_product 
WHERE stock = 0
")->fetchColumn();

// Total Users
$total_users = $pdo->query("SELECT COUNT(*) FROM tbl_user WHERE is_archived = 0")->fetchColumn();

// Disposal Items
$disposal_count = $pdo->query("SELECT COUNT(*) FROM tbl_disposal WHERE disposed_at IS NOT NULL")->fetchColumn();

/* ===============================
   EQUIPMENT OVERDUE
================================ */
$stmtBorrow = $pdo->prepare("
SELECT COUNT(*) 
FROM ris_header
WHERE is_returned = 0
AND end_datetime < NOW()
");
$stmtBorrow->execute();
$total_overdue = $stmtBorrow->fetchColumn();

/* ===============================
   MAINTENANCE ALERTS
================================ */
// Overdue
$stmtMaintOver = $pdo->prepare("
SELECT COUNT(*) 
FROM maintenance_reports
WHERE next_maintenance_date < CURDATE()
");
$stmtMaintOver->execute();
$maintenance_overdue = $stmtMaintOver->fetchColumn();

// Due Soon (7 days)
$stmtMaintSoon = $pdo->prepare("
SELECT COUNT(*) 
FROM maintenance_reports
WHERE next_maintenance_date 
BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
");
$stmtMaintSoon->execute();
$maintenance_due = $stmtMaintSoon->fetchColumn();

$total_maintenance = $maintenance_overdue + $maintenance_due;

/* ===============================
   TOTAL NOTIFICATIONS
================================ */
$total_notifications = $total_overdue + $total_maintenance + $low_stock_count + $out_of_stock_count;

/* ===============================
   CHART DATA
================================ */
// Acquisition Type (Combined Products & Properties)
$acqQuery = $pdo->query("
SELECT acquisition_type, SUM(total) as total FROM (
    SELECT acquisition_type, COUNT(*) as total FROM tbl_product GROUP BY acquisition_type
    UNION ALL
    SELECT acquisition_type, COUNT(*) as total FROM tbl_property GROUP BY acquisition_type
) t GROUP BY acquisition_type
");
$acqLabels = [];
$acqTotals = [];
while($row = $acqQuery->fetch(PDO::FETCH_ASSOC)){
    $acqLabels[] = $row['acquisition_type'];
    $acqTotals[] = $row['total'];
}

// Category Distribution (Combined Products & Properties)
$catQuery = $pdo->query("
SELECT category_name, SUM(total) as total FROM (
    -- Products
    SELECT COALESCE(c.category, 'Uncategorized') as category_name, COUNT(*) as total
    FROM tbl_product p
    LEFT JOIN tbl_category c ON p.category = c.catid
    GROUP BY COALESCE(c.category, 'Uncategorized')
    
    UNION ALL
    
    -- Properties
    SELECT COALESCE(c.category, 'Uncategorized') as category_name, COUNT(*) as total
    FROM tbl_property prop
    LEFT JOIN tbl_item i ON prop.item_name = i.item_name
    LEFT JOIN tbl_category c ON i.category_id = c.catid
    GROUP BY COALESCE(c.category, 'Uncategorized')
) t GROUP BY category_name
");
$catLabels = [];
$catTotals = [];
while($row = $catQuery->fetch(PDO::FETCH_ASSOC)){
    $catLabels[] = $row['category_name'];
    $catTotals[] = $row['total'];
}
?>

<!-- Styles -->
<style>
.content-wrapper{background:#f4f6f9;padding-bottom:50px;}
.card-box {
    border-radius: 20px;
    padding: 25px;
    color: white;
    margin-bottom: 20px;
    box-shadow: 0 10px 20px rgba(0,0,0,0.15), 0 6px 6px rgba(0,0,0,0.2); /* 3D effect */
    transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.1);
}

.card-box:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}

.card-box i.bg-icon {
    position: absolute;
    right: -10px;
    bottom: -10px;
    font-size: 80px;
    opacity: 0.2;
    transition: 0.3s;
}

.card-box:hover i.bg-icon {
    transform: scale(1.2) rotate(-10deg);
    opacity: 0.3;
}

.card-box h3, .card-box p, .card-box a {
    color: white;
    text-decoration: none;
    position: relative;
    z-index: 2;
}

.blue { background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); }
.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
.orange { background: linear-gradient(135deg, #f09819 0%, #edde5d 100%); }
.red { background: linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%); }
.red-blink { animation: blink 1s infinite; background: linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%); }
.gray { background: linear-gradient(135deg, #3e5151 0%, #decba4 100%); }

@keyframes blink { 0%, 50%, 100% { opacity: 1; } 25%, 75% { opacity: 0.7; } }
.white-box { 
    background: white; 
    border-radius: 20px; 
    padding: 30px; 
    margin-bottom: 25px; 
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1); 
    border: none; 
    transition: 0.3s;
}
.white-box:hover {
    box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.15);
}
.chart-container { position: relative; height: 350px; width: 100%; }
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0 text-dark">Inventory Dashboard</h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

<!-- SUMMARY CARDS -->
<div class="row">

  <div class="col-md-3">
    <div class="card-box <?= ($total_overdue > 0) ? 'red-blink' : 'blue' ?>">
      <a href="borrowed.php?filter=overdue">
        <i class="fas fa-history bg-icon"></i>
        <h3><?= $total_overdue ?></h3>
        <p>Equipment Overdue</p>
      </a>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card-box <?= ($total_maintenance > 0) ? 'red-blink' : 'green' ?>">
      <a href="maintenancerecords.php">
        <i class="fas fa-tools bg-icon"></i>
        <h3><?= $total_maintenance ?></h3>
        <p>Maintenance Alerts</p>
      </a>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card-box orange">
      <a href="productlist.php">
        <i class="fas fa-exclamation-triangle bg-icon"></i>
        <h3><?= $low_stock_count ?></h3>
        <p>Low Stock Items</p>
      </a>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card-box red">
      <a href="productlist.php">
        <i class="fas fa-times-circle bg-icon"></i>
        <h3><?= $out_of_stock_count ?></h3>
        <p>Out of Stock</p>
      </a>
    </div>
  </div>

 <?php if($_SESSION['role'] == "Admin"): ?>
<div class="col-md-3">
    <div class="card-box gray">
      <a href="registration.php">
        <i class="fas fa-users bg-icon"></i>
        <h3><?= $total_users ?></h3>
        <p>Total Users</p>
      </a>
    </div>
</div>
<?php endif; ?>

  <div class="col-md-3">
    <div class="card-box blue">
      <a href="disposal.php">
        <i class="fas fa-trash-alt bg-icon"></i>
        <h3><?= $disposal_count ?></h3>
        <p>Disposed Items</p>
      </a>
    </div>
  </div>

</div>

<!-- CHARTS -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="white-box">
            <h5 class="text-primary font-weight-bold mb-4">
                <i class="fas fa-hand-holding-usd mr-2"></i>Acquisition Distribution
            </h5>
            <div class="chart-container"><canvas id="acqChart"></canvas></div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="white-box">
            <h5 class="text-primary font-weight-bold mb-4">
                <i class="fas fa-th-large mr-2"></i>Category Distribution
            </h5>
            <div class="chart-container"><canvas id="catChart"></canvas></div>
        </div>
    </div>
</div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Function to show system alerts
    function showSystemAlerts() {
        let overdue = <?= $total_overdue ?>;
        let maintenance = <?= $total_maintenance ?>;
        let lowstock = <?= $low_stock_count ?>;
        let outstock = <?= $out_of_stock_count ?>;
        
        if(overdue > 0 || maintenance > 0 || lowstock > 0 || outstock > 0){
            Swal.fire({
                icon: 'warning',
                title: 'System Alerts',
                html: `<b>${overdue}</b> Equipment Overdue<br>
                      <b>${maintenance}</b> Maintenance Alerts<br>
                      <b>${lowstock}</b> Low Stock Items<br>
                      <b>${outstock}</b> Out of Stock`
            });
        }
    }

    // ✅ Login success alert (only once)
    <?php if($showLoginAlert): ?>
    Swal.fire({
        icon: 'success',
        title: 'Login Successfully',
        text: 'Welcome back!',
        timer: 2000,
        showConfirmButton: false
    }).then(() => {
        // Show system alerts after login success alert is finished
        showSystemAlerts();
    });
    <?php else: ?>
    // If not just logged in, show system alerts immediately
    showSystemAlerts();
    <?php endif; ?>

    // Modern Color Palette
    const modernColors = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', 
        '#858796', '#5a5c69', '#2e59d9', '#17a673', '#2c9faf'
    ];

    const chartOptions = {
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    padding: 20,
                    font: {
                        size: 12
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(255, 255, 255, 0.9)',
                titleColor: '#333',
                bodyColor: '#666',
                borderColor: '#e3e6f0',
                borderWidth: 1,
                padding: 12,
                displayColors: true,
                caretSize: 6,
            }
        },
        cutout: '70%', // For modern doughnut look
        animation: {
            animateScale: true,
            animateRotate: true
        }
    };

    // Acquisition Chart
    new Chart(document.getElementById("acqChart"), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($acqLabels) ?>,
            datasets: [{
                data: <?= json_encode($acqTotals) ?>,
                backgroundColor: modernColors,
                hoverBackgroundColor: modernColors,
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }]
        },
        options: chartOptions
    });

    // Category Chart
    new Chart(document.getElementById("catChart"), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($catLabels) ?>,
            datasets: [{
                data: <?= json_encode($catTotals) ?>,
                backgroundColor: modernColors,
                hoverBackgroundColor: modernColors,
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }]
        },
        options: chartOptions
    });
});
</script>

<?php include_once "footer.php"; ?>