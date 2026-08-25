<?php
include_once 'connectdb.php';
$category = trim($_POST['category']);

$stmt = $pdo->prepare("SELECT * FROM tbl_category WHERE category=:cat");
$stmt->bindParam(':cat',$category);
$stmt->execute();
if($stmt->rowCount()>0){
    echo json_encode(['status'=>'exists']);
    exit;
}

$insert = $pdo->prepare("INSERT INTO tbl_category (category) VALUES (:cat)");
$insert->bindParam(':cat',$category);
if($insert->execute()){
    echo json_encode(['status'=>'success','id'=>$pdo->lastInsertId()]);
}else{
    echo json_encode(['status'=>'error']);
}
?>