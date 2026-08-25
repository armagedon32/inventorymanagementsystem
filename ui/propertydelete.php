<?php
ob_start();
include_once 'connectdb.php';
session_start();
ob_clean();

header('Content-Type: text/plain');

if(isset($_POST['property_id'])){

    $id = $_POST['property_id'];
    $isPermanent = isset($_POST['action']) && $_POST['action'] === 'permanent';

    try {
        $pdo->beginTransaction();

        // 1. Get property details for logging
        $stmtName = $pdo->prepare("SELECT item_name, serial_no FROM tbl_property WHERE property_id = :id");
        $stmtName->execute([':id' => $id]);
        $prop = $stmtName->fetch(PDO::FETCH_ASSOC);

        if (!$prop) {
            echo "Property not found.";
            $pdo->rollBack();
            exit();
        }

        if ($isPermanent) {
            // 2. Delete from related tables that might block (RESTRICT/NO ACTION)
            
            // Delete from tbl_disposal
            $pdo->prepare("DELETE FROM tbl_disposal WHERE property_id = :id")->execute([':id' => $id]);

            // Delete from ptr_items
            $pdo->prepare("DELETE FROM ptr_items WHERE property_id = :id")->execute([':id' => $id]);

            // Delete from ris_items
            $pdo->prepare("DELETE FROM ris_items WHERE property_id = :id")->execute([':id' => $id]);

            // Delete from rse_items
            $pdo->prepare("DELETE FROM rse_items WHERE property_id = :id")->execute([':id' => $id]);

            // 3. Permanent delete from tbl_property
            $stmt = $pdo->prepare("DELETE FROM tbl_property WHERE property_id = :id");
            $actionWord = "Permanently Deleted";
        } else {
            // 2. Soft delete (Archive)
            $stmt = $pdo->prepare("UPDATE tbl_property SET is_archived = 1 WHERE property_id = :id");
            $actionWord = "Archived";
        }

        if($stmt->execute([':id' => $id])){
            // Log activity
            logActivity($pdo, "$actionWord Property: " . $prop['item_name'] . " (" . $prop['serial_no'] . ")");
            $pdo->commit();
            echo "1";
        } else {
            $pdo->rollBack();
            echo "$actionWord execution failed.";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == '23000') {
            echo "Cannot delete property. It is still being referenced in the database: " . $e->getMessage();
        } else {
            echo "Database Error: " . $e->getMessage();
        }
    }

} else {
    echo "No property ID provided.";
}
exit();