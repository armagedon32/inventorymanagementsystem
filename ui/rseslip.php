<?php
include_once 'connectdb.php';
session_start();

// Only Admin access
if (!isset($_SESSION['useremail']) || ($_SESSION['role'] ?? '') !== "Admin") {
    header('location:../index.php');
    exit;
}

// ==================== HANDLE RETURN ====================
if (isset($_GET['return_id'])) {
    $rse_id = (int) $_GET['return_id'];

    $pdo->beginTransaction();
    try {
        // Fetch items to return to stock
        $stmtItems = $pdo->prepare("
            SELECT ri.quantity, p.property_id
            FROM rse_items ri
            LEFT JOIN tbl_property p ON ri.property_id = p.property_id
            WHERE ri.rse_id = ?
        ");
        $stmtItems->execute([$rse_id]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            if ($item['property_id']) {
                $stmtStock = $pdo->prepare("
                    UPDATE tbl_property
                    SET quantity = quantity + ?
                    WHERE property_id = ?
                ");
                $stmtStock->execute([$item['quantity'], $item['property_id']]);
            }
        }

        // Update RSE header
        $stmtReturn = $pdo->prepare("
            UPDATE rse_header
            SET is_returned = 1, return_date = NOW()
            WHERE id = ?
        ");
        $stmtReturn->execute([$rse_id]);

        $pdo->commit();
        header("Location: rseslip.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Error returning RSE: " . $e->getMessage() . "');</script>";
    }
}

// ==================== HANDLE DELETE ====================
if (isset($_GET['delete_id'])) {
    $rse_id = (int) $_GET['delete_id'];

    $pdo->beginTransaction();
    try {
        // Fetch items to check if they were returned
        $stmtCheck = $pdo->prepare("SELECT is_returned FROM rse_header WHERE id = ?");
        $stmtCheck->execute([$rse_id]);
        $rse = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        // If NOT returned, return stock first before deleting
        if ($rse && ($rse['is_returned'] ?? 0) == 0) {
            $stmtItems = $pdo->prepare("SELECT quantity, property_id FROM rse_items WHERE rse_id = ?");
            $stmtItems->execute([$rse_id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
            foreach ($items as $item) {
                if ($item['property_id']) {
                    $pdo->prepare("UPDATE tbl_property SET quantity = quantity + ? WHERE property_id = ?")
                        ->execute([$item['quantity'], $item['property_id']]);
                }
            }
        }

        // Archive items
        $stmtItemsDel = $pdo->prepare("UPDATE rse_items SET is_archived = 1 WHERE rse_id = ?");
        $stmtItemsDel->execute([$rse_id]);

        // Archive RSE header
        $stmtHeader = $pdo->prepare("UPDATE rse_header SET is_archived = 1 WHERE id = ?");
        $stmtHeader->execute([$rse_id]);

        $pdo->commit();
        header("Location: rseslip.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Error deleting RSE: " . $e->getMessage() . "');</script>";
    }
}

// ==================== FETCH RSE WITH FILTER ====================
$filter = $_GET['filter'] ?? 'all';
$where = ["rh.is_archived = 0"];
if ($filter === 'borrowed') {
    $where[] = "rh.is_returned = 0";
} elseif ($filter === 'returned') {
    $where[] = "rh.is_returned = 1";
} elseif ($filter === 'overdue') {
    $where[] = "rh.is_returned = 0 AND rh.end_datetime < NOW()";
}

$sql = "SELECT rh.*, 
               COALESCE(o.address, org.president) AS oic_name 
        FROM rse_header rh 
        LEFT JOIN tbl_office o ON rh.requesting_office = o.office_name
        LEFT JOIN tbl_organization org ON rh.requesting_office = org.org_name";

$sql .= " WHERE " . implode(" AND ", $where);

$sql .= " ORDER BY rh.id DESC";
$stmtr = $pdo->prepare($sql);
$stmtr->execute();
$rse_list = $stmtr->fetchAll(PDO::FETCH_ASSOC);

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

/* Borrowed.php Style Overrides */
.card-header-custom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.table thead th {
    background-color: #343a40;
    color: white;
    border-color: #454d55;
    vertical-align: middle;
}
.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0,0,0,.05);
}
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h3 class="m-0 text-dark">
                        <a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
                        Equipment Request Records
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">

        <div class="card card-primary card-outline shadow-sm">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="filter-buttons mb-0">
                        <a href="rseslip.php?filter=all" class="filter-all <?= $filter === 'all' ? 'active' : '' ?> shadow-sm">All</a>
                        <a href="rseslip.php?filter=borrowed" class="filter-borrowed <?= $filter === 'borrowed' ? 'active' : '' ?> shadow-sm">Borrowed</a>
                        <a href="rseslip.php?filter=returned" class="filter-returned <?= $filter === 'returned' ? 'active' : '' ?> shadow-sm">Returned</a>
                        <a href="rseslip.php?filter=overdue" class="filter-overdue <?= $filter === 'overdue' ? 'active' : '' ?> shadow-sm">Overdue</a>
                    </div>
                    <button class="btn btn-primary shadow-sm" id="btnPrintAllRSE">
                        <i class="fas fa-print mr-1"></i> Print All
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 text-center">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Requesting Office</th>
                                <th>Event Name</th>
                                <th>Date Filed</th>
                                <th>Schedule</th>
                                <th>Contact No.</th>
                                <th>Items</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rse_list as $rse):
                                $isOverdue = ($rse['is_returned'] ?? 0) == 0 && strtotime($rse['end_datetime']) < time();
                            ?>
                            <tr class="<?= $isOverdue ? 'table-danger' : '' ?>">
                                <td class="align-middle"><?= $rse['id']; ?></td>
                                <td class="align-middle"><strong><?= htmlentities($rse['requesting_office']); ?></strong></td>
                                <td class="align-middle"><?= htmlentities($rse['event_name']); ?></td>
                                <td class="align-middle"><?= date('M d, Y', strtotime($rse['date_of_filing'])); ?></td>
                                <td class="align-middle">
                                    <span class="badge badge-info"><?= date('M d, h:i A', strtotime($rse['start_datetime'])); ?></span><br>
                                    <span class="badge badge-secondary"><?= date('M d, h:i A', strtotime($rse['end_datetime'])); ?></span>
                                    <?php if ($rse['is_returned'] ?? 0): ?>
                                        <br><span class="badge badge-success mt-1">Returned: <?= date('M d, Y', strtotime($rse['return_date'])); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle"><?= htmlentities($rse['contact_no']); ?></td>
                                <td class="text-left align-middle">
                                    <ul class="items-list mb-0">
                                        <?php
                                        $stmtItems = $pdo->prepare("
                                            SELECT ri.quantity,
                                                   COALESCE(p.item_name,'Unknown Item') AS item_name,
                                                   COALESCE(p.serial_no,'') AS serial_no,
                                                   COALESCE(o.office_name,'Unknown Office') AS office_name
                                            FROM rse_items ri
                                            LEFT JOIN tbl_property p ON ri.property_id = p.property_id
                                            LEFT JOIN tbl_office o ON ri.borrowed_from = o.id
                                            WHERE ri.rse_id = ?
                                        ");
                                        $stmtItems->execute([$rse['id']]);
                                        $itemsList = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($itemsList as $item):
                                        ?>
                                            <li class="small" 
                                                data-qty="<?= $item['quantity']; ?>" 
                                                data-item="<?= htmlentities($item['item_name']); ?>" 
                                                data-serial="<?= htmlentities($item['serial_no']); ?>"
                                                data-office="<?= htmlentities($item['office_name']); ?>">
                                                <strong><?= $item['quantity']; ?></strong> x <?= htmlentities($item['item_name']); ?>
                                                <?php if(!empty($item['serial_no'])): ?>
                                                <br><span class="text-muted">SN: <?= htmlentities($item['serial_no']); ?></span>
                                                <?php endif; ?>
                                                <br><span class="text-muted">(From: <?= htmlentities($item['office_name']); ?>)</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td class="align-middle">
                                    <div class="btn-group shadow-sm">
                                        <button class="btn btn-primary btn-sm print-single" 
                                           title="Print" 
                                           data-id="<?= $rse['id']; ?>"
                                           data-office="<?= htmlentities($rse['requesting_office']); ?>"
                                           data-oic="<?= htmlentities($rse['oic_name'] ?? ''); ?>"
                                           data-event="<?= htmlentities($rse['event_name']); ?>"
                                           data-date="<?= htmlentities($rse['date_of_filing']); ?>"
                                           data-start="<?= date('M d Y h:i A', strtotime($rse['start_datetime'])); ?>"
                                           data-end="<?= date('M d Y h:i A', strtotime($rse['end_datetime'])); ?>"
                                           data-contact="<?= htmlentities($rse['contact_no']); ?>"
                                           data-address="<?= htmlentities($rse['address']); ?>">
                                           <i class="fa-solid fa-print"></i>
                                        </button>

                                        <?php if (($rse['is_returned'] ?? 0) == 0): ?>
                                            <button class="btn btn-success btn-sm"
                                               title="Mark as Returned"
                                               onclick="window.location.href='rseslip.php?return_id=<?= $rse['id']; ?>'">
                                               <i class="fa-solid fa-rotate-left"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-success btn-sm" title="Already Returned" disabled>
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        <?php endif; ?>

                                        <button class="btn btn-danger btn-sm"
                                           title="Delete"
                                           onclick="window.location.href='rseslip.php?delete_id=<?= $rse['id']; ?>'">
                                           <i class="fa-solid fa-trash"></i>
                                        </button>

                                        <?php if ($isOverdue): ?>
                                            <button class="btn btn-danger btn-sm" title="Overdue">
                                                <i class="fa-solid fa-clock"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
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

  // Print Single RSE (JS-driven)
  $('.print-single').click(function(e) {
    e.preventDefault();
    var btn = $(this);
    var row = btn.closest('tr');
    
    // Extract data from attributes
    var office = btn.data('office');
    var oicName = btn.data('oic') || office; // Use office name as fallback if OIC is empty
    var event = btn.data('event');
    var dateFiled = btn.data('date');
    var start = btn.data('start');
    var end = btn.data('end');
    var contact = btn.data('contact');
    var address = btn.data('address');

    // Extract items from the items column (Col 6)
    var itemsRows = '';
    row.find('td').eq(6).find('li').each(function() {
        var qty = $(this).data('qty');
        var item = $(this).data('item');
        var serial = $(this).data('serial') || '';
        var office = $(this).data('office');
        
        itemsRows += '<tr>';
        itemsRows += '<td style="text-align:center;">' + qty + '</td>';
        itemsRows += '<td>' + item + (serial ? ' <br><small>SN: ' + serial + '</small>' : '') + '</td>';
        itemsRows += '<td>' + office + '</td>';
        itemsRows += '</tr>';
    });

    var printHTML = '<html><head><title>RSE Report</title>';
    printHTML += '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">';
    printHTML += '<style>body{padding:20px;font-size:14px;} .form-label{font-weight:bold;margin-bottom:0;} .form-value{border-bottom:1px solid #eee;padding-bottom:2px;margin-bottom:15px;} table{width:100%;border-collapse:collapse;} th,td{padding:8px;border:1px solid #000;}</style></head><body>';

    // COLLEGE HEADER (borrowed.php style)
    printHTML += '<div style="display:flex; align-items:center; justify-content:center; margin-bottom:20px;">';
    printHTML += '<img src="../dist/img/logo.png" style="width:80px; margin-right:15px;">';
    printHTML += '<div style="text-align:center;">';
    printHTML += '<h5 style="margin:0;">KOLEHIYO NG SUBIC</h5>';
    printHTML += '<p style="margin:0;">WFI Compound, Wawandue, Subic, Zambales</p>';
    printHTML += '<p style="margin:0;">Tel.no.: (047)232 – 4896/232-4897</p></div></div>';

    printHTML += '<h4 class="text-center mb-4">Request for Equipment (RSE)</h4>';

    // Form-like Layout (borrowed.php style)
    printHTML += '<div class="container-fluid">';
    
    // Requester Info (borrowed.php style with RSE variables)
    printHTML += '<h5 class="mb-3">Requester Information</h5>';
    printHTML += '  <div class="row">';
    printHTML += '    <div class="col-8"><p class="form-label">Office / Organization</p><p class="form-value">' + office + '</p></div>';
    printHTML += '    <div class="col-4"><p class="form-label">Contact Number</p><p class="form-value">' + contact + '</p></div>';
    printHTML += '  </div>';
    printHTML += '  <div class="row">';
    printHTML += '    <div class="col-12"><p class="form-label">Address</p><p class="form-value">' + address + '</p></div>';
    printHTML += '  </div>';

    // Event Details (borrowed.php style with RSE variables)
    printHTML += '<h5 class="mt-4 mb-3">Event Details</h5>';
    printHTML += '  <div class="row">';
    printHTML += '    <div class="col-6"><p class="form-label">Name of Event</p><p class="form-value">' + event + '</p></div>';
    printHTML += '    <div class="col-6"><p class="form-label">Date of Filing</p><p class="form-value">' + dateFiled + '</p></div>';
    printHTML += '  </div>';
    printHTML += '  <div class="row">';
    printHTML += '    <div class="col-6"><p class="form-label">Start Date & Time</p><p class="form-value">' + start + '</p></div>';
    printHTML += '    <div class="col-6"><p class="form-label">End Date & Time</p><p class="form-value">' + end + '</p></div>';
    printHTML += '  </div>';
    
    printHTML += '</div>';

    printHTML += '<h5 class="mt-4">Equipment Borrowed</h5>';
    printHTML += '<table class="table table-bordered"><thead><tr>';
    printHTML += '<th width="100" style="text-align:center;">Quantity</th><th>Item Name</th><th>Borrowed From</th>';
    printHTML += '</tr></thead><tbody>';
    printHTML += itemsRows;
    printHTML += '</tbody></table>';

    printHTML += '<div style="margin-top:50px; display: flex; justify-content: space-between;">';
    
    // Left Side: Prepared & Approved (signatories maintained)
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

    // Right Side: Borrowed By (borrowed.php style with RSE variables)
    printHTML += '<div>';
    printHTML += '  <p style="margin:0; font-weight:bold;">Borrowed by:</p>';
    printHTML += '  <div style="margin-top:30px; width:250px; border-bottom:1px solid #000; text-align:center;">';
    printHTML += '    <span style="font-weight:bold; text-transform:uppercase;">' + oicName + '</span>';
    printHTML += '  </div>';
    printHTML += '  <p style="margin:0; text-align:center;">' + office + '</p>';
    printHTML += '</div>';
    
    printHTML += '</div></body></html>';

    var w = window.open('', '', 'width=1000,height=800'); 
    w.document.write(printHTML); 
    w.document.close(); 
    w.print();
  });


  $('#btnPrintAllRSE').click(function(){
    var headers = [], rowsData = [];
    $('table thead th').each((i, th) => { if(i!==7) headers.push(th.innerText); });
    $('table tbody tr').each(function(){
      var rowData = [];
      $(this).find('td').each((i, td) => { 
        if(i!==7) {
          // Remove badges and keep text only for cleaner print
          var content = $(td).clone();
          content.find('.badge').each(function() {
            var txt = $(this).text();
            $(this).replaceWith(txt + ' ');
          });
          rowData.push(content.html());
        }
      });
      rowsData.push(rowData);
    });

    var printHTML = '<html><head><title>RSE Reports</title>';
    printHTML += '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">';
    printHTML += '<style>body{padding:20px;font-size:12px;}table{width:100%;border-collapse:collapse;}th,td{padding:6px;border:1px solid #000;} .text-muted{color:#000 !important;}</style></head><body>';

    // Logo beside text
    printHTML += '<div style="display:flex; align-items:center; justify-content:center; margin-bottom:20px;">';
    printHTML += '<img src="../dist/img/logo.png" style="width:80px; margin-right:15px;">';
    printHTML += '<div style="text-align:center;">';
    printHTML += '<h5 style="margin:0;">KOLEHIYO NG SUBIC</h5>';
    printHTML += '<p style="margin:0;">WFI Compound, Wawandue, Subic, Zambales</p>';
    printHTML += '<p style="margin:0;">Tel.no.: (047)232 – 4896/232-4897</p></div></div>';

    printHTML += '<h4>RSE Reports</h4><table class="table table-bordered"><thead><tr>';
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