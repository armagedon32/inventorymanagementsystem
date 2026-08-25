<?php
include_once 'connectdb.php';
session_start();

include_once "header.php";
include 'barcode/barcode128.php'; // Barcode generator
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0 text-dark">
            <a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
            View Property
          </h1>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-lg-12">

          <div class="card card-info card-outline">
            <div class="card-header">
              <h5 class="m-0">Property Details</h5>
            </div>
            <div class="card-body">

<?php
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="alert alert-danger">No property selected.</div>';
} else {

    $id = $_GET['id'];

    $select = $pdo->prepare("
        SELECT p.*, o.office_name
        FROM tbl_property p
        LEFT JOIN tbl_office o ON p.office_id = o.id
        WHERE p.property_id = :id
        LIMIT 1
    ");
    $select->execute([':id' => $id]);

    $row = $select->fetch(PDO::FETCH_OBJ);

    if (!$row) {
        echo '<div class="alert alert-danger">Property not found.</div>';
    } else {
        // Determine filesystem path for image
        $imagePath = __DIR__ . "/productimage/" . $row->image;
        $imageFile = (!empty($row->image) && file_exists($imagePath)) ? $row->image : 'default.png';
?>

<div class="row">
  <!-- Left Column: Property Info -->
  <div class="col-md-6">
    <ul class="list-group">
      <center><p class="list-group-item list-group-item-info"><b>PROPERTY DETAILS</b></p></center>
      <li class="list-group-item"> <b>Inventory Number</b> <span class="badge badge-light float-right"><?= htmlspecialchars($row->inventory_no) ?></span></li>
      <li class="list-group-item"> <b>Serial Number</b> <span class="badge badge-primary float-right"><?= htmlspecialchars($row->serial_no ?: 'N/A') ?></span></li>
      <li class="list-group-item"> <b>Item Name</b> <span class="badge badge-warning float-right"><?= htmlspecialchars($row->item_name) ?></span></li>
      <li class="list-group-item"> <b>Brand</b> <span class="badge badge-success float-right"><?= htmlspecialchars($row->brand) ?></span></li>
      <li class="list-group-item"> <b>Acquisition Type</b> <span class="badge badge-primary float-right"><?= htmlspecialchars($row->acquisition_type) ?></span></li>
      <li class="list-group-item"> <b>Quantity</b> <span class="badge badge-danger float-right"><?= htmlspecialchars($row->quantity) ?></span></li>
      <li class="list-group-item"> <b>Date Added</b> <span class="badge badge-secondary float-right"><?= htmlspecialchars($row->date_added) ?></span></li>
      <li class="list-group-item"> <b>Month Added</b> <span class="badge badge-dark float-right"><?= htmlspecialchars($row->month_added) ?></span></li>
      <li class="list-group-item"> <b>Year Added</b> <span class="badge badge-success float-right"><?= htmlspecialchars($row->year_added) ?></span></li>
      <li class="list-group-item"> <b>Remarks</b> <span class="badge badge-warning float-right"><?= htmlspecialchars($row->remarks) ?></span></li>
      <li class="list-group-item"> <b>Office Allocation</b> <span class="badge badge-info float-right"><?= htmlspecialchars($row->office_name ?: 'Unassigned') ?></span></li>
    </ul>
  </div>

  <!-- Right Column: Property Image -->
  <div class="col-md-6">
    <ul class="list-group">
      <center><p class="list-group-item list-group-item-info"><b>PROPERTY IMAGE</b></p></center>
      <img src="productimage/<?php echo htmlspecialchars($imageFile); ?>" 
           class="img-responsive" 
           style="max-width:100%; height:auto;" />
    </ul>

    <?php if(!empty($row->warranty_image) && file_exists(__DIR__ . "/productimage/" . $row->warranty_image)): ?>
    <ul class="list-group mt-3">
      <center><p class="list-group-item list-group-item-success d-flex justify-content-between align-items-center">
        <span><b>WARRANTY IMAGE</b></span>
        <button onclick="printWarrantyImage('productimage/<?= htmlspecialchars($row->warranty_image) ?>')" class="btn btn-sm btn-light shadow-sm">
            <i class="fas fa-print mr-1"></i>Print
        </button>
      </p></center>
      <img src="productimage/<?php echo htmlspecialchars($row->warranty_image); ?>" 
           class="img-responsive" 
           style="max-width:100%; height:auto;" />
    </ul>
    <script>
    function printWarrantyImage(image) {
        var printWin = window.open('', '_blank');
        printWin.document.write('<html><head><title>Print Warranty</title></head><body style="text-align:center; margin:0; padding:20px;"><img src="' + image + '" style="max-width:100%; height:auto;" onload="window.print(); window.close();"></body></html>');
        printWin.document.close();
    }
    </script>
    <?php endif; ?>
  </div>
</div>

<?php
    } // end else $row exists
} // end else id exists
?>

            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once "footer.php"; ?>