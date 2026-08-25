<?php
include_once 'connectdb.php';
session_start();

if (!isset($_SESSION['useremail']) || ($_SESSION['role'] ?? '') == 'User') {
    header('location:../index.php');
    exit;
}

/* HANDLE STATUS UPDATE */
if (isset($_POST['update_status_id'])) {
    $id = (int)$_POST['update_status_id'];
    $today = date('Y-m-d');

    $stmtDetails = $pdo->prepare("SELECT maintenance_code FROM maintenance_reports WHERE id = ?");
    $stmtDetails->execute([$id]);
    $reportCode = $stmtDetails->fetchColumn();

    $stmt = $pdo->prepare("
        UPDATE maintenance_reports
        SET previous_maintenance_date = :today,
            next_maintenance_date = DATE_ADD(:today, INTERVAL frequency_days DAY)
        WHERE id = :id
    ");
    $stmt->execute([':today' => $today, ':id' => $id]);

    $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
    $stmtLog->execute([$_SESSION['userid'], "Updated Maintenance Status (Marked OK): " . $reportCode]);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* HANDLE DELETE / ARCHIVE */
if (isset($_POST['delete_report_id'])) {
    $id = (int)$_POST['delete_report_id'];

    try {
        $stmtDetails = $pdo->prepare("SELECT maintenance_code FROM maintenance_reports WHERE id = ?");
        $stmtDetails->execute([$id]);
        $reportCode = $stmtDetails->fetchColumn();

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS archive_maintenance (
                id INT NOT NULL,
                item_name VARCHAR(150),
                office VARCHAR(150),
                brand VARCHAR(100),
                serial_number VARCHAR(100),
                maintenance_code VARCHAR(50),
                maintenance_task TEXT,
                frequency_days INT,
                previous_maintenance_date DATE,
                next_maintenance_date DATE,
                days_before_due INT,
                archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $stmtArchive = $pdo->prepare("
            INSERT INTO archive_maintenance 
            (id, item_name, office, brand, serial_number, maintenance_code, maintenance_task, 
             frequency_days, previous_maintenance_date, next_maintenance_date, days_before_due)
            SELECT id, item_name, office, brand, serial_number, maintenance_code, maintenance_task, 
                   frequency_days, previous_maintenance_date, next_maintenance_date, days_before_due
            FROM maintenance_reports
            WHERE id = ?
        ");
        $stmtArchive->execute([$id]);

        $stmtDelete = $pdo->prepare("DELETE FROM maintenance_reports WHERE id = ?");
        $stmtDelete->execute([$id]);

        $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
        $stmtLog->execute([$_SESSION['userid'], "Deleted and Archived Maintenance Report: " . $reportCode]);

        $_SESSION['status'] = "Report deleted and archived successfully!";
        $_SESSION['status_code'] = "success";
    } catch (PDOException $e) {
        $_SESSION['status'] = "Error: " . $e->getMessage();
        $_SESSION['status_code'] = "danger";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

include_once "header.php";

/* FETCH MAINTENANCE REPORTS — COALESCE handles both old records (office=ID) and new records (office=name) */
$stmt = $pdo->prepare("
    SELECT mr.*,
           COALESCE(o.office_name, mr.office) AS office_name,
           DATEDIFF(mr.next_maintenance_date, CURDATE()) AS days_before_due
    FROM maintenance_reports mr
    LEFT JOIN tbl_office o ON mr.office = o.id
    ORDER BY mr.next_maintenance_date ASC
");
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

<style>
.content-wrapper { padding:20px; }
.table-responsive { max-height:600px; overflow-y:auto; }
.status-due { background:#fff3cd; }
.status-overdue { background:#f8d7da; }
.status-ok { background:#d1e7dd; }
button.update-btn { margin-left:5px; font-size:0.8rem; padding:2px 6px; }

@media print {
    body * { visibility: hidden; }
    .table-responsive, .table-responsive * { visibility: visible; }
    .table-responsive { position: absolute; left:0; top:0; width:100%; }
    .update-btn { display: none !important; }
    .actions-column, .status-column { display: none !important; }
}
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">
                        <a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
                        Maintenance Reports
                    </h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

        <?php if(isset($_SESSION['status'])): ?>
            <div class="alert alert-<?=$_SESSION['status_code']?> alert-dismissible fade show mt-3">
                <i class="icon fas <?=$_SESSION['status_code']=='success'?'fa-check':'fa-ban'?>"></i>
                <?= $_SESSION['status'] ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php unset($_SESSION['status'], $_SESSION['status_code']); endif; ?>

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Active Maintenance Schedules</h3>
                <div class="card-tools">
                    <button class="btn btn-primary btn-sm" id="btnPrintAll">
                        <i class="fa fa-print"></i> Print All
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped text-center" id="table_maintenance">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Item</th>
                                <th>Office</th>
                                <th>Brand</th>
                                <th>Serial No.</th>
                                <th>Maintenance</th>
                                <th>Frequency (Days)</th>
                                <th>Last Maintenance</th>
                                <th>Next Maintenance</th>
                                <th>Days Before Due</th>
                                <th class="status-column">Status</th>
                                <th class="actions-column">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if(!empty($reports)): ?>
                            <?php foreach($reports as $r): ?>
                                <?php
                                if($r['days_before_due'] < 0){
                                    $status = "Overdue"; $rowClass="status-overdue";
                                } elseif($r['days_before_due'] <= 7){
                                    $status = "Due Soon"; $rowClass="status-due";
                                } else {
                                    $status = "OK"; $rowClass="status-ok";
                                }
                                ?>
                                <tr class="<?= $rowClass ?>">
                                    <td><?= $r['id']; ?></td>
                                    <td><?= htmlentities($r['item_name']); ?></td>
                                    <td><?= htmlentities($r['office_name'] ?? ''); ?></td>
                                    <td><?= htmlentities($r['brand']); ?></td>
                                    <td><?= htmlentities($r['serial_number']); ?></td>
                                    <td>
                                        <strong><?= htmlentities($r['maintenance_code']); ?></strong><br>
                                        <small><?= htmlentities($r['maintenance_task']); ?></small>
                                    </td>
                                    <td><?= $r['frequency_days']; ?></td>
                                    <td><?= date('M d, Y', strtotime($r['previous_maintenance_date'])); ?></td>
                                    <td><?= date('M d, Y', strtotime($r['next_maintenance_date'])); ?></td>
                                    <td><?= $r['days_before_due']; ?></td>
                                    <td class="status-column">
                                        <span class="badge <?= $status==='Overdue'?'badge-danger':($status==='Due Soon'?'badge-warning':'badge-success'); ?>">
                                            <?= $status ?>
                                        </span>
                                    </td>
                                    <td class="actions-column">
                                        <div class="btn-group">
                                            <?php if($status === 'Overdue'): ?>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="update_status_id" value="<?= $r['id'] ?>">
                                                    <button type="submit" class="btn btn-success btn-xs update-btn" title="Mark OK">
                                                        <i class="fa fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-primary btn-xs btnprint"
                                                    title="Print"
                                                    data-office="<?= htmlentities($r['office_name'] ?? ''); ?>"
                                                    data-item="<?= htmlentities($r['item_name']); ?>"
                                                    data-brand="<?= htmlentities($r['brand']); ?>"
                                                    data-serial="<?= htmlentities($r['serial_number']); ?>"
                                                    data-code="<?= htmlentities($r['maintenance_code']); ?>"
                                                    data-task="<?= htmlentities($r['maintenance_task']); ?>"
                                                    data-freq="<?= $r['frequency_days']; ?>"
                                                    data-prev="<?= date('M d, Y', strtotime($r['previous_maintenance_date'])); ?>">
                                                <i class="fa fa-print"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="12">No maintenance records found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include_once "footer.php"; ?>

<script>
$(document).ready(function(){

    // ================= PRINT SINGLE ROW =================
    $('#table_maintenance').on('click', '.btnprint', function(){
        var btn = $(this);
        var office = btn.data('office');
        var item   = btn.data('item');
        var brand  = btn.data('brand');
        var serial = btn.data('serial');
        var code   = btn.data('code');
        var task   = btn.data('task');
        var freq   = btn.data('freq');
        var prev   = btn.data('prev');

        var printHTML = '<html><head><title>Maintenance Report</title>';
        printHTML += '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">';
        printHTML += '<style>body{padding:20px;font-size:14px;}.form-label{font-weight:bold;margin-bottom:0;}.form-value{border-bottom:1px solid #eee;padding-bottom:2px;margin-bottom:15px;}table{width:100%;border-collapse:collapse;}th,td{padding:8px;border:1px solid #000;}</style>';
        printHTML += '</head><body>';

        printHTML += '<div style="display:flex;align-items:center;justify-content:center;margin-bottom:20px;">';
        printHTML += '<img src="../dist/img/logo.png" style="width:80px;margin-right:15px;">';
        printHTML += '<div style="text-align:center;"><h5 style="margin:0;">KOLEHIYO NG SUBIC</h5><p style="margin:0;">WFI Compound, Wawandue, Subic, Zambales</p><p style="margin:0;">Tel.no.: (047)232 – 4896/232-4897</p></div></div>';

        printHTML += '<h4 class="text-center mb-4">Maintenance Report Form</h4>';
        printHTML += '<div class="container-fluid">';
        printHTML += '<h5>Item Information</h5>';
        printHTML += '<div class="row"><div class="col-6"><p class="form-label">Office</p><p class="form-value">' + office + '</p></div>';
        printHTML += '<div class="col-6"><p class="form-label">Item Name</p><p class="form-value">' + item + '</p></div></div>';
        printHTML += '<div class="row"><div class="col-6"><p class="form-label">Brand</p><p class="form-value">' + brand + '</p></div>';
        printHTML += '<div class="col-6"><p class="form-label">Serial Number</p><p class="form-value">' + serial + '</p></div></div>';

        printHTML += '<h5 class="mt-4">Maintenance Details</h5>';
        printHTML += '<div class="row"><div class="col-6"><p class="form-label">Maintenance Code</p><p class="form-value">' + code + '</p></div>';
        printHTML += '<div class="col-6"><p class="form-label">Maintenance to be Performed</p><p class="form-value">' + task + '</p></div></div>';
        printHTML += '<div class="row"><div class="col-6"><p class="form-label">Maintenance Frequency (Days)</p><p class="form-value">' + freq + '</p></div>';
        printHTML += '<div class="col-6"><p class="form-label">Previous Maintenance Date</p><p class="form-value">' + prev + '</p></div></div>';
        printHTML += '</div>';

        printHTML += '<div style="margin-top:50px;display:flex;justify-content:space-between;">';
        printHTML += '<div><p style="margin:0;font-weight:bold;">Prepared by:</p><div style="width:250px;text-align:center;"><div style="margin-top:30px;border-bottom:1px solid #000;"><span style="font-weight:bold;text-transform:uppercase;">' + window.globalOIC.property + '</span></div><p style="margin:0;">Property/Supplies Officer</p></div></div>';
        printHTML += '<div><p style="margin:0;font-weight:bold;">Checked by:</p><div style="width:250px;text-align:center;"><div style="margin-top:30px;border-bottom:1px solid #000;">&nbsp;</div><p style="margin:0;">Maintenance Personnel</p></div></div>';
        printHTML += '</div>';

        printHTML += '<div style="margin-top:40px;"><p style="margin:0;font-weight:bold;">Approved by:</p><div style="width:250px;text-align:center;"><div style="margin-top:30px;border-bottom:1px solid #000;"><span style="font-weight:bold;text-transform:uppercase;">' + window.globalOIC.president + '</span></div><p style="margin:0;">College President</p></div></div>';

        printHTML += '</body></html>';

        var printWindow = window.open('','','height=700,width=900');
        printWindow.document.write(printHTML);
        printWindow.document.close();
        printWindow.onload = function(){ printWindow.print(); };
    });

    // ================= PRINT ALL =================
    $('#btnPrintAll').click(function(){
        var headers=[], rowsData=[];

        $('#table_maintenance thead th').each(function(i){
            if(i!==10 && i!==11) headers.push($(this).text());
        });
        $('#table_maintenance tbody tr').each(function(){
            var rowData=[];
            $(this).find('td').each(function(i){
                if(i!==10 && i!==11) rowData.push($(this).html());
            });
            rowsData.push(rowData);
        });

        var printHTML = '<html><head><title>Maintenance Report</title>';
        printHTML += '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">';
        printHTML += '<style>body{padding:20px;font-size:12px;}table{width:100%;border-collapse:collapse;}th,td{padding:6px;border:1px solid #000;}</style>';
        printHTML += '</head><body>';

        printHTML += '<div style="display:flex;align-items:center;justify-content:center;margin-bottom:20px;">';
        printHTML += '<img src="../dist/img/logo.png" style="width:80px;margin-right:15px;">';
        printHTML += '<div style="text-align:center;"><h5 style="margin:0;">KOLEHIYO NG SUBIC</h5><p style="margin:0;">WFI Compound, Wawandue, Subic, Zambales</p><p style="margin:0;">Tel.no.: (047)232 – 4896/232-4897</p></div></div>';

        printHTML += '<h4>Maintenance Report</h4><table class="table table-bordered"><thead><tr>';
        headers.forEach(h => printHTML += '<th>' + h + '</th>');
        printHTML += '</tr></thead><tbody>';
        rowsData.forEach(row => {
            printHTML += '<tr>'; row.forEach(col => printHTML += '<td>' + col + '</td>'); printHTML += '</tr>';
        });
        printHTML += '</tbody></table>';

        printHTML += '<div style="margin-top:80px;display:flex;justify-content:space-between;">';
        printHTML += '<div><div style="width:250px;border-bottom:1px solid #000;text-align:center;"><span style="font-weight:bold;text-transform:uppercase;">' + window.globalOIC.president + '</span></div><p style="margin:0;text-align:center;">College President</p></div>';
        printHTML += '<div><div style="width:250px;border-bottom:1px solid #000;text-align:center;"><span style="font-weight:bold;text-transform:uppercase;">' + window.globalOIC.property + '</span></div><p style="margin:0;text-align:center;">Property/Supplies Officer</p></div>';
        printHTML += '</div>';

        printHTML += '</body></html>';

        var printWindow = window.open('','','width=1000,height=800');
        printWindow.document.write(printHTML);
        printWindow.document.close();
        printWindow.onload = function(){ printWindow.print(); };
    });
});
</script>