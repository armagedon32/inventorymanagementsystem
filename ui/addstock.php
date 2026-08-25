<?php
include_once 'connectdb.php';
session_start();

if (!isset($_SESSION['useremail']) || $_SESSION['useremail'] == "") {
    header("Location: ../index.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['status'] = "No product ID provided for restock.";
    $_SESSION['status_code'] = "error";
    header("Location: productlist.php");
    exit;
}

$pid = intval($_GET['id']);

$stmt = $pdo->prepare("SELECT * FROM tbl_product WHERE pid = :pid");
$stmt->execute([':pid' => $pid]);
$product = $stmt->fetch(PDO::FETCH_OBJ);

if (!$product) {
    $_SESSION['status'] = "Product not found.";
    $_SESSION['status_code'] = "error";
    header("Location: productlist.php");
    exit;
}

if (isset($_POST['btnrestock'])) {
    $restock_qty = $_POST['restock_qty'] ?? 0;
    $restock_qty = intval($restock_qty);

    if ($restock_qty <= 0) {
        $_SESSION['status'] = "Restock quantity must be greater than 0.";
        $_SESSION['status_code'] = "warning";
        header("Location: addstock.php?id=" . $pid);
        exit;
    }

    $new_stock = $product->stock + $restock_qty;

    $update = $pdo->prepare("UPDATE tbl_product SET stock = :stock WHERE pid = :pid");
    $update->execute([':stock' => $new_stock, ':pid' => $pid]);

    // Log activity
    $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
    $stmtLog->execute([$_SESSION['userid'], "Restocked Product: " . $product->name . " (+" . $restock_qty . ")"]);

    $_SESSION['status'] = "Product restocked successfully! New stock: " . $new_stock;
    $_SESSION['status_code'] = "success";
    header("Location: productlist.php");
    exit;
}

include_once "header.php";
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0 text-dark">Restock Supply</h1>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="card card-primary card-outline">
        <div class="card-header">
          <h5 class="m-0">Product Details</h5>
        </div>
        <div class="card-body">

          <form class="form-horizontal" method="post" action="addstock.php?id=<?= $pid ?>">

            <div class="row">

              <div class="col-md-6">
                <ul class="list-group">
                  <center><p class="list-group-item list-group-item-info"><b>Restock Details</b></p></center>

                  <div class="form-group">
                    <label for="product_name">Product Name:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($product->name) ?>" id="product_name" readonly>
                  </div>

                  <div class="form-group">
                    <label for="current_stock">Current Stock:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($product->stock) ?>" id="current_stock" readonly>
                  </div>

                  <div class="form-group">
                    <label for="reorder_level">Reorder Level:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($product->reorder_level) ?>" id="reorder_level" readonly>
                  </div>

                  <div class="form-group">
                    <label for="restock_qty">Quantity to Restock:</label>
                    <input type="number" class="form-control" id="restock_qty" name="restock_qty" min="1" required>
                  </div>

                  <div class="form-group">
                    <button type="submit" name="btnrestock" class="btn btn-primary">Restock</button>
                    <a href="productlist.php" class="btn btn-secondary">Cancel</a>
                  </div>

                </ul>
              </div>

              <div class="col-md-6">
                <ul class="list-group">
                  <center><p class="list-group-item list-group-item-info"><b>PRODUCT IMAGE</b></p></center>
                  <?php
                  $image_path = (!empty($product->image) && file_exists("productimage/".$product->image))
                      ? "productimage/".$product->image
                      : "productimage/noimage.png";
                  ?>
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