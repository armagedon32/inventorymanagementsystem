<?php
include_once 'connectdb.php';
session_start();

// Only Admin access
if ($_SESSION['useremail'] == "" || ($_SESSION['role'] == "")) {
    header('location:../index.php');
    exit;
}

// ====================== HANDLE DELETE OFFICE ======================
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $redirect_id = null;

    try {
        // 1. Fetch details first
        $stmt = $pdo->prepare("SELECT office_name, parent_id FROM tbl_office WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $office = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($office) {
            $redirect_id = $office['parent_id'];
            $officeName = $office['office_name'];

            // Only allow sub-office deletion (parent_id > 0 or not null)
            if (!empty($office['parent_id'])) {
                // Perform archiving
                $archive = $pdo->prepare("UPDATE tbl_office SET is_archived = 1 WHERE id = :id");
                $archive->execute(['id' => $id]);

                // Log if successful
                if ($archive->rowCount() > 0) {
                    $userId = $_SESSION['userid'] ?? 0;
                    $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
                    $stmtLog->execute([$userId, "Archived Office: " . $officeName]);

                    $_SESSION['office_deleted'] = true;
                }
            } else {
                $_SESSION['office_deleted_error'] = "Main offices cannot be archived.";
            }
        }
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            $_SESSION['office_deleted_error'] = "Cannot delete office. It has linked inventory records or reports.";
        } else {
            $_SESSION['office_deleted_error'] = "Database error occurred.";
        }
    }

    // Redirect back
    $url = "office.php" . ($redirect_id ? "?id=$redirect_id" : "");
    header("Location: $url");
    exit;
}

// ====================== COUNT CHILD OFFICES ======================
function countSubOffices($pdo, $id) {
    $count = 0;
    $stmtChildren = $pdo->prepare("SELECT id FROM tbl_office WHERE parent_id = :id");
    $stmtChildren->execute([':id' => $id]);
    while ($child = $stmtChildren->fetch(PDO::FETCH_ASSOC)) {
        $count += 1 + countSubOffices($pdo, $child['id']);
    }
    return $count;
}

