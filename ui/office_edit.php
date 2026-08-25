<?php
include_once 'connectdb.php';
session_start();

// ======================
// Redirect if user not allowed
// ======================
if ($_SESSION['useremail'] == "" || $_SESSION['role'] == "User") {
    header('location:../index.php');
    exit;
}

// ======================
// Handle Update
// ======================
if (isset($_POST['btn_update']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $office_name = $_POST['office_name'];
    $address     = $_POST['address'];
    $contact     = $_POST['contact'];
    $max_capacity = $_POST['max_capacity'];

    $update = $pdo->prepare("UPDATE tbl_office SET office_name=:name, address=:address, contact=:contact, max_capacity=:capacity WHERE id=:id");
    $update->bindParam(':name', $office_name);
    $update->bindParam(':address', $address);
    $update->bindParam(':contact', $contact);
    $update->bindParam(':capacity', $max_capacity);
    $update->bindParam(':id', $id);

    if ($update->execute()) {
        // Log activity
        $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
        $stmtLog->execute([$_SESSION['userid'], "Updated Office Details: " . $office_name]);

        $_SESSION['office_updated'] = true;
    } else {
        $_SESSION['office_update_error'] = true;
    }

    // Redirect to same page to prevent resubmission and allow SweetAlert
    header("Location: office_edit.php?id=$id");
    exit;
}

// ======================
// Load Office Info
// ======================
if (!isset($_GET['id'])) {
    echo "<script>alert('No office selected'); window.location='office.php';</script>";
    exit;
}

$id = $_GET['id'];
$select = $pdo->prepare("SELECT * FROM tbl_office WHERE id=:id");
$select->bindParam(':id', $id);
$select->execute();
$row = $select->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo "<script>alert('Office not found!'); window.location='office.php';</script>";
    exit;
}

$office_name = $row['office_name'];
$address     = $row['address'];
$contact     = $row['contact'];
$max_capacity = $row['max_capacity'];

// ======================
// Include Header AFTER redirect logic
// ======================
include_once "header.php";
?>

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="../plugins/sweetalert2/sweetalert2.min.css">

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">
                        <a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
                        Edit Office
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Update Office Information</h3>
                </div>

                <form method="POST">
                    <div class="card-body">
                        <div class="form-group">
                            <label>Office Name</label>
                            <input type="text" name="office_name" class="form-control name-mask" value="<?php echo htmlspecialchars($office_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Officer In Charge</label>
                            <input type="text" name="address" class="form-control name-mask" value="<?php echo htmlspecialchars($address); ?>">
                        </div>
                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="text" name="contact" class="form-control contact-mask" value="<?php echo htmlspecialchars($contact); ?>" maxlength="11" placeholder="09xxxxxxxxx">
                        </div>
                        <div class="form-group">
                            <label>Max Capacity</label>
                            <input type="text" name="max_capacity" class="form-control number-mask" value="<?php echo htmlspecialchars($max_capacity); ?>" maxlength="4" placeholder="Enter capacity">
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" name="btn_update" class="btn btn-success">Update Office</button>
                    </div>
                </form>
            </div>

        </div>
    </section>
</div>

<!-- SweetAlert2 JS -->
<script src="../plugins/sweetalert2/sweetalert2.all.min.js"></script>
<script>
<?php if(isset($_SESSION['office_updated'])): ?>
Swal.fire({
    icon: 'success',
    title: 'Updated!',
    text: 'Office information updated successfully.',
    showConfirmButton: false,
    timer: 2000
});
<?php unset($_SESSION['office_updated']); endif; ?>

<?php if(isset($_SESSION['office_update_error'])): ?>
Swal.fire({
    icon: 'error',
    title: 'Error',
    text: 'Failed to update office information.',
    confirmButtonText: 'OK'
});
<?php unset($_SESSION['office_update_error']); endif; ?>

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
</script>

<?php include_once "footer.php"; ?>