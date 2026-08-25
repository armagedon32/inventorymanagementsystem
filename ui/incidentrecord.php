<?php
include_once 'connectdb.php';
session_start();

// Only Admin access
if (!isset($_SESSION['useremail']) || ($_SESSION['role'] ?? '') == 'User') {
    header('location:../index.php');
    exit;
}

/* ==================== HANDLE ACTIONS ==================== */
$msg = '';

// Handle Disposal
if (isset($_GET['dispose_id'])) {
    $incident_id = (int)$_GET['dispose_id'];
    try {
        $pdo->beginTransaction();

        // 1. Fetch items involved in this incident
        $stmtItems = $pdo->prepare("SELECT property_id, quantity FROM incident_items WHERE incident_id = ?");
        $stmtItems->execute([$incident_id]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            throw new Exception("No items found for this incident report.");
        }

        foreach ($items as $item) {
            $prop_id = $item['property_id'];
            $report_qty = (int)$item['quantity'];
            
            // 2. Get property info
            $stmtProp = $pdo->prepare("SELECT item_name, inventory_no, office_id, serial_no FROM tbl_property WHERE property_id = ?");
            $stmtProp->execute([$prop_id]);
            $prop = $stmtProp->fetch(PDO::FETCH_ASSOC);

            if ($prop) {
                // A. Insert into disposal table (to track history) BEFORE deleting from property table
                // We capture the current property data for the disposal history
                $stmtDisp = $pdo->prepare("INSERT INTO tbl_disposal (property_id, item_name, inventory_no, office_id, quantity, remarks, disposed_by, disposed_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmtDisp->execute([
                    $prop_id, 
                    $prop['item_name'],
                    $prop['inventory_no'],
                    $prop['office_id'], 
                    $report_qty, 
                    "Disposed from Incident Report #$incident_id", 
                    $_SESSION['fullname'] ?? 'Admin'
                ]);

                // B. Cleanup related tables to allow deletion (Foreign Key constraints)
                // Note: We do NOT delete from tbl_disposal here anymore, as we want to KEEP the history
                $pdo->prepare("DELETE FROM ptr_items WHERE property_id = ?")->execute([$prop_id]);
                $pdo->prepare("DELETE FROM ris_items WHERE property_id = ?")->execute([$prop_id]);
                $pdo->prepare("DELETE FROM rse_items WHERE property_id = ?")->execute([$prop_id]);

                // C. Delete completely from tbl_property
                $stmtDeleteProp = $pdo->prepare("DELETE FROM tbl_property WHERE property_id = ?");
                $stmtDeleteProp->execute([$prop_id]);

                // 5. Log activity
                logActivity($pdo, "Permanently Disposed Item: " . $prop['item_name'] . " (" . ($prop['serial_no'] ?? 'N/A') . ") from Incident #$incident_id");
            }
        }

        // 6. Archive incident records
        $pdo->prepare("UPDATE incident_items SET is_archived = 1 WHERE incident_id = ?")->execute([$incident_id]);
        $pdo->prepare("UPDATE incident_reports SET is_archived = 1 WHERE id = ?")->execute([$incident_id]);

        $pdo->commit();
        $_SESSION['status'] = "Item permanently disposed and recorded in Disposal History.";
        $_SESSION['status_code'] = "success";
        header("Location: disposal.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Disposal Error: " . $e->getMessage();
        header("Location: incidentrecord.php");
        exit();
    }
}

