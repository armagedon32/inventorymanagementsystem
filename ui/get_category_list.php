<?php
include_once 'connectdb.php';
$categories = $pdo->query("SELECT * FROM tbl_category WHERE is_archived = 0 ORDER BY catid ASC")->fetchAll(PDO::FETCH_OBJ);
foreach($categories as $cat){
    echo "<li class='list-group-item d-flex justify-content-between align-items-center' data-id='{$cat->catid}'>
            <span class='btn-category'>{$cat->category}</span>
            <button class='btn btn-sm btn-danger btn-delete-category'>Delete</button>
          </li>";
}
?>