<?php
include_once 'connectdb.php';
session_start();

// ================= SESSION CHECK =================
if (!isset($_SESSION['useremail']) || $_SESSION['role'] == "User") {
    header('location:../index.php');
    exit();
}

// ================= HEADER =================
if ($_SESSION['role'] == "Admin") {
    include_once "header.php";
} else {
    include_once "headeruser.php";
}

// ================= FETCH USERS FOR FILTER =================
$stmtUsers = $pdo->query("SELECT userid, username FROM tbl_user ORDER BY username ASC");
$allUsers = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

// ================= HANDLE FILTERS =================
$filterUser = $_GET['filter_user'] ?? '';
$filterAction = $_GET['filter_action'] ?? '';
$filterDate = $_GET['filter_date'] ?? '';

$sql = "
    SELECT a.id, a.action, a.date_created, u.username
    FROM activity_log a
    LEFT JOIN tbl_user u ON a.user_id = u.userid
    WHERE 1=1
";
$params = [];

if (!empty($filterUser)) {
    $sql .= " AND a.user_id = :userid";
    $params[':userid'] = $filterUser;
}

if (!empty($filterAction)) {
    $sql .= " AND a.action LIKE :action";
    $params[':action'] = "%$filterAction%";
}

if (!empty($filterDate)) {
    $sql .= " AND DATE(a.date_created) = :date";
    $params[':date'] = $filterDate;
}

$sql .= " ORDER BY a.date_created DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-wrapper">

    <!-- FIXED TOP SECTION (Title + Filters + Table Header) -->
    <div class="sticky-top shadow-sm" style="top: 0; z-index: 1020; background: #f4f6f9;">
        <div class="content-header pb-0">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 text-dark">
                            <a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
                            Activity Log
                        </h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="content pb-2">
            <div class="container-fluid">
                <!-- FILTER SECTION -->
                <div class="card card-secondary card-outline mb-2">
                    <div class="card-body py-3">
                        <form method="GET" id="filterForm">
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="mb-2">Filter by User</label>
                                    <select name="filter_user" class="form-control select2" onchange="this.form.submit()">
                                        <option value="">-- All Users --</option>
                                        <?php foreach ($allUsers as $u): ?>
                                            <option value="<?= $u['userid'] ?>" <?= $filterUser == $u['userid'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($u['username']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="mb-2">Search Action</label>
                                    <div class="input-group">
                                        <input type="text" name="filter_action" class="form-control" placeholder="e.g. Added, Deleted, Login..." value="<?= htmlspecialchars($filterAction) ?>">
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="mb-2">Filter by Date</label>
                                    <input type="date" name="filter_date" class="form-control" value="<?= htmlspecialchars($filterDate) ?>" onchange="this.form.submit()">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <a href="activitylog.php" class="btn btn-default btn-block">Clear Filters</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- TABLE HEADER PART OF STICKY -->
                <div class="card card-primary card-outline mb-0" style="border-bottom: none; border-bottom-left-radius: 0; border-bottom-right-radius: 0;">
                    <div class="card-header py-2">
                        <h5 class="card-title m-0">System Activity Logs</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th style="width: 80px;">#</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SCROLLABLE TABLE BODY -->
    <div class="content pt-0">
        <div class="container-fluid">
            <div class="card card-primary card-outline" style="border-top: none; border-top-left-radius: 0; border-top-right-radius: 0;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <tbody id="logTableBody">
                                <?php if (!empty($logs)): ?>
                                    <?php foreach ($logs as $row): ?>
                                        <tr>
                                            <td style="width: 80px;"><?= htmlspecialchars($row['id']) ?></td>
                                            <td><?= htmlspecialchars($row['username'] ?? 'System') ?></td>
                                            <td><?= htmlspecialchars($row['action']) ?></td>
                                            <td><?= date("M d, Y h:i A", strtotime($row['date_created'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No activity logs found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include_once "footer.php"; ?>