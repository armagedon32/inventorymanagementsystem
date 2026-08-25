<?php
include_once 'connectdb.php';
session_start();

$selected_category = $_POST['category_id'] ?? '';

/* ================= SAVE PRODUCT ================= */
if(isset($_POST['btnsave'])){

    $barcode    = $_POST['txtbarcode'] ?? '';
    $brand      = $_POST['txtbrand'] ?? '';
    $acq_type   = $_POST['txtacqtype'] ?? '';
    $desc       = $_POST['txtdesc'] ?? '';
    $stock      = $_POST['txtstock'] ?? 0;
    $reorder    = $_POST['txtreorder'] ?? 0;
    $category_id= $_POST['category_id'] ?? '';
    $item_id    = $_POST['item_id'] ?? '';
    $date_added = $_POST['txtdateadded'] ?? date('Y-m-d');

    /* GET ITEM NAME */
    $stmt=$pdo->prepare("SELECT item_name FROM tbl_item WHERE itemid=:id");
    $stmt->execute([':id'=>$item_id]);
    $name=$stmt->fetchColumn();

    /* ================= AUTO BARCODE ================= */
    if(empty($barcode)){
        $today = date("Ymd");
        $stmt = $pdo->prepare("SELECT barcode 
                               FROM tbl_product 
                               WHERE barcode LIKE :today
                               ORDER BY barcode DESC 
                               LIMIT 1");
        $stmt->execute([ ':today' => "BC-".$today."-%" ]);
        $last_barcode = $stmt->fetchColumn();
        $running = $last_barcode ? ((int)substr($last_barcode,-4)+1) : 1;
        $barcode = "BC-".$today."-".str_pad($running,4,"0",STR_PAD_LEFT);
    }

    /* ================= IMAGE UPLOAD ================= */
    $imageFile = NULL;
    $upload_dir = "productimage/";
    if(!file_exists($upload_dir)){
        mkdir($upload_dir,0777,true);
    }
    if(!empty($_FILES['image']['name'])){
        $filename = $_FILES['image']['name'];
        $tmpname  = $_FILES['image']['tmp_name'];
        $filesize = $_FILES['image']['size'];
        $ext = strtolower(pathinfo($filename,PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if(!in_array($ext,$allowed)){
            $_SESSION['status'] = "Invalid Image Format";
            $_SESSION['status_code'] = "error";
            header("Location: addproduct.php");
            exit;
        }
        if($filesize > 2*1024*1024){
            $_SESSION['status'] = "Image must be less than 2MB";
            $_SESSION['status_code'] = "error";
            header("Location: addproduct.php");
            exit;
        }
        $newname = uniqid().".".$ext;
        move_uploaded_file($tmpname,$upload_dir.$newname);
        $imageFile = $newname;
    }

    /* ================= CHECK FOR DUPLICATE (same name, category, and description) ================= */
    $stmtCheck = $pdo->prepare("SELECT pid, stock FROM tbl_product WHERE name = :name AND category = :cat AND description = :desc LIMIT 1");
    $stmtCheck->execute([':name' => $name, ':cat' => $category_id, ':desc' => $desc]);
    $existingProduct = $stmtCheck->fetch(PDO::FETCH_OBJ);

    if ($existingProduct) {
        $_SESSION['status'] = "Existing supply found! Please update the existing supply instead.";
        $_SESSION['status_code'] = "warning";
    } else {
        /* ================= INSERT NEW PRODUCT ================= */
        $insert = $pdo->prepare("
            INSERT INTO tbl_product
            (barcode,name,brand,acquisition_type,category,description,stock,reorder_level,date_added,image)
            VALUES
            (:barcode,:name,:brand,:acq,:cat,:des,:stock,:reorder,:date_added,:image)
        ");
        $insert->bindParam(':barcode',$barcode);
        $insert->bindParam(':name',$name);
        $insert->bindParam(':brand',$brand);
        $insert->bindParam(':acq',$acq_type);
        $insert->bindParam(':cat',$category_id);
        $insert->bindParam(':des',$desc);
        $insert->bindParam(':stock',$stock);
        $insert->bindParam(':reorder',$reorder);
        $insert->bindParam(':date_added',$date_added);
        $insert->bindParam(':image',$imageFile);

        if($insert->execute()){
            $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
            $stmtLog->execute([$_SESSION['userid'], "Added New Product: " . $name]);

            $_SESSION['status'] = "Product Added Successfully";
            $_SESSION['status_code'] = "success";
        }else{
            $_SESSION['status'] = "Error Adding Product";
            $_SESSION['status_code'] = "error";
        }
    }
    header("Location: addproduct.php");
    exit();
}

if($_SESSION['role']=="Admin"){
    include_once "header.php";
}else{
    include_once "headeruser.php";
}
?>

<div class="content-wrapper">
  <div class="content-header border-bottom mb-4">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h4 class="m-0 text-dark font-weight-bold text-uppercase">
            <i class="fas fa-plus-circle mr-2 text-primary"></i>Add New Supply
          </h4>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <div class="card card-outline card-primary shadow-sm">
        <div class="card-header border-0 pt-4 px-4">
          <h5 class="card-title text-muted mb-0">Supply Information Details</h5>
        </div>
        <div class="card-body p-4">

          <form action="" method="post" enctype="multipart/form-data" id="addProductForm">
            <div class="row">

              <!-- LEFT COLUMN -->
              <div class="col-md-6 border-right pr-md-4">

                <div class="form-group mb-4">
                  <label class="small text-dark font-weight-bold mb-1"><i class="fas fa-barcode mr-1"></i> Barcode</label>
                  <input type="text" class="form-control form-control-lg bg-light" name="txtbarcode" placeholder="Auto-generated if blank">
                </div>

                <div class="form-group mb-4">
                  <label class="small text-dark font-weight-bold mb-1"><i class="fas fa-tags mr-1"></i> Category</label>
                  <select name="category_id" id="categorySelect" class="form-control form-control-lg shadow-sm" required>
                    <option value="">-- Select Category --</option>
                    <?php
                    $stmt=$pdo->query("SELECT * FROM tbl_category WHERE is_archived = 0 ORDER BY category ASC");
                    while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
                        $selected = ($selected_category==$row['catid']) ? "selected" : "";
                        echo "<option value='".$row['catid']."' $selected>".$row['category']."</option>";
                    }
                    ?>
                  </select>
                </div>

                <div class="form-group mb-4">
                  <label class="small text-dark font-weight-bold mb-1"><i class="fas fa-list mr-1"></i> Item Name</label>
                  <select name="item_id" id="itemSelect" class="form-control form-control-lg shadow-sm" required>
                    <option value="">-- Select Item --</option>
                    <?php
                    if($selected_category){
                        $stmt=$pdo->prepare("SELECT * FROM tbl_item WHERE category_id=:cat AND is_archived = 0");
                        $stmt->execute([':cat'=>$selected_category]);
                        while($item=$stmt->fetch(PDO::FETCH_ASSOC)){
                            echo "<option value='".$item['itemid']."'>".$item['item_name']."</option>";
                        }
                    }
                    ?>
                  </select>
                </div>

                <div class="form-group mb-4">
                  <label class="small text-dark font-weight-bold mb-1"><i class="fas fa-copyright mr-1"></i> Brand</label>
                  <input type="text" class="form-control form-control-lg shadow-sm" name="txtbrand" placeholder="Brand (Optional)">
                </div>

                <div class="form-group mb-4">
                  <label class="small text-dark font-weight-bold mb-1"><i class="fas fa-hand-holding mr-1"></i> Acquisition Type</label>
                  <select class="form-control form-control-lg shadow-sm" name="txtacqtype">
                    <option>Purchased</option>
                    <option>Donated</option>
                  </select>
                </div>

              </div>

              <!-- RIGHT COLUMN -->
              <div class="col-md-6 pl-md-4">

                <div class="form-group mb-4">
                  <label class="small text-dark font-weight-bold mb-1"><i class="fas fa-align-left mr-1"></i> Description</label>
                  <textarea class="form-control shadow-sm" name="txtdesc" rows="3" placeholder="Enter supply description"></textarea>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group mb-4">
                      <label class="small text-dark font-weight-bold mb-1"><i class="fas fa-cubes mr-1"></i> Stock Quantity</label>
                      <input type="number" class="form-control form-control-lg shadow-sm" name="txtstock" min="0" value="0">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-4">
                      <label class="small text-dark font-weight-bold mb-1"><i class="fas fa-bell mr-1"></i> Reorder Level</label>
                      <input type="number" class="form-control form-control-lg shadow-sm" name="txtreorder" min="0" value="1">
                    </div>
                  </div>
                </div>

                <div class="form-group mb-4">
                  <label class="small text-dark font-weight-bold mb-1"><i class="fas fa-calendar-alt mr-1"></i> Date Added</label>
                  <input type="date" class="form-control form-control-lg shadow-sm" name="txtdateadded" value="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group mb-4">
                  <label class="small text-dark font-weight-bold mb-1"><i class="fas fa-image mr-1"></i> Supply Image</label>
                  <div class="custom-file mb-3">
                    <input type="file" class="custom-file-input" name="image" id="imageUpload">
                    <label class="custom-file-label" for="imageUpload">Choose supply image...</label>
                  </div>
                  <div class="text-center mt-3 bg-light rounded p-2 border" style="min-height: 120px; display: flex; align-items: center; justify-content: center;">
                    <img id="previewImage" src="" style="display:none; max-height:100px; width:auto; border-radius: 4px; border: 1px solid #ddd; padding: 3px; background: #fff;">
                    <div id="noPreviewText" class="text-muted small"><i class="fas fa-camera fa-2x d-block mb-1"></i>No Image Preview</div>
                  </div>
                </div>

              </div>

            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-end">
              <a href="productlist.php" class="btn btn-light btn-lg px-4 mr-2 border shadow-sm">
                <i class="fas fa-times mr-2"></i>Cancel
              </a>
              <button type="submit" name="btnsave" class="btn btn-primary btn-lg px-5 shadow">
                <i class="fas fa-save mr-2"></i>Save Supply
              </button>
            </div>

          </form>

        </div>
      </div>

    </div>
  </div>
</div>

<?php include_once "footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function(){

    // SweetAlert Notifications
    <?php if(isset($_SESSION['status'])): ?>
    Swal.fire({
        icon: '<?= $_SESSION['status_code'] ?>',
        title: '<?= $_SESSION['status'] ?>',
        showConfirmButton: false,
        timer: 2000
    }).then(function() {
        <?php if($_SESSION['status_code'] == 'success' && strpos($_SESSION['status'], 'Existing supply found') === false): ?>
        window.location.href = "productlist.php";
        <?php endif; ?>
    });
    <?php unset($_SESSION['status'], $_SESSION['status_code']); ?>
    <?php endif; ?>

    // Image preview
    $("#imageUpload").on("change", function(e){
        const file = e.target.files[0];
        if(file){
            const reader = new FileReader();
            reader.onload = function(event){
                $("#previewImage").attr("src", event.target.result).fadeIn();
                $("#noPreviewText").hide();
                $(".custom-file-label").text(file.name);
            }
            reader.readAsDataURL(file);
        }
    });

    // Dynamic item based on category
    $("#categorySelect").on("change", function(){
        const categoryId = this.value;
        const itemSelect = $("#itemSelect");
        itemSelect.html('<option value="">-- Select Item --</option>');

        if(categoryId){
            fetch('get_items.php?category_id='+categoryId)
            .then(response => response.json())
            .then(data => {
                data.forEach(item => {
                    const option = $('<option></option>').val(item.itemid).text(item.item_name);
                    itemSelect.append(option);
                });
            });
        }
    });

    // Confirmation on Save - Removed as per user preference
    $("#addProductForm").on("submit", function(e){
        // No confirmation, just submit
    });
});
</script>