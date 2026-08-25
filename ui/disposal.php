<?php
include_once 'connectdb.php';
session_start();

// Disable display errors
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Allow Admin, Intern, Student Assistant
if (!isset($_SESSION['useremail']) || !in_array($_SESSION['role'], ["Admin","Intern","Student Assistant"])) {
    header('Location: ../index.php');
    exit();
}

// Header based on role
if($_SESSION['role']=="Admin"){
    include_once "header.php";
}else{
    include_once "headeruser.php";
}

// =================== FORM HANDLER ===================
$success_message = '';
$error_message = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dispose_submit'])){
    $property_id = (int)$_POST['property_id'];
    $quantity = (int)$_POST['quantity'];
    $remarks = trim($_POST['remarks']);
    $disposed_by = $_SESSION['fullname']; // Store fullname instead of email

    // Fetch property
    $stmt = $pdo->prepare("SELECT property_id, inventory_no, item_name, quantity, office_id FROM tbl_property WHERE property_id=?");
    $stmt->execute([$property_id]);
    $prop = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$prop){
        $error_message = 'Property not found.';
    } elseif($quantity < 1 || $quantity > $prop['quantity']){
        $error_message = 'Invalid quantity.';
    } else {
        // Insert into disposal table
        $stmt = $pdo->prepare("INSERT INTO tbl_disposal (property_id, item_name, inventory_no, office_id, quantity, remarks, disposed_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$property_id, $prop['item_name'], $prop['inventory_no'], $prop['office_id'], $quantity, $remarks, $disposed_by]);

        // Log activity
        $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
        $stmtLog->execute([$_SESSION['userid'], "Disposed Item: " . $prop['item_name'] . " (Qty: $quantity)"]);

        // Calculate new quantity
        $new_qty = $prop['quantity'] - $quantity;

        // Check outstanding borrowed quantity
        $stmt_borrow = $pdo->prepare("
            SELECT COALESCE(SUM(ri.quantity), 0) as borrowed_qty 
            FROM ris_items ri 
            JOIN ris_header rh ON ri.ris_id = rh.id 
            WHERE rh.is_returned = 0 AND ri.property_id = ?
        ");
        $stmt_borrow->execute([$property_id]);
        $borrowed_qty = $stmt_borrow->fetch(PDO::FETCH_ASSOC)['borrowed_qty'];

        // Update property based on conditions
        if ($new_qty > 0) {
            // Still some left
            $stmt = $pdo->prepare("UPDATE tbl_property SET quantity=? WHERE property_id=?");
            $stmt->execute([$new_qty, $property_id]);
            $success_message = "Item disposed successfully!";
        } else {
            if ($borrowed_qty > 0) {
                // Disposed but borrowed outstanding - update qty only, keep active
                $stmt = $pdo->prepare("UPDATE tbl_property SET quantity=0 WHERE property_id=?");
                $stmt->execute([$property_id]);
                $success_message = "Item fully disposed but kept active due to outstanding borrows (Qty: $borrowed_qty). Disposed successfully!";
            } else {
                // Fully disposed and no borrows - soft delete
                $stmt = $pdo->prepare("UPDATE tbl_property SET quantity=0, is_active=0 WHERE property_id=?");
                $stmt->execute([$property_id]);
                $success_message = "Item disposed and deactivated successfully!";
            }
        }
    }
}
?>

<div class="content-wrapper">
<div class="content-header">
<div class="container-fluid">
<h4>Dispose / Damaged Items</h4>
</div>
</div>

<div class="content">
<div class="container-fluid">

<!-- ================= ALERTS ================= -->
<?php if($success_message): ?>
<div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
<?php endif; ?>
<?php if($error_message): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
<?php endif; ?>

<!-- ================= FORM ================= -->
<div class="card card-primary card-outline mb-4">
<div class="card-header"><h5>Dispose an Item</h5></div>
<div class="card-body">

<form method="POST" id="disposeForm">
<div class="row">

<div class="col-md-3">
<label>Office / Department</label>
<select name="office_id" id="office_id" class="form-control" required>
<option value="">-- Select Office --</option>
<?php
$stmt = $pdo->prepare("SELECT id, office_name FROM tbl_office WHERE parent_id IS NOT NULL AND parent_id != 0 AND is_archived = 0 ORDER BY office_name ASC");
$stmt->execute();
while($row = $stmt->fetch(PDO::FETCH_OBJ)){
    echo '<option value="'.$row->id.'">'.htmlspecialchars($row->office_name).'</option>';
}
?>
</select>
</div>

<div class="col-md-3">
<label>Property Item</label>
<select name="property_id" id="property_id" class="form-control" required disabled>
<option value="">-- Select Office First --</option>
</select>
</div>

<div class="col-md-2">
<label>Barcode / Inv No.</label>
<input type="text" id="display_barcode" class="form-control" readonly placeholder="Barcode">
</div>

<div class="col-md-2">
<label>Serial Number</label>
<input type="text" id="display_serial" class="form-control" readonly placeholder="Serial No">
</div>

<div class="col-md-2">
<label>Quantity</label>
<input type="number" name="quantity" id="dispose_qty" class="form-control" min="1" value="1" readonly >
</div>

</div>

<div class="row mt-3">
<div class="col-md-12">
<label>Remarks</label>
<input type="text" name="remarks" id="remarks" class="form-control" placeholder="Enter disposal remarks">
</div>
</div>

<div class="mt-3">
<button type="submit" name="dispose_submit" class="btn btn-dark">Dispose Item</button>
</div>
</form>

</div>
</div>

<!-- ================= DISPOSAL HISTORY ================= -->
<div class="card card-primary card-outline">
<div class="card-header">
<h5 class="m-0 float-left">Disposal History</h5>
<div class="card-tools">
<button class="btn btn-primary btn-sm" id="btnPrintAllRSE">
<i class="fa fa-print"></i> Print All
</button>
</div>
</div>

<div class="card-body">
<table id="table_disposal" class="table table-striped table-hover">
<thead>
<tr>
<th>Inventory No.</th>
<th>Item Name</th>
<th>Remarks</th>
<th>Quantity Disposed</th>
<th>Disposed By</th>
<th>Date Disposed</th>
</tr>
</thead>
<tbody>
<?php
$stmt = $pdo->prepare("
SELECT COALESCE(p.inventory_no, d.inventory_no) as inventory_no, d.quantity, d.remarks, d.disposed_at, COALESCE(p.item_name, d.item_name) as item_name, COALESCE(u.fullname, d.disposed_by) as disposed_by
FROM tbl_disposal d
LEFT JOIN tbl_property p ON d.property_id = p.property_id
LEFT JOIN tbl_user u ON d.disposed_by = u.useremail
ORDER BY d.disposed_at DESC
");
$stmt->execute();
while($row = $stmt->fetch(PDO::FETCH_OBJ)){
    echo '<tr>
        <td>'.htmlspecialchars($row->inventory_no ?? '-').'</td>
        <td>'.htmlspecialchars($row->item_name).'</td>
        <td>'.htmlspecialchars($row->remarks).'</td>
        <td>'.htmlspecialchars($row->quantity).'</td>
        <td>'.htmlspecialchars($row->disposed_by).'</td>
        <td>'.date("Y-m-d H:i", strtotime($row->disposed_at)).'</td>
    </tr>';
}
?>
</tbody>
</table>
</div>
</div>

</div>
</div>
</div>

<?php include_once "footer.php"; ?>

<script>
$(document).ready(function() {
    $('#table_disposal').DataTable({
        "order": [[5, "desc"]]
    });

    // Office change -> load items
    $('#office_id').change(function(){
        var officeId = $(this).val();
        var itemSelect = $('#property_id');
        
        itemSelect.prop('disabled', true);
        itemSelect.html('<option value="">-- Loading Items... --</option>');
        
        if(officeId){
            fetch('get_itemris.php?office_id=' + officeId)
            .then(res => res.json())
            .then(data => {
                itemSelect.html('<option value="">-- Select Item --</option>');
                
                var withBarcode = [];
                var withoutBarcode = [];

                data.forEach(item => {
                    if(item.inventory_no && item.inventory_no.trim() !== ""){
                        withBarcode.push(item);
                    } else {
                        withoutBarcode.push(item);
                    }
                });

                if(withBarcode.length > 0){
                        var group = $('<optgroup label="With Barcode / Inventory No."></optgroup>');
                        withBarcode.forEach(item => {
                            group.append('<option value="' + item.property_id + '" data-qty="' + item.quantity + '" data-barcode="' + item.inventory_no + '" data-serial="' + (item.serial_no || 'N/A') + '">' + item.item_name + ' [' + item.inventory_no + '] (Available: ' + item.quantity + ')</option>');
                        });
                        itemSelect.append(group);
                    }

                    if(withoutBarcode.length > 0){
                        var group = $('<optgroup label="Without Barcode"></optgroup>');
                        withoutBarcode.forEach(item => {
                            group.append('<option value="' + item.property_id + '" data-qty="' + item.quantity + '" data-barcode="N/A" data-serial="' + (item.serial_no || 'N/A') + '">' + item.item_name + ' (Available: ' + item.quantity + ')</option>');
                        });
                        itemSelect.append(group);
                    }

                    itemSelect.prop('disabled', false);
                })
                .catch(err => {
                    console.error(err);
                    itemSelect.html('<option value="">-- Error Loading Items --</option>');
                });
            } else {
                itemSelect.html('<option value="">-- Select Office First --</option>');
            }
            $('#dispose_qty').val('').attr('max', '');
            $('#display_barcode').val('');
            $('#display_serial').val('');
        });

        // Auto quantity limit and display barcode/serial
        $('#property_id').change(function(){
            var selected = $(this).find(':selected');
            var maxQty = selected.data('qty');
            var barcode = selected.data('barcode');
            var serial = selected.data('serial');
            
            $('#dispose_qty').attr('max', maxQty).val('');
            $('#display_barcode').val(barcode || '');
            $('#display_serial').val(serial || '');
        });

    // Print All Disposal History
    $('#btnPrintAllRSE').click(function(){
        var headers = [], rowsData = [];
        $('#table_disposal thead th').each(function(i){ headers.push($(this).text()); });
        $('#table_disposal tbody tr').each(function(){
            var rowData = [];
            $(this).find('td').each(function(i){ rowData.push($(this).html()); });
            rowsData.push(rowData);
        });

        var printHTML = '<html><head><title>Disposal History Report</title>';
        printHTML += '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">';
        printHTML += '<style>body{padding:20px; font-size:12px;} table{width:100%; border-collapse: collapse;} th, td{padding:6px; border:1px solid #000;}</style>';
        printHTML += '</head><body>';

        printHTML += '<div style="display:flex; align-items:center; justify-content:center; margin-bottom:20px;">';
        printHTML += '<img src="../dist/img/logo.png" style="width:80px; margin-right:15px;">';
        printHTML += '<div style="text-align:center;"><h5 style="margin:0;">KOLEHIYO NG SUBIC</h5><p style="margin:0;">WFI Compound, Wawandue, Subic, Zambales</p><p style="margin:0;">Tel.no.: (047)232 – 4896/232-4897</p></div></div>';

        printHTML += '<h4>Disposal History Report</h4><table class="table table-bordered"><thead><tr>';
        headers.forEach(h=>printHTML += '<th>'+h+'</th>');
        printHTML += '</tr></thead><tbody>';
        rowsData.forEach(row=>{
            printHTML += '<tr>'; row.forEach(col=>printHTML += '<td>'+col+'</td>'); printHTML += '</tr>';
        });
        printHTML += '</tbody></table>';

        printHTML += '<div style="margin-top:80px; display: flex; justify-content: space-between;">';
        
        // College President Section
        printHTML += '<div>';
        printHTML += '  <div style="width:250px; border-bottom:1px solid #000; text-align:center;">';
        printHTML += '    <span style="font-weight:bold; text-transform:uppercase;">' + window.globalOIC.president + '</span>';
        printHTML += '  </div>';
        printHTML += '  <p style="margin:0; text-align:center;">College President</p>';
        printHTML += '</div>';

        // Property/Supplies Officer Section
        printHTML += '<div>';
        printHTML += '  <div style="width:250px; border-bottom:1px solid #000; text-align:center;">';
        printHTML += '    <span style="font-weight:bold; text-transform:uppercase;">' + window.globalOIC.property + '</span>';
        printHTML += '  </div>';
        printHTML += '  <p style="margin:0; text-align:center;">Property/Supplies Officer</p>';
        printHTML += '</div>';
        
        printHTML += '</div></body></html>';

        var printWindow = window.open('','','width=1000,height=800');
        printWindow.document.write(printHTML);
        printWindow.document.close();
        printWindow.onload = function(){ printWindow.print(); };
    });
});
</script>