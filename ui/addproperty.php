<?php
include_once 'connectdb.php';
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);

/* ================= LOGIN CHECK ================= */
if(!isset($_SESSION['useremail']) || $_SESSION['useremail']==""){
    header("Location: ../index.php");
    exit;
}

/* ================= SAVE PROPERTY ================= */
if(isset($_POST['btnsave'])){

    $serial_no = $_POST['serial_no'] ?? '';
    $brand = $_POST['brand'] ?? '';
    $description = $_POST['description'] ?? '';
    $acquisition = $_POST['acquisition'] ?? '';
    $quantity = $_POST['quantity'] ?? 1;
    $date_added = $_POST['date_added'] ?? date('Y-m-d');
    $remarks = 'Serviceable'; // Default to Serviceable
    $office_id = $_POST['office_id'] ?? 0;
    $category_id = $_POST['category_id'] ?? 0;
    $item_id = $_POST['item_id'] ?? 0;
    $instructor_id = $_POST['instructor_id'] ?? NULL;

    /* ================= VALIDATION ================= */
    if(!$office_id || !$category_id || !$item_id){
        $_SESSION['status'] = "Please select Office, Category and Item";
        $_SESSION['status_code'] = "warning";
        header("Location: addproperty.php");
        exit;
    }

    /* ================= IMAGE UPLOAD ================= */
    $upload_dir = "productimage/";
    if(!file_exists($upload_dir)){
        mkdir($upload_dir,0777,true);
    }

    // Property Image
    $imageFile = NULL;
    if(!empty($_FILES['image']['name'])){
        $filename = $_FILES['image']['name'];
        $tmpname  = $_FILES['image']['tmp_name'];
        $filesize = $_FILES['image']['size'];
        $ext = strtolower(pathinfo($filename,PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];

        if(!in_array($ext,$allowed)){
            $_SESSION['status'] = "Invalid Property Image Format";
            $_SESSION['status_code'] = "error";
            header("Location: addproperty.php");
            exit;
        }

        if($filesize > 2*1024*1024){
            $_SESSION['status'] = "Property Image must be less than 2MB";
            $_SESSION['status_code'] = "error";
            header("Location: addproperty.php");
            exit;
        }

        $newname = uniqid().".".$ext;
        move_uploaded_file($tmpname,$upload_dir.$newname);
        $imageFile = $newname;
    }

    // Warranty Image
    $warrantyFile = NULL;
    if(!empty($_FILES['warranty_image']['name'])){
        $filename = $_FILES['warranty_image']['name'];
        $tmpname  = $_FILES['warranty_image']['tmp_name'];
        $filesize = $_FILES['warranty_image']['size'];
        $ext = strtolower(pathinfo($filename,PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];

        if(!in_array($ext,$allowed)){
            $_SESSION['status'] = "Invalid Warranty Image Format";
            $_SESSION['status_code'] = "error";
            header("Location: addproperty.php");
            exit;
        }

        if($filesize > 2*1024*1024){
            $_SESSION['status'] = "Warranty Image must be less than 2MB";
            $_SESSION['status_code'] = "error";
            header("Location: addproperty.php");
            exit;
        }

        $newname = "w_".uniqid().".".$ext;
        move_uploaded_file($tmpname,$upload_dir.$newname);
        $warrantyFile = $newname;
    }

    /* ================= GET ITEM NAME ================= */
    $stmt=$pdo->prepare("SELECT item_name FROM tbl_item WHERE itemid=:id");
    $stmt->execute([':id'=>$item_id]);
    $item_name=$stmt->fetchColumn();

    /* ================= INSERT PROPERTY (LOOP BY QUANTITY) ================= */
    $year_full=date("Y",strtotime($date_added));
    $year_short=date("y",strtotime($date_added));
    $today = date("Ymd");

    // Prepare statements outside the loop
    $stmtInsert = $pdo->prepare("INSERT INTO tbl_property
    (inventory_no, serial_no, item_name, brand, description, acquisition_type, quantity, date_added, month_added, year_added, remarks, office_id, instructor_id, image, warranty_image)
    VALUES
    (:inventory_no, :serial_no, :item_name, :brand, :description, :acquisition_type, :quantity, :date_added, :month_added, :year_added, :remarks, :office_id, :instructor_id, :image, :warranty_image)");

    $last_inserted_serials = [];

    for($i = 0; $i < $quantity; $i++) {
        /* ================= AUTO SERIAL NUMBER ================= */
        $current_serial = $serial_no;
        if(empty($current_serial)){
            $stmt = $pdo->prepare("SELECT serial_no 
                                   FROM tbl_property 
                                   WHERE serial_no LIKE :today 
                                   ORDER BY serial_no DESC 
                                   LIMIT 1");
            $stmt->execute([':today' => "SN-".$today."-%"]);
            $last_serial = $stmt->fetchColumn();
            
            // Also check serials generated in this loop
            if(!empty($last_inserted_serials)){
                $loop_last = end($last_inserted_serials);
                if($last_serial){
                    $last_serial = (int)substr($last_serial,-4) > (int)substr($loop_last,-4) ? $last_serial : $loop_last;
                } else {
                    $last_serial = $loop_last;
                }
            }

            $running_sn = $last_serial ? ((int)substr($last_serial,-4)+1) : 1;
            $current_serial = "SN-".$today."-".str_pad($running_sn,4,"0",STR_PAD_LEFT);
        } else {
            // If serial is provided, append a suffix if quantity > 1
            if($quantity > 1) {
                $current_serial = $serial_no . "-" . ($i + 1);
            }
        }
        $last_inserted_serials[] = $current_serial;

        /* ================= INVENTORY NUMBER ================= */
        $stmt=$pdo->prepare("SELECT inventory_no 
                             FROM tbl_property 
                             WHERE year_added=:year 
                             ORDER BY inventory_no DESC 
                             LIMIT 1");
        $stmt->execute([':year'=>$year_full]);
        $last_inventory=$stmt->fetchColumn();
        $running_inv = $last_inventory ? ((int)substr($last_inventory,-4)+1) : 1;
        $current_inventory_no="KNS".$year_short."-".str_pad($running_inv,4,"0",STR_PAD_LEFT);

        $stmtInsert->execute([
            ':inventory_no'=>$current_inventory_no,
            ':serial_no'=>$current_serial,
            ':item_name'=>$item_name,
            ':brand'=>$brand,
            ':description'=>$description,
            ':acquisition_type'=>$acquisition,
            ':quantity'=>1, // Always 1 per row
            ':date_added'=>$date_added,
            ':month_added'=>date("F",strtotime($date_added)),
            ':year_added'=>$year_full,
            ':remarks'=>$remarks,
            ':office_id'=>$office_id,
            ':instructor_id'=>$instructor_id,
            ':image'=>$imageFile,
            ':warranty_image'=>$warrantyFile
        ]);
    }

    // Log activity
    $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
    $stmtLog->execute([$_SESSION['userid'], "Added " . $quantity . " New Property: " . $item_name]);

    $_SESSION['status'] = "Property Saved Successfully";
    $_SESSION['status_code'] = "success";
    header("Location: view_inventory.php?location_id=" . $office_id);
    exit();
}

if($_SESSION['role']=="Admin"){
    include_once "header.php";
}else{
    include_once "headeruser.php";
}

$selected_category = $_POST['category_id'] ?? '';
$selected_office = $_POST['office_id'] ?? '';
$selected_instructor = $_POST['instructor_id'] ?? '';
?>

<div class="content-wrapper">
  <div class="content-header border-bottom mb-4">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h4 class="m-0 text-dark font-weight-bold text-uppercase">
            <i class="fas fa-plus-circle mr-2 text-primary"></i>Add New Property
          </h4>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="card card-outline card-primary shadow-sm">
        <div class="card-header border-0 pt-4 px-4">
          <h5 class="card-title text-muted mb-0">Property Information Details</h5>
        </div>
        <div class="card-body p-4">
          <form action="" method="post" enctype="multipart/form-data" id="addPropertyForm">
            <div class="row">

              <!-- LEFT COLUMN -->
              <div class="col-md-6 border-right pr-md-4">
                <div class="form-group mb-4">
                  <label class="small text-dark font-weight-bold mb-1"><i class="fas fa-barcode mr-1"></i> Serial Number</label>
                  <input type="text" name="serial_no" class="form-control form-control-lg bg-light" placeholder="Auto-generated if blank">
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
                  <input type="text" name="brand" class="form-control form-control-lg shadow-sm" placeholder="Brand (Optional)">
                </div>

                <div class="form-group mb-4">
                  <label class="small text-dark font-weight-bold mb-1"><i class="fas fa-hand-holding mr-1"></i> Acquisition Type</label>
                  <select name="acquisition" class="form-control form-control-lg shadow-sm">
                    <option value="Purchased">Purchased</option>
                    <option value="Donated">Donated</option>
                    <option value="Transferred">Transferred</option>
                  </select>
                </div>

                <div class="form-group mb-4">
                  <label class="small text-dark font-weight-bold mb-1"><i class="fas fa-align-left mr-1"></i> Description</label>
                  <textarea name="description" class="form-control form-control-lg shadow-sm" rows="3" placeholder="Enter detailed description..."></textarea>
                </div>

                <div class="form-group mb-4">
                  <label class="small text-dark font-weight-bold mb-1"><i class="fas fa-image mr-1"></i> Property Image</label>
                  <div class="custom-file mb-3">
                    <input type="file" name="image" id="imageUpload" class="custom-file-input" accept="image/*">
                    <label class="custom-file-label" for="imageUpload">Choose property image...</label>
                  </div>
                  <div class="text-center mt-3 bg-light rounded p-2 border" style="min-height: 120px; display: flex; align-items: center; justify-content: center;">
                    <img id="previewImage" src="" style="display:none; max-height:100px; width:auto; border-radius: 4px; border: 1px solid #ddd; padding: 3px; background: #fff;">
                    <div id="noPreviewText" class="text-muted small"><i class="fas fa-camera fa-2x d-block mb-1"></i>No Image Preview</div>
                  </div>
                </div>
              </div>

              <!-- RIGHT COLUMN -->
              <div class="col-md-6 pl-md-4">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group mb-4">
                      <label class="small text-dark font-weight-bold mb-1"><i class="fas fa-cubes mr-1"></i> Quantity</label>
                      <input type="number" name="quantity" class="form-control form-control-lg shadow-sm bg-light" required>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-4">
                      <label class="small text-dark font-weight-bold mb-1"><i class="fas fa-calendar-alt mr-1"></i> Date Added</label>
                      <input type="date" name="date_added" class="form-control form-control-lg shadow-sm" value="<?=date('Y-m-d')?>">
                    </div>
                  </div>
                </div>

                <div class="form-group mb-4">
                  <label class="small text-dark font-weight-bold mb-1"><i class="fas fa-building mr-1"></i> Office</label>
                  <select name="office_id" id="officeSelect" class="form-control form-control-lg shadow-sm" required>
                    <option value="">-- Select Office --</option>
                    <?php
                    $stmt=$pdo->query("SELECT * FROM tbl_office WHERE parent_id IS NOT NULL AND parent_id != 0 AND is_archived = 0 ORDER BY office_name ASC");
                    while($office=$stmt->fetch(PDO::FETCH_ASSOC)){
                        $selected = ($selected_office==$office['id']) ? "selected" : "";
                        echo "<option value='".$office['id']."' $selected>".$office['office_name']."</option>";
                    }
                    ?>
                  </select>
                </div>

                <div class="form-group mb-4">
                  <label class="small text-dark font-weight-bold mb-1"><i class="fas fa-user mr-1"></i> Received by (Instructor)</label>
                  <select name="instructor_id" id="instructorSelect" class="form-control form-control-lg shadow-sm">
                    <option value="">-- Select Instructor --</option>
                    <?php
                    if($selected_office){
                        $stmtOIC = $pdo->prepare("SELECT address FROM tbl_office WHERE id=:office");
                        $stmtOIC->execute([':office'=>$selected_office]);
                        $office_row = $stmtOIC->fetch(PDO::FETCH_ASSOC);

                        if($office_row && !empty($office_row['address'])){
                            $oic_name = $office_row['address'];
                            
                            $stmtCheck = $pdo->prepare("SELECT id FROM tbl_instructors WHERE fullname = ? AND is_archived = 0");
                            $stmtCheck->execute([$oic_name]);
                            $oic_inst = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                            
                            if($oic_inst){
                                $selected = ($selected_instructor == $oic_inst['id']) ? "selected" : "";
                                echo "<option value='".$oic_inst['id']."' $selected>".$oic_name." (OIC)</option>";
                            } else {
                                echo "<option value=''>".$oic_name." (OIC)</option>";
                            }
                        }
                    }
                    ?>
                  </select>
                </div>

                <div class="form-group mb-4">
                  <label class="small text-dark font-weight-bold mb-1"><i class="fas fa-file-contract mr-1"></i> Warranty Image</label>
                  <div class="custom-file mb-3">
                    <input type="file" name="warranty_image" id="warrantyUpload" class="custom-file-input" accept="image/*">
                    <label class="custom-file-label" for="warrantyUpload">Choose warranty image...</label>
                  </div>
                  <div class="text-center mt-3 bg-light rounded p-2 border" style="min-height: 120px; display: flex; align-items: center; justify-content: center;">
                    <img id="previewWarranty" src="" style="display:none; max-height:100px; width:auto; border-radius: 4px; border: 1px solid #ddd; padding: 3px; background: #fff;">
                    <div id="noPreviewWarrantyText" class="text-muted small"><i class="fas fa-camera fa-2x d-block mb-1"></i>No Warranty Preview</div>
                  </div>
                </div>
              </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-end">
              <a href="javascript:history.back()" class="btn btn-light btn-lg px-4 mr-2 border shadow-sm">
                <i class="fas fa-times mr-2"></i>Cancel
              </a>
              <button type="submit" name="btnsave" class="btn btn-primary btn-lg px-5 shadow">
                <i class="fas fa-save mr-2"></i>Save Property
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
    });
    <?php unset($_SESSION['status'], $_SESSION['status_code']); ?>
    <?php endif; ?>

    // Property image preview
    $("#imageUpload").on("change", function(e){
        const file = e.target.files[0];
        if(file){
            const reader = new FileReader();
            reader.onload = function(event){
                $("#previewImage").attr("src", event.target.result).fadeIn();
                $("#noPreviewText").hide();
                $(e.target).next('.custom-file-label').text(file.name);
            }
            reader.readAsDataURL(file);
        }
    });

    // Warranty image preview
    $("#warrantyUpload").on("change", function(e){
        const file = e.target.files[0];
        if(file){
            const reader = new FileReader();
            reader.onload = function(event){
                $("#previewWarranty").attr("src", event.target.result).fadeIn();
                $("#noPreviewWarrantyText").hide();
                $(e.target).next('.custom-file-label').text(file.name);
            }
            reader.readAsDataURL(file);
        }
    });

    // Dynamic instructor based on office
    $("#officeSelect").on("change", function(){
        const officeId = this.value;
        const instructorSelect = $("#instructorSelect");
        instructorSelect.html('<option value="">-- Select Instructor --</option>');

        if(officeId){
            fetch('get_instructors.php?office_id='+officeId+'&oic_only=1')
            .then(response => response.json())
            .then(data => {
                data.forEach(inst => {
                    const option = $('<option></option>').val(inst.id).text(inst.name);
                    instructorSelect.append(option);
                });
            });
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
    $("#addPropertyForm").on("submit", function(e){
        // No confirmation, just submit
    });

    // Prevent Enter key from submitting the form (common during barcode scanning)
    $(window).keydown(function(event){
        if(event.keyCode == 13) {
            event.preventDefault();
            return false;
        }
    });
});
</script>