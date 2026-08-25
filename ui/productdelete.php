

<?php
ob_start();
include_once 'connectdb.php';
session_start();
ob_clean();

header('Content-Type: text/plain');

if(isset($_POST['pidd'])){

    $id = $_POST['pidd'];
    $isPermanent = isset($_POST['action']) && $_POST['action'] === 'permanent';

    try {
        $pdo->beginTransaction();

        // 1. Get product details for logging
        $stmtName = $pdo->prepare("SELECT name FROM tbl_product WHERE pid = :id");
        $stmtName->execute([':id' => $id]);
        $productName = $stmtName->fetchColumn();

        if (!$productName) {
            echo "Product not found.";
            $pdo->rollBack();
            exit();
        }

        if ($isPermanent) {
            // 2. Permanent delete
            // Check for related records if any (e.g. sales, stockout)
            // For now, assume it can be deleted or handle constraints
            
            // Delete from related tables if necessary (e.g. tbl_stockout)
            // (Assuming PID is the key used in other tables)
            
            $stmt = $pdo->prepare("DELETE FROM tbl_product WHERE pid = :id");
            $actionWord = "Permanently Deleted";
        } else {
            // 2. Soft delete (Archive)
            $stmt = $pdo->prepare("UPDATE tbl_product SET is_archived = 1 WHERE pid = :id");
            $actionWord = "Archived";
        }

        if($stmt->execute([':id' => $id])){
            // Log activity
            $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
            $stmtLog->execute([$_SESSION['userid'], "$actionWord Product: " . $productName]);
            
            $pdo->commit();
            echo "1";
        } else {
            $pdo->rollBack();
            echo "$actionWord execution failed.";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "Database Error: " . $e->getMessage();
    }

} else {
    echo "No product ID provided.";
}
exit();
?>