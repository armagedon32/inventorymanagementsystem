<?php
include_once 'connectdb.php';
session_start();

// Only Admin access
if ($_SESSION['useremail'] == "" || $_SESSION['role'] !== "Admin") {
    header('location:../index.php');
    exit;
}

if (isset($_SESSION['must_change_password'])) {
    header('Location: ../reset_change_password.php');
    exit();
}

// ==================== HANDLE RETURN ====================
if (isset($_GET['return_id'])) {
    $ris_id = (int) $_GET['return_id'];

    $stmtItems = $pdo->prepare("
        SELECT ri.quantity, p.item_name
        FROM ris_items ri
        LEFT JOIN tbl_property p ON ri.property_id = p.property_id
        WHERE ri.ris_id = ?
    ");
    $stmtItems->execute([$ris_id]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $stmtStock = $pdo->prepare("
            UPDATE tbl_property
            SET quantity = quantity + ?
            WHERE item_name = ?
        ");
        $stmtStock->execute([$item['quantity'], $item['item_name']]);
    }

    $stmtReturn = $pdo->prepare("
        UPDATE ris_header
        SET is_returned = 1, return_date = NOW()
        WHERE id = ?
    ");
    $stmtReturn->execute([$ris_id]);

    header("Location: borrowed.php");
    exit;
}

// ==================== HANDLE DELETE ====================
if (isset($_GET['delete_id'])) {
    $ris_id = (int) $_GET['delete_id'];

    $pdo->beginTransaction();
    try {
        $stmtItems = $pdo->prepare("UPDATE ris_items SET is_archived = 1 WHERE ris_id = ?");
        $stmtItems->execute([$ris_id]);

        $stmtHeader = $pdo->prepare("UPDATE ris_header SET is_archived = 1 WHERE id = ?");
        $stmtHeader->execute([$ris_id]);

        $pdo->commit();
        header("Location: borrowed.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Error archiving RIS: " . $e->getMessage() . "');</script>";
    }
}

// ==================== FETCH ALL RIS WITH FILTER ====================
$filter = $_GET['filter'] ?? 'all';
$sql = "SELECT * FROM ris_header";
$where = ["is_archived = 0"];

if ($filter === 'borrowed') {
    $where[] = "is_returned = 0";
} elseif ($filter === 'returned') {
    $where[] = "is_returned = 1";
} elseif ($filter === 'overdue') {
    $where[] = "is_returned = 0 AND end_datetime < NOW()";
}

$sql .= " WHERE " . implode(" AND ", $where);

$sql .= " ORDER BY id DESC";
$stmtr = $pdo->prepare($sql);
$stmtr->execute();
$ris_list = $stmtr->fetchAll(PDO::FETCH_ASSOC);

include_once "header.php";
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
.content-wrapper { padding: 20px; }
.table-responsive { max-height: 600px; overflow-y: auto; }
.action-icons { display: flex; justify-content: center; gap: 15px; }
.action-icons i { cursor: pointer; font-size: 1.3rem; transition: transform 0.2s; }
.action-icons i:hover { transform: scale(1.2); }
.items-list { padding-left: 15px; text-align: left; margin: 0; }
.items-list li { list-style: disc; margin-left: 15px; }
.table-danger { background-color: #f8d7da !important; }
.filter-buttons { margin-bottom: 15px; }
.filter-buttons a { margin-right: 10px; text-decoration: none; padding: 5px 12px; border-radius: 5px; color: #fff; }
.filter-buttons a.active { font-weight: bold; }
.filter-all { background-color: #6c757d; }
.filter-borrowed { background-color: #007bff; }
.filter-returned { background-color: #28a745; }
.filter-overdue { background-color: #dc3545; }
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h3 class="m-0 text-dark">
                        <a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
                        Borrowed RIS Records
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">

        <!-- FILTER BUTTONS -->
 <div class="d-flex justify-content-between align-items-center mb-3">
    <!-- Filters on the left -->
    <div class="filter-buttons">
        <a href="borrowed.php?filter=all" class="filter-all <?= $filter === 'all' ? 'active' : '' ?>">All</a>
        <a href="borrowed.php?filter=borrowed" class="filter-borrowed <?= $filter === 'borrowed' ? 'active' : '' ?>">Borrowed</a>
        <a href="borrowed.php?filter=returned" class="filter-returned <?= $filter === 'returned' ? 'active' : '' ?>">Returned</a>
        <a href="borrowed.php?filter=overdue" class="filter-overdue <?= $filter === 'overdue' ? 'active' : '' ?>">Overdue</a>
    </div>

    <!-- Print button on the right -->
    <div>
        <button class="btn btn-primary" id="btnPrintAllRSE">Print All</button>
    </div>
</div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped text-center">
                <thead class="thead-dark">
                    <tr>
                        <th>Request No</th>
                        <th>Borrower Name</th>
                        <th>Position</th>
                        <th>Contact</th>
                        <th>Event Name</th>
                        <th>Event Date</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Items</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ris_list as $ris):
                        $isOverdue = ($ris['is_returned'] ?? 0) == 0 && strtotime($ris['end_datetime']) < time();
                    ?>
                    <tr class="<?= $isOverdue ? 'table-danger' : '' ?>">
                        <td><?= htmlentities($ris['request_no'] ?? $ris['id']); ?></td>
                        <td><?= htmlentities($ris['last_name'] . ', ' . $ris['first_name'] . ' ' . $ris['mi_name']); ?></td>
                        <td><?= htmlentities($ris['position']); ?></td>
                        <td><?= htmlentities($ris['cp_number']); ?></td>
                        <td><?= htmlentities($ris['event_name']); ?></td>
                        <td><?= htmlentities($ris['event_date']); ?></td>
                        <td><?= htmlentities($ris['start_datetime']); ?></td>
                        <td><?= htmlentities($ris['end_datetime']); ?></td>
                        <td class="text-left">
                            <ul class="items-list">
                                <?php
                                $stmtItems = $pdo->prepare("
                                    SELECT ri.quantity,
                                           COALESCE(p.item_name,'Unknown Item') AS item_name,
                                           COALESCE(p.serial_no,'') AS serial_no,
                                           COALESCE(o.office_name,'Unknown Office') AS office_name
                                    FROM ris_items ri
                                    LEFT JOIN tbl_property p ON ri.property_id = p.property_id
                                    LEFT JOIN tbl_office o ON ri.borrowed_from = o.id
                                    WHERE ri.ris_id = ?
                                ");
                                $stmtItems->execute([$ris['id']]);
                                $itemsList = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($itemsList as $item):
                                ?>
                                <li>
                                    <?= htmlentities($item['item_name']); ?>
                                    <?php if(!empty($item['serial_no'])): ?>
                                    (SN: <?= htmlentities($item['serial_no']); ?>)
                                    <?php endif; ?>
                                    (Qty: <?= $item['quantity']; ?>, Office: <?= htmlentities($item['office_name']); ?>)
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                        <td>
                            <div class="action-icons">
                                <i class="fa-solid fa-print text-primary print-single"
                                   title="Print"
                                   data-id="<?= $ris['id']; ?>"
                                   data-name="<?= htmlentities($ris['last_name'] . ', ' . $ris['first_name'] . ' ' . $ris['mi_name']); ?>"
                                   data-position="<?= htmlentities($ris['position']); ?>"
                                   data-contact="<?= htmlentities($ris['cp_number']); ?>"
                                   data-event="<?= htmlentities($ris['event_name']); ?>"
                                   data-date="<?= htmlentities($ris['event_date']); ?>"
                                   data-start="<?= htmlentities($ris['start_datetime']); ?>"
                                   data-end="<?= htmlentities($ris['end_datetime']); ?>"></i>

                                <?php if (($ris['is_returned'] ?? 0) == 0): ?>
                                    <i class="fa-solid fa-rotate-left text-success"
                                       title="Mark as Returned"
                                       onclick="window.location.href='borrowed.php?return_id=<?= $ris['id']; ?>'"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-check text-muted" title="Already Returned"></i>
                                <?php endif; ?>

                                <?php if ($isOverdue): ?>
                                    <i class="fa-solid fa-clock text-danger" title="Overdue"></i>
                                <?php endif; ?>

                                <i class="fa-solid fa-trash text-danger"
                                   title="Delete"
                                   onclick="window.location.href='borrowed.php?delete_id=<?= $ris['id']; ?>'"></i>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once "footer.php"; ?>

<script>
$(document).ready(function() {

  // Print Single (JS-driven)
  $('.print-single').click(function(e) {
    e.preventDefault();
    var btn = $(this);
    var row = btn.closest('tr');
    
    // Extract data from attributes
    var name = btn.data('name');
    var position = btn.data('position');
    var contact = btn.data('contact');
    var event = btn.data('event');
    var dateFiled = btn.data('date');
    var start = btn.data('start');
    var end = btn.data('end');

    // Extract items from the items column (Col 8)
    var itemsRows = '';
    row.find('td').eq(8).find('li').each(function() {
        var text = $(this).text().trim();
        // Format: "Item Name (SN: serial) (Qty: 1, Office: Admin Office)"
        var match = text.match(/(.*?)(?:\s*\(SN:([^)]+)\))?\s*\(Qty:\s*(\d+),\s*Office:\s*(.*)\)/);
        if (match) {
            var itemName = match[1].trim();
            var serial = (match[2] || '').trim();
            var qty = match[3];
            var office = match[4].trim();
            itemsRows += '<tr><td>' + qty + '</td><td>' + itemName + (serial ? '<br><small>SN: ' + serial + '</small>' : '') + '</td><td>' + office + '</td></tr>';
        } else {
            itemsRows += '<tr><td colspan="3">' + text + '</td></tr>';
        }
    });

    var printHTML = '<html><head><title>RIS Report</title>';
    printHTML += '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">';
    printHTML += '<style>body{padding:20px;font-size:14px;} .form-label{font-weight:bold;margin-bottom:0;} .form-value{border-bottom:1px solid #eee;padding-bottom:2px;margin-bottom:15px;} table{width:100%;border-collapse:collapse;} th,td{padding:8px;border:1px solid #000;}</style></head><body>';

    // Logo beside text
    printHTML += '<div style="display:flex; align-items:center; justify-content:center; margin-bottom:20px;">';
    printHTML += '<img src="../dist/img/logo.png" style="width:80px; margin-right:15px;">';
    printHTML += '<div style="text-align:center;">';
    printHTML += '<h5 style="margin:0;">KOLEHIYO NG SUBIC</h5>';
    printHTML += '<p style="margin:0;">WFI Compound, Wawandue, Subic, Zambales</p>';
    printHTML += '<p style="margin:0;">Tel.no.: (047)232 – 4896/232-4897</p></div></div>';

    printHTML += '<h4 class="text-center mb-4">Requisition & Issue Slip (RIS)</h4>';

    // Form-like Layout
    printHTML += '<div class="container-fluid">';
    
    // Borrower Info
    printHTML += '<h5 class="text-primary mb-3">Borrower Information</h5>';
    printHTML += '  <div class="row">';
    printHTML += '    <div class="col-8"><p class="form-label">Full Name</p><p class="form-value">' + name + '</p></div>';
    printHTML += '    <div class="col-4"><p class="form-label">Contact Number</p><p class="form-value">' + contact + '</p></div>';
    printHTML += '  </div>';
    printHTML += '  <div class="row">';
    printHTML += '    <div class="col-12"><p class="form-label">Position / Designation</p><p class="form-value">' + position + '</p></div>';
    printHTML += '  </div>';

    // Event Details
    printHTML += '<h5 class="text-primary mt-4 mb-3">Event Details</h5>';
    printHTML += '  <div class="row">';
    printHTML += '    <div class="col-6"><p class="form-label">Name of Event</p><p class="form-value">' + event + '</p></div>';
    printHTML += '    <div class="col-6"><p class="form-label">Date of Filing</p><p class="form-value">' + dateFiled + '</p></div>';
    printHTML += '  </div>';
    printHTML += '  <div class="row">';
    printHTML += '    <div class="col-6"><p class="form-label">Date & Time Borrowed</p><p class="form-value">' + start + '</p></div>';
    printHTML += '    <div class="col-6"><p class="form-label">Date & Time Returned</p><p class="form-value">' + end + '</p></div>';
    printHTML += '  </div>';
    
    printHTML += '</div>';

    printHTML += '<h5 class="mt-4">Items Borrowed</h5>';
    printHTML += '<table class="table table-bordered"><thead><tr>';
    printHTML += '<th width="100">Quantity</th><th>Item Name</th><th>Borrowed From</th>';
    printHTML += '</tr></thead><tbody>';
    printHTML += itemsRows;
    printHTML += '</tbody></table>';

    printHTML += '<div style="margin-top:50px; display: flex; justify-content: space-between;">';
    
    // Left Side: Prepared & Approved
    printHTML += '<div>';
    printHTML += '  <div style="margin-bottom: 40px;">';
    printHTML += '    <p style="margin:0; font-weight:bold;">Prepared by:</p>';
    printHTML += '    <div style="margin-top:30px; width:250px; border-bottom:1px solid #000; text-align:center;">';
    printHTML += '      <span style="font-weight:bold; text-transform:uppercase;">' + window.globalOIC.property + '</span>';
    printHTML += '    </div>';
    printHTML += '    <p style="margin:0; text-align:center;">Property/Supplies Officer</p>';
    printHTML += '  </div>';
    
    printHTML += '  <div>';
    printHTML += '    <p style="margin:0; font-weight:bold;">Approved by:</p>';
    printHTML += '    <div style="margin-top:30px; width:250px; border-bottom:1px solid #000; text-align:center;">';
    printHTML += '      <span style="font-weight:bold; text-transform:uppercase;">' + window.globalOIC.president + '</span>';
    printHTML += '    </div>';
    printHTML += '    <p style="margin:0; text-align:center;">College President</p>';
    printHTML += '  </div>';
    printHTML += '</div>';

    // Right Side: Borrowed By
    printHTML += '<div>';
    printHTML += '  <p style="margin:0; font-weight:bold;">Borrowed by:</p>';
    printHTML += '  <div style="margin-top:30px; width:250px; border-bottom:1px solid #000; text-align:center;">';
    printHTML += '    <span style="font-weight:bold; text-transform:uppercase;">' + name + '</span>';
    printHTML += '  </div>';
    printHTML += '  <p style="margin:0; text-align:center;">' + position + '</p>';
    printHTML += '</div>';
    
    printHTML += '</div></body></html>';

    var w = window.open('', '', 'width=1000,height=800'); 
    w.document.write(printHTML); 
    w.document.close(); 
    w.print();
  });


  $('#btnPrintAllRSE').click(function(){
    var headers = [], rowsData = [];
    $('table thead th').each((i, th) => { if(i!==9) headers.push(th.innerText); });
    $('table tbody tr').each(function(){
      var rowData = [];
      $(this).find('td').each((i, td) => { if(i!==9) rowData.push(td.innerHTML); });
      rowsData.push(rowData);
    });

    var printHTML = '<html><head><title>RIS Reports</title>';
    printHTML += '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">';
    printHTML += '<style>body{padding:20px;font-size:12px;}table{width:100%;border-collapse:collapse;}th,td{padding:6px;border:1px solid #000;}</style></head><body>';

    // Logo beside text
    printHTML += '<div style="display:flex; align-items:center; justify-content:center; margin-bottom:20px;">';
    printHTML += '<img src="../dist/img/logo.png" style="width:80px; margin-right:15px;">';
    printHTML += '<div style="text-align:center;">';
    printHTML += '<h5 style="margin:0;">KOLEHIYO NG SUBIC</h5>';
    printHTML += '<p style="margin:0;">WFI Compound, Wawandue, Subic, Zambales</p>';
    printHTML += '<p style="margin:0;">Tel.no.: (047)232 – 4896/232-4897</p></div></div>';

    printHTML += '<h4>RIS Reports</h4><table class="table table-bordered"><thead><tr>';
    headers.forEach(h => printHTML += '<th>' + h + '</th>'); printHTML += '</tr></thead><tbody>';
    rowsData.forEach(r => { printHTML += '<tr>'; r.forEach(c => printHTML += '<td>' + c + '</td>'); printHTML += '</tr>'; });
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

    var w = window.open('', '', 'width=1000,height=800'); 
    w.document.write(printHTML); 
    w.document.close(); 
    w.print();
  });

});
</script>