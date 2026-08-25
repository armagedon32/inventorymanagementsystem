<?php
include_once 'connectdb.php';
session_start();

// ================= AUTO-MIGRATION: Ensure 'is_archived' exists =================
$tables_to_check = [
    'tbl_user' => 'userid',
    'tbl_office' => 'id',
    'tbl_organization' => 'id',
    'tbl_instructors' => 'id',
    'incident_reports' => 'id',
    'incident_items' => 'id',
    'ris_header' => 'id',
    'ris_items' => 'id',
    'rse_header' => 'id',
    'rse_items' => 'id',
    'facility_header' => 'id',
    'facility_items' => 'id',
    'ptr_header' => 'id',
    'ptr_items' => 'id'
];

foreach ($tables_to_check as $tbl => $pk) {
    try {
        $pdo->query("SELECT is_archived FROM $tbl LIMIT 1");
    } catch (PDOException $e) {
        if ($e->getCode() == '42S22') { // Column not found
            $pdo->exec("ALTER TABLE $tbl ADD COLUMN is_archived TINYINT(1) DEFAULT 0");
        }
    }
}

// Ensure 'request_no' exists in ris_header (Special Case)
try {
    $pdo->query("SELECT request_no FROM ris_header LIMIT 1");
} catch (PDOException $e) {
    if ($e->getCode() == '42S22') {
        $pdo->exec("ALTER TABLE ris_header ADD COLUMN request_no VARCHAR(50) AFTER id");
        // Populate existing records with a default request number
        $pdo->exec("UPDATE ris_header SET request_no = CONCAT('RIS-OLD-', id) WHERE request_no IS NULL");
    }
}

// Ensure 'date_created' exists in ris_header (Special Case)
try {
    $pdo->query("SELECT date_created FROM ris_header LIMIT 1");
} catch (PDOException $e) {
    if ($e->getCode() == '42S22') {
        $pdo->exec("ALTER TABLE ris_header ADD COLUMN date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER is_archived");
    }
}

// ================= SESSION CHECK =================
if (!isset($_SESSION['useremail']) || ($_SESSION['role'] ?? "") == "User") {
    header('location:../index.php');
    exit();
}

// ================= HEADER =================
if ($_SESSION['role'] == "Admin") {
    include_once "header.php";
} else {
    include_once "headeruser.php";
}

$statusMessage = '';
$statusCode = '';

// ================= RESTORE ACTIONS =================
if (isset($_GET['restore_type']) && isset($_GET['id'])) {
    $type = $_GET['restore_type'];
    $id = (int)$_GET['id'];
    $table = '';
    $pk = 'id';
    $nameField = '';

    switch ($type) {
        case 'user': $table = 'tbl_user'; $pk = 'userid'; $nameField = 'fullname'; break;
        case 'office': $table = 'tbl_office'; $nameField = 'office_name'; break;
        case 'org': $table = 'tbl_organization'; $nameField = 'org_name'; break;
        case 'personnel': $table = 'tbl_instructors'; $nameField = 'fullname'; break;
        case 'incident': $table = 'incident_reports'; break;
        case 'ris': $table = 'ris_header'; break;
        case 'rse': $table = 'rse_header'; break;
        case 'facility': $table = 'facility_header'; break;
        case 'ptr': $table = 'ptr_header'; break;
    }

    if ($table) {
        $stmt = $pdo->prepare("UPDATE $table SET is_archived = 0 WHERE $pk = ?");
        if ($stmt->execute([$id])) {
            if (in_array($type, ['ris', 'rse', 'facility', 'ptr'])) {
                $itemTable = ($type == 'ris') ? 'ris_items' : (($type == 'rse') ? 'rse_items' : (($type == 'facility') ? 'facility_items' : 'ptr_items'));
                $fk = ($type == 'ris') ? 'ris_id' : (($type == 'rse') ? 'rse_id' : (($type == 'facility') ? 'facility_id' : 'ptr_id'));
                $pdo->prepare("UPDATE $itemTable SET is_archived = 0 WHERE $fk = ?")->execute([$id]);
            } elseif ($type == 'incident') {
                $pdo->prepare("UPDATE incident_items SET is_archived = 0 WHERE incident_id = ?")->execute([$id]);
            }
            $statusMessage = "Record restored successfully!";
            $statusCode = "success";
            logActivity($pdo, "Restored archived $type (ID: $id)");
        }
    }
}

