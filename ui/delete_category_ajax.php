<?php
include_once 'connectdb.php';
$catid = $_POST['catid'];
$delete = $pdo->prepare("DELETE FROM tbl_category WHERE catid=:id");
$delete->bindParam(':id',$catid);
if($delete->execute()){
    echo json_encode(['status'=>'success']);
}else{
    echo json_encode(['status'=>'error']);
}
?>