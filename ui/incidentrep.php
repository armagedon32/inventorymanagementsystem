<?php
include_once 'connectdb.php';
session_start();

if (!isset($_SESSION['useremail']) || ($_SESSION['role'] ?? '') == 'User') {
    header('location:../index.php');
    exit;
}

/* SUBMIT INCIDENT */
if (isset($_POST['submit_incident'])) {

    $report_no   = $_POST['report_number'];
    $reported_by = $_POST['reported_by'];
    $office      = $_POST['office'];
    $date        = $_POST['incident_date'];
    $time        = $_POST['incident_time'];
    $desc        = $_POST['description'];
    $extent      = $_POST['extent_of_damage'];

    // Insert main incident report
    $stmt = $pdo->prepare("
        INSERT INTO incident_reports
        (report_number, reported_by, office, incident_date, incident_time, description, extent_of_damage)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$report_no, $reported_by, $office, $date, $time, $desc, $extent]);
    $incident_id = $pdo->lastInsertId();

    // Insert items using property_id
    foreach ($_POST['item_name'] as $i => $property_id) {
        if ($property_id == '') continue;

        $stmtItem = $pdo->prepare("
            INSERT INTO incident_items
            (incident_id, property_id, quantity, serial_number, location, last_borrower)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmtItem->execute([
            $incident_id,
            $property_id,
            $_POST['quantity'][$i],
            $_POST['serial_number'][$i],
            $_POST['location'][$i],
            $_POST['last_borrower'][$i]
        ]);

        // Update property remarks to 'Unserviceable' without deducting quantity
        $stmtUpdateProp = $pdo->prepare("UPDATE tbl_property SET remarks = 'Unserviceable' WHERE property_id = ?");
        $stmtUpdateProp->execute([$property_id]);
    }

    // Log activity
    $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
    $stmtLog->execute([$_SESSION['userid'], "Created Incident Report: " . $report_no]);

    header("Location: ".$_SERVER['PHP_SELF']."?success=1");
    exit;
}

include_once "header.php";

/* FETCH OFFICES */
$stmtoff = $pdo->prepare("SELECT id, parent_id, office_name FROM tbl_office WHERE parent_id IS NOT NULL AND parent_id != 0 AND is_archived = 0 ORDER BY office_name ASC");
$stmtoff->execute();
$offices = $stmtoff->fetchAll(PDO::FETCH_ASSOC);

/* Build parent → children map */
$officeChildren = [];
foreach($offices as $off){
    $pid = $off['parent_id'] ?? 0;
    if($pid){
        $officeChildren[$pid][] = $off['id'];
    }
}

/* AUTO-GENERATE REPORT NUMBER */
$lastReport = $pdo->query("SELECT report_number FROM incident_reports ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($lastReport) {
    preg_match('/IR-(\d+)/', $lastReport['report_number'], $matches);
    $nextNumber = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
} else {
    $nextNumber = 1;
}
$reportNumberAuto = 'IR-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