// ================= PERMANENT DELETE ACTIONS =================
if (isset($_GET['perm_delete_type']) && isset($_GET['id'])) {
    $type = $_GET['perm_delete_type'];
    $id = (int)$_GET['id'];
    $table = '';
    $pk = 'id';

    switch ($type) {
        case 'user': $table = 'tbl_user'; $pk = 'userid'; break;
        case 'office': $table = 'tbl_office'; break;
        case 'org': $table = 'tbl_organization'; break;
        case 'personnel': $table = 'tbl_instructors'; break;
        case 'incident': $table = 'incident_reports'; break;
        case 'ris': $table = 'ris_header'; break;
        case 'rse': $table = 'rse_header'; break;
        case 'facility': $table = 'facility_header'; break;
        case 'ptr': $table = 'ptr_header'; break;
    }

    if ($table) {
        $pdo->beginTransaction();
        try {
            if (in_array($type, ['ris', 'rse', 'facility', 'ptr'])) {
                $itemTable = ($type == 'ris') ? 'ris_items' : (($type == 'rse') ? 'rse_items' : (($type == 'facility') ? 'facility_items' : 'ptr_items'));
                $fk = ($type == 'ris') ? 'ris_id' : (($type == 'rse') ? 'rse_id' : (($type == 'facility') ? 'facility_id' : 'ptr_id'));
                $pdo->prepare("DELETE FROM $itemTable WHERE $fk = ?")->execute([$id]);
            } elseif ($type == 'incident') {
                $pdo->prepare("DELETE FROM incident_items WHERE incident_id = ?")->execute([$id]);
            }
            
            $stmt = $pdo->prepare("DELETE FROM $table WHERE $pk = ?");
            $stmt->execute([$id]);
            $pdo->commit();
            $statusMessage = "Record permanently deleted!";
            $statusCode = "success";
            logActivity($pdo, "Permanently deleted archived $type (ID: $id)");
        } catch (Exception $e) {
            $pdo->rollBack();
            $statusMessage = "Error: " . $e->getMessage();
            $statusCode = "danger";
        }
    }
}

// Existing property/product actions...
if (isset($_GET['restore_property'])) {
    $id = (int)$_GET['restore_property'];
    $stmt = $pdo->prepare("UPDATE tbl_property SET is_archived = 0 WHERE property_id = ?");
    if ($stmt->execute([$id])) {
        $stmtName = $pdo->prepare("SELECT item_name FROM tbl_property WHERE property_id = ?");
        $stmtName->execute([$id]);
        $itemName = $stmtName->fetchColumn();
        logActivity($pdo, "Restored Property: " . $itemName);
        $statusMessage = "Property restored successfully!";
        $statusCode = "success";
    }
}
if (isset($_GET['perm_delete_property'])) {
    $id = (int)$_GET['perm_delete_property'];
    try {
        $pdo->beginTransaction();
        $stmtName = $pdo->prepare("SELECT item_name, serial_no FROM tbl_property WHERE property_id = ?");
        $stmtName->execute([$id]);
        $prop = $stmtName->fetch(PDO::FETCH_ASSOC);
        if ($prop) {
            $pdo->prepare("DELETE FROM tbl_disposal WHERE property_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM ptr_items WHERE property_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM ris_items WHERE property_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM rse_items WHERE property_id = ?")->execute([$id]);
            $stmt = $pdo->prepare("DELETE FROM tbl_property WHERE property_id = ?");
            if ($stmt->execute([$id])) {
                logActivity($pdo, "Permanently Deleted Property: " . $prop['item_name'] . " (" . $prop['serial_no'] . ")");
                $pdo->commit();
                $statusMessage = "Property permanently deleted.";
                $statusCode = "success";
            } else { $pdo->rollBack(); }
        } else { $pdo->rollBack(); }
    } catch (PDOException $e) { $pdo->rollBack(); $statusMessage = "Error: " . $e->getMessage(); $statusCode = "danger"; }
}

