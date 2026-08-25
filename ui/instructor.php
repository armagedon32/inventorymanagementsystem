<?php
ob_start(); // Start output buffering
include_once 'connectdb.php';
session_start();

// Only Admin access
if ($_SESSION['useremail'] == "" || ($_SESSION['role'] == "")) {
    header('location:../index.php');
    exit;
}

// ====================== HANDLE ADD INSTRUCTOR ======================
if(isset($_POST['btn_add_instructor'])){
    $fullname = trim($_POST['fullname']);
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    $assigned_dept = trim($_POST['assigned_dept']); // Use this for department identification only

    if(!empty($fullname)){
        // Check if personnel already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_instructors WHERE fullname = :fullname AND is_archived = 0");
        $checkStmt->execute([':fullname' => $fullname]);
        if ($checkStmt->fetchColumn() > 0) {
            $_SESSION['instructor_exists'] = true;
            header("Location: instructor.php");
            exit;
        }

        try {
            // Insert with identification dept
            $stmt = $pdo->prepare("INSERT INTO tbl_instructors (fullname, contact, email, assigned_dept) VALUES (:fullname, :contact, :email, :dept)");
            $stmt->execute([
                ':fullname'=>$fullname,
                ':contact'=>$contact,
                ':email'=>$email,
                ':dept'=>$assigned_dept
            ]);
        } catch (PDOException $e) {
            // Fallback if column missing
            $stmt = $pdo->prepare("INSERT INTO tbl_instructors (fullname, contact, email) VALUES (:fullname, :contact, :email)");
            $stmt->execute([
                ':fullname'=>$fullname,
                ':contact'=>$contact,
                ':email'=>$email
            ]);
        }

        // Log activity
        $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
        $stmtLog->execute([$_SESSION['userid'], "Added New Personnel: " . $fullname]);

        $_SESSION['instructor_added'] = true;
        header("Location: instructor.php");
        exit;
    }
}

// ====================== HANDLE DELETE INSTRUCTOR ======================
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);

    // Fetch name for logging
    $stmtName = $pdo->prepare("SELECT fullname FROM tbl_instructors WHERE id=:id");
    $stmtName->execute([':id'=>$id]);
    $instName = $stmtName->fetchColumn();

    // Unassign instructor from any office
    $pdo->prepare("UPDATE tbl_office SET instructor_id=NULL WHERE instructor_id=:id")->execute([':id'=>$id]);

    // Archive instructor
    $pdo->prepare("UPDATE tbl_instructors SET is_archived = 1 WHERE id=:id")->execute([':id'=>$id]);

    // Log activity
    $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
    $stmtLog->execute([$_SESSION['userid'], "Archived Personnel: " . $instName]);

    $_SESSION['instructor_deleted'] = true;
    header("Location: instructor.php"); // Redirect to self instead of office.php
    exit;
}

