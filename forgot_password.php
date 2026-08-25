<?php
include_once "ui/connectdb.php";
session_start();

$user_found = false;
$recovery_question = "";
$user_id = null;
$user_role = "";
$request_pending = false;

// Stage 1: Find User
if (isset($_POST['btn_find_user'])) {
    $user_input = trim($_POST['txt_user_input']);
    
    $select = $pdo->prepare("SELECT * FROM tbl_user WHERE (useremail = :user OR username = :user OR fullname = :user) AND is_archived = 0 LIMIT 1");
    $select->bindParam(':user', $user_input);
    $select->execute();
    $row = $select->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $user_id = $row['userid'];
        $user_role = $row['role'];

        if ($user_role === 'Admin') {
            if ($row['recovery_question'] && $row['recovery_answer']) {
                $user_found = true;
                $recovery_question = $row['recovery_question'];
                $_SESSION['temp_user_id'] = $row['userid'];
            } else {
                $_SESSION['status'] = "Admin account has no recovery question set.";
                $_SESSION['status_code'] = "warning";
            }
        } else {
            // Intern or Student Assistant: Check for pending request
            $check = $pdo->prepare("SELECT * FROM password_reset_requests WHERE user_id = :uid AND status = 'pending'");
            $check->execute([':uid' => $user_id]);
            if ($check->rowCount() > 0) {
                $_SESSION['status'] = "You already have a pending reset request. Please wait for Admin approval.";
                $_SESSION['status_code'] = "info";
            } else {
                // Check if there's a completed request that hasn't been shown yet (optional logic)
                $_SESSION['temp_user_id'] = $user_id;
                $_SESSION['temp_user_fullname'] = $row['fullname'];
                $request_pending = true;
            }
        }
    } else {
        $_SESSION['status'] = "Account not found.";
        $_SESSION['status_code'] = "error";
    }
}

// Stage 3: Request Reset (for Intern/SA)
if (isset($_POST['btn_request_reset'])) {
    $user_id = $_SESSION['temp_user_id'] ?? null;
    $fullname = $_SESSION['temp_user_fullname'] ?? "User";

    if ($user_id) {
        $insert = $pdo->prepare("INSERT INTO password_reset_requests (user_id) VALUES (:uid)");
        if ($insert->execute([':uid' => $user_id])) {
            $_SESSION['status'] = "Reset request sent to Admin. Please contact your Admin for the new password, then use it to login normally. You will be prompted to change it after login.";
            $_SESSION['status_code'] = "success";
            unset($_SESSION['temp_user_id']);
            unset($_SESSION['temp_user_fullname']);
        } else {
            $_SESSION['status'] = "Failed to send request.";
            $_SESSION['status_code'] = "error";
        }
    }
}

