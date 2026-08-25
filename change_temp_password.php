<?php
include_once "ui/connectdb.php";
session_start();

$error = "";
$success = "";

if (isset($_POST['btn_update_temp_password'])) {
    $email = $_POST['txt_email'];
    $temp_pass = $_POST['txt_temp_password'];
    $new_pass = $_POST['txt_new_password'];
    $confirm_pass = $_POST['txt_confirm_password'];

    // Find user by email or username
    $select = $pdo->prepare("SELECT * FROM tbl_user WHERE useremail = :email OR username = :username");
    $select->execute([':email' => $email, ':username' => $email]);
    $row = $select->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Verify if it's actually a temporary password (must_change_password flag must be 1)
        if ($row['must_change_password'] == 0) {
            $error = "This account does not have a temporary password or has already been updated.";
        } elseif ($temp_pass !== $row['userpassword']) {
            // In this system, passwords seem to be stored in plain text or handled simply in index.php
            // (based on my observation of index.php's login check: $password==$row['userpassword'])
            $error = "Incorrect temporary password.";
        } elseif (strlen($new_pass) < 8) {
            $error = "New password must be at least 8 characters long.";
        } elseif ($new_pass === $temp_pass) {
            $error = "New password cannot be the same as the temporary password.";
        } elseif ($new_pass !== $confirm_pass) {
            $error = "New passwords do not match.";
        } else {
            // Update password and clear flag
            $update = $pdo->prepare("UPDATE tbl_user SET userpassword = :pass, must_change_password = 0 WHERE userid = :id");
            if ($update->execute([':pass' => $new_pass, ':id' => $row['userid']])) {
                
                logActivity($pdo, "Updated temporary password for: " . $row['useremail']);
                
                $_SESSION['status'] = "Password updated successfully! You can now login.";
                $_SESSION['status_code'] = "success";
                header("Location: index.php");
                exit();
            } else {
                $error = "Failed to update password. Please try again.";
            }
        }
    } else {
        $error = "Account not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Update Temp Password | PSO</title>

<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">

<style>
body.login-page {
    background: linear-gradient(rgba(0,0,0,0.55),rgba(0,0,0,0.55)), url('dist/img/OIP.jpg');
    background-size: cover;
    background-position: center;
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Segoe UI', sans-serif;
}
.login-box { width: 100%; max-width: 450px; }
.card {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(12px);
    border-radius: 20px;
    padding-top: 80px;
    position: relative;
    border: none;
    box-shadow: 0 15px 40px rgba(0,0,0,0.3);
}
.logo-floating {
    width: 140px; height: 140px; border-radius: 50%; background: white; padding: 5px;
    position: absolute; top: -70px; left: 50%; transform: translateX(-50%);
    box-shadow: 0 10px 25px rgba(0,0,0,0.4);
}
.card-header a { color: white; font-size: 18px; }
.form-control {
    background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
    color: white; border-radius: 10px 0 0 10px; height: 45px;
}
.form-control::placeholder { color: rgba(255,255,255,0.7); }
.form-control:focus {
    background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4);
    box-shadow: none; color: white;
}
.input-group-text {
    background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
    border-left: none; color: white; border-radius: 0 10px 10px 0; width: 45px; justify-content: center;
}
.btn-custom {
    background: linear-gradient(45deg, #7ed6a5, #5cb884); border: none; border-radius: 10px;
    font-weight: 600; color: #fff; height: 50px; transition: 0.3s ease;
}
.btn-custom:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(92, 184, 132, 0.4); }
</style>
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="card shadow-lg">
        <img src="dist/img/logo.png" class="logo-floating">
        <div class="card-header text-center pb-3">
            <a href="#"><b>PASSWORD UPDATE</b><br><span style="font-weight:300; opacity:0.9; font-size:0.9rem;">Change your temporary password</span></a>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small text-center"><?= $error ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group mb-3">
                    <label class="text-white small">Email or Username</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="txt_email" placeholder="Enter Email or Username" required>
                        <div class="input-group-append"><div class="input-group-text"><span class="fas fa-user"></span></div></div>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label class="text-white small">Temporary Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="txt_temp_password" placeholder="Enter Temp Password" required>
                        <div class="input-group-append"><div class="input-group-text"><span class="fas fa-key"></span></div></div>
                    </div>
                </div>
                <hr style="border-top: 1px solid rgba(255,255,255,0.1);">
                <div class="form-group mb-3">
                    <label class="text-white small">New Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="txt_new_password" placeholder="New Password (min 8 chars)" required minlength="8">
                        <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
                    </div>
                </div>
                <div class="form-group mb-4">
                    <label class="text-white small">Confirm New Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="txt_confirm_password" placeholder="Confirm New Password" required minlength="8">
                        <div class="input-group-append"><div class="input-group-text"><span class="fas fa-check-double"></span></div></div>
                    </div>
                </div>
                <button type="submit" name="btn_update_temp_password" class="btn btn-custom btn-block">UPDATE PASSWORD</button>
            </form>
            
            <a href="index.php" class="text-white text-center d-block mt-3 small" style="opacity:0.8; text-decoration:none;"><i class="fas fa-arrow-left mr-1"></i> Back to Login</a>
        </div>
    </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
