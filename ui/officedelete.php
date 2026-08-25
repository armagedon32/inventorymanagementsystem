<?php
include_once 'connectdb.php';
session_start();

// Only Admin access
if (!isset($_SESSION['useremail']) || $_SESSION['role'] != 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_POST['office_id']) || empty($_POST['office_id'])) {
    echo json_encode(['success' => false, 'message' => 'Office ID is required']);
    exit;
}

$id = intval($_POST['office_id']);
$userId = $_SESSION['userid'] ?? 0;

/**
 * Checks if an office or any of its descendants have linked records that prevent deletion.
 * Returns an array with 'total' count and 'details' string.
 */
function checkLinkedRecordsRecursive($pdo, $officeId) {
    $total = 0;
    $details = [];

    // 1. Check current office for linked records
    
    // Properties (tbl_property)
    $propStmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_property WHERE office_id = :id");
    $propStmt->execute(['id' => $officeId]);
    $count = $propStmt->fetchColumn();
    if($count > 0) {
        $total += $count;
        $details[] = "$count Properties";
    }

    // Products/Supplies (tbl_product)
    $prodStmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_product WHERE office_id = :id");
    $prodStmt->execute(['id' => $officeId]);
    $count = $prodStmt->fetchColumn();
    if($count > 0) {
        $total += $count;
        $details[] = "$count Supplies";
    }

    // PTR Records (ptr_header)
    $ptrStmt = $pdo->prepare("SELECT COUNT(*) FROM ptr_header WHERE from_office = :id OR to_office = :id");
    $ptrStmt->execute(['id' => $officeId]);
    $count = $ptrStmt->fetchColumn();
    if($count > 0) {
        $total += $count;
        $details[] = "$count PTR Records";
    }

    // Disposals (tbl_disposal)
    $dispStmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_disposal WHERE office_id = :id");
    $dispStmt->execute(['id' => $officeId]);
    $count = $dispStmt->fetchColumn();
    if($count > 0) {
        $total += $count;
        $details[] = "$count Disposal Records";
    }

    // Stockouts (tbl_stockout)
    $stkStmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_stockout WHERE office_id = :id");
    $stkStmt->execute(['id' => $officeId]);
    $count = $stkStmt->fetchColumn();
    if($count > 0) {
        $total += $count;
        $details[] = "$count Stockout Records";
    }

    // Incident Reports (incident_reports) - office column stores ID as varchar
    $incStmt = $pdo->prepare("SELECT COUNT(*) FROM incident_reports WHERE office = :id");
    $incStmt->execute(['id' => (string)$officeId]);
    $count = $incStmt->fetchColumn();
    if($count > 0) {
        $total += $count;
        $details[] = "$count Incident Reports";
    }

    // Maintenance Reports (maintenance_reports) - office column stores ID as varchar
    $maintStmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance_reports WHERE office = :id");
    $maintStmt->execute(['id' => (string)$officeId]);
    $count = $maintStmt->fetchColumn();
    if($count > 0) {
        $total += $count;
        $details[] = "$count Maintenance Reports";
    }

    // Facility Items (facility_items)
    $facStmt = $pdo->prepare("SELECT COUNT(*) FROM facility_items WHERE office_id = :id");
    $facStmt->execute(['id' => $officeId]);
    $count = $facStmt->fetchColumn();
    if($count > 0) {
        $total += $count;
        $details[] = "$count Facility Items";
    }

    // 2. Check descendants recursively
    $children = $pdo->prepare("SELECT id, office_name FROM tbl_office WHERE parent_id = :id");
    $children->execute(['id' => $officeId]);
    while ($child = $children->fetch(PDO::FETCH_ASSOC)) {
        $childRes = checkLinkedRecordsRecursive($pdo, $child['id']);
        if ($childRes['total'] > 0) {
            $total += $childRes['total'];
            $details[] = "Sub-office '{$child['office_name']}' has: " . $childRes['details'];
        }
    }

    return [
        'total' => $total,
        'details' => implode(", ", $details)
    ];
}

/**
 * Recursive delete function (caller handles transaction)
 */
function deleteOfficeByIdRecursive($pdo, $id, $userId) {
    // Delete children first
    $children = $pdo->prepare("SELECT id FROM tbl_office WHERE parent_id = :id");
    $children->execute(['id' => $id]);
    while ($child = $children->fetch(PDO::FETCH_ASSOC)) {
        deleteOfficeByIdRecursive($pdo, $child['id'], $userId);
    }
    
    // Get office name for logging
    $officeStmt = $pdo->prepare("SELECT office_name FROM tbl_office WHERE id = :id AND parent_id IS NOT NULL");
    $officeStmt->execute(['id' => $id]);
    $office = $officeStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($office) {
        $officeName = $office['office_name'];
        
        // Delete the office
        $delete = $pdo->prepare("DELETE FROM tbl_office WHERE id = :id");
        $delete->execute(['id' => $id]);
        
        // Log activity
        $logStmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (:user_id, :action, NOW())");
        $logStmt->execute([
            ':user_id' => $userId,
            ':action' => "Deleted Office: " . $officeName
        ]);
    }
}

// Main logic
try {
    // Check if sub-office
    $checkStmt = $pdo->prepare("SELECT office_name, parent_id FROM tbl_office WHERE id = :id");
    $checkStmt->execute(['id' => $id]);
    $office = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$office) {
        throw new Exception("Office not found.");
    }
    
    if ($office['parent_id'] === null) {
        throw new Exception("Main offices cannot be deleted.");
    }
    
    // Check for linked inventory/records
    $linkedRes = checkLinkedRecordsRecursive($pdo, $id);
    if ($linkedRes['total'] > 0) {
        throw new Exception("Cannot delete '{$office['office_name']}'. It (or its sub-offices) has linked records: " . $linkedRes['details'] . ". Please clear or transfer these records first.");
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Perform delete
    deleteOfficeByIdRecursive($pdo, $id, $userId);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Office deleted successfully'
    ]);
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>