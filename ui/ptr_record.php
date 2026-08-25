<?php
include_once 'connectdb.php';
session_start();

include_once "header.php";

// ================= AUTO-MIGRATION: Ensure 'is_archived' exists =================
try {
    $pdo->query("SELECT is_archived FROM ptr_header LIMIT 1");
} catch (PDOException $e) {
    if ($e->getCode() == '42S22') { // Column not found
        $pdo->exec("ALTER TABLE ptr_header ADD COLUMN is_archived TINYINT(1) DEFAULT 0");
    }
}
try {
    $pdo->query("SELECT is_archived FROM ptr_items LIMIT 1");
} catch (PDOException $e) {
    if ($e->getCode() == '42S22') { // Column not found
        $pdo->exec("ALTER TABLE ptr_items ADD COLUMN is_archived TINYINT(1) DEFAULT 0");
    }
}

/* ================= HANDLE ARCHIVE ================= */
if (isset($_GET['archive_id'])) {
    $ptr_id = (int)$_GET['archive_id'];
    try {
        $pdo->beginTransaction();
        
        // Archive header
        $stmtH = $pdo->prepare("UPDATE ptr_header SET is_archived = 1 WHERE id = ?");
        $stmtH->execute([$ptr_id]);
        
        // Archive items
        $stmtI = $pdo->prepare("UPDATE ptr_items SET is_archived = 1 WHERE ptr_id = ?");
        $stmtI->execute([$ptr_id]);
        
        // Log activity
        $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
        $stmtLog->execute([$_SESSION['userid'], "Archived PTR Record (ID: $ptr_id)"]);
        
        $pdo->commit();
        
        echo "<script>
            setTimeout(function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Archived',
                    text: 'PTR Record archived successfully',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.href = 'ptr_record.php';
                });
            }, 100);
        </script>";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>Swal.fire('Error', '".$e->getMessage()."', 'error');</script>";
    }
}

/* FETCH PTR RECORDS */

$stmt = $pdo->prepare("
SELECT 
ptr_header.id,
ptr_header.ptr_no,
ptr_header.transfer_date,
o1.office_name AS from_office,
o2.office_name AS to_office,
ptr_header.remarks
FROM ptr_header
LEFT JOIN tbl_office o1 ON ptr_header.from_office = o1.id
LEFT JOIN tbl_office o2 ON ptr_header.to_office = o2.id
WHERE ptr_header.is_archived = 0
ORDER BY ptr_header.id DESC
");

$stmt->execute();

if(isset($_SESSION['status'], $_SESSION['status_code'])){
    $flashIcon  = $_SESSION['status_code'];
    $flashTitle = $_SESSION['status'];
    unset($_SESSION['status'], $_SESSION['status_code']);
}
?>

<div class="content-wrapper">
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3 class="m-0 text-dark">
                    <a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
                    Property Transfer Records
                </h3>
            </div>
            <div class="col-sm-6 text-right">
                <a href="print_ptr_all.php" target="_blank" class="btn btn-primary shadow-sm">
                    <i class="fas fa-print mr-1"></i> Print All
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">

<div class="card">
<div class="card-body">

<table class="table table-bordered table-striped">

<thead class="thead-dark">

<tr>
<th width="15%">PTR No</th>
<th width="12%">Date</th>
<th width="18%">From Office</th>
<th width="18%">To Office</th>
<th width="22%">Remarks</th>
<th width="15%">Action</th>
</tr>

</thead>

<tbody>

<?php
while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
?>

<tr>

<td><?php echo $row['ptr_no']; ?></td>

<td><?php echo $row['transfer_date']; ?></td>

<td><?php echo $row['from_office']; ?></td>

<td><?php echo $row['to_office']; ?></td>

<td><?php echo $row['remarks']; ?></td>

<td class="text-center">
<div class="d-flex justify-content-center" style="gap: 5px;">
<a href="ptr_view.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">
<i class="fas fa-eye"></i>
</a>
<a href="ptr_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
<i class="fas fa-edit"></i>
</a>
<a href="print_ptr_single.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-success btn-sm">
<i class="fas fa-print"></i>
</a>
<a href="#" class="btn btn-danger btn-sm" onclick="window.location.href='ptr_record.php?archive_id=<?php echo $row['id']; ?>'">
<i class="fas fa-archive"></i>
</a>
</div>
</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>
</div>

</div>
</div>

<?php include_once "footer.php"; ?>

<script>
<?php if(isset($flashIcon, $flashTitle)): ?>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: '<?= $flashIcon ?>',
        title: '<?= addslashes($flashTitle) ?>',
        showConfirmButton: false,
        timer: 2000
    });
});
<?php endif; ?>
</script>