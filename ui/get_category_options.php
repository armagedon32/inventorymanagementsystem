<?php
include_once 'connectdb.php';
$categories = $pdo->query("SELECT * FROM tbl_category WHERE is_archived = 0 ORDER BY catid ASC")->fetchAll(PDO::FETCH_OBJ);
foreach($categories as $cat){
    echo "<option value='{$cat->catid}'>{$cat->category}</option>";
}
?>