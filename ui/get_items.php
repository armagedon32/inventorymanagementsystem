<?php
include_once 'connectdb.php';
header('Content-Type: application/json');

$category_id = $_GET['category_id'] ?? 0;
$data = [];

if($category_id){
    $stmt = $pdo->prepare("SELECT itemid, item_name FROM tbl_item WHERE category_id = :cat AND is_archived = 0 ORDER BY item_name ASC");
    $stmt->execute([':cat' => $category_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($data);