// ====================== LOAD OFFICES & INSTRUCTORS ======================
$offices = $pdo->query("SELECT * FROM tbl_office WHERE parent_id IS NOT NULL AND parent_id != 0 AND is_archived = 0 ORDER BY office_name ASC")->fetchAll(PDO::FETCH_ASSOC);
// Load instructors with their OIC office (if any)
try {
    // If assigned_dept exists in tbl_instructors, this will include it
    $instructors = $pdo->query("SELECT i.*, o.office_name as oic_office FROM tbl_instructors i LEFT JOIN tbl_office o ON i.id=o.instructor_id WHERE i.is_archived = 0 ORDER BY i.fullname ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback if assigned_dept column is missing (e.g. user hasn't run the SQL yet)
    $instructors = $pdo->query("SELECT i.id, i.fullname, i.contact, i.email, o.office_name as oic_office FROM tbl_instructors i LEFT JOIN tbl_office o ON i.id=o.instructor_id WHERE i.is_archived = 0 ORDER BY i.fullname ASC")->fetchAll(PDO::FETCH_ASSOC);
}

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
Personnel Management
</h1>
</div>
</div>
</div>
</section>

<section class="content">
<div class="container-fluid">

<!-- ADD INSTRUCTOR BUTTON -->
<button class="btn btn-primary mb-3 shadow-sm" data-toggle="modal" data-target="#addPersonnelModal">
    <i class="fas fa-plus-circle mr-2"></i>Add Personnel
</button>

<!-- ADD PERSONNEL MODAL -->
<div class="modal fade" id="addPersonnelModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content shadow-lg border-0">
      <form method="POST">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title"><i class="fas fa-user-tie mr-2"></i>Add New Personnel</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label><i class="fas fa-id-card mr-1"></i> Full Name</label>
            <input type="text" name="fullname" class="form-control name-mask" required placeholder="Enter full name">
          </div>
          <div class="form-group">
            <label><i class="fas fa-phone mr-1"></i> Contact Number</label>
            <input type="text" name="contact" class="form-control contact-mask" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="09xxxxxxxxx">
          </div>
          <div class="form-group">
            <label><i class="fas fa-envelope mr-1"></i> Email</label>
            <input type="email" name="email" class="form-control" placeholder="example@email.com">
          </div>
          <div class="form-group">
            <label><i class="fas fa-building mr-1"></i> Assigned Department (Identification Only)</label>
            <select name="assigned_dept" class="form-control">
              <option value="">Select Department</option>
              <?php foreach($offices as $office): ?>
              <option value="<?= htmlspecialchars($office['office_name']) ?>"><?= htmlspecialchars($office['office_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" name="btn_add_instructor" class="btn btn-primary px-4 shadow-sm"><i class="fas fa-save mr-2"></i>Save Personnel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- PERSONNEL LIST -->
<div class="card card-primary card-outline shadow-sm">
<div class="card-header"><h3 class="card-title">Personnel List</h3></div>
<div class="card-body table-responsive">
<table id="table_personnel" class="table table-bordered table-striped table-hover">
<thead>
<tr>
<th>ID</th>
<th>Full Name</th>
<th>Contact</th>
<th>Email</th>
<th>Assigned Office</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach($instructors as $inst): ?>
<tr>
<td><?= $inst['id'] ?></td>
<td><?= htmlspecialchars($inst['fullname']) ?></td>
<td><?= htmlspecialchars($inst['contact']) ?></td>
<td><?= htmlspecialchars($inst['email']) ?></td>
<td>
    <?php 
    // Show identification dept if available, or OIC office if assigned
    $dept = $inst['assigned_dept'] ?? ''; 
    $oic = $inst['oic_office'] ?? '';
    
    if(!empty($dept)){
        echo htmlspecialchars($dept);
    } elseif(!empty($oic)){
        echo "OIC: " . htmlspecialchars($oic);
    } else {
        echo "-";
    }
    ?>
</td>
<td>
<a href="instructor_edit.php?id=<?= $inst['id'] ?>" class="btn btn-sm btn-info">Edit</a>
<a href="#" class="btn btn-sm btn-danger deleteBtn" data-id="<?= $inst['id'] ?>">Delete</a>
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

<?php include_once "footer.php"; ?>

<script src="../plugins/sweetalert2/sweetalert2.all.min.js"></script>
<script>
$(document).ready(function() {
    $('#table_personnel').DataTable({
        "order": [[1, "asc"]],
        "responsive": true
    });
});

// Delete direct
$(document).on('click', '.deleteBtn', function(e) {
    e.preventDefault();
    let id = $(this).data('id');
    // Immediate redirect as requested, bypassing confirmation.
    window.location.href = 'instructor.php?delete=' + id;
});

// Remove Confirm on Save/Update
$('form').on('submit', function(e) {
    // Let the form submit naturally without confirmation as requested.
    return true; 
});
</script>
<script>
<?php if(isset($_SESSION['instructor_added'])): ?>
Swal.fire({icon:'success', title:'Personnel Added!', text:'New personnel successfully added.', showConfirmButton: false, timer: 2000});
<?php unset($_SESSION['instructor_added']); endif; ?>

<?php if(isset($_SESSION['instructor_exists'])): ?>
Swal.fire({icon:'error', title:'Already Exists!', text:'This personnel is already registered.', showConfirmButton: false, timer: 2000});
<?php unset($_SESSION['instructor_exists']); endif; ?>

<?php if(isset($_SESSION['status'])): ?>
Swal.fire({icon:'<?= $_SESSION['status_code'] ?>', title:'<?= $_SESSION['status'] ?>', showConfirmButton: false, timer: 2000});
<?php unset($_SESSION['status']); unset($_SESSION['status_code']); endif; ?>

<?php if(isset($_SESSION['instructor_deleted'])): ?>
Swal.fire({icon:'success', title:'Personnel Deleted!', text:'Personnel successfully deleted.', showConfirmButton: false, timer: 2000});
<?php unset($_SESSION['instructor_deleted']); endif; ?>

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

// Full Name mask (Letters and spaces only)
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
</script>

<?php ob_end_flush(); // Flush output buffering ?>