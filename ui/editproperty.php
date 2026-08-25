<?php
// ==========================
// editproperty.php - Editable Property with Image Upload
// ==========================

include_once 'connectdb.php';
session_start();

// --------------------------
// 1. Ensure user is logged in
// --------------------------
if (!isset($_SESSION['useremail']) || $_SESSION['useremail'] == "") {
    header("Location: ../index.php");
    exit;
}

// --------------------------
// 2. Get property ID from URL
// --------------------------
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['status'] = "No property ID provided.";
    $_SESSION['status_code'] = "error";
    header("Location: addproperty.php");
    exit;
}

$id = intval($_GET['id']); // sanitize ID

// --------------------------
// 3. Fetch property info
// --------------------------
$stmt = $pdo->prepare("SELECT * FROM tbl_property WHERE property_id = :id");
$stmt->execute([':id' => $id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    $_SESSION['status'] = "Property not found.";
    $_SESSION['status_code'] = "error";
    header("Location: addproperty.php");
    exit;
}

// Extract current values
$inventory_no_db = $property['inventory_no'];
$serial_no_db    = $property['serial_no'];
$item_name_db    = $property['item_name'];
$brand_db        = $property['brand'];
$description_db  = $property['description'];
$acquisition_db  = $property['acquisition_type'];
$quantity_db     = $property['quantity'];
$date_added_db   = $property['date_added'];
$remarks_db      = $property['remarks'];
$office_id_db    = $property['office_id']; // value stored in tbl_property
$image_db        = $property['image'] ?? null;
$warranty_db     = $property['warranty_image'] ?? null;

// --------------------------
// Set your actual primary key column name for tbl_office
// --------------------------
$office_pk = 'id'; // <---- replace 'id' with your actual primary key column in tbl_office

// --------------------------
// 4. Handle form submission
// --------------------------
if (isset($_POST['btnupdate'])) {

    $serial_no   = $_POST['serial_no'];
    $item_name   = $_POST['item_name'];
    $brand       = $_POST['brand'];
    $description = $_POST['description'];
    $acquisition = $_POST['acquisition'];
    $quantity    = $_POST['quantity'];
    $date_added  = $_POST['date_added'];
    $remarks     = $_POST['remarks'];

    $month_added = date("F", strtotime($date_added));
    $year_full   = date("Y", strtotime($date_added));

    // --------------------------
    // Handle image upload
    // --------------------------
    $image_file = $image_db; // keep existing by default

    if (isset($_FILES['image_file']) && $_FILES['image_file']['name'] != '') {
        $f_name = $_FILES['image_file']['name'];
        $f_tmp  = $_FILES['image_file']['tmp_name'];
        $f_size = $_FILES['image_file']['size'];
        $f_ext  = strtolower(pathinfo($f_name, PATHINFO_EXTENSION));
        $f_new  = uniqid() . '.' . $f_ext;
        $upload_dir = "productimage/" . $f_new;

        if (!in_array($f_ext, ['jpg','jpeg','png','gif','webp'])) {
            $_SESSION['status'] = "Only jpg, jpeg, png, gif, webp files are supported";
            $_SESSION['status_code'] = "warning";
        } elseif ($f_size >= 2000000) { // max 2MB
            $_SESSION['status'] = "Max file size should be 2MB";
            $_SESSION['status_code'] = "warning";
        } elseif (move_uploaded_file($f_tmp, $upload_dir)) {
            // Optional: delete old image if exists
            if ($image_db && file_exists("productimage/".$image_db)) {
                unlink("productimage/".$image_db);
            }
            $image_file = $f_new;
        }
    }

    // --------------------------
    // Handle warranty image upload
    // --------------------------
    $warranty_file = $warranty_db; // keep existing by default

    if (isset($_FILES['warranty_file']) && $_FILES['warranty_file']['name'] != '') {
        $w_name = $_FILES['warranty_file']['name'];
        $w_tmp  = $_FILES['warranty_file']['tmp_name'];
        $w_size = $_FILES['warranty_file']['size'];
        $w_ext  = strtolower(pathinfo($w_name, PATHINFO_EXTENSION));
        $w_new  = "w_" . uniqid() . '.' . $w_ext;
        $w_upload_dir = "productimage/" . $w_new;

        if (!in_array($w_ext, ['jpg','jpeg','png','gif','webp'])) {
            $_SESSION['status'] = "Only jpg, jpeg, png, gif, webp files are supported for warranty";
            $_SESSION['status_code'] = "warning";
        } elseif ($w_size >= 2000000) { // max 2MB
            $_SESSION['status'] = "Max warranty file size should be 2MB";
            $_SESSION['status_code'] = "warning";
        } elseif (move_uploaded_file($w_tmp, $w_upload_dir)) {
            // Optional: delete old warranty if exists
            if ($warranty_db && file_exists("productimage/".$warranty_db)) {
                unlink("productimage/".$warranty_db);
            }
            $warranty_file = $w_new;
        }
    }

    try {
        $update = $pdo->prepare("UPDATE tbl_property SET
            serial_no        = :serial_no,
            item_name        = :item_name,
            brand            = :brand,
            description      = :description,
            acquisition_type = :acquisition,
            quantity         = :quantity,
            date_added       = :date_added,
            month_added      = :month_added,
            year_added       = :year_added,
            remarks          = :remarks,
            image            = :image,
            warranty_image   = :warranty_image
            WHERE property_id = :id");

        $update->execute([
            ':serial_no'    => $serial_no,
            ':item_name'    => $item_name,
            ':brand'        => $brand,
            ':description'  => $description,
            ':acquisition'  => $acquisition,
            ':quantity'     => $quantity,
            ':date_added'   => $date_added,
            ':month_added'  => $month_added,
            ':year_added'   => $year_full,
            ':remarks'      => $remarks,
            ':image'        => $image_file,
            ':warranty_image' => $warranty_file,
            ':id'           => $id
        ]);

        // Log activity
        $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
        $stmtLog->execute([$_SESSION['userid'], "Updated Property: " . $item_name . " (" . $serial_no . ")"]);

        $_SESSION['status'] = "Property updated successfully! Inventory No: " . $inventory_no_db;
        $_SESSION['status_code'] = "success";
        header("Location: view_inventory.php?location_id=" . $office_id_db);
        exit;

    } catch (PDOException $e) {
        $_SESSION['status'] = "Database Error: " . $e->getMessage();
        $_SESSION['status_code'] = "error";
    }

    header("Location: editproperty.php?id=$id");
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
            <a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
            Edit Property
          </h4>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <div class="card card-success card-outline shadow-sm">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Update Property Information</h3>
        </div>
        <div class="card-body">

          <form action="" method="post" enctype="multipart/form-data">
            <div class="row">

              <!-- LEFT COLUMN: CORE DETAILS -->
              <div class="col-md-6 border-right">
                <h5 class="text-success mb-3"><i class="fas fa-info-circle mr-2"></i>Property Details</h5>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><i class="fas fa-hashtag mr-1"></i> Inventory No</label>
                      <input type="text" class="form-control" value="<?= htmlspecialchars($inventory_no_db) ?>" disabled>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><i class="fas fa-barcode mr-1"></i> Serial Number</label>
                      <input type="text" name="serial_no" class="form-control" value="<?= htmlspecialchars($serial_no_db) ?>" placeholder="Serial No">
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label><i class="fas fa-cube mr-1"></i> Item Name</label>
                  <input type="text" name="item_name" class="form-control" value="<?= htmlspecialchars($item_name_db) ?>" required placeholder="Item Name">
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><i class="fas fa-copyright mr-1"></i> Brand</label>
                      <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($brand_db) ?>" placeholder="Brand (Optional)">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><i class="fas fa-hand-holding mr-1"></i> Acquisition</label>
                      <select name="acquisition" class="form-control" required>
                        <option value="Purchased" <?= $acquisition_db=='Purchased'?'selected':'' ?>>Purchased</option>
                        <option value="Donated" <?= $acquisition_db=='Donated'?'selected':'' ?>>Donated</option>
                        <option value="Transferred" <?= $acquisition_db=='Transferred'?'selected':'' ?>>Transferred</option>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label><i class="fas fa-align-left mr-1"></i> Description</label>
                  <textarea name="description" class="form-control" rows="4" required placeholder="Description"><?= htmlspecialchars($description_db) ?></textarea>
                </div>

              </div>

              <!-- RIGHT COLUMN: ASSIGNMENT & IMAGE -->
              <div class="col-md-6 pl-md-4">
                <h5 class="text-success mb-3"><i class="fas fa-map-marker-alt mr-2"></i>Assignment & Status</h5>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><i class="fas fa-sort-amount-up mr-1"></i> Quantity</label>
                      <input type="number" name="quantity" min="1" class="form-control" value="<?= htmlspecialchars($quantity_db) ?>" required>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><i class="fas fa-calendar-alt mr-1"></i> Date Added</label>
                      <input type="date" name="date_added" class="form-control" value="<?= htmlspecialchars($date_added_db) ?>" required>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><i class="fas fa-clipboard-check mr-1"></i> Remarks</label>
                      <select name="remarks" class="form-control" required>
                        <option value="Serviceable" <?= $remarks_db=='Serviceable'?'selected':'' ?>>Serviceable</option>
                        <option value="Not Serviceable" <?= $remarks_db=='Not Serviceable'?'selected':'' ?>>Not Serviceable</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><i class="fas fa-building mr-1"></i> Office Allocation</label>
                      <?php
                        $stmt_office = $pdo->prepare("SELECT office_name FROM tbl_office WHERE $office_pk = :id");
                        $stmt_office->execute([':id' => $office_id_db]);
                        $office_name = $stmt_office->fetchColumn();
                      ?>
                      <input type="text" class="form-control" value="<?= htmlspecialchars($office_name) ?>" disabled>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label><i class="fas fa-image mr-1"></i> Property Image</label>
                  <div class="input-group">
                    <div class="custom-file">
                      <input type="file" class="custom-file-input" name="image_file" id="imageUpload">
                      <label class="custom-file-label" for="imageUpload">Choose new file...</label>
                    </div>
                  </div>
                  <small class="text-muted">Leave blank to keep current image.</small>

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

                <div class="form-group">
                  <label><i class="fas fa-file-contract mr-1"></i> Warranty Image</label>
                  <div class="input-group">
                    <div class="custom-file">
                      <input type="file" class="custom-file-input" name="warranty_file" id="warrantyUpload">
                      <label class="custom-file-label" for="warrantyUpload">Choose new warranty...</label>
                    </div>
                  </div>
                  <small class="text-muted">Leave blank to keep current warranty.</small>

                  <div class="mt-3 text-center p-2 border rounded bg-light" style="min-height: 150px;">
                    <?php if($warranty_db): ?>
                      <img id="previewWarranty" src="productimage/<?= htmlspecialchars($warranty_db) ?>" style="max-height:150px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin: 0 auto; display: block;">
                      <div id="noWarrantyText" style="display:none;" class="text-muted pt-4">
                        <i class="fas fa-file-contract fa-3x mb-2"></i><br>New warranty preview
                      </div>
                    <?php else: ?>
                      <img id="previewWarranty" style="display:none; max-height:150px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin: 0 auto;">
                      <div id="noWarrantyText" class="text-muted pt-4">
                        <i class="fas fa-file-contract fa-3x mb-2"></i><br>No warranty uploaded
                      </div>
                    <?php endif; ?>
                  </div>
                </div>

              </div>

            </div>

            <hr>
            <div class="text-right">
              <button type="submit" name="btnupdate" class="btn btn-success btn-lg px-5 shadow-sm">
                <i class="fas fa-save mr-2"></i> Update Property
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

    // Warranty preview
    document.getElementById("warrantyUpload").addEventListener("change", function(e){
        const file = e.target.files[0];
        if(file){
            const reader = new FileReader();
            reader.onload = function(event){
                let preview = document.getElementById("previewWarranty");
                let noWarrantyText = document.getElementById("noWarrantyText");
                preview.src = event.target.result;
                preview.style.display = "block";
                if(noWarrantyText) noWarrantyText.style.display = "none";
            }
            reader.readAsDataURL(file);
        }
    });
});
</script>

<?php if(isset($_SESSION['status']) && $_SESSION['status'] != ''){ ?>
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
<?php unset($_SESSION['status'], $_SESSION['status_code']); } ?>