// ====================== HANDLE ADD OFFICE ======================
if (isset($_POST['btn_add'])) {
    $office_name = trim($_POST['office_name']);
    $address     = $_POST['address'];
    $contact     = $_POST['contact'];
    $maxcapacity = $_POST['max_capacity'];
    $parent_id   = !empty($_POST['parent_id']) ? $_POST['parent_id'] : NULL;

    // Check if office name already exists
    $check = $pdo->prepare("SELECT o.*, p.office_name as parent_name 
                           FROM tbl_office o 
                           LEFT JOIN tbl_office p ON o.parent_id = p.id 
                           WHERE o.office_name = :name AND o.is_archived = 0");
    $check->execute([':name' => $office_name]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $_SESSION['office_exists'] = true;
        $_SESSION['existing_office_name'] = $existing['office_name'];
        $_SESSION['existing_parent_name'] = $existing['parent_name'] ?? 'Main Office';
        header("Location: office.php" . ($parent_id ? "?id=$parent_id" : ""));
        exit;
    }

    $insert = $pdo->prepare("INSERT INTO tbl_office (office_name, address, contact, max_capacity, parent_id)
                             VALUES (:name, :address, :contact, :max_capacity, :parent)");
    $insert->execute([
        ':name' => $office_name,
        ':address' => $address,
        ':contact' => $contact,
        ':max_capacity' => $maxcapacity,
        ':parent' => $parent_id
    ]);

    // Log activity
    $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
    $stmtLog->execute([$_SESSION['userid'], "Added New Office: " . $office_name]);

    $_SESSION['office_added'] = true;
    header("Location: office.php" . ($parent_id ? "?id=$parent_id" : ""));
    exit;
}

// ====================== HANDLE ADD ORGANIZATION ======================
if(isset($_POST['btn_add_org'])){
    $org_name = trim($_POST['org_name']);
    $president = trim($_POST['president']);
    $org_logo = $_FILES['org_logo']['name'] ?? null;

    if(!empty($org_name)){
        // Check if organization already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_organization WHERE org_name = :name AND is_archived = 0");
        $checkStmt->execute([':name' => $org_name]);
        if ($checkStmt->fetchColumn() > 0) {
            $_SESSION['org_exists'] = true;
            header("Location: office.php");
            exit;
        }

        $logoPath = null;
        if($org_logo){
            $targetDir = "../uploads/org_logos/";
            if(!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $targetFile = $targetDir . basename($_FILES["org_logo"]["name"]);
            if(move_uploaded_file($_FILES["org_logo"]["tmp_name"], $targetFile)){
                $logoPath = "uploads/org_logos/" . basename($_FILES["org_logo"]["name"]);
            }
        }
        $stmt = $pdo->prepare("INSERT INTO tbl_organization (org_name, president, org_logo) VALUES (:name, :president, :logo)");
        $stmt->execute([':name'=>$org_name, ':president'=>$president, ':logo'=>$logoPath]);

        // Log activity
        $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
        $stmtLog->execute([$_SESSION['userid'], "Added New Organization: " . $org_name]);

        $_SESSION['org_added'] = true;
        header("Location: office.php");
        exit;
    }
}

// ====================== HANDLE DELETE ORGANIZATION ======================
if(isset($_GET['delete_org'])){
    $id = intval($_GET['delete_org']);
    $stmt = $pdo->prepare("UPDATE tbl_organization SET is_archived = 1 WHERE id=:id");
    $stmt->execute([':id'=>$id]);
    $_SESSION['org_deleted'] = true;
    header("Location: office.php");
    exit;
}

// ====================== BREADCRUMB FUNCTION ======================
function getBreadcrumb($pdo, $id){
    $breadcrumb = [];
    while($id != null){
        $stmt = $pdo->prepare("SELECT id, office_name, parent_id FROM tbl_office WHERE id=:id");
        $stmt->execute(['id'=>$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row){
            $breadcrumb[] = $row;
            $id = $row['parent_id'];
        } else { break; }
    }
    return array_reverse($breadcrumb);
}

$current_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if($_SESSION['role']=="Admin"){
    include_once "header.php";
}else{
    include_once "headeruser.php";
}

// ====================== LOAD OFFICES ======================
$gradients = ['grad-blue','grad-green','grad-red','grad-yellow'];
if($current_id){
    $select = $pdo->prepare("SELECT * FROM tbl_office WHERE parent_id=:id AND is_archived = 0");
    $select->execute(['id'=>$current_id]);
}else{
    $select = $pdo->prepare("SELECT * FROM tbl_office WHERE parent_id IS NULL AND is_archived = 0");
    $select->execute();
}

// ====================== LOAD ORGANIZATIONS ======================
$orgs = $pdo->query("SELECT * FROM tbl_organization WHERE is_archived = 0 ORDER BY id ASC")->fetchAll(PDO::FETCH_OBJ);
?>

<link rel="stylesheet" href="../plugins/sweetalert2/sweetalert2.min.css">
<style>
.office-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 25px; }
.office-box { position: relative; border-radius: 14px; padding: 25px; color: #fff; box-shadow: 0 8px 25px rgba(0,0,0,0.15); transition: 0.3s ease; min-height: 150px; }
.office-box:hover { transform: translateY(-6px); box-shadow: 0 12px 35px rgba(0,0,0,0.25); }
.office-actions { position: absolute; top: 12px; right: 12px; display: flex; gap: 10px; }
.office-actions a { color: #fff; font-size: 14px; background: rgba(0,0,0,0.3); padding: 6px 8px; border-radius: 6px; transition: 0.3s; }
.office-actions a:hover { transform: scale(1.2); }
.inventory-btn { position: absolute; bottom: 15px; right: 15px; background: #fff; color: #333; font-weight: bold; padding: 8px 14px; border-radius: 8px; font-size: 13px; }
.inventory-btn:hover { background: #ffd54f; }
.grad-blue { background: linear-gradient(135deg,#4f8dfc,#1e3fa0); }
.grad-green { background: linear-gradient(135deg,#10b981,#065f46); }
.grad-red { background: linear-gradient(135deg,#ef4444,#991b1b); }
.grad-yellow { background: linear-gradient(135deg,#f59e0b,#b45309); }
#addOfficeModal, #addOrganizationModal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
#addOfficeModal .modal-content, #addOrganizationModal .modal-content { background: #fff; margin: 8% auto; padding: 20px; border-radius: 12px; width: 400px; }
</style>

<div class="content-wrapper">
<section class="content-header">
<div class="container-fluid">
<div class="row mb-2">
<div class="col-sm-6">
<h1 class="m-0 text-dark">
<a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
Offices & Organizations
</h1>
</div>
</div>
</div>
</section>

<section class="content">
<div class="container-fluid">

<!-- ACTION BUTTONS -->
<div class="mb-3 d-flex flex-wrap gap-2">
    <button class="btn btn-primary shadow-sm mr-2" data-toggle="modal" data-target="#addOfficeModalBootstrap">
        <i class="fas fa-plus-circle mr-2"></i>Add Office
    </button>
    <button class="btn btn-info shadow-sm mr-2 text-white" data-toggle="modal" data-target="#viewOrganizationsModal">
        <i class="fas fa-users mr-2"></i>View Organizations
    </button>
    <button class="btn btn-warning shadow-sm mr-2" data-toggle="modal" data-target="#viewPersonnelModal">
        <i class="fas fa-user-tie mr-2"></i>View Personnel
    </button>
</div>

<!-- VIEW ORGANIZATIONS MODAL -->
<div class="modal fade" id="viewOrganizationsModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title"><i class="fas fa-users mr-2"></i>Organizations List</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-bordered table-striped table-hover datatable-modal">
            <thead>
              <tr>
                <th>Org Name</th>
                <th>President</th>
                <th>Logo</th>
                <th width="80" class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($orgs as $org): ?>
              <tr>
                <td><?= htmlspecialchars($org->org_name) ?></td>
                <td><?= htmlspecialchars($org->president ?? '-') ?></td>
                <td>
                  <?php if($org->org_logo): ?>
                    <img src="../<?= $org->org_logo ?>" style="height:30px;">
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
                <td class="text-center">
                    <a href="organization_edit.php?id=<?= $org->id ?>" class="btn btn-warning btn-xs"><i class="fas fa-edit"></i></a>
                    <button class="btn btn-danger btn-xs" onclick="deleteOrg(<?= $org->id ?>)"><i class="fas fa-trash"></i></button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="mt-3">
            <button class="btn btn-info btn-sm text-white" data-toggle="modal" data-target="#addOrganizationModalBootstrap" data-dismiss="modal">
                <i class="fas fa-plus mr-1"></i> Add New Organization
            </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- VIEW PERSONNEL MODAL -->
<div class="modal fade" id="viewPersonnelModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="fas fa-user-tie mr-2"></i>Personnel List</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-bordered table-striped table-hover datatable-modal">
            <thead>
              <tr>
                <th>Full Name</th>
                <th>Contact</th>
                <th>Assigned Office</th>
                <th width="40" class="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $instructors = $pdo->query("SELECT * FROM tbl_instructors WHERE is_archived = 0 ORDER BY fullname ASC")->fetchAll(PDO::FETCH_ASSOC);
              foreach($instructors as $inst): 
              ?>
              <tr>
                <td><?= htmlspecialchars($inst['fullname']) ?></td>
                <td><?= htmlspecialchars($inst['contact']) ?></td>
                <td><?= htmlspecialchars($inst['assigned_dept'] ?? '-') ?></td>
                <td class="text-center">
                    <a href="instructor_edit.php?id=<?= $inst['id'] ?>" class="btn btn-warning btn-xs"><i class="fas fa-edit"></i></a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="mt-3">
            <a href="instructor.php" class="btn btn-warning btn-sm">
                <i class="fas fa-plus mr-1"></i> Manage Personnel
            </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ADD OFFICE MODAL (Bootstrap) -->
<div class="modal fade" id="addOfficeModalBootstrap" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content shadow-lg border-0">
      <form method="POST">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title"><i class="fas fa-building mr-2"></i>Add New Office</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label><i class="fas fa-id-card mr-1"></i> Office Name</label>
            <input type="text" name="office_name" class="form-control name-mask" required placeholder="Enter office name">
          </div>
          <div class="form-group">
            <label><i class="fas fa-user-tie mr-1"></i> Officer In Charge</label>
            <input type="text" name="address" class="form-control name-mask" placeholder="Enter name of OIC">
          </div>
          <div class="form-group">
            <label><i class="fas fa-phone mr-1"></i> Contact</label>
            <input type="text" name="contact" class="form-control contact-mask" maxlength="11" placeholder="09xxxxxxxxx">
          </div>
          <div class="form-group">
            <label><i class="fas fa-users mr-1"></i> Maximum Capacity</label>
            <input type="text" name="max_capacity" class="form-control number-mask" maxlength="4" placeholder="Enter capacity">
          </div>
          <div class="form-group">
            <label><i class="fas fa-sitemap mr-1"></i> Parent Office</label>
            <select name="parent_id" class="form-control">
              <option value="">Main Office</option>
              <?php
              $parents = $pdo->prepare("SELECT * FROM tbl_office WHERE parent_id IS NULL AND is_archived = 0");
              $parents->execute();
              while($p = $parents->fetch(PDO::FETCH_ASSOC)){
                  echo '<option value="'.$p['id'].'">'.$p['office_name'].'</option>';
              }
              ?>
            </select>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" name="btn_add" class="btn btn-primary px-4 shadow-sm"><i class="fas fa-save mr-2"></i>Save Office</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ADD ORGANIZATION MODAL (Bootstrap) -->
<div class="modal fade" id="addOrganizationModalBootstrap" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content shadow-lg border-0">
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title"><i class="fas fa-users mr-2"></i>Add New Organization</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label><i class="fas fa-id-badge mr-1"></i> Organization Name</label>
            <input type="text" name="org_name" class="form-control" required placeholder="Enter organization name">
          </div>
          <div class="form-group">
            <label><i class="fas fa-user-circle mr-1"></i> President</label>
            <input type="text" name="president" class="form-control" placeholder="Enter President Full Name">
          </div>
          <div class="form-group">
            <label><i class="fas fa-image mr-1"></i> Logo</label>
            <div class="custom-file">
              <input type="file" name="org_logo" class="custom-file-input" id="orgLogoInput">
              <label class="custom-file-label" for="orgLogoInput">Choose logo file</label>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" name="btn_add_org" class="btn btn-info px-4 shadow-sm text-white"><i class="fas fa-save mr-2"></i>Save Organization</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- OFFICE GRID -->
<div class="office-grid mt-4">
<?php
$i=0;
while($row = $select->fetch(PDO::FETCH_ASSOC)){
    $gradClass = $gradients[$i % count($gradients)];
    $i++;
    $hasChildStmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_office WHERE parent_id=:id");
    $hasChildStmt->execute(['id'=>$row['id']]);
    $hasChild = $hasChildStmt->fetchColumn();
    $link = $hasChild > 0 ? "office.php?id=".$row['id'] : "view_inventory.php?location_id=".$row['id'];
?>
<div class="office-box <?php echo $gradClass; ?>">
<div class="office-actions">
<a href="office_edit.php?id=<?php echo $row['id']; ?>"><i class="fas fa-edit"></i></a>
<?php 
// Only sub-offices can be deleted (has parent_id that is not null or empty)
if($row['parent_id'] !== null && $row['parent_id'] !== ''):
    $subCount = countSubOffices($pdo,$row['id']);
?>
<a href="javascript:void(0);" onclick="deleteOffice(<?php echo $row['id']; ?>, <?php echo $subCount; ?>)"><i class="fas fa-trash"></i></a>
<?php endif; ?>
</div>
<h5><?php echo $row['office_name']; ?></h5>
<?php if($row['parent_id'] !== null && $row['parent_id'] !== ''): ?>
<p><?php echo $row['address']; ?></p>
<p><?php echo $row['contact']; ?></p>
<?php endif; ?>
<a href="<?php echo $link; ?>" class="inventory-btn"><?php echo $hasChild > 0 ? "Open Sub-Offices →" : "📦 View Inventory"; ?></a>
</div>
<?php } ?>
</div>

</div>
</section>
</div>

<?php include_once "footer.php"; ?>

<script>
$(document).ready(function() {
    // Initialize DataTables for modals
    if ($.fn.DataTable) {
        $('.datatable-modal').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
        });
    }

    // BS Custom File Input Initialization
    if (typeof bsCustomFileInput !== 'undefined') {
        bsCustomFileInput.init();
    }
});

function deleteOffice(id, subCount){
    // Immediate redirect as requested, bypassing confirmation.
    window.location.href = 'office.php?delete=' + id;
}

function deleteOrg(id){
    // Immediate redirect as requested, bypassing confirmation.
    window.location.href = 'office.php?delete_org=' + id;
}

// Confirm on Save
$('form').on('submit', function(e) {
    // If it's a delete request via URL, don't intercept.
    // The previous form submit interceptor might be blocking normal submissions.
    // Let's simplify this to just allow normal form submission without confirmation as requested.
    return true; 
});

// Full Name / Office Name mask (Letters and spaces only)
$(document).on('keypress', '.name-mask', function(e) {
    // Allow letters (A-Z, a-z), spaces, dots, and hyphens
    var regex = new RegExp("^[a-zA-Z .-]+$");
    var key = String.fromCharCode(!e.charCode ? e.which : e.charCode);
    if (!regex.test(key)) {
        e.preventDefault();
        return false;
    }
});

$(document).on('input', '.name-mask', function() {
    this.value = this.value.replace(/[^a-zA-Z .-]+/g, '');
});

// Contact number mask (Numeric only & Max 11 digits)
$(document).on('keypress', '.contact-mask', function(e) {
    if (e.which < 48 || e.which > 57) {
        e.preventDefault();
    }
});

$(document).on('input', '.contact-mask', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
    if (this.value.length > 11) {
        this.value = this.value.slice(0, 11);
    }
});

// Number only mask (Generic)
$(document).on('keypress', '.number-mask', function(e) {
    if (e.which < 48 || e.which > 57) {
        e.preventDefault();
    }
});

$(document).on('input', '.number-mask', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
    if (this.value.length > 4) {
        this.value = this.value.slice(0, 4);
    }
});

// SweetAlert notifications
<?php if(isset($_SESSION['office_added'])): ?>
Swal.fire({icon:'success', title:'Office Added!', text:'New office successfully added.'});
<?php unset($_SESSION['office_added']); endif; ?>

<?php if(isset($_SESSION['office_exists'])): ?>
Swal.fire({
    icon: 'error',
    title: 'Office Already Exists!',
    text: 'The office "<?php echo $_SESSION['existing_office_name']; ?>" is already registered under: <?php echo $_SESSION['existing_parent_name']; ?>'
});
<?php unset($_SESSION['office_exists'], $_SESSION['existing_office_name'], $_SESSION['existing_parent_name']); endif; ?>

<?php if(isset($_SESSION['office_deleted'])): ?>
Swal.fire({icon:'success', title:'Deleted!', text:'Office deleted successfully.'});
<?php unset($_SESSION['office_deleted']); endif; ?>

<?php if(isset($_SESSION['office_deleted_error'])): ?>
Swal.fire({icon:'error', title:'Cannot Delete!', text:'<?php echo $_SESSION['office_deleted_error']; ?>'});
<?php unset($_SESSION['office_deleted_error']); endif; ?>

<?php if(isset($_SESSION['org_added'])): ?>
Swal.fire({icon:'success', title:'Organization Added!', text:'New organization successfully added.'});
<?php unset($_SESSION['org_added']); endif; ?>

<?php if(isset($_SESSION['org_exists'])): ?>
Swal.fire({icon:'error', title:'Already Exists!', text:'This organization name is already registered.'});
<?php unset($_SESSION['org_exists']); endif; ?>

<?php if(isset($_SESSION['org_deleted'])): ?>
Swal.fire({icon:'success', title:'Deleted!', text:'Organization deleted successfully.'});
<?php unset($_SESSION['org_deleted']); endif; ?>

<?php if(isset($_SESSION['status'])): ?>
Swal.fire({
    icon: '<?= $_SESSION['status_code'] ?>',
    title: '<?= $_SESSION['status'] ?>',
    showConfirmButton: false,
    timer: 2000
});
<?php unset($_SESSION['status'], $_SESSION['status_code']); endif; ?>
</script>