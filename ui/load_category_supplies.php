<?php
include 'connectdb.php';
if(isset($_POST['catid'])){
    $catid = $_POST['catid'];
    $supplies = $pdo->prepare("SELECT * FROM tbl_supply WHERE category_id=:catid ORDER BY supplyid ASC");
    $supplies->bindParam(':catid',$catid);
    $supplies->execute();
    echo "<ul class='list-group'>";
    while($s = $supplies->fetch(PDO::FETCH_OBJ)){
        echo "<li class='list-group-item'>{$s->supply_name} - Qty: {$s->quantity} {$s->unit} - Status: {$s->status}</li>";
    }
    echo "</ul>";
}
?>