// Handle Replacement
if (isset($_GET['replace_id'])) {
    $incident_id = (int)$_GET['replace_id'];
    try {
        $pdo->beginTransaction();

        // 1. Fetch items involved
        $stmtItems = $pdo->prepare("SELECT property_id, quantity FROM incident_items WHERE incident_id = ?");
        $stmtItems->execute([$incident_id]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            throw new Exception("No items found for this incident report.");
        }

        foreach ($items as $item) {
            $prop_id = $item['property_id'];
            $report_qty = (int)$item['quantity'];

            // 2. Get property info
            $stmtProp = $pdo->prepare("SELECT item_name, office_id FROM tbl_property WHERE property_id = ?");
            $stmtProp->execute([$prop_id]);
            $prop = $stmtProp->fetch(PDO::FETCH_ASSOC);

            if ($prop) {
                // 3. Update remarks only, do not add in quantity
                $stmtUpdate = $pdo->prepare("UPDATE tbl_property SET remarks = 'Cleared / OK', is_active = 1, is_archived = 0 WHERE property_id = ?");
                $stmtUpdate->execute([$prop_id]);

                // 4. Log activity
                logActivity($pdo, "Cleared/Replaced Item: " . $prop['item_name'] . " (Incident #$incident_id)");
            }
        }

        // 5. Archive incident records
        $pdo->prepare("UPDATE incident_items SET is_archived = 1 WHERE incident_id = ?")->execute([$incident_id]);
        $pdo->prepare("UPDATE incident_reports SET is_archived = 1 WHERE id = ?")->execute([$incident_id]);

        $pdo->commit();
        $_SESSION['msg'] = "replaced";
        header("Location: incidentrecord.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Replacement Error: " . $e->getMessage();
        header("Location: incidentrecord.php");
        exit();
    }
}

include_once "header.php";

/* ==================== FETCH INCIDENT REPORTS ==================== */
$reports = $pdo->query("
    SELECT ir.*, o.office_name
    FROM incident_reports ir
    LEFT JOIN tbl_office o ON ir.office = o.id
    WHERE ir.is_archived = 0
    ORDER BY ir.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h4 class="m-0 text-dark">
            <a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
            Incident Reports
          </h4>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <?php if (isset($_SESSION['msg'])): ?>
        <?php if ($_SESSION['msg'] == "disposed"): ?>
          <script>Swal.fire('Disposed!', 'Items moved to Disposal History and removed from inventory.', 'success');</script>
        <?php elseif ($_SESSION['msg'] == "replaced"): ?>
          <script>Swal.fire('Cleared!', 'Items marked as OK and returned to property inventory.', 'success');</script>
        <?php endif; unset($_SESSION['msg']); ?>
      <?php endif; ?>

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

      <?php if (isset($_SESSION['error'])): ?>
        <script>Swal.fire('Error!', '<?= htmlspecialchars($_SESSION['error']); ?>', 'error');</script>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <div class="card card-primary card-outline">
        <div class="card-header d-flex align-items-center">
          <div><h5 class="m-0">Incident Reports</h5></div>
          <div class="ml-auto">
            <button type="button" class="btn btn-primary btn-sm" id="btnPrintAll">
              <i class="fa fa-print"></i> Print All
            </button>
          </div>
        </div>

        <div class="card-body">
          <table id="table_incidents" class="table table-striped table-hover table-bordered text-center">
            <thead>
              <tr>
                <th>ID</th>
                <th>Report No.</th>
                <th>Reported By</th>
                <th>Office</th>
                <th>Date / Time</th>
                <th>Incident Details</th>
                <th>Items Involved</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
<?php foreach ($reports as $rep): ?>
<tr>
  <td><?= $rep['id']; ?></td>
  <td><?= htmlentities($rep['report_number']); ?></td>
  <td><?= htmlentities($rep['reported_by']); ?></td>
  <td><?= htmlentities($rep['office_name'] ?? 'Unknown'); ?></td>
  <td>
    <?= date('M d Y', strtotime($rep['incident_date'])); ?><br>
    <small><?= date('h:i A', strtotime($rep['incident_time'])); ?></small>
  </td>
  <td class="text-left">
    <strong>Description:</strong><br><?= nl2br(htmlentities($rep['description'])); ?><br><br>
    <strong>Extent:</strong><br><?= nl2br(htmlentities($rep['extent_of_damage'])); ?>
  </td>
  <td class="text-left">
    <ul style="padding-left:15px; margin:0;">
<?php
$stmtItems = $pdo->prepare("
    SELECT COALESCE(p.item_name,'Unknown Item') AS item_name,
           i.quantity,
           i.serial_number,
           i.location,
           i.last_borrower
    FROM incident_items i
    LEFT JOIN tbl_property p ON i.property_id = p.property_id
    WHERE i.incident_id = ?
");
$stmtItems->execute([$rep['id']]);
foreach ($stmtItems->fetchAll(PDO::FETCH_ASSOC) as $item):
?>
      <li>
        <strong>Qty: <?= $item['quantity']; ?></strong> - <?= htmlentities($item['item_name']); ?><br>
        <small>SN: <?= htmlentities($item['serial_number']); ?> | Location: <?= htmlentities($item['location']); ?> | Last Borrower: <?= htmlentities($item['last_borrower']); ?></small>
      </li>
<?php endforeach; ?>
    </ul>
  </td>
  <td>
    <div class="btn-group">
      <a href="#" class="btn btn-info btn-sm btnprint" 
         title="Print" 
         data-id="<?= $rep['id']; ?>"
         data-report-no="<?= htmlentities($rep['report_number']); ?>"
         data-reported-by="<?= htmlentities($rep['reported_by']); ?>"
         data-office="<?= htmlentities($rep['office_name'] ?? 'Unknown'); ?>"
         data-date="<?= date('M d Y', strtotime($rep['incident_date'])); ?>"
         data-time="<?= date('h:i A', strtotime($rep['incident_time'])); ?>"
         data-desc="<?= htmlentities($rep['description']); ?>"
         data-extent="<?= htmlentities($rep['extent_of_damage']); ?>"><i class="fa fa-print"></i></a>
      <a href="?dispose_id=<?= $rep['id']; ?>" class="btn btn-warning btn-sm btndispose" title="Move to Disposal"><i class="fa fa-trash"></i></a>
      <a href="?replace_id=<?= $rep['id']; ?>" class="btn btn-success btn-sm btnreplace" title="Mark as OK / Replace"><i class="fa fa-arrows-rotate"></i></a>
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

  var table = $('#table_incidents').DataTable({
    "order": [[0, "desc"]],
    "columnDefs": [{ "orderable": false, "targets": 7 }]
  });

  // SweetAlert Disposal - Removed confirmation as per user preference
  $('#table_incidents').on('click', '.btndispose', function(e) {
    e.preventDefault();
    var link = $(this).attr('href');
    window.location.href = link;
  });

  // SweetAlert Replacement - Removed confirmation as per user preference
  $('#table_incidents').on('click', '.btnreplace', function(e) {
    e.preventDefault();
    var link = $(this).attr('href');
    window.location.href = link;
  });

  // Print Single Incident
  $('#table_incidents').on('click', '.btnprint', function() {
    var btn = $(this);
    var row = btn.closest('tr');
    
    // Extract data from attributes
    var reportNo = btn.data('report-no');
    var reportedBy = btn.data('reported-by');
    var office = btn.data('office');
    var date = btn.data('date');
    var time = btn.data('time');
    var desc = btn.data('desc');
    var extent = btn.data('extent');

    // Extract items from the items column (Col 6)
    var itemsHtml = '';
    row.find('td').eq(6).find('li').each(function() {
        var text = $(this).text().trim();
        // Format: "Qty: 1 - Item Name \n SN: SN123 | Location: Admin Office | Last Borrower: John Doe"
        var lines = text.split('\n');
        var itemLine = lines[0].trim();
        var qtyMatch = itemLine.match(/Qty:\s*(\d+)\s*-\s*(.*)/);
        var qty = qtyMatch ? qtyMatch[1] : '1';
        var itemName = qtyMatch ? qtyMatch[2] : itemLine;
        
        var details = lines[1] ? lines[1].trim() : '';
        
        // Parse details: "SN: SN123 | Location: Admin Office | Last Borrower: John Doe"
        var parts = details.split('|');
        var sn = parts[0] ? parts[0].replace('SN:', '').trim() : '';
        var loc = parts[1] ? parts[1].replace('Location:', '').trim() : '';
        var borrower = parts[2] ? parts[2].replace('Last Borrower:', '').trim() : '';
        
        itemsHtml += '<tr>';
        itemsHtml += '<td>' + itemName + '</td>';
        itemsHtml += '<td class="text-center">' + qty + '</td>';
        itemsHtml += '<td>' + sn + '</td>';
        itemsHtml += '<td>' + loc + '</td>';
        itemsHtml += '<td>' + borrower + '</td>';
        itemsHtml += '</tr>';
    });

    var printHTML = '<html><head><title>Incident Report</title>';
    printHTML += '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">';
    printHTML += '<style>body{padding:20px;font-size:14px;} .form-label{font-weight:bold;margin-bottom:0;} .form-value{border-bottom:1px solid #eee;padding-bottom:2px;margin-bottom:15px;} table{width:100%;border-collapse:collapse;} th,td{padding:8px;border:1px solid #000;}</style></head><body>';

    // Logo beside text
    printHTML += '<div style="display:flex; align-items:center; justify-content:center; margin-bottom:20px;">';
    printHTML += '<img src="../dist/img/logo.png" style="width:80px; margin-right:15px;">';
    printHTML += '<div style="text-align:center;">';
    printHTML += '<h5 style="margin:0;">KOLEHIYO NG SUBIC</h5>';
    printHTML += '<p style="margin:0;">WFI Compound, Wawandue, Subic, Zambales</p>';
    printHTML += '<p style="margin:0;">Tel.no.: (047)232 – 4896/232-4897</p></div></div>';

    printHTML += '<h4 class="text-center mb-4">Incident Report Form</h4>';

    // Form-like Layout
    printHTML += '<div class="container-fluid">';
    
    // General Information Section
    printHTML += '<h5>General Information</h5>';
    printHTML += '  <div class="row">';
    printHTML += '    <div class="col-6"><p class="form-label">Report Number</p><p class="form-value">' + reportNo + '</p></div>';
    printHTML += '    <div class="col-6"><p class="form-label">Reported By</p><p class="form-value">' + reportedBy + '</p></div>';
    printHTML += '  </div>';
    printHTML += '  <div class="row">';
    printHTML += '    <div class="col-12"><p class="form-label">Office</p><p class="form-value">' + office + '</p></div>';
    printHTML += '  </div>';

    // Item Details Section
    printHTML += '<h5 class="mt-4">Item Details</h5>';
    printHTML += '<table class="table table-bordered"><thead><tr>';
    printHTML += '<th>Item Name</th><th class="text-center">Qty</th><th>Serial Number</th><th>Location</th><th>Last Borrower</th>';
    printHTML += '</tr></thead><tbody>';
    printHTML += itemsHtml;
    printHTML += '</tbody></table>';

    // Incident Description Section
    printHTML += '<h5 class="mt-4">Incident Description</h5>';
    printHTML += '  <div class="row">';
    printHTML += '    <div class="col-6"><p class="form-label">Date of Incident</p><p class="form-value">' + date + '</p></div>';
    printHTML += '    <div class="col-6"><p class="form-label">Time of Incident</p><p class="form-value">' + time + '</p></div>';
    printHTML += '  </div>';
    
    printHTML += '  <div class="row">';
    printHTML += '    <div class="col-12"><p class="form-label">Description of Damage</p><p class="form-value">' + desc.replace(/\n/g, '<br>') + '</p></div>';
    printHTML += '  </div>';
    
    printHTML += '  <div class="row">';
    printHTML += '    <div class="col-12"><p class="form-label">Extent of Damage</p><p class="form-value">' + extent.replace(/\n/g, '<br>') + '</p></div>';
    printHTML += '  </div>';
    
    printHTML += '</div>';

    // Signatures Section
    printHTML += '<div style="margin-top:50px; display: flex; justify-content: space-between;">';
    
    // Left side: Prepared & Approved
    printHTML += '<div>';
    printHTML += '  <div style="margin-bottom: 40px;">';
    printHTML += '    <p style="margin:0; font-weight:bold;">Prepared by:</p>';
    printHTML += '    <div style="width:250px; text-align:center;">';
    printHTML += '      <div style="margin-top:30px; border-bottom:1px solid #000;">';
    printHTML += '        <span style="font-weight:bold; text-transform:uppercase;">' + window.globalOIC.property + '</span>';
    printHTML += '      </div>';
    printHTML += '      <p style="margin:0;">Property/Supplies Officer</p>';
    printHTML += '    </div>';
    printHTML += '  </div>';
    
    printHTML += '  <div>';
    printHTML += '    <p style="margin:0; font-weight:bold;">Approved by:</p>';
    printHTML += '    <div style="width:250px; text-align:center;">';
    printHTML += '      <div style="margin-top:30px; border-bottom:1px solid #000;">';
    printHTML += '        <span style="font-weight:bold; text-transform:uppercase;">' + window.globalOIC.president + '</span>';
    printHTML += '      </div>';
    printHTML += '      <p style="margin:0;">College President</p>';
    printHTML += '    </div>';
    printHTML += '  </div>';
    printHTML += '</div>';

    // Right side: Reported by
    printHTML += '<div>';
    printHTML += '  <p style="margin:0; font-weight:bold;">Reported by:</p>';
    printHTML += '  <div style="width:250px; text-align:center;">';
    printHTML += '    <div style="margin-top:30px; border-bottom:1px solid #000;">';
    printHTML += '      <span style="font-weight:bold; text-transform:uppercase;">' + reportedBy + '</span>';
    printHTML += '    </div>';
    printHTML += '  </div>';
    printHTML += '</div>';
    
    printHTML += '</div></body></html>';

    var w = window.open('', '', 'width=1000,height=800'); w.document.write(printHTML); w.document.close(); w.print();
  });

  // Print All
  $('#btnPrintAll').click(function(){
    var headers = [], rowsData = [];
    $('#table_incidents thead th').each((i, th) => { if(i!==7) headers.push(th.innerText); });
    $('#table_incidents tbody tr').each(function(){
      var rowData = [];
      $(this).find('td').each((i, td) => { if(i!==7) rowData.push(td.innerHTML); });
      rowsData.push(rowData);
    });

    var printHTML = '<html><head><title>Incident Reports</title>';
    printHTML += '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">';
    printHTML += '<style>body{padding:20px;font-size:12px;}table{width:100%;border-collapse:collapse;}th,td{padding:6px;border:1px solid #000;}</style></head><body>';

    // Logo beside text
    printHTML += '<div style="display:flex; align-items:center; justify-content:center; margin-bottom:20px;">';
    printHTML += '<img src="../dist/img/logo.png" style="width:80px; margin-right:15px;">';
    printHTML += '<div style="text-align:center;">';
    printHTML += '<h5 style="margin:0;">KOLEHIYO NG SUBIC</h5>';
    printHTML += '<p style="margin:0;">WFI Compound, Wawandue, Subic, Zambales</p>';
    printHTML += '<p style="margin:0;">Tel.no.: (047)232 – 4896/232-4897</p></div></div>';
    printHTML += '</div></div>';

    printHTML += '<h4>Incident Reports</h4><table class="table table-bordered"><thead><tr>';
    headers.forEach(h => printHTML += '<th>' + h + '</th>'); printHTML += '</tr></thead><tbody>';
    rowsData.forEach(r => { printHTML += '<tr>'; r.forEach(c => printHTML += '<td>' + c + '</td>'); printHTML += '</tr>'; });
    printHTML += '</tbody></table>';

    printHTML += '<div style="margin-top:80px; display: flex; justify-content: space-between;">';
    
    // College President Section
    printHTML += '<div>';
    printHTML += '  <div style="width:250px; border-bottom:1px solid #000; text-align:center;">';
    printHTML += '    <span style="font-weight:bold; text-transform:uppercase;">DR. ROSELY H. AGUSTIN</span>';
    printHTML += '  </div>';
    printHTML += '  <p style="margin:0; text-align:center;">College President Office</p>';
    printHTML += '</div>';

    // Property/Supplies Officer Section
    printHTML += '<div>';
    printHTML += '  <div style="width:250px; border-bottom:1px solid #000; text-align:center;">';
    printHTML += '    <span style="font-weight:bold; text-transform:uppercase;">' + window.globalOIC.property + '</span>';
    printHTML += '  </div>';
    printHTML += '  <p style="margin:0; text-align:center;">Property/Supplies Officer</p>';
    printHTML += '</div>';
    
    printHTML += '</div></body></html>';

    var w = window.open('', '', 'width=1000,height=800'); w.document.write(printHTML); w.document.close(); w.print();
  });

});
</script>