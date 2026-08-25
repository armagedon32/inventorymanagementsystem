<?php 
include_once 'connectdb.php';
session_start();

if (!isset($_SESSION['useremail']) || ($_SESSION['role'] ?? '') !== "Admin") {
    header('location:../index.php');
    exit;
}

// ==================== HANDLE DELETE ====================
if (isset($_GET['delete_id'])) {
    $facility_id = (int) $_GET['delete_id'];

    $pdo->beginTransaction();
    try {
        $stmtItems = $pdo->prepare("UPDATE facility_items SET is_archived = 1 WHERE facility_id = ?");
        $stmtItems->execute([$facility_id]);

        $stmtHeader = $pdo->prepare("UPDATE facility_header SET is_archived = 1 WHERE id = ?");
        $stmtHeader->execute([$facility_id]);

        $pdo->commit();
        header("Location: facility_record.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Error archiving request: " . $e->getMessage() . "');</script>";
    }
}

// ==================== HANDLE STATUS UPDATE ====================
if (isset($_GET['status_id']) && isset($_GET['new_status'])) {
    $req_id = (int) $_GET['status_id'];
    $new_status = $_GET['new_status'];

    try {
        $stmtStatus = $pdo->prepare("UPDATE facility_header SET status = ? WHERE id = ?");
        $stmtStatus->execute([$new_status, $req_id]);
    } catch (PDOException $e) {
        if ($e->getCode() == '42S22') {
            $pdo->exec("ALTER TABLE facility_header ADD COLUMN status VARCHAR(20) DEFAULT 'Pending' AFTER end_datetime");
            $stmtStatus = $pdo->prepare("UPDATE facility_header SET status = ? WHERE id = ?");
            $stmtStatus->execute([$new_status, $req_id]);
        } else {
            throw $e;
        }
    }

    header("Location: facility_record.php");
    exit;
}

// ==================== FETCH ====================
$filter = $_GET['filter'] ?? 'all';
$sql = "SELECT fh.*, o.address AS oic_name 
        FROM facility_header fh 
        LEFT JOIN tbl_office o ON fh.requesting_office = o.office_name";

$where = ["fh.is_archived = 0"];
if ($filter !== 'all') {
    try {
        $pdo->query("SELECT status FROM facility_header LIMIT 1");
        $where[] = "fh.status = " . $pdo->quote($filter);
    } catch (PDOException $e) {
        if ($e->getCode() == '42S22') {
            $pdo->exec("ALTER TABLE facility_header ADD COLUMN status VARCHAR(20) DEFAULT 'Pending' AFTER end_datetime");
            $where[] = "fh.status = " . $pdo->quote($filter);
        } else {
            throw $e;
        }
    }
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY fh.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$facility_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
.filter-buttons a { margin-right: 10px; padding: 5px 12px; border-radius: 5px; color: #fff; text-decoration:none; }
.filter-buttons a.active { font-weight: bold; border: 2px solid #000; }
.filter-all { background-color: #6c757d; }
.filter-pending { background-color: #ffc107; color: #000 !important; }
.filter-progress { background-color: #17a2b8; }
.filter-completed { background-color: #28a745; }
.filter-cancelled { background-color: #dc3545; }
</style>

<div class="content-wrapper">
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3 class="m-0 text-dark">
                    <a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
                    Facility Requests
                </h3>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="filter-buttons">
        <a href="facility_record.php?filter=all" class="filter-all <?= $filter === 'all' ? 'active' : '' ?>">All</a>
        <a href="facility_record.php?filter=Pending" class="filter-pending <?= $filter === 'Pending' ? 'active' : '' ?>">Pending</a>
        <a href="facility_record.php?filter=In Progress" class="filter-progress <?= $filter === 'In Progress' ? 'active' : '' ?>">In Progress</a>
        <a href="facility_record.php?filter=Completed" class="filter-completed <?= $filter === 'Completed' ? 'active' : '' ?>">Completed</a>
        <a href="facility_record.php?filter=Cancelled" class="filter-cancelled <?= $filter === 'Cancelled' ? 'active' : '' ?>">Cancelled</a>
    </div>
    <button class="btn btn-primary" id="btnPrintAllRSE">Print All</button>
</div>

<div class="table-responsive">
<table class="table table-bordered table-striped text-center" id="tblFacility">

<thead class="thead-dark">
<tr>
    <th>ID</th>
    <th>Requesting Office</th>
    <th>Event</th>
    <th>Date Filed</th>
    <th>Schedule</th>
    <th>Contact No.</th>
    <th>Facilities</th>
    <th>Participants</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>

<tbody>
<?php foreach ($facility_list as $req):
    $status = $req['status'] ?? 'Pending';
    $statusBadge = 'badge-secondary';
    if ($status === 'Pending') $statusBadge = 'badge-warning';
    elseif ($status === 'In Progress') $statusBadge = 'badge-info';
    elseif ($status === 'Completed') $statusBadge = 'badge-success';
    elseif ($status === 'Cancelled') $statusBadge = 'badge-danger';
?>
<tr>
    <td><?= $req['id']; ?></td>
    <td><?= htmlentities($req['requesting_office']); ?></td>
    <td><?= htmlentities($req['event_name']); ?></td>
    <td><?= htmlentities($req['date_of_filing']); ?></td>
    <td>
        <?= date('M d Y h:i A', strtotime($req['start_datetime'])); ?><br>to<br>
        <?= date('M d Y h:i A', strtotime($req['end_datetime'])); ?>
    </td>
    <td><?= htmlentities($req['contact_no']); ?></td>

    <td class="text-left">
        <ul class="items-list">
        <?php
        $stmtItems = $pdo->prepare("
            SELECT o.office_name
            FROM facility_items fi
            LEFT JOIN tbl_office o ON fi.office_id = o.id
            WHERE fi.facility_id = ?
        ");
        $stmtItems->execute([$req['id']]);
        foreach ($stmtItems->fetchAll(PDO::FETCH_ASSOC) as $item):
        ?>
            <li><?= htmlentities($item['office_name']); ?></li>
        <?php endforeach; ?>
        </ul>
    </td>

    <td><?= htmlentities($req['num_participants']); ?></td>
    <td><span class="badge <?= $statusBadge ?>"><?= $status ?></span></td>

    <td>
        <div class="action-icons">
            <?php if ($status === 'Pending'): ?>
                <i class="fa-solid fa-spinner text-warning" title="Set to In Progress"
                   onclick="window.location.href='facility_record.php?status_id=<?= $req['id']; ?>&new_status=In Progress'"></i>
            <?php endif; ?>

            <?php if ($status === 'Pending' || $status === 'In Progress'): ?>
                <i class="fa-solid fa-check-circle text-success" title="Mark as Completed"
                   onclick="window.location.href='facility_record.php?status_id=<?= $req['id']; ?>&new_status=Completed'"></i>
            <?php endif; ?>

            <?php if ($status !== 'Cancelled' && $status !== 'Completed'): ?>
                <i class="fa-solid fa-ban text-danger" title="Cancel Request"
                   onclick="window.location.href='facility_record.php?status_id=<?= $req['id']; ?>&new_status=Cancelled'"></i>
            <?php endif; ?>

    <i class="fa-solid fa-print text-primary print-single" 
                title="Print"
                data-id="<?= $req['id']; ?>"
                data-office="<?= htmlentities($req['requesting_office']); ?>"
                data-oic="<?= htmlentities($req['oic_name'] ?? ''); ?>"
                data-event="<?= htmlentities($req['event_name']); ?>"
                data-date="<?= htmlentities($req['date_of_filing']); ?>"
                data-start="<?= date('Y-m-d h:i A', strtotime($req['start_datetime'])); ?>"
                data-end="<?= date('Y-m-d h:i A', strtotime($req['end_datetime'])); ?>"
                data-contact="<?= htmlentities($req['contact_no']); ?>"
                data-address="<?= htmlentities($req['address']); ?>"
                data-participants="<?= htmlentities($req['num_participants']); ?>"
                data-fname="<?= htmlentities($req['first_name'] ?? ''); ?>"
                data-lname="<?= htmlentities($req['last_name'] ?? ''); ?>"
                data-mi="<?= htmlentities($req['mi'] ?? ''); ?>"
                data-pos="<?= htmlentities($req['position_designation'] ?? ''); ?>"></i>

            <i class="fa-solid fa-trash text-muted"
               title="Delete"
               onclick="window.location.href='facility_record.php?delete_id=<?= $req['id']; ?>'"></i>
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

  // ================= PRINT SINGLE =================
  $('.print-single').click(function(e) {
    e.preventDefault();
    var btn = $(this);
    var id = btn.data('id');
    var office       = btn.data('office');
    var event        = btn.data('event');
    var dateFiled    = btn.data('date');
    var start        = btn.data('start');
    var end          = btn.data('end');
    var contact      = btn.data('contact');
    var address      = btn.data('address');
    var participants = btn.data('participants');
    var fname        = btn.data('fname');
    var lname        = btn.data('lname');
    var mi           = btn.data('mi');
    var pos          = btn.data('pos');
    var fullName = (fname + ' ' + (mi ? mi + ' ' : '') + lname).trim();

    var start_date = start.split(' ')[0];
    var start_time = start.split(' ')[1] + ' ' + start.split(' ')[2];
    var end_date = end.split(' ')[0];
    var end_time = end.split(' ')[1] + ' ' + end.split(' ')[2];

    var facilitiesHtml = btn.closest('tr').find('td').eq(6).html();

    // Fetch equipment via AJAX
    $.ajax({
      url: 'get_facility_equipment.php',
      type: 'GET',
      data: { id: id },
      success: function(response) {
        var equipment = JSON.parse(response);
        var equipmentRows = '';
        equipment.forEach(function(eq) {
          equipmentRows += '<tr><td>' + eq.quantity + '</td><td>' + eq.item_name + '</td><td>' + eq.description + '</td></tr>';
        });

        var printHTML = '<html><head><title>Facility Use Request Form</title>';
        printHTML += '<style>';
        printHTML += 'body{font-family: "Times New Roman", serif; padding: 40px; line-height: 1.2;}';
        printHTML += '.form-row{display: flex; margin-bottom: 10px; border-bottom: 1px solid #000; padding-bottom: 2px;}';
        printHTML += '.label{font-weight: bold; margin-right: 10px;}';
        printHTML += 'table{width: 100%; border-collapse: collapse; margin-top: 20px;}';
        printHTML += 'th, td{border: 1px solid #000; padding: 5px; text-align: center;}';
        printHTML += '.sig-section{margin-top: 40px; display: flex; justify-content: space-between;}';
        printHTML += '.sig-box{text-align: center; width: 45%;}';
        printHTML += '.sig-line{border-top: 1px solid #000; margin-top: 2px; font-size: 14px;}';
        printHTML += '.sig-name{font-weight: bold; text-transform: uppercase; margin-top: 20px;}';
        printHTML += '</style></head><body>';

        printHTML += '<div style="display:flex;align-items:center;justify-content:center;margin-bottom:20px;">';
        printHTML += '<img src="../dist/img/logo.png" style="width:80px;margin-right:15px;">';
        printHTML += '<div style="text-align:center;"><h5 style="margin:0;">KOLEHIYO NG SUBIC</h5><p style="margin:0;">WFI Compound, Wawandue, Subic, Zambales</p><p style="margin:0;">Tel.no.: (047)232 – 4896/232-4897</p></div></div>';
        
        printHTML += '<div style="text-align:center;">';
        printHTML += '<h2 style="margin: 5px 0; font-size: 18px;">OFFICE OF THE SCHOOL PROPERTY CUSTODIAN</h2>';
        printHTML += '<h3 style="text-decoration: underline; margin: 5px 0; font-size: 16px;">FACILITY USE REQUEST FORM</h3>';
        printHTML += '</div><br>';

        printHTML += '<div class="form-row"><span class="label">Name of Organization/Office:</span>' + office + '</div>';
        printHTML += '<div class="form-row"><span class="label">Address:</span>' + address + '</div>';
        printHTML += '<div class="form-row"><span class="label">Contact Person:</span>' + fullName + ' <span class="label" style="margin-left:auto;">Position/Designation:</span>' + pos + '</div>';
        printHTML += '<div class="form-row"><span class="label">Name of Event:</span>' + event + '</div>';
        printHTML += '<div class="form-row"><span class="label">Start Date:</span>' + start_date + ' <span class="label" style="margin-left:auto;">Ending Date:</span>' + end_date + '</div>';
        printHTML += '<div class="form-row"><span class="label">Time:</span>' + start_time + ' <span class="label" style="margin-left:auto;">To:</span>' + end_time + '</div>';
        printHTML += '<div class="form-row"><span class="label">Number of Participants:</span>' + participants + ' <span class="label" style="margin-left:auto;">Date Filed:</span>' + dateFiled + '</div>';
        printHTML += '<div class="form-row"><span class="label">Facilities to use:</span>' + facilitiesHtml + '</div>';

        printHTML += '<p><b>Facilities Equipment Needed:</b></p>';
        printHTML += '<table><thead><tr><th>Quantity</th><th>Name</th><th>Description</th></tr></thead><tbody>' + (equipmentRows || '<tr><td colspan="3">None</td></tr>') + '</tbody></table>';

        printHTML += '<div class="sig-section" style="justify-content: flex-end;"><div class="sig-box"><div class="sig-name">' + fullName + '</div><div class="sig-line">Signature over printed name of Borrower</div></div></div>';
        
        printHTML += '<div class="sig-section"><div class="sig-box" style="text-align: left;"><p><b>Checked and Verified by:</b></p><div class="sig-name" style="text-align: center;">' + window.globalOIC.property + '</div><div class="sig-line" style="text-align: center;">Property/Supplies Officer</div></div></div>';

        printHTML += '<div class="sig-section"><div class="sig-box" style="text-align: left;"><p><b>Approved by:</b></p><div class="sig-name" style="text-align: center;">Juan R. Deventurda III</div><div class="sig-line" style="text-align: center;">College Administrator</div></div>';
        printHTML += '<div class="sig-box" style="text-align: left;"><p><b>&nbsp;</b></p><div class="sig-name" style="text-align: center;">Rosely H. Agustin, DPA</div><div class="sig-line" style="text-align: center;">College President</div></div></div>';

        printHTML += '</body></html>';

        var w = window.open('', '', 'width=1000,height=800');
        w.document.write(printHTML);
        w.document.close();
        w.print();
      }
    });
  });

  // ================= PRINT ALL =================
  $('#btnPrintAllRSE').click(function(){
    var headers = [], rowsData = [];

    /* Collect headers — skip the last column (Action = index 9) */
    $('#tblFacility thead th').each(function(i, th){
        if(i !== 9) headers.push(th.innerText);
    });

    /* Collect rows — skip Action column (9), use plain text for Status column (8) */
    $('#tblFacility tbody tr').each(function(){
        var rowData = [];
        $(this).find('td').each(function(i, td){
            if(i !== 9){
                if(i === 8){
                    /* Status column: strip badge colors, use plain text only */
                    rowData.push(td.innerText.trim());
                } else {
                    rowData.push(td.innerHTML);
                }
            }
        });
        rowsData.push(rowData);
    });

    var printHTML = '<html><head><title>Request Facilities Reports</title>';
    printHTML += '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">';
    printHTML += '<style>body{padding:20px;font-size:12px;}table{width:100%;border-collapse:collapse;}th,td{padding:6px;border:1px solid #000;text-align:center;}th{background:#333;color:#fff;}</style></head><body>';

    printHTML += '<div style="display:flex;align-items:center;justify-content:center;margin-bottom:20px;">';
    printHTML += '<img src="../dist/img/logo.png" style="width:80px;margin-right:15px;">';
    printHTML += '<div style="text-align:center;"><h5 style="margin:0;">KOLEHIYO NG SUBIC</h5><p style="margin:0;">WFI Compound, Wawandue, Subic, Zambales</p><p style="margin:0;">Tel.no.: (047)232 – 4896/232-4897</p></div></div>';

    printHTML += '<h4>Request Facilities Reports</h4>';
    printHTML += '<table><thead><tr>';
    headers.forEach(h => printHTML += '<th>' + h + '</th>');
    printHTML += '</tr></thead><tbody>';
    rowsData.forEach(r => {
        printHTML += '<tr>';
        r.forEach(c => printHTML += '<td>' + c + '</td>');
        printHTML += '</tr>';
    });
    printHTML += '</tbody></table>';

    printHTML += '<div style="margin-top:80px;display:flex;justify-content:space-between;">';
    printHTML += '<div><div style="width:250px;border-bottom:1px solid #000;text-align:center;"><span style="font-weight:bold;text-transform:uppercase;">' + window.globalOIC.president + '</span></div><p style="margin:0;text-align:center;">College President</p></div>';
    printHTML += '<div><div style="width:250px;border-bottom:1px solid #000;text-align:center;"><span style="font-weight:bold;text-transform:uppercase;">' + window.globalOIC.property + '</span></div><p style="margin:0;text-align:center;">Property/Supplies Officer</p></div>';
    printHTML += '</div></body></html>';

    var w = window.open('', '', 'width=1000,height=800');
    w.document.write(printHTML);
    w.document.close();
    w.print();
  });

});
</script>