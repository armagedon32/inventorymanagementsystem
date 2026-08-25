<?php
include_once 'connectdb.php';
session_start();
include_once "header.php";

// =====================
// 1. GET PRODUCT ID
// =====================
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['status'] = "No product selected for barcode generation.";
    $_SESSION['status_code'] = "error";
    header("Location: productlist.php");
    exit;
}

$id = intval($_GET['id']);

// =====================
// 2. FETCH PRODUCT
// =====================
$stmt = $pdo->prepare("SELECT * FROM tbl_product WHERE pid=:id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch(PDO::FETCH_OBJ);

if (!$product) {
    $_SESSION['status'] = "Product not found.";
    $_SESSION['status_code'] = "error";
    header("Location: productlist.php");
    exit;
}

// =====================
// 3. SAFE IMAGE PATH
// =====================
$image_path = (!empty($product->image) && file_exists("productimage/".$product->image))
    ? "productimage/".$product->image
    : "productimage/noimage.png";

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
          <h5 class="m-0">Product Details</h5>
        </div>
        <div class="card-body">

          <form class="form-horizontal" method="post" action="barcode/barcode.php" target="_blank">

            <div class="row">

              <!-- Left Column: Barcode Form -->
              <div class="col-md-6">
                <ul class="list-group">
                  <center><p class="list-group-item list-group-item-info"><b>Print Barcode</b></p></center>

                  <div class="form-group">
                    <label for="product">Product:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($product->name) ?>" id="product" name="product" readonly>
                  </div>

                  <div class="form-group">
                    <label for="barcode">Barcode:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($product->barcode) ?>" id="barcode" name="barcode" readonly>
                  </div>

                  <div class="form-group">
                    <label for="stock">Stock QTY:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($product->stock) ?>" id="stock" name="stock" readonly>
                  </div>

                  <div class="form-group">
                    <label for="print_qty">Barcode Quantity:</label>
                    <input type="number" class="form-control" id="print_qty" name="print_qty" value="1" min="1" required autocomplete="off">
                  </div>

                  <div class="form-group">
                    <button type="submit" class="btn btn-primary">Generate Barcode</button>
                  </div>

                </ul>
              </div>

              <!-- Right Column: Product Image -->
              <div class="col-md-6">
                <ul class="list-group">
                  <center><p class="list-group-item list-group-item-info"><b>PRODUCT IMAGE</b></p></center>
                  <img src="<?= $image_path ?>" class="img-responsive" style="max-width:100%; margin-top:5px;">
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