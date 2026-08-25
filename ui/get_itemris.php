<?php
// get_itemris.php
include_once 'connectdb.php';

// Check if office_id is provided
if(isset($_GET['office_id']) && !empty($_GET['office_id'])) {

    $office_id = $_GET['office_id'];

    // Fetch items from tbl_property belonging to the selected office
    // Only include items with quantity > 0
    $stmt = $pdo->prepare("
        SELECT property_id, item_name, description, quantity, inventory_no, serial_no 
        FROM tbl_property 
        WHERE office_id = :office_id AND quantity > 0 AND is_archived = 0
        ORDER BY item_name ASC
    ");
    $stmt->execute([':office_id' => $office_id]);

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return as JSON
    header('Content-Type: application/json');
    echo json_encode($items);

} else {
    // No office_id provided
    header('Content-Type: application/json');
    echo json_encode([]);
}