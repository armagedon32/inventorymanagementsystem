<?php
include_once "ui/connectdb.php";
session_start();

// Ensure user is in the "must change password" state
if (!isset($_SESSION['userid']) || !isset($_SESSION['must_change_password'])) {
    header("Location: index.php");
    exit();
}

$error = "";
if (isset($_POST['btn_update_password'])) {
    $new_pass = $_POST['txt_new_password'];
    $confirm_pass = $_POST['txt_confirm_password'];

    if (strlen($new_pass) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "Passwords do not match.";
    } else {
        // Update password and clear flag
        $stmt = $pdo->prepare("UPDATE tbl_user SET userpassword = :pass, must_change_password = 0 WHERE userid = :id");
        if ($stmt->execute([':pass' => $new_pass, ':id' => $_SESSION['userid']])) {
            
            // Log activity
            logActivity($pdo, "Changed password after reset");

            // Clear special session flag and redirect to dashboard based on role
            unset($_SESSION['must_change_password']);
            
            $_SESSION['status'] = "Password updated successfully! You can now access your dashboard.";
            $_SESSION['status_code'] = "success";

            if ($_SESSION['role'] === "Admin") {
                header("Location: ui/dashboard.php");
            } else {
                header("Location: ui/dashboard.php");
            }
            exit();
        } else {
            $error = "Failed to update password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reset Password | PSO</title>

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
.login-box { width: 100%; max-width: 420px; }
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
    width: 150px; height: 150px; border-radius: 50%; background: white; padding: 5px;
    position: absolute; top: -75px; left: 50%; transform: translateX(-50%);
    box-shadow: 0 10px 25px rgba(0,0,0,0.4);
}
.card-header a { color: white; font-size: 18px; }
.form-control {
    background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
    color: white; border-radius: 10px 0 0 10px; height: 50px;
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
    <div class="card">
        <img src="dist/img/logo.png" class="logo-floating">
        <div class="card-header text-center pb-4">
            <a href="#"><b>SECURE YOUR ACCOUNT</b><br><span style="font-weight:300; opacity:0.9;">Update Password</span></a>
        </div>
        <div class="card-body">
            <p class="text-white text-center mb-4 small">You are using a temporary password. Please set a new password to continue.</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small"><?= $error ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="input-group mb-3">
                    <input type="password" class="form-control" name="txt_new_password" placeholder="New Password" required minlength="8">
                    <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
                </div>
                <div class="input-group mb-4">
                    <input type="password" class="form-control" name="txt_confirm_password" placeholder="Confirm Password" required minlength="8">
                    <div class="input-group-append"><div class="input-group-text"><span class="fas fa-check-double"></span></div></div>
                </div>
                <button type="submit" name="btn_update_password" class="btn btn-custom btn-block">UPDATE & LOGIN</button>
            </form>
            
            <a href="ui/logout.php" class="text-white text-center d-block mt-3 small" style="opacity:0.7;">Cancel and Logout</a>
        </div>
    </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/sweetalert2/sweetalert2.all.min.js"></script>
</body>
</html>