// Fetch all archived data
$archivedUsers = $pdo->query("SELECT * FROM tbl_user WHERE is_archived = 1")->fetchAll(PDO::FETCH_ASSOC);
$archivedOffices = $pdo->query("SELECT * FROM tbl_office WHERE is_archived = 1")->fetchAll(PDO::FETCH_ASSOC);
$archivedOrgs = $pdo->query("SELECT * FROM tbl_organization WHERE is_archived = 1")->fetchAll(PDO::FETCH_ASSOC);
$archivedPersonnel = $pdo->query("SELECT * FROM tbl_instructors WHERE is_archived = 1")->fetchAll(PDO::FETCH_ASSOC);
$archivedIncidents = $pdo->query("SELECT * FROM incident_reports WHERE is_archived = 1")->fetchAll(PDO::FETCH_ASSOC);
$archivedRequests = $pdo->query("
    (SELECT id, request_no, 'RIS' as type, date_created as date FROM ris_header WHERE is_archived = 1)
    UNION
    (SELECT id, request_no, 'RSE' as type, created_at as date FROM rse_header WHERE is_archived = 1)
    UNION
    (SELECT id, request_no, 'Facility' as type, created_at as date FROM facility_header WHERE is_archived = 1)
    UNION
    (SELECT id, ptr_no as request_no, 'PTR' as type, transfer_date as date FROM ptr_header WHERE is_archived = 1)
    ORDER BY date DESC
")->fetchAll(PDO::FETCH_ASSOC);
$archivedProperties = $pdo->query("SELECT p.*, o.office_name FROM tbl_property p LEFT JOIN tbl_office o ON p.office_id = o.id WHERE p.is_archived = 1 ORDER BY p.property_id DESC")->fetchAll(PDO::FETCH_ASSOC);

?>

<style>
    .card-header { cursor: pointer; }
    .table-sm td, .table-sm th { font-size: 0.9rem; }
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h4><a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a> Archive Management</h4>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if ($statusMessage): ?>
            <div class="alert alert-<?= $statusCode ?> alert-dismissible fade show">
                <?= htmlspecialchars($statusMessage) ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
            <?php endif; ?>

            <div class="row">
                <!-- Archived Users -->
                <div class="col-md-6">
                    <div class="card card-outline card-danger">
                        <div class="card-header" data-toggle="collapse" data-target="#collapseUsers">
                            <h5 class="card-title"><i class="fas fa-users-slash mr-2"></i> Archived Users</h5>
                        </div>
                        <div id="collapseUsers" class="collapse show">
                            <div class="card-body p-0 table-responsive" style="max-height: 300px;">
                                <table class="table table-sm table-striped m-0">
                                    <thead><tr><th>Name</th><th>Role</th><th class="text-right">Action</th></tr></thead>
                                    <tbody>
                                        <?php foreach($archivedUsers as $u): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($u['fullname']) ?></td>
                                            <td><?= htmlspecialchars($u['role']) ?></td>
                                            <td class="text-right">
                                                <a href="?restore_type=user&id=<?= $u['userid'] ?>" class="btn btn-xs btn-success"><i class="fas fa-undo"></i></a>
                                                <a href="?perm_delete_type=user&id=<?= $u['userid'] ?>" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                        <?php endforeach; if(empty($archivedUsers)) echo '<tr><td colspan="3" class="text-center">Empty</td></tr>'; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Archived Personnel -->
                <div class="col-md-6">
                    <div class="card card-outline card-warning">
                        <div class="card-header" data-toggle="collapse" data-target="#collapsePersonnel">
                            <h5 class="card-title"><i class="fas fa-user-tie mr-2"></i> Archived Personnel</h5>
                        </div>
                        <div id="collapsePersonnel" class="collapse show">
                            <div class="card-body p-0 table-responsive" style="max-height: 300px;">
                                <table class="table table-sm table-striped m-0">
                                    <thead><tr><th>Name</th><th>Dept</th><th class="text-right">Action</th></tr></thead>
                                    <tbody>
                                        <?php foreach($archivedPersonnel as $p): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($p['fullname']) ?></td>
                                            <td><?= htmlspecialchars($p['assigned_dept'] ?? '-') ?></td>
                                            <td class="text-right">
                                                <a href="?restore_type=personnel&id=<?= $p['id'] ?>" class="btn btn-xs btn-success"><i class="fas fa-undo"></i></a>
                                                <a href="?perm_delete_type=personnel&id=<?= $p['id'] ?>" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                        <?php endforeach; if(empty($archivedPersonnel)) echo '<tr><td colspan="3" class="text-center">Empty</td></tr>'; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Archived Offices & Orgs -->
                <div class="col-md-6">
                    <div class="card card-outline card-info">
                        <div class="card-header" data-toggle="collapse" data-target="#collapseOffices">
                            <h5 class="card-title"><i class="fas fa-building mr-2"></i> Archived Offices & Orgs</h5>
                        </div>
                        <div id="collapseOffices" class="collapse">
                            <div class="card-body p-0 table-responsive" style="max-height: 300px;">
                                <table class="table table-sm table-striped m-0">
                                    <thead><tr><th>Name</th><th>Type</th><th class="text-right">Action</th></tr></thead>
                                    <tbody>
                                        <?php foreach($archivedOffices as $o): ?>
                                        <tr><td><?= htmlspecialchars($o['office_name']) ?></td><td>Office</td><td class="text-right">
                                            <a href="?restore_type=office&id=<?= $o['id'] ?>" class="btn btn-xs btn-success"><i class="fas fa-undo"></i></a>
                                            <a href="?perm_delete_type=office&id=<?= $o['id'] ?>" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></a>
                                        </td></tr>
                                        <?php endforeach; foreach($archivedOrgs as $org): ?>
                                        <tr><td><?= htmlspecialchars($org['org_name']) ?></td><td>Org</td><td class="text-right">
                                            <a href="?restore_type=org&id=<?= $org['id'] ?>" class="btn btn-xs btn-success"><i class="fas fa-undo"></i></a>
                                            <a href="?perm_delete_type=org&id=<?= $org['id'] ?>" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></a>
                                        </td></tr>
                                        <?php endforeach; if(empty($archivedOffices) && empty($archivedOrgs)) echo '<tr><td colspan="3" class="text-center">Empty</td></tr>'; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Archived Incident Records -->
                <div class="col-md-6">
                    <div class="card card-outline card-secondary">
                        <div class="card-header" data-toggle="collapse" data-target="#collapseIncidents">
                            <h5 class="card-title"><i class="fas fa-file-invoice mr-2"></i> Archived Incident Records</h5>
                        </div>
                        <div id="collapseIncidents" class="collapse">
                            <div class="card-body p-0 table-responsive" style="max-height: 300px;">
                                <table class="table table-sm table-striped m-0">
                                    <thead><tr><th>Report #</th><th>Date</th><th class="text-right">Action</th></tr></thead>
                                    <tbody>
                                        <?php foreach($archivedIncidents as $i): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($i['report_number']) ?></td>
                                            <td><?= htmlspecialchars($i['incident_date']) ?></td>
                                            <td class="text-right">
                                                <a href="?perm_delete_type=incident&id=<?= $i['id'] ?>" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                        <?php endforeach; if(empty($archivedIncidents)) echo '<tr><td colspan="3" class="text-center">Empty</td></tr>'; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Archived Requests (RIS/RSE/Facility) -->
                <div class="col-md-12">
                    <div class="card card-outline card-primary">
                        <div class="card-header" data-toggle="collapse" data-target="#collapseRequests">
                            <h5 class="card-title"><i class="fas fa-clipboard-list mr-2"></i> Archived Requests (RIS/RSE/Facility)</h5>
                        </div>
                        <div id="collapseRequests" class="collapse">
                            <div class="card-body p-0 table-responsive">
                                <table class="table table-sm table-striped m-0">
                                    <thead><tr><th>Request No</th><th>Type</th><th>Date</th><th class="text-right">Action</th></tr></thead>
                                    <tbody>
                                        <?php foreach($archivedRequests as $r): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($r['request_no']) ?></td>
                                            <td><span class="badge badge-info"><?= $r['type'] ?></span></td>
                                            <td><?= htmlspecialchars($r['date']) ?></td>
                                            <td class="text-right">
                                                <a href="?restore_type=<?= strtolower($r['type']) ?>&id=<?= $r['id'] ?>" class="btn btn-xs btn-success"><i class="fas fa-undo"></i></a>
                                                <a href="?perm_delete_type=<?= strtolower($r['type']) ?>&id=<?= $r['id'] ?>" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                        <?php endforeach; if(empty($archivedRequests)) echo '<tr><td colspan="4" class="text-center py-3">No archived requests found.</td></tr>'; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Archived Properties -->
                <div class="col-md-12">
                    <div class="card card-outline card-warning">
                        <div class="card-header" data-toggle="collapse" data-target="#collapseProps">
                            <h5 class="card-title"><i class="fas fa-boxes mr-2"></i> Archived Properties</h5>
                        </div>
                        <div id="collapseProps" class="collapse">
                            <div class="card-body p-0 table-responsive">
                                <table class="table table-sm table-striped m-0">
                                    <thead><tr><th>Inventory No</th><th>Item</th><th>Office</th><th class="text-right">Action</th></tr></thead>
                                    <tbody>
                                        <?php foreach($archivedProperties as $p): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($p['inventory_no']) ?></td>
                                            <td><?= htmlspecialchars($p['item_name']) ?></td>
                                            <td><?= htmlspecialchars($p['office_name'] ?? 'Unassigned') ?></td>
                                            <td class="text-right">
                                                <a href="?restore_property=<?= $p['property_id'] ?>" class="btn btn-xs btn-success"><i class="fas fa-undo"></i></a>
                                                <a href="?perm_delete_property=<?= $p['property_id'] ?>" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                        <?php endforeach; if(empty($archivedProperties)) echo '<tr><td colspan="4" class="text-center py-3">No archived properties found.</td></tr>'; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include_once "footer.php"; ?>
