<?php
include_once 'connectdb.php';
session_start();

if (!isset($_SESSION['useremail']) || ($_SESSION['role'] ?? '') !== "Admin") {
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT quantity, item_name, description FROM facility_equipment WHERE facility_request_id = ?");
    $stmt->execute([$id]);
    $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($equipment);
}
?>