/* FETCH ITEMS FROM RIS / PROPERTY AND RSE */
$stmtItems = $pdo->prepare("
SELECT
    p.property_id,
    p.item_name,
    p.description,
    p.serial_no,
    COALESCE(o.id, 0) AS office_id,
    COALESCE(o.office_name, 'Unknown') AS office_name,
    rh.first_name,
    rh.mi_name,
    rh.last_name,
    'RIS' AS source
FROM ris_items ri
LEFT JOIN tbl_property p ON ri.property_id = p.property_id
LEFT JOIN ris_header rh ON ri.ris_id = rh.id
LEFT JOIN tbl_office o ON ri.borrowed_from = o.id
WHERE ri.quantity > 0

UNION ALL

SELECT
    p.property_id,
    p.item_name,
    p.description,
    p.serial_no,
    COALESCE(o.id, 0) AS office_id,
    COALESCE(o.office_name, 'Unknown') AS office_name,
    rh.requesting_office AS first_name,
    '' AS mi_name,
    '' AS last_name,
    'RSE' AS source
FROM rse_items ri
LEFT JOIN tbl_property p ON ri.property_id = p.property_id
LEFT JOIN rse_header rh ON ri.rse_id = rh.id
LEFT JOIN tbl_office o ON ri.borrowed_from = o.id
WHERE ri.quantity > 0

ORDER BY office_name, item_name
");
$stmtItems->execute();
$allItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

/* ORGANIZE ITEMS BY OFFICE */
$itemsPerOffice = [];
foreach ($allItems as $it) {
    $oid = $it['office_id'] ?? 0;
    
    // Construct borrower name based on source
    if (($it['source'] ?? '') === 'RSE') {
        $borrower = $it['first_name']; // For RSE, first_name contains requesting_office
    } else {
        $borrower = trim($it['first_name'].' '.($it['mi_name'] ?? '').' '.$it['last_name']);
    }

    $itemData = [
        'property_id' => $it['property_id'],
        'item_name' => $it['item_name'],
        'description' => $it['description'],
        'serial' => $it['serial_no'],
        'location' => $it['office_name'],
        'borrower' => $borrower,
        'source' => $it['source'] ?? 'RIS'
    ];
    $itemsPerOffice[$oid][] = $itemData;
    if(isset($officeChildren[$oid])){
        foreach($officeChildren[$oid] as $childId){
            $itemsPerOffice[$childId][] = $itemData;
        }
    }
}
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
.content-wrapper{padding:20px;}
.form-row{display:flex;gap:15px;margin-bottom:15px;}
.form-group{flex:1;}
.table-responsive{margin-top:10px;}
.items-list { padding-left:15px; text-align:left; margin:0; }
.items-list li { list-style: disc; margin-left:15px; }
</style>

<div class="content-wrapper">
<div class="container-fluid">

<div class="card p-4 shadow-sm">

<h3 class="mb-4">
    <a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
    Incident Report Form
</h3>

<form method="POST">

<h5>General Information</h5>

<div class="form-row">
<div class="form-group">
<label>Report Number</label>
<input type="text" name="report_number" class="form-control" value="<?= $reportNumberAuto ?>" readonly>
</div>
<div class="form-group">
<label>Reported By</label>
<input type="text" name="reported_by" class="form-control" required>
</div>
</div>

<div class="form-row">
<div class="form-group">
<label>Office</label>
<select name="office" id="officeSelect" class="form-control" required>
<option value="">Select Office</option>
<?php foreach($offices as $off): ?>
<option value="<?= $off['id'] ?>">
<?= htmlspecialchars($off['office_name']) ?>
</option>
<?php endforeach; ?>
</select>
</div>
</div>

<hr>

<h5>Item Details</h5>
<div class="table-responsive">
<table class="table table-bordered" id="item_table">
<thead>
<tr>
<th>Item Name</th>
<th>Quantity</th>
<th>Serial Number</th>
<th>Location</th>
<th>Last Borrower</th>
</tr>
</thead>
<tbody>
<tr>
<td>
<select name="item_name[]" class="form-control itemSelect">
<option value="">Select Item</option>
</select>
</td>
<td><input type="number" name="quantity[]" class="form-control qtyInput" min="1" value="1" readonly required></td>
<td><input type="text" name="serial_number[]" class="form-control serialInput" readonly></td>
<td><input type="text" name="location[]" class="form-control locationInput" readonly></td>
<td><input type="text" name="last_borrower[]" class="form-control borrowerInput" readonly></td>
</tr>
</tbody>
</table>
</div>

<button type="button" class="btn btn-secondary mt-2" id="addRow">+ Add Item</button>

<hr>

<h5>Incident Description</h5>
<div class="form-row">
<div class="form-group">
<label>Date of Incident</label>
<input type="date" name="incident_date" class="form-control" required>
</div>
<div class="form-group">
<label>Time of Incident</label>
<input type="time" name="incident_time" class="form-control" required>
</div>
</div>

<div class="form-group">
<label>Description of Damage</label>
<textarea name="description" class="form-control" rows="3" required></textarea>
</div>
<div class="form-group">
<label>Extent of Damage</label>
<textarea name="extent_of_damage" class="form-control" rows="3" required></textarea>
</div>

<button type="submit" name="submit_incident" class="btn btn-primary btn-lg mt-3">Submit Incident Report</button>
</form>
</div>
</div>
</div>

<script>
const itemsPerOffice = <?= json_encode($itemsPerOffice); ?>;

function populateItems(select){
    const officeId = parseInt(document.getElementById("officeSelect").value);
    select.innerHTML = '<option value="">Select Item</option>';
    const items = itemsPerOffice[officeId] || [];
    items.forEach(item=>{
        const opt = document.createElement("option");
        opt.value = item.property_id; // <-- submit property_id
        // Show item name, description, and borrower for better identification
        opt.textContent = item.item_name + 
                          (item.description ? ' (' + item.description + ')' : '') + 
                          ' [' + item.borrower + ']'; 
        opt.dataset.serial = item.serial;
        opt.dataset.location = item.location;
        opt.dataset.borrower = item.borrower;
        select.appendChild(opt);
    });
}

/* ADD ROW */
document.getElementById("addRow").onclick = function(){
    const tbody = document.querySelector("#item_table tbody");
    const row = tbody.rows[0].cloneNode(true);
    row.querySelectorAll("input").forEach(i => {
        if(i.name === "quantity[]"){
            i.value = "1";
        } else {
            i.value = "";
        }
    });
    const select = row.querySelector("select");
    select.selectedIndex = 0;
    populateItems(select);
    tbody.appendChild(row);
};

/* OFFICE CHANGE */
document.getElementById("officeSelect").addEventListener("change", function(){
    document.querySelectorAll(".itemSelect").forEach(select=>{
        populateItems(select);
        const row = select.closest("tr");
        row.querySelectorAll("input").forEach(i => {
            if(i.name === "quantity[]"){
                i.value = "1";
            } else {
                i.value = "";
            }
        });
    });
});

/* ITEM SELECT */
document.addEventListener("change", function(e){
    if(e.target.classList.contains("itemSelect")){
        const row = e.target.closest("tr");
        const opt = e.target.selectedOptions[0];
        row.querySelector(".serialInput").value = opt?.dataset.serial || "";
        row.querySelector(".locationInput").value = opt?.dataset.location || "";
        row.querySelector(".borrowerInput").value = opt?.dataset.borrower || "";
    }
});
</script>

<?php
if(isset($_GET['success']) && $_GET['success'] == 1) {
?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Incident Report Submitted',
    text: 'The report has been successfully saved!',
    timer: 3000,
    showConfirmButton: false
});
</script>
<?php
}
?>

<?php include_once "footer.php"; ?>