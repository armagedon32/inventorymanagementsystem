<?php
include_once 'connectdb.php';
session_start();
include_once "header.php";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ptr_record.php");
    exit();
}

$ptr_id = $_GET['id'];

/* =========================
   FETCH PTR HEADER
========================= */
$headerStmt = $pdo->prepare("SELECT * FROM ptr_header WHERE id = ?");
$headerStmt->execute([$ptr_id]);
$header = $headerStmt->fetch(PDO::FETCH_ASSOC);

if (!$header) {
    header("Location: ptr_record.php");
    exit();
}

/* =========================
   FETCH PTR ITEMS
========================= */
$itemsStmt = $pdo->prepare("SELECT * FROM ptr_items WHERE ptr_id = ?");
$itemsStmt->execute([$ptr_id]);
$ptr_items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   FETCH OFFICES
========================= */
$stmtOffice = $pdo->prepare("
    SELECT id, office_name
    FROM tbl_office
    WHERE parent_id IS NOT NULL AND parent_id != 0
    ORDER BY office_name ASC
");
$stmtOffice->execute();
$offices = $stmtOffice->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   UPDATE PTR
========================= */
if (isset($_POST['update_ptr'])) {
    $transfer_date = $_POST['transfer_date'];
    $remarks = $_POST['remarks'];

    try {
        $pdo->beginTransaction();

        // Update Header (Date and Remarks only for safety of stock integrity)
        $updateHeader = $pdo->prepare("UPDATE ptr_header SET transfer_date = ?, remarks = ? WHERE id = ?");
        $updateHeader->execute([$transfer_date, $remarks, $ptr_id]);

        // Log activity
        $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
        $stmtLog->execute([$_SESSION['userid'], "Updated PTR Details: " . $header['ptr_no']]);

        $pdo->commit();
        echo "<script>
            Swal.fire({
                icon:'success',
                title:'Updated',
                text:'PTR details updated successfully'
            }).then(()=>{window.location='ptr_record.php';});
        </script>";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>Swal.fire('Error', '".$e->getMessage()."', 'error');</script>";
    }
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">
                        <a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
                        Edit PTR Details
                    </h1>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <form method="post">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>PTR No</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($header['ptr_no']) ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Transfer Date</label>
                                    <input type="date" name="transfer_date" class="form-control" value="<?= $header['transfer_date'] ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>From Office</label>
                                    <input type="text" class="form-control" value="<?php 
                                        foreach($offices as $o) if($o['id'] == $header['from_office']) echo htmlspecialchars($o['office_name']); 
                                    ?>" readonly>
                                    <small class="text-muted">Origin office cannot be changed after transfer.</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>To Office</label>
                                    <input type="text" class="form-control" value="<?php 
                                        foreach($offices as $o) if($o['id'] == $header['to_office']) echo htmlspecialchars($o['office_name']); 
                                    ?>" readonly>
                                    <small class="text-muted">Destination office cannot be changed after transfer.</small>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Remarks</label>
                                    <textarea name="remarks" class="form-control" rows="2"><?= htmlspecialchars($header['remarks']) ?></textarea>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5>Transferred Items (Read-only)</h5>
                        <table class="table table-sm table-bordered table-striped">
                            <thead class="thead-light">
                                <tr>
                                    <th>Inventory No</th>
                                    <th>Item Name</th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ptr_items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['inventory_no']) ?></td>
                                    <td><?= htmlspecialchars($item['description']) ?></td>
                                    <td><?= htmlspecialchars($item['quantity']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p class="small text-danger mt-2">Note: To correct items or offices, please archive this record and create a new PTR.</p>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" name="update_ptr" class="btn btn-primary">Update Details</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php include_once "footer.php"; ?>
