<?php
include_once 'connectdb.php';
session_start();

// =====================
// 1. LOGIN CHECK
// =====================
if (!isset($_SESSION['useremail']) || $_SESSION['useremail'] == "") {
    header("Location: ../index.php");
    exit;
}

// =====================
// 2. GET PRODUCT ID
// =====================
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['status'] = "No product ID provided.";
    $_SESSION['status_code'] = "error";
    header("Location: productlist.php");
    exit;
}

$id = intval($_GET['id']);

// =====================
// 3. FETCH PRODUCT INFO
// =====================
$stmt = $pdo->prepare("SELECT * FROM tbl_product WHERE pid=:id");
$stmt->execute([':id'=>$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    $_SESSION['status'] = "Product not found.";
    $_SESSION['status_code'] = "error";
    header("Location: productlist.php");
    exit;
}

// Fetch categories for dropdown
$stmt_cat = $pdo->query("SELECT * FROM tbl_category WHERE is_archived = 0 ORDER BY category ASC");
$categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

// Extract fields
$name_db       = $product['name'];
$brand_db      = $product['brand'];
$description_db= $product['description'];
$acquisition_db= $product['acquisition_type'];
$category_db   = $product['category'];
$stock_db      = $product['stock'];
$reorder_db    = $product['reorder_level'];
$image_db      = $product['image'] ?? null;

// =====================
// 4. HANDLE FORM SUBMISSION
// =====================
if (isset($_POST['btnupdate'])) {

    $name        = $_POST['name'];
    $brand       = $_POST['brand'];
    $description = $_POST['description'];
    $acquisition = $_POST['acquisition'];
    $category    = $_POST['category'];
    $stock       = $_POST['stock'];

    // CHECK FOR DUPLICATE DESCRIPTION (EXCLUDING CURRENT PRODUCT)
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM tbl_product WHERE description = :description AND pid != :id");
    $stmt_check->execute([':description' => $description, ':id' => $id]);
    
    if ($stmt_check->fetchColumn() > 0) {
        $_SESSION['status'] = "Description already exists for another supply!";
        $_SESSION['status_code'] = "warning";
        header("Location: editproduct.php?id=$id");
        exit;
    }

    $image_file = $image_db; // default keep existing

    // Handle image upload
    if(isset($_FILES['image_file']) && $_FILES['image_file']['name'] != '') {
        $f_name = $_FILES['image_file']['name'];
        $f_tmp  = $_FILES['image_file']['tmp_name'];
        $f_size = $_FILES['image_file']['size'];
        $f_ext  = strtolower(pathinfo($f_name, PATHINFO_EXTENSION));
        $f_new  = uniqid() . '.' . $f_ext;
        $upload_dir = "productimage/" . $f_new;

        if (!in_array($f_ext,['jpg','jpeg','png','gif','webp'])) {
            $_SESSION['status'] = "Only jpg, jpeg, png, gif, webp files are supported";
            $_SESSION['status_code'] = "warning";
        } elseif ($f_size >= 2000000) {
            $_SESSION['status'] = "Max file size should be 2MB";
            $_SESSION['status_code'] = "warning";
        } elseif (move_uploaded_file($f_tmp,$upload_dir)) {
            if ($image_db && file_exists("productimage/".$image_db)) unlink("productimage/".$image_db);
            $image_file = $f_new;
        }
    }

    try {
        $update = $pdo->prepare("UPDATE tbl_product SET
            name            = :name,
            brand           = :brand,
            description     = :description,
            acquisition_type= :acquisition,
            category        = :category,
            stock           = :stock,
            image           = :image
            WHERE pid       = :id
        ");

        $update->execute([
            ':name'           => $name,
            ':brand'          => $brand,
            ':description'    => $description,
            ':acquisition'    => $acquisition,
            ':category'       => $category,
            ':stock'          => $stock,
            ':image'          => $image_file,
            ':id'             => $id
        ]);

        // Log activity
        $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
        $stmtLog->execute([$_SESSION['userid'], "Updated Product: " . $name]);

        $_SESSION['status'] = "Product updated successfully!";
        $_SESSION['status_code'] = "success";

    } catch(PDOException $e) {
        $_SESSION['status'] = "Database Error: ".$e->getMessage();
        $_SESSION['status_code'] = "error";
    }

    header("Location: editproduct.php?id=$id");
    exit;
}

include_once "header.php";
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h4 class="m-0 text-dark">
            <a href="productlist.php" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
            Edit Supply
          </h4>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <div class="card card-success card-outline shadow-sm">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Update Supply Information</h3>
        </div>
        <div class="card-body">

          <form action="" method="post" enctype="multipart/form-data">
            <div class="row">

              <!-- LEFT COLUMN: CORE DETAILS -->
              <div class="col-md-6 border-right">
                <h5 class="text-success mb-3"><i class="fas fa-info-circle mr-2"></i>Product Details</h5>

                <div class="form-group">
                  <label><i class="fas fa-cube mr-1"></i> Supply Name</label>
                  <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name_db) ?>" required placeholder="Enter supply name">
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                  <label><i class="fas fa-tags mr-1"></i> Category</label>
                  <select name="category" class="form-control" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach($categories as $cat): ?>
                    <option value="<?= $cat['catid'] ?>" <?= ($cat['catid'] == $category_db) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['category']) ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><i class="fas fa-copyright mr-1"></i> Brand</label>
                      <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($brand_db) ?>" placeholder="Brand (Optional)">
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label><i class="fas fa-hand-holding mr-1"></i> Acquisition Type</label>
                  <select name="acquisition" class="form-control" required>
                    <option value="Purchased" <?= $acquisition_db=='Purchased'?'selected':'' ?>>Purchased</option>
                    <option value="Donated" <?= $acquisition_db=='Donated'?'selected':'' ?>>Donated</option>
                    <option value="Transferred" <?= $acquisition_db=='Transferred'?'selected':'' ?>>Transferred</option>
                  </select>
                </div>

                <div class="form-group">
                  <label><i class="fas fa-align-left mr-1"></i> Description</label>
                  <textarea name="description" class="form-control" rows="4" required placeholder="Enter description..."><?= htmlspecialchars($description_db) ?></textarea>
                </div>

              </div>

              <!-- RIGHT COLUMN: STOCK & IMAGE -->
              <div class="col-md-6 pl-md-4">
                <h5 class="text-success mb-3"><i class="fas fa-boxes mr-2"></i>Stock & Inventory</h5>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><i class="fas fa-sort-amount-up mr-1"></i> Current Stock</label>
                      <input type="number" name="stock" class="form-control" min="0" value="<?= htmlspecialchars($stock_db) ?>" required>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><i class="fas fa-exclamation-triangle mr-1"></i> Reorder Level</label>
                      <input type="number" class="form-control" min="0" value="<?= htmlspecialchars($reorder_db) ?>" readonly>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label><i class="fas fa-image mr-1"></i> Product Image</label>
                  <div class="input-group">
                    <div class="custom-file">
                      <input type="file" class="custom-file-input" name="image_file" id="imageUpload">
                      <label class="custom-file-label" for="imageUpload">Choose new file...</label>
                    </div>
                  </div>
                  <small class="text-muted">Leave blank to keep the current image.</small>
                  
                  <div class="mt-3 text-center p-2 border rounded bg-light" style="min-height: 150px;">
                    <?php if($image_db): ?>
                      <img id="previewImage" src="productimage/<?= htmlspecialchars($image_db) ?>" style="max-height:150px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin: 0 auto; display: block;">
                      <div id="noImageText" style="display:none;" class="text-muted pt-4">
                        <i class="fas fa-image fa-3x mb-2"></i><br>New image preview
                      </div>
                    <?php else: ?>
                      <img id="previewImage" style="display:none; max-height:150px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin: 0 auto;">
                      <div id="noImageText" class="text-muted pt-4">
                        <i class="fas fa-image fa-3x mb-2"></i><br>No image uploaded
                      </div>
                    <?php endif; ?>
                  </div>
                </div>

              </div>

            </div>

            <hr>
            <div class="text-right">
              <button type="submit" name="btnupdate" class="btn btn-success btn-lg px-5 shadow-sm">
                <i class="fas fa-save mr-2"></i> Update Product
              </button>
            </div>

          </form>

        </div>
      </div>

    </div>
  </div>
</div>

<?php include_once "footer.php"; ?>

<script>
$(document).ready(function() {
    // BS Custom File Input Initialization
    if (typeof bsCustomFileInput !== 'undefined') {
        bsCustomFileInput.init();
    }

    // Confirm on Update - Removed as per user preference for direct action
    $('form').on('submit', function(e) {
        // Let the form submit naturally without confirmation
        return true;
    });

    // Image preview
    document.getElementById("imageUpload").addEventListener("change", function(e){
        const file = e.target.files[0];
        if(file){
            const reader = new FileReader();
            reader.onload = function(event){
                let preview = document.getElementById("previewImage");
                let noImageText = document.getElementById("noImageText");
                preview.src = event.target.result;
                preview.style.display = "block";
                if(noImageText) noImageText.style.display = "none";
            }
            reader.readAsDataURL(file);
        }
    });
});
</script>

<?php if(!empty($_SESSION['status'])): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    Swal.fire({
        icon: '<?= $_SESSION['status_code'] ?>',
        title: '<?= $_SESSION['status'] ?>',
        showConfirmButton: false,
        timer: 2000
    });
});
</script>
<?php unset($_SESSION['status'], $_SESSION['status_code']); endif; ?>