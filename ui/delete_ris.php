<?php
header('Content-Type: application/json');
include_once 'connectdb.php';
session_start();

// Only Admin access
if (!isset($_SESSION['useremail']) || ($_SESSION['role'] ?? '') !== "Admin") {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']);
    exit;
}

// Get RIS ID from GET
$ris_id = $_GET['id'] ?? 0;
if (!$ris_id) {
    echo json_encode(['status'=>'error','message'=>'RIS ID missing']);
    exit;
}

try {
    // Check if RIS exists
    $stmt = $pdo->prepare("SELECT ris_no FROM ris_header WHERE id=?");
    $stmt->execute([$ris_id]);
    $ris = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ris) {
        echo json_encode(['status'=>'error','message'=>'RIS not found']);
        exit;
    }

    $risNo = $ris['ris_no'];

    // Begin transaction
    $pdo->beginTransaction();

    // Delete associated items
    $stmtItems = $pdo->prepare("DELETE FROM ris_items WHERE ris_id=?");
    $stmtItems->execute([$ris_id]);

    // Delete RIS header
    $stmtDelete = $pdo->prepare("DELETE FROM ris_header WHERE id=?");
    $stmtDelete->execute([$ris_id]);

    // Log activity
    $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
    $stmtLog->execute([$_SESSION['userid'], "Deleted RIS: " . $risNo]);

    $pdo->commit();
    echo json_encode(['status'=>'success','message'=>'RIS deleted successfully.']);

} catch(Exception $e){
    $pdo->rollBack();
    echo json_encode(['status'=>'error','message'=>'Error: '.$e->getMessage()]);
}