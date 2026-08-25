<?php
include_once 'connectdb.php';
session_start();

/* LOGIN CHECK */
if (!isset($_SESSION['useremail']) || $_SESSION['useremail'] == "") {
    header('Location: ../index.php');
    exit();
}

/* ALLOW ONLY INTERN / STUDENT ASSISTANT */
if (!in_array($_SESSION['role'], ["Intern", "Student Assistant"])) {
    header('Location: ../index.php'); // redirect all others, including Admin
    exit();
}

/* LOAD USER HEADER */
include_once "headeruser.php";
?>

<style>
.content-wrapper {
  background: #ffffff;
  min-height: 100vh;
  padding-bottom: 50px;
}
.modern-box {
  border-radius: 20px;
  padding: 28px;
  color: white;
  box-shadow: 0px 5px 18px rgba(0,0,0,0.12);
  transition: 0.3s ease;
  margin-bottom: 28px;
}
.modern-box:hover {
  transform: translateY(-8px);
}
.grad-blue { background: linear-gradient(135deg, #4f8dfc, #1e3fa0); }
.grad-red { background: linear-gradient(135deg, #f45f5f, #8e1d1d); }
.grad-green { background: linear-gradient(135deg, #1ecb96, #0a5f45); }
.grad-yellow { background: linear-gradient(135deg, #f7b74b, #c57a11); }
</style>

<div class="content-wrapper">

  <div class="content-header">
    <div class="container-fluid">
      <h1>Intern / Student Assistant Dashboard</h1>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="row">

        <?php
        // Get total offices
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_office");
        $stmt->execute();
        $total_office = $stmt->fetchColumn();

        // Get total stocks
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_property");
        $stmt->execute();
        $total_stocks = $stmt->fetchColumn();

        // Get total employees
        // $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_employee");
        // $stmt->execute();
        // $total_employees = $stmt->fetchColumn();

        // Get total departments
        // $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_department");
        // $stmt->execute();
        // $total_departments = $stmt->fetchColumn();
        ?>

        <!-- Offices -->
        <div class="col-lg-3 col-md-6 col-sm-12">
          <div class="modern-box grad-blue">
            <h3><?php echo $total_office; ?></h3>
            <p>Offices</p>
            <a href="office.php" class="btn btn-light btn-sm">More Info</a>
          </div>
        </div>

        <!-- Stocks -->
        <div class="col-lg-3 col-md-6 col-sm-12">
          <div class="modern-box grad-red">
            <h3><?php echo $total_stocks; ?></h3>
            <p>Stocks</p>
            <a href="stock.php" class="btn btn-light btn-sm">More Info</a>
          </div>
        </div>

        <!-- Employees -->
        <!-- <div class="col-lg-3 col-md-6 col-sm-12">
          <div class="modern-box grad-green">
            <h3><?php echo $total_employees; ?></h3>
            <p>Employees</p>
            <a href="employee.php" class="btn btn-light btn-sm">More Info</a>
          </div>
        </div>

          Departments -->
        <!-- <div class="col-lg-3 col-md-6 col-sm-12">
          <div class="modern-box grad-yellow">
            <h3><?php echo $total_departments; ?></h3>
            <p>Departments</p>
            <a href="department.php" class="btn btn-light btn-sm">More Info</a>
          </div>
        </div> --> 

      </div>
    </div>
  </div>

</div> 

<?php include_once "footer.php"; ?>