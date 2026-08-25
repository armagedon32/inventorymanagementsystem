<?php
ob_start();
include_once 'connectdb.php';
session_start();

// Only Admin access
if ($_SESSION['useremail'] == "" || ($_SESSION['role'] == "")) {
    header('location:../index.php');
    exit;
}

// ====================== HANDLE ADD ORGANIZATION ======================
if(isset($_POST['btn_add_org'])){
    $org_name = trim($_POST['org_name']);
    $president = trim($_POST['president']);
    $org_logo = $_FILES['org_logo']['name'] ?? null;

    if(!empty($org_name)){
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
        header("Location: organization.php");
        exit;
    }
}

// ====================== HANDLE DELETE ORGANIZATION ======================
if(isset($_GET['delete_org'])){
    $id = intval($_GET['delete_org']);
    $stmt = $pdo->prepare("DELETE FROM tbl_organization WHERE id=:id");
    $stmt->execute([':id'=>$id]);
    $_SESSION['org_deleted'] = true;
    header("Location: organization.php");
    exit;
}

// ====================== FETCH ORGANIZATIONS ======================
$orgs = $pdo->query("SELECT * FROM tbl_organization WHERE is_archived = 0 ORDER BY id ASC")->fetchAll(PDO::FETCH_OBJ);

// Header
if($_SESSION['role']=="Admin"){
    include_once "header.php";
}else{
    include_once "headeruser.php";
}
?>

<div class="content-wrapper">
<section class="content-header">
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark">
                <a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
                Organization Management
            </h1>
        </div>
    </div>
</div>
</section>

<section class="content">
<div class="container-fluid">

<!-- ADD ORGANIZATION BUTTON -->
<button class="btn btn-info mb-3 shadow-sm text-white" data-toggle="modal" data-target="#addOrganizationModal">
    <i class="fas fa-plus-circle mr-2"></i>Add Organization
</button>

<!-- ADD ORGANIZATION MODAL -->
<div class="modal fade" id="addOrganizationModal" tabindex="-1">
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

<div class="card card-info card-outline shadow-sm">
    <div class="card-header">
        <h3 class="card-title">Organizations List</h3>
    </div>
    <div class="card-body table-responsive">
        <table id="table_orgs" class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Organization Name</th>
                    <th>President</th>
                    <th>Logo</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($orgs as $org): ?>
                <tr>
                    <td><?= $org->id ?></td>
                    <td><?= htmlspecialchars($org->org_name) ?></td>
                    <td><?= htmlspecialchars($org->president ?? '-') ?></td>
                    <td>
                        <?php if($org->org_logo): ?>
                            <img src="../<?= $org->org_logo ?>" alt="<?= htmlspecialchars($org->org_name) ?>" style="height:40px;" class="img-thumbnail">
                        <?php else: ?>
                            <span class="badge badge-secondary">No Logo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="organization_edit.php?id=<?= $org->id ?>" class="btn btn-warning btn-sm">Edit</a>
                        <button class="btn btn-danger btn-sm" onclick="deleteOrg(<?= $org->id ?>)">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</div>
</section>
</div>

<script src="../plugins/sweetalert2/sweetalert2.all.min.js"></script>
<script>
$(document).ready(function() {
    $('#table_orgs').DataTable({
        "order": [[1, "asc"]],
        "responsive": true
    });

    // BS Custom File Input Initialization
    if (typeof bsCustomFileInput !== 'undefined') {
        bsCustomFileInput.init();
    }
});

function deleteOrg(id){
    window.location.href = 'organization.php?delete_org=' + id;
}

// SweetAlert for deletion
<?php if(isset($_SESSION['org_deleted'])): ?>
Swal.fire({icon:'success', title:'Deleted!', text:'Organization deleted successfully.'});
<?php unset($_SESSION['org_deleted']); endif; ?>

<?php if(isset($_SESSION['org_added'])): ?>
Swal.fire({icon:'success', title:'Organization Added!', text:'New organization successfully added.'});
<?php unset($_SESSION['org_added']); endif; ?>

// SweetAlert for other status
<?php if(isset($_SESSION['status'])): ?>
Swal.fire({
    icon: '<?= $_SESSION['status_code'] ?>',
    title: '<?= $_SESSION['status'] ?>',
    showConfirmButton: false,
    timer: 2000
});
<?php unset($_SESSION['status'], $_SESSION['status_code']); endif; ?>
</script>

<?php include_once "footer.php"; ?>