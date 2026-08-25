<?php
header('Content-Type: application/json');
include_once 'connectdb.php';
session_start();

// Only Admin access
if (!isset($_SESSION['useremail']) || ($_SESSION['role'] ?? '') !== "Admin") {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']);
    exit;
}

// Get RIS ID from POST
$ris_id = $_POST['ris_id'] ?? 0;
if (!$ris_id) {
    echo json_encode(['status'=>'error','message'=>'RIS ID missing']);
    exit;
}

try {
    // Check if RIS exists and not returned yet
    $stmt = $pdo->prepare("SELECT id, is_returned FROM ris_header WHERE id=?");
    $stmt->execute([$ris_id]);
    $ris = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ris) {
        echo json_encode(['status'=>'error','message'=>'RIS not found']);
        exit;
    }

    if ($ris['is_returned'] == 1) {
        echo json_encode(['status'=>'error','message'=>'RIS already returned']);
        exit;
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Get borrowed items
    $stmtItems = $pdo->prepare("
        SELECT property_id, quantity
        FROM ris_items
        WHERE ris_id = ?
    ");
    $stmtItems->execute([$ris_id]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // Update tbl_property stock
    $stmtUpdate = $pdo->prepare("UPDATE tbl_property SET quantity = quantity + ? WHERE property_id = ?");
    foreach($items as $item){
        $stmtUpdate->execute([$item['quantity'], $item['property_id']]);
    }

    // Mark RIS as returned
    $stmtReturn = $pdo->prepare("UPDATE ris_header SET is_returned = 1, return_date = NOW() WHERE id=?");
    $stmtReturn->execute([$ris_id]);

    $pdo->commit();
    echo json_encode(['status'=>'success','message'=>'Items successfully returned.']);

} catch(Exception $e){
    $pdo->rollBack();
    echo json_encode(['status'=>'error','message'=>'Error: '.$e->getMessage()]);
}