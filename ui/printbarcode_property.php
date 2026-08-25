<?php
include_once 'connectdb.php';
session_start();

include_once "header.php";

if (!isset($_GET['id'])) {
    die("Invalid Request");
}

$id = intval($_GET['id']); // prevent SQL injection

$select = $pdo->prepare("SELECT * FROM tbl_property WHERE property_id = :id");
$select->execute([':id' => $id]);
$row = $select->fetch(PDO::FETCH_OBJ);

if (!$row) {
    die("Property not found");
}
?>

<div class="content-wrapper">
<div class="content">
<div class="container-fluid">
<div class="card card-primary card-outline">
<div class="card-header">
<h5 class="m-0">Generate Property Barcode</h5>
</div>

<div class="card-body">

<form class="form-horizontal" method="post" action="barcode/barcode.php" target="_blank">

<div class="row">

<div class="col-md-6">
<ul class="list-group">

<center>
<p class="list-group-item list-group-item-info">
<b>Print Property Barcode</b>
</p>
</center>

<div class="form-group">
<label>Item Name</label>
<input type="text" class="form-control"
value="<?php echo htmlspecialchars($row->item_name); ?>"
readonly>
</div>

<div class="form-group">
<label>Inventory Number</label>
<input type="text" class="form-control"
value="<?php echo htmlspecialchars($row->inventory_no); ?>"
name="barcode"
readonly>
</div>

<div class="form-group">
<label>Serial Number</label>
<input type="text" class="form-control"
value="<?php echo htmlspecialchars($row->serial_no); ?>"
readonly>
</div>

<div class="form-group">
<label>Quantity to Print</label>
<input type="number" class="form-control"
name="print_qty" required>
</div>

<div class="form-group">
<button type="submit" class="btn btn-primary">
Generate Barcode
</button>
</div>

</ul>
</div>

<div class="col-md-6">
<ul class="list-group">
<center>
<p class="list-group-item list-group-item-info">
<b>PROPERTY DETAILS</b>
</p>
</center>

<li class="list-group-item">
<strong>Brand:</strong> <?php echo $row->brand; ?>
</li>

<li class="list-group-item">
<strong>Remarks:</strong> <?php echo $row->remarks; ?>
</li>

<li class="list-group-item">
<strong>Date Added:</strong> <?php echo $row->date_added; ?>
</li>

</ul>
</div>

</div>

</form>

</div>
</div>
</div>
</div>
</div>

<?php include_once "footer.php"; ?>