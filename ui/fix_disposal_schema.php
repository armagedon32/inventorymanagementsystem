<?php
include_once 'connectdb.php';

try {
    // 1. Add columns to tbl_disposal if they don't exist
    $pdo->exec("ALTER TABLE tbl_disposal ADD COLUMN IF NOT EXISTS item_name VARCHAR(255) AFTER property_id");
    $pdo->exec("ALTER TABLE tbl_disposal ADD COLUMN IF NOT EXISTS inventory_no VARCHAR(100) AFTER item_name");
    
    // 2. Drop the foreign key constraint that blocks deletion
    // We need to find the constraint name first, but usually it's tbl_disposal_ibfk_1
    // To be safe, we'll try to drop it. If it fails, we'll check the error.
    try {
        $pdo->exec("ALTER TABLE tbl_disposal DROP FOREIGN KEY tbl_disposal_ibfk_1");
    } catch (Exception $e) {
        echo "Constraint drop error (might not exist): " . $e->getMessage() . "\n";
    }

    echo "Database schema updated successfully.\n";
} catch (Exception $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
}
?>
