<?php
include_once 'connectdb.php';
session_start();
include_once "header.php";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['status'] = "No property selected for barcode generation.";
    $_SESSION['status_code'] = "error";
    header("Location: propertylist.php");
    exit;
}

$id = intval($_GET['id']);

$stmt = $pdo->prepare("SELECT * FROM tbl_property WHERE property_id=:id");
$stmt->execute([':id' => $id]);
$property = $stmt->fetch(PDO::FETCH_OBJ);

if (!$property) {
    $_SESSION['status'] = "Property not found.";
    $_SESSION['status_code'] = "error";
    header("Location: propertylist.php");
    exit;
}

$property_image = !empty($property->image) && file_exists("productimage/".$property->image)
    ? "productimage/".$property->image
    : "productimage/noimage.png"; // You might want to ensure this file exists or use a placeholder

?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0 text-dark">Generate Barcode Stickers</h1>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="card card-primary card-outline">
        <div class="card-header">
          <h5 class="m-0">Property Details</h5>
        </div>
        <div class="card-body">

          <form class="form-horizontal" method="post" action="barcode/barcode.php" target="_blank">

            <div class="row">

              <div class="col-md-6">
                <ul class="list-group">
                  <center><p class="list-group-item list-group-item-info"><b>Print Barcode</b></p></center>

                  <div class="form-group">
                    <label for="product">Item Name:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($property->item_name) ?>" id="product" name="product" readonly>
                  </div>

                  <div class="form-group">
                    <label for="barcode">Inventory No.:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($property->inventory_no) ?>" id="barcode" name="barcode" readonly>
                  </div>

                  <div class="form-group">
                    <label for="serial">Serial No.:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($property->serial_no) ?>" id="serial" name="serial" readonly>
                  </div>

                  <div class="form-group">
                    <label for="print_qty">Barcode Quantity:</label>
                    <input type="number" class="form-control" id="print_qty" name="print_qty" value="1" min="1" required autocomplete="off" name="barcode" readonly>
                  </div>

                  <div class="form-group">
                    <button type="submit" class="btn btn-primary">Generate Barcode</button>
                  </div>

                </ul>
              </div>

              <div class="col-md-6">
                <ul class="list-group">
                  <center><p class="list-group-item list-group-item-info"><b>PROPERTY IMAGE</b></p></center>
                  <img src="<?= $property_image ?>" class="img-responsive" style="max-width:100%; height:auto; margin-top:5px; border-radius: 5px;">
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