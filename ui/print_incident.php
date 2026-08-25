<?php
include_once 'connectdb.php';
session_start();

// Only Admin access
if (!isset($_SESSION['useremail']) || ($_SESSION['role'] ?? '') == 'User') {
    header('location:../index.php');
    exit;
}

// Validate incident ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid incident ID.");
}

$incident_id = (int)$_GET['id'];

// Fetch incident report with office name
$stmtReport = $pdo->prepare("
    SELECT ir.*, o.office_name
    FROM incident_reports ir
    LEFT JOIN tbl_office o ON ir.office = o.id
    WHERE ir.id = ?
");
$stmtReport->execute([$incident_id]);
$report = $stmtReport->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    die("Incident report not found.");
}

// Fetch items involved
$stmtItems = $pdo->prepare("
    SELECT 
        COALESCE(p.item_name,'Unknown Item') AS item_name,
        i.serial_number,
        i.location,
        i.last_borrower
    FROM incident_items i
    LEFT JOIN tbl_property p ON i.property_id = p.property_id
    WHERE i.incident_id = ?
");
$stmtItems->execute([$incident_id]);
$itemsList = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Print Incident Report <?= htmlentities($report['report_number']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { text-align: center; margin-bottom: 20px; }
        p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table, th, td { border: 1px solid #000; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>

<h2>Incident Report: <?= htmlentities($report['report_number']); ?></h2>

<p><strong>Reported By:</strong> <?= htmlentities($report['reported_by']); ?></p>
<p><strong>Office:</strong> <?= htmlentities($report['office_name'] ?? 'Unknown'); ?></p>
<p><strong>Date / Time:</strong> <?= date('M d Y', strtotime($report['incident_date'])) . ' ' . date('h:i A', strtotime($report['incident_time'])); ?></p>

<p><strong>Description:</strong><br><?= nl2br(htmlentities($report['description'])); ?></p>
<p><strong>Extent of Damage:</strong><br><?= nl2br(htmlentities($report['extent_of_damage'])); ?></p>

<h3>Items Involved</h3>
<?php if (!empty($itemsList)): ?>
<table>
    <tr>
        <th>Item Name</th>
        <th>Serial Number</th>
        <th>Location</th>
        <th>Last Borrower</th>
    </tr>
    <?php foreach ($itemsList as $item): ?>
    <tr>
        <td><?= htmlentities($item['item_name']); ?></td>
        <td><?= htmlentities($item['serial_number']); ?></td>
        <td><?= htmlentities($item['location']); ?></td>
        <td><?= htmlentities($item['last_borrower']); ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php else: ?>
<p>No items recorded for this incident.</p>
<?php endif; ?>

<script>
window.onload = function() {
    window.print(); // automatically open print dialog
};
</script>

</body>
</html>