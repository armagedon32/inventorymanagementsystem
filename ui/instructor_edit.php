<?php
ob_start();
include_once 'connectdb.php';
session_start();

// Only Admin access
if ($_SESSION['useremail'] == "" || ($_SESSION['role'] == "")) {
    header('location:../index.php');
    exit;
}

$id = $_GET['id'] ?? null;
if(!$id){
    header('location:instructor.php');
    exit;
}

// Fetch instructor details
try {
    $select = $pdo->prepare("SELECT * FROM tbl_instructors WHERE id=:id");
    $select->execute([':id'=>$id]);
    $row = $select->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback if assigned_dept column is missing
    $select = $pdo->prepare("SELECT id, fullname, contact, email FROM tbl_instructors WHERE id=:id");
    $select->execute([':id'=>$id]);
    $row = $select->fetch(PDO::FETCH_ASSOC);
}

if(!$row){
    header('location:instructor.php');
    exit;
}

// Fetch current assigned dept
$current_dept = $row['assigned_dept'] ?? '';

// Handle Update
if(isset($_POST['btnupdate'])){
    $fullname = trim($_POST['fullname']);
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    $assigned_dept = trim($_POST['assigned_dept']); // Identification only

    if(!empty($fullname)){
        try {
            // Update instructor with identification dept
            $update = $pdo->prepare("UPDATE tbl_instructors SET fullname=:name, contact=:contact, email=:email, assigned_dept=:dept WHERE id=:id");
            $update->execute([
                ':name'=>$fullname,
                ':contact'=>$contact,
                ':email'=>$email,
                ':dept'=>$assigned_dept,
                ':id'=>$id
            ]);
        } catch (PDOException $e) {
            // Fallback if column missing
            $update = $pdo->prepare("UPDATE tbl_instructors SET fullname=:name, contact=:contact, email=:email WHERE id=:id");
            $update->execute([
                ':name'=>$fullname,
                ':contact'=>$contact,
                ':email'=>$email,
                ':id'=>$id
            ]);
        }

        // Log activity
        logActivity($pdo, "Updated Personnel: " . $fullname);

        $_SESSION['status'] = "Personnel Updated Successfully";
        $_SESSION['status_code'] = "success";
        header("Location: instructor.php");
        exit;
    }
}

// Load all offices for dropdown
$offices = $pdo->query("SELECT * FROM tbl_office WHERE parent_id IS NOT NULL AND parent_id != 0 AND is_archived = 0 ORDER BY office_name ASC")->fetchAll(PDO::FETCH_ASSOC);

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
                    <h1>Edit Personnel</h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-info card-outline shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-edit mr-2"></i>Edit Personnel Details</h3>
                </div>
                <form action="" method="post">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" class="form-control name-mask" name="fullname" value="<?= htmlspecialchars($row['fullname']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Contact Number</label>
                                    <input type="text" class="form-control contact-mask" name="contact" value="<?= htmlspecialchars($row['contact']) ?>" maxlength="11">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email Address</label>
                                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($row['email']) ?>">
                                </div>
                                <div class="form-group">
                                    <label>Assigned Department (Identification Only)</label>
                                    <select name="assigned_dept" class="form-control">
                                        <option value="">-- No Department --</option>
                                        <?php foreach($offices as $off): ?>
                                            <option value="<?= htmlspecialchars($off['office_name']) ?>" <?= ($off['office_name'] == $current_dept) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($off['office_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light text-right">
                        <a href="javascript:history.back()" class="btn btn-secondary">Cancel</a>
                        <button type="submit" name="btnupdate" class="btn btn-info px-4 shadow-sm"><i class="fas fa-save mr-2"></i>Update Personnel</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<script src="../plugins/sweetalert2/sweetalert2.all.min.js"></script>
<script>
$(document).on('input', '.contact-mask', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
    if (this.value.length > 11) {
        this.value = this.value.slice(0, 11);
    }
});

// Full Name mask (Letters and spaces only)
$(document).on('keypress', '.name-mask', function(e) {
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

<?php include_once "footer.php"; ?>
