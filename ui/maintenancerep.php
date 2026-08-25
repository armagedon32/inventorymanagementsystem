<?php
include_once 'connectdb.php';
session_start();

if (!isset($_SESSION['useremail']) || ($_SESSION['role'] ?? '') == 'User') {
    header('location:../index.php');
    exit;
}

/* FETCH OFFICES */
$stmtoff = $pdo->prepare("SELECT id, office_name FROM tbl_office WHERE is_archived = 0 ORDER BY office_name ASC");
$stmtoff->execute();
$offices = $stmtoff->fetchAll(PDO::FETCH_ASSOC);

/* FETCH PROPERTIES WITHOUT EXISTING MAINTENANCE REPORT */
$stmtprop = $pdo->prepare("
    SELECT p.property_id, p.item_name, p.brand, p.serial_no,
           COALESCE(o.office_name, '') AS office_name
    FROM tbl_property p
    LEFT JOIN tbl_office o ON o.id = p.office_id
    WHERE p.is_archived = 0
      AND NOT EXISTS (
        SELECT 1 FROM maintenance_reports mr
        WHERE mr.serial_number = p.serial_no
        AND p.serial_no IS NOT NULL
        AND p.serial_no != ''
    )
    ORDER BY p.item_name ASC
");
$stmtprop->execute();
$properties = $stmtprop->fetchAll(PDO::FETCH_ASSOC);

/* AUTO GENERATE MAINTENANCE CODE */
$year = date("Y");
$stmtCode = $pdo->prepare("SELECT maintenance_code FROM maintenance_reports ORDER BY id DESC LIMIT 1");
$stmtCode->execute();
$lastCode = $stmtCode->fetch(PDO::FETCH_ASSOC);

if ($lastCode) {
    $num = intval(substr($lastCode['maintenance_code'], -4)) + 1;
    $maintenance_code = "MC-$year-" . str_pad($num,4,'0',STR_PAD_LEFT);
} else {
    $maintenance_code = "MC-$year-0001";
}

/* SAVE MAINTENANCE REPORT */
if (isset($_POST['submit_maintenance'])) {
    $property_id      = (int) $_POST['property_id'];
    $office           = $_POST['office'];
    $maintenance_code = $_POST['maintenance_code'];
    $maintenance_task = $_POST['maintenance_task'];
    $frequency_days   = (int) $_POST['frequency_days'];
    $prev_date        = $_POST['previous_maintenance_date'];

    /* GET PROPERTY DETAILS */
    $stmt = $pdo->prepare("SELECT item_name, brand, serial_no FROM tbl_property WHERE property_id=? AND is_archived = 0");
    $stmt->execute([$property_id]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($property) {
        $serial_number = $property['serial_no'];

        /* CHECK IF PROPERTY ALREADY HAS MAINTENANCE REPORT — skip check if serial is empty */
        $exists = 0;
        if (!empty($serial_number)) {
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM maintenance_reports WHERE serial_number = ?");
            $stmtCheck->execute([$serial_number]);
            $exists = $stmtCheck->fetchColumn();
        }

        if ($exists > 0) {
            $_SESSION['status'] = "This property already has a maintenance report.";
            $_SESSION['status_code'] = "error";
        } else {
            $item_name = $property['item_name'];
            $brand     = $property['brand'];

            /* NEXT MAINTENANCE DATE */
            $next_date = date('Y-m-d', strtotime($prev_date . " +{$frequency_days} days"));

            /* DAYS BEFORE DUE */
            $today = new DateTime();
            $next  = new DateTime($next_date);
            $days_before_due = $today->diff($next)->format('%r%a');

            /* INSERT MAINTENANCE REPORT */
            $stmtInsert = $pdo->prepare("
                INSERT INTO maintenance_reports
                (item_name,office,brand,serial_number,maintenance_code,maintenance_task,
                frequency_days,previous_maintenance_date,next_maintenance_date,days_before_due)
                VALUES (?,?,?,?,?,?,?,?,?,?)
            ");
            $stmtInsert->execute([
                $item_name,
                $office,
                $brand,
                $serial_number,
                $maintenance_code,
                $maintenance_task,
                $frequency_days,
                $prev_date,
                $next_date,
                $days_before_due
            ]);

            header("Location: ".$_SERVER['PHP_SELF']."?success=1");
            exit;
        }

    } else {
        $_SESSION['status'] = "Selected property not found";
        $_SESSION['status_code'] = "error";
    }
}

include_once "header.php";
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
.content-wrapper{padding:20px;}
.card{padding:20px;}
.form-row{display:flex;gap:15px;margin-bottom:15px;}
.form-group{flex:1;}
</style>

<div class="content-wrapper">

<?php if (isset($_SESSION['status'])): ?>
<script>
Swal.fire({
  icon: '<?= $_SESSION['status_code'] ?>',
  title: '<?= $_SESSION['status'] ?>',
  showConfirmButton: false,
  timer: 2000
});
</script>
<?php unset($_SESSION['status'], $_SESSION['status_code']); endif; ?>

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">
                        <a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
                        New Maintenance Report
                    </h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Maintenance Details</h3>
                </div>
                <div class="card-body">
                    <form method="POST" id="maintenanceForm">
                        <!-- ITEM INFORMATION -->
                        <h5>Item Information</h5>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Office</label>
                                <select id="officeSelect" name="office" class="form-control" required onchange="filterProperties()">
                                    <option value="">Select Office First</option>
                                    <?php foreach ($offices as $off): ?>
                                        <option value="<?= htmlentities($off['office_name']); ?>">
                                            <?= htmlentities($off['office_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Select Property</label>
                                <select id="propertySelect" name="property_id" class="form-control" required disabled onchange="fillPropertyDetails(this)">
                                    <option value="">Select Property</option>
                                    <?php foreach ($properties as $prop): ?>
                                        <option value="<?= $prop['property_id']; ?>"
                                                data-brand="<?= htmlentities($prop['brand']); ?>"
                                                data-serial="<?= htmlentities($prop['serial_no']); ?>"
                                                data-office="<?= htmlentities($prop['office_name']); ?>">
                                            <?= htmlentities($prop['item_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Brand</label>
                                <input type="text" id="brand" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label>Serial Number</label>
                                <input type="text" id="serial_number" class="form-control" readonly>
                            </div>
                        </div>

                        <!-- MAINTENANCE DETAILS -->
                        <h5 class="mt-4">Maintenance Details</h5>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Maintenance Code</label>
                                <input type="text" name="maintenance_code" class="form-control" value="<?= $maintenance_code ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Maintenance to be Performed</label>
                                <input type="text" name="maintenance_task" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Maintenance Frequency (Days)</label>
                                <input type="number" name="frequency_days" class="form-control" min="1" required>
                            </div>
                            <div class="form-group">
                                <label>Previous Maintenance Date</label>
                                <input type="date" name="previous_maintenance_date" class="form-control" required>
                            </div>
                        </div>

                        <button type="submit" name="submit_maintenance" class="btn btn-primary mt-3">
                            <i class="fa fa-save"></i> Save Maintenance Report
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
function fillPropertyDetails(select){
    var selectedOption = select.options[select.selectedIndex];
    document.getElementById('brand').value = selectedOption.getAttribute('data-brand');
    document.getElementById('serial_number').value = selectedOption.getAttribute('data-serial');
}

/* FILTER PROPERTY BY OFFICE NAME */
function filterProperties(){
    var officeName = document.getElementById('officeSelect').value;
    var propSelect = document.getElementById('propertySelect');
    var options = propSelect.options;

    propSelect.disabled = !officeName;

    for(var i = 0; i < options.length; i++){
        var option = options[i];
        var propOffice = option.getAttribute('data-office');
        option.style.display = (!officeName || propOffice === officeName) ? '' : 'none';
    }

    propSelect.value = '';
    document.getElementById('brand').value = '';
    document.getElementById('serial_number').value = '';
}
</script>

<?php
if(isset($_GET['success']) && $_GET['success'] == 1) {
?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Maintenance Report Saved',
    text: 'The Maintenance Report has been successfully saved!',
    timer: 3000,
    showConfirmButton: false
});
</script>
<?php
}
?>

<?php include_once "footer.php"; ?>