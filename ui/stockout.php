<?php
ob_start(); // Start output buffering to prevent "headers already sent" errors
include_once 'connectdb.php';
session_start();
include_once "header.php";

// Fetch products for dropdown
$products = $pdo->query("SELECT pid, name, description, stock FROM tbl_product WHERE is_archived = 0 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch offices for dropdown
$offices = $pdo->query("SELECT id, office_name FROM tbl_office WHERE parent_id IS NOT NULL AND parent_id != 0 AND is_archived = 0 ORDER BY office_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle stock out submission
if(isset($_POST['save_stockout'])){
    $product_id = $_POST['product_id'];
    $office_id = $_POST['office_id'];
    $quantity = (int)$_POST['quantity'];
    $instructor_id = (!empty($_POST['instructor_id'])) ? $_POST['instructor_id'] : NULL;
    $remarks = $_POST['remarks'];

    // Check available stock
    $stmt = $pdo->prepare("SELECT stock, name FROM tbl_product WHERE pid=?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$product){
        $_SESSION['status'] = "Product not found.";
        $_SESSION['status_code'] = "error";
    } elseif($quantity > $product['stock']){
        $_SESSION['status'] = "Insufficient stock for {$product['name']}!";
        $_SESSION['status_code'] = "error";
    } else {
        // Insert stock out record
        $stmt = $pdo->prepare("
            INSERT INTO tbl_stockout (product_id, office_id, instructor_id, quantity, remarks)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$product_id, $office_id, $instructor_id, $quantity, $remarks]);

        // Reduce stock in product table
        $stmt = $pdo->prepare("UPDATE tbl_product SET stock = stock - ? WHERE pid=?");
        $stmt->execute([$quantity, $product_id]);

        $_SESSION['status'] = "Stock out recorded successfully!";
        $_SESSION['status_code'] = "success";
    }

    // Redirect to avoid form resubmission and headers sent issues
    header("Location: stockout.php");
    exit;
}

// Fetch stock out records for display
$stockouts = $pdo->query("
    SELECT s.id, p.name AS product_name, p.description AS product_description, s.quantity, o.office_name, o.address AS office_oic, s.stockout_date, s.remarks, i.fullname AS instructor_name
    FROM tbl_stockout s
    LEFT JOIN tbl_product p ON s.product_id = p.pid
    LEFT JOIN tbl_office o ON s.office_id = o.id
    LEFT JOIN tbl_instructors i ON s.instructor_id = i.id
    ORDER BY s.stockout_date DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-wrapper">
<div class="container-fluid">
<h3 class="mt-3 mb-4">Stock Out - Fast Moving Supplies</h3>

<!-- Stock Out Form -->
<div class="card card-primary card-outline">
<div class="card-header"><h5>Record Stock Out</h5></div>
<div class="card-body">
<form method="post">

<div class="row">
    <div class="col-md-4">
        <label>Product</label>
        <select name="product_id" class="form-control" required>
            <option value="">Select Product</option>
            <?php foreach($products as $p): ?>
            <option value="<?= $p['pid'] ?>"><?= htmlspecialchars($p['name'])." - ".htmlspecialchars($p['description'])." (Stock: ".$p['stock'].")" ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-4">
        <label>Office (Where it went)</label>
        <select name="office_id" id="officeSelect" class="form-control" required>
            <option value="">Select Office</option>
            <?php foreach($offices as $o): ?>
            <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['office_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-4">
        <label>Quantity</label>
        <input type="number" name="quantity" class="form-control" min="1" value="1" required>
    </div>

    <div class="col-md-4">
        <label>Instructor/Personnel (Received By)</label>
        <select name="instructor_id" id="instructorSelect" class="form-control" required>
            <option value="">Select Instructor</option>
            <?php
            $stmt = $pdo->query("SELECT id, fullname FROM tbl_instructors WHERE is_archived = 0 ORDER BY fullname ASC");
            while($inst = $stmt->fetch(PDO::FETCH_ASSOC)){
                echo "<option value='".$inst['id']."'>".$inst['fullname']."</option>";
            }
            ?>
        </select>
    </div>
</div>

<div class="form-group mt-2">
<label>Remarks</label>
<textarea name="remarks" class="form-control" required></textarea>
</div>

<button type="submit" name="save_stockout" class="btn btn-primary">Save Stock Out</button>
</form>
</div>
</div>

<!-- Stock Out Records -->
<div class="card card-info card-outline mt-3">
<div class="card-header d-flex align-items-center">
    <h5>Stock Out History</h5>
    <button type="button" class="btn btn-primary btn-sm ml-auto" id="btnPrintAll">
        <i class="fa fa-print"></i> Print All
    </button>
</div>
<div class="card-body">
<table class="table table-striped table-hover" id="table_stockout">
    <thead>
        <tr>
            <th>Date</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Office</th>
            <th>Instructor</th>
            <th>Remarks</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($stockouts as $s): 
        $displayInstructor = !empty($s['instructor_name']) ? $s['instructor_name'] : (!empty($s['office_oic']) ? $s['office_oic'] : '-');
    ?>
        <tr>
            <td><?= htmlspecialchars($s['stockout_date']) ?></td>
            <td><?= htmlspecialchars($s['product_name']) . " - " . htmlspecialchars($s['product_description']) ?></td>
            <td><?= htmlspecialchars($s['quantity']) ?></td>
            <td><?= htmlspecialchars($s['office_name'] ?: 'Unassigned') ?></td>
            <td><?= htmlspecialchars($displayInstructor) ?></td>
            <td><?= htmlspecialchars($s['remarks']) ?></td>
            <td>
                <button type="button" class="btn btn-primary btn-xs btnprint" 
                        data-id="<?= $s['id'] ?>" 
                        data-product="<?= htmlspecialchars($s['product_name']) . " - " . htmlspecialchars($s['product_description']) ?>"
                        data-qty="<?= $s['quantity'] ?>"
                        data-office="<?= htmlspecialchars($s['office_name'] ?: 'Unassigned') ?>"
                        data-instructor="<?= htmlspecialchars($displayInstructor != '-' ? $displayInstructor : '____________________') ?>">
                    <i class="fa fa-print"></i>
                </button>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

</div>
</div>

<?php include_once "footer.php"; ?>

<script>
$(document).ready(function() {
    $('#table_stockout').DataTable({
        "order": [[0, "desc"]]
    });

    // Office change -> load instructors
    $('#officeSelect').change(function() {
        var officeId = $(this).val();
        var instructorSelect = $('#instructorSelect');

        instructorSelect.html('<option value="">-- Loading --</option>');

        if (officeId) {
            $.ajax({
                url: 'get_instructors.php',
                type: 'GET',
                data: { office_id: officeId },
                dataType: 'json',
                success: function(data) {
                    instructorSelect.html('<option value="">Select Instructor</option>');
                    if (data.length > 0) {
                        data.forEach(function(inst) {
                            instructorSelect.append('<option value="' + inst.id + '">' + inst.name + '</option>');
                        });
                    } else {
                        instructorSelect.append('<option value="">No instructors found</option>');
                    }
                },
                error: function() {
                    instructorSelect.html('<option value="">Error loading instructors</option>');
                }
            });
        } else {
            instructorSelect.html('<option value="">Select Instructor</option>');
        }
    });

    // Single Print
    $('#table_stockout').on('click', '.btnprint', function() {
        var prod = $(this).data('product');
        var qty = $(this).data('qty');
        var office = $(this).data('office');
        var instructor = $(this).data('instructor');

        var printHTML = '<html><head><title>Stock Out Slip</title>';
        printHTML += '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">';
        printHTML += '<style>body{padding:20px; font-size:14px;} .header{text-align:center; margin-bottom:20px;} .signature-area{margin-top:80px; display:flex; justify-content:space-between;}</style>';
        printHTML += '</head><body>';

        printHTML += '<div style="display:flex; align-items:center; justify-content:center; margin-bottom:20px;">';
        printHTML += '<img src="../dist/img/logo.png" style="width:80px; margin-right:15px;">';
        printHTML += '<div style="text-align:center;">';
        printHTML += '<h5 style="margin:0;">KOLEHIYO NG SUBIC</h5>';
        printHTML += '<p style="margin:0;">WFI Compound, Wawandue, Subic, Zambales</p>';
        printHTML += '<p style="margin:0;">Tel.no.: (047)232 – 4896/232-4897</p>';
        printHTML += '</div></div>';

        printHTML += '<h4>Stock Out Slip</h4>';
        printHTML += '<table class="table table-bordered">';
        printHTML += '<tr><th>Product</th><td>'+prod+'</td></tr>';
        printHTML += '<tr><th>Quantity</th><td>'+qty+'</td></tr>';
        printHTML += '<tr><th>Office</th><td>'+office+'</td></tr>';
        printHTML += '</table>';

        printHTML += '<div class="signature-area">';
        printHTML += '  <div><p style="margin:0; font-weight:bold;">Prepared by:</p><div style="margin-top:30px; width:250px; border-bottom:1px solid #000; text-align:center;"><span style="font-weight:bold; text-transform:uppercase;">MARITES MENDIGORIN</span></div><p style="margin:0; text-align:center;">Property and Supplies Office</p></div>';
        printHTML += '  <div><p style="margin:0; font-weight:bold;">Received by:</p><div style="margin-top:30px; width:250px; border-bottom:1px solid #000; text-align:center;"><span style="font-weight:bold; text-transform:uppercase;">'+instructor+'</span></div><p style="margin:0; text-align:center;">'+office+'</p></div>';
        printHTML += '</div>';

        printHTML += '</body></html>';

        var win = window.open('','','width=800,height=600');
        win.document.write(printHTML);
        win.document.close();
        win.onload = function(){ win.print(); };
    });

    // Print All
    $('#btnPrintAll').click(function() {
        var headers = [], rows = [];
        $('#table_stockout thead th').each(function(i){ if(i<6) headers.push($(this).text()); });
        $('#table_stockout tbody tr').each(function(){
            var row = [];
            $(this).find('td').each(function(i){ if(i<6) row.push($(this).text()); });
            rows.push(row);
        });

        var printHTML = '<html><head><title>Stock Out History</title>';
        printHTML += '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">';
        printHTML += '<style>body{padding:20px; font-size:12px;} table{width:100%; border-collapse: collapse;} th, td{padding:6px; border:1px solid #000;}</style></head><body>';

        printHTML += '<div style="display:flex; align-items:center; justify-content:center; margin-bottom:20px;">';
        printHTML += '<img src="../dist/img/logo.png" style="width:80px; margin-right:15px;">';
        printHTML += '<div style="text-align:center;">';
        printHTML += '<h5 style="margin:0;">KOLEHIYO NG SUBIC</h5>';
        printHTML += '<p style="margin:0;">WFI Compound, Wawandue, Subic, Zambales</p>';
        printHTML += '<p style="margin:0;">Tel.no.: (047)232 – 4896/232-4897</p>';
        printHTML += '</div></div>';

        printHTML += '<h4>Stock Out History Report</h4>';
        printHTML += '<table class="table table-bordered"><thead><tr>';
        headers.forEach(h => printHTML += '<th>'+h+'</th>');
        printHTML += '</tr></thead><tbody>';
        rows.forEach(r => { printHTML += '<tr>'; r.forEach(c => printHTML += '<td>'+c+'</td>'); printHTML += '</tr>'; });
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

        var win = window.open('','','width=1000,height=800');
        win.document.write(printHTML);
        win.document.close();
        win.onload = function(){ win.print(); };
    });

    <?php if(!empty($_SESSION['status'])): ?>
    Swal.fire({
        icon: '<?= $_SESSION['status_code'] ?>',
        title: '<?= $_SESSION['status'] ?>'
    });
    <?php 
    unset($_SESSION['status']);
    unset($_SESSION['status_code']);
    endif; ?>
});
</script>