// Stage 2: Verify Answer
if (isset($_POST['btn_verify_answer'])) {
    $answer = trim($_POST['txt_recovery_answer']);
    $user_id = $_SESSION['temp_user_id'] ?? null;

    if ($user_id) {
        $select = $pdo->prepare("SELECT * FROM tbl_user WHERE userid = :id LIMIT 1");
        $select->bindParam(':id', $user_id);
        $select->execute();
        $row = $select->fetch(PDO::FETCH_ASSOC);

        if ($row && strtolower($answer) === strtolower($row['recovery_answer'])) {
            // Generate a random temporary password
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%";
            $temp_password = "";
            for ($i = 0; $i < 8; $i++) {
                $temp_password .= $chars[rand(0, strlen($chars) - 1)];
            }

            // Update user password in database and set flag
            $update = $pdo->prepare("UPDATE tbl_user SET userpassword = :pass, must_change_password = 1 WHERE userid = :id");
            if ($update->execute([':pass' => $temp_password, ':id' => $user_id])) {
                // Log activity
                $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
                $stmtLog->execute([$user_id, "Admin Reset Password via Recovery Question"]);

                $_SESSION['temp_password_shown'] = $temp_password;
                // Don't set $_SESSION['status'] here to avoid double alert in index.php
                
                unset($_SESSION['temp_user_id']);
                
                header("Location: index.php");
                exit();
            } else {
                $_SESSION['status'] = "Failed to update password. Please try again.";
                $_SESSION['status_code'] = "error";
            }
        } else {
            $_SESSION['status'] = "Incorrect answer!"; 
            $_SESSION['status_code'] = "error";
            
            // Re-show the question if answer was wrong
            $user_found = true;
            $recovery_question = $row['recovery_question'] ?? "";
        }
    } else {
        $_SESSION['status'] = "Session expired. Please try again.";
        $_SESSION['status_code'] = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Forgot Password | PSO</title>

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
.back-to-login { display: block; text-align: center; margin-top: 15px; color: rgba(255,255,255,0.8); font-size: 14px; }
.back-to-login:hover { color: white; text-decoration: none; }
</style>
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="card">
        <img src="dist/img/logo.png" class="logo-floating">
        <div class="card-header text-center pb-4">
            <a href="#"><b>FORGOT PASSWORD</b><br><span style="font-weight:300; opacity:0.9;">Admin Recovery</span></a>
        </div>
        <div class="card-body">
            
            <?php if (!$user_found && !$request_pending): ?>
                <p class="text-white text-center mb-4 small">Enter your Email or Username to reset your password.</p>
                <form method="post">
                    <div class="input-group mb-4">
                        <input type="text" class="form-control" name="txt_user_input" placeholder="Email / Username" required>
                        <div class="input-group-append"><div class="input-group-text"><span class="fas fa-user"></span></div></div>
                    </div>
                    <button type="submit" name="btn_find_user" class="btn btn-custom btn-block">FIND ACCOUNT</button>
                    <a href="index.php" class="back-to-login"><i class="fas fa-arrow-left mr-2"></i>Back to Login</a>
                </form>
            <?php elseif ($user_found): ?>
                <p class="text-white text-center mb-4 small">Answer your recovery question to login.</p>
                <form method="post">
                    <div class="form-group mb-3">
                        <label class="text-white">Question:</label>
                        <p class="text-white font-weight-bold" style="background: rgba(255,255,255,0.1); padding: 10px; border-radius: 5px;">
                            <?php echo htmlspecialchars($recovery_question); ?>
                        </p>
                    </div>
                    <div class="input-group mb-4">
                        <input type="text" class="form-control" name="txt_recovery_answer" placeholder="Your Answer" required autofocus>
                        <div class="input-group-append"><div class="input-group-text"><span class="fas fa-key"></span></div></div>
                    </div>
                    <button type="submit" name="btn_verify_answer" class="btn btn-custom btn-block">LOGIN</button>
                    <a href="forgot_password.php" class="back-to-login"><i class="fas fa-arrow-left mr-2"></i>Try again</a>
                </form>
            <?php elseif ($request_pending): ?>
                <div class="text-center">
                    <p class="text-white mb-4">Hello <b><?php echo $_SESSION['temp_user_fullname']; ?></b>,</p>
                    <p class="text-white small mb-4">You are an <b>Intern / Student Assistant</b>. To reset your password, you must send a request to the Admin. The Admin will generate a new password for you.</p>
                    <form method="post">
                        <button type="submit" name="btn_request_reset" class="btn btn-warning btn-block mb-3">SEND REQUEST TO ADMIN</button>
                        <a href="forgot_password.php" class="back-to-login"><i class="fas fa-arrow-left mr-2"></i>Back</a>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/sweetalert2/sweetalert2.min.js"></script>

<?php if(isset($_SESSION['status'])) { ?>
<script>
Swal.fire({
    icon: '<?php echo $_SESSION['status_code']; ?>',
    title: '<?php echo $_SESSION['status']; ?>',
    showConfirmButton: false,
    timer: 3000
});
</script>
<?php unset($_SESSION['status']); } ?>
</body>
</html>
