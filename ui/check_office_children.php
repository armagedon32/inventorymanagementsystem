<?php
include_once 'connectdb.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM tbl_office WHERE parent_id=:id");
$stmt->execute(['id'=>$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['count' => $row['cnt']]);