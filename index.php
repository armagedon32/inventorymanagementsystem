<?php
include_once "ui/connectdb.php";
session_start();

if (isset($_POST['btn_login'])) {

    $user_input = trim($_POST['txt_fullname_email_username']);
    $password   = $_POST['txt_password'];
    $pin_input  = $_POST['txt_pin'] ?? '';

    $select = $pdo->prepare("
        SELECT * FROM tbl_user 
        WHERE (useremail = :user 
        OR username = :user 
        OR fullname = :user)
        AND is_archived = 0
        LIMIT 1
    ");
    $select->bindParam(':user', $user_input, PDO::PARAM_STR);
    $select->execute();
    $row = $select->fetch(PDO::FETCH_ASSOC);

    // Check if there are any active users in the database
    $stmt_active_users = $pdo->prepare("SELECT COUNT(*) FROM tbl_user");
    $stmt_active_users->execute();
    $active_user_count = $stmt_active_users->fetchColumn();

    // Hardcoded default admin credentials (SECURITY RISK: DO NOT USE IN PRODUCTION)
    $default_admin_username = 'superadmin';
    $default_admin_password = 'admin'; // This should be hashed in a real application
    $default_admin_pin      = '2025'; // New PIN requirement

    // If no active users and provided credentials match default admin, log in as default admin
    if ($active_user_count == 0 && $user_input === $default_admin_username && $password === $default_admin_password) {
        
        // Check PIN if it's the default admin
        if ($pin_input !== $default_admin_pin) {
            $_SESSION['status'] = "Incorrect PIN for Default Admin.";
            $_SESSION['status_code'] = "error";
        } else {
            $_SESSION['userid']     = 0; // A special ID for the default admin
            $_SESSION['fullname']   = 'Super Admin';
            $_SESSION['username']   = $default_admin_username;
            $_SESSION['useremail']  = 'superadmin@example.com';
            $_SESSION['role']       = 'Admin';
            $_SESSION['photo']      = 'default_admin.png'; // Placeholder photo

            // Log activity for default admin
            $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
            $stmtLog->execute([0, "Default Admin Logged In"]);

            $_SESSION['login_success'] = true;
            header("Location: ui/dashboard.php");
            exit();
        }
    }

    if ($row) {

        if ($password === $row['userpassword']) {

            $_SESSION['userid']     = $row['userid'];
            $_SESSION['fullname']   = $row['fullname'];
            $_SESSION['username']   = $row['username'];
            $_SESSION['useremail']  = $row['useremail'];
            $_SESSION['role']       = $row['role'];
            $_SESSION['photo']      = $row['photo'];

            // Check if password change is required
            if ($row['must_change_password'] == 1) {
                $_SESSION['must_change_password'] = true;
                header("Location: reset_change_password.php");
                exit();
            }

            // Log activity with role identification
            $userRole = $row['role'];
            $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
            $stmtLog->execute([$row['userid'], "$userRole Logged In"]);

            $_SESSION['login_success'] = true;

            if ($row['role'] === "Admin") {

                header("Location: ui/dashboard.php");
                exit();

            } elseif ($row['role'] === "Intern" || $row['role'] === "Student Assistant") {

                header("Location: ui/dashboard.php");
                exit();

            } else {

                $_SESSION['status'] = "Role not recognized.";
                $_SESSION['status_code'] = "error";

            }

        } else {

            $_SESSION['status'] = "Incorrect Password.";
            $_SESSION['status_code'] = "error";

        }

    } else {

        $_SESSION['status'] = "User not found.";
        $_SESSION['status_code'] = "error";

    }

}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>PROPERTY AND SUPPLIES OFFICE | Login</title>

<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
<link rel="stylesheet" href="plugins/toastr/toastr.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">

<style>

body.login-page{
    background: linear-gradient(rgba(0,0,0,0.55),rgba(0,0,0,0.55)),
    url('dist/img/OIP.jpg');
    background-size: cover;
    background-position:center;
    background-attachment: fixed; /* Keep background static */
    height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    font-family: 'Segoe UI', sans-serif;
    overflow: hidden; /* Prevent body jump when modal opens */
}

.swal2-shown, .swal2-height-auto {
    padding-right: 0 !important; /* Prevent scrollbar padding jump */
    overflow: hidden !important;
}

body.swal2-height-auto {
    height: 100vh !important; /* Force full height to prevent jump */
}

.login-box{
    width:100%;
    max-width:420px;
}

.card{
    background:rgba(255,255,255,0.15);
    backdrop-filter:blur(12px);
    border-radius:20px;
    padding-top:80px;
    position:relative;
    border:none;
    box-shadow:0 15px 40px rgba(0,0,0,0.3);
}

.logo-floating{

    width:150px;
    height:150px;
    border-radius:50%;
    background:white;
    padding:5px;

    position:absolute;
    top:-75px;
    left:50%;
    transform:translateX(-50%);

    box-shadow:0 10px 25px rgba(0,0,0,0.4);
}

.card-header{
    border:none;
    background:transparent;
}

.card-header a{
    color:white;
    font-size:18px;
}

.card-header b{
    font-weight:600;
}

.form-control{
    background:rgba(255,255,255,0.1);
    border:1px solid rgba(255,255,255,0.2);
    color:white;
    border-radius:10px 0 0 10px;
    height:50px;
}

.form-control::placeholder{
    color:rgba(255,255,255,0.7);
}

.form-control:focus{
    background:rgba(255,255,255,0.2);
    border:1px solid rgba(255,255,255,0.4);
    box-shadow:none;
    color:white;
}

.input-group-text{
    background:rgba(255,255,255,0.1);
    border:1px solid rgba(255,255,255,0.2);
    border-left:none;
    color:white;
    border-radius:0 10px 10px 0;
    width:45px;
    justify-content:center;
}

.btn-login{
    background:linear-gradient(45deg, #7ed6a5, #5cb884);
    border:none;
    border-radius:10px;
    font-weight:600;
    color:#fff;
    height:50px;
    letter-spacing:1px;
    box-shadow:0 5px 15px rgba(92, 184, 132, 0.3);
    transition:0.3s ease;
}

.btn-login:hover{
    background:linear-gradient(45deg, #66c493, #4ea376);
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(92, 184, 132, 0.4);
}

.forgot-password{
    display:block;
    text-align:center;
    margin-top:15px;
    color:rgba(255,255,255,0.8);
    font-size:14px;
    transition:0.3s;
}

.forgot-password:hover{
    color:white;
    text-decoration:none;
}

.showpass{
    cursor:pointer;
}

</style>

</head>

<body class="hold-transition login-page">

<div class="login-box">

<div class="card">

<img src="dist/img/logo.png" class="logo-floating">

<div class="card-header text-center pb-4">

<a href="#">

<b style="font-size:22px; letter-spacing:1px;">PROPERTY AND SUPPLIES OFFICE</b><br>
<span style="font-weight:300; opacity:0.9;">Inventory Management System</span>

</a>

</div>

<div class="card-body">

<form method="post">

<div class="input-group mb-4">

<input type="text"
class="form-control"
name="txt_fullname_email_username"
placeholder="Email / Username"
required>

<div class="input-group-append">

<div class="input-group-text">
<span class="fas fa-user"></span>
</div>

</div>

</div>

<div class="input-group mb-4">

<input type="password"
class="form-control"
id="password"
name="txt_password"
placeholder="Password"
required>

<div class="input-group-append">

<div class="input-group-text showpass" onclick="togglePassword()">
<i class="fas fa-eye"></i>
</div>

</div>

</div>

<div id="pin_field" style="display:none;" class="input-group mb-4">

<input type="text"
class="form-control"
id="txt_pin"
name="txt_pin"
placeholder="Enter PIN (For Default Admin)"
maxlength="4">

<div class="input-group-append">

<div class="input-group-text">
<span class="fas fa-key"></span>
</div>

</div>

</div>

<div class="row">

<div class="col-12 text-center">

<button type="submit"
name="btn_login"
class="btn btn-login btn-block shadow-sm mb-3">

LOGIN

</button>

<div style="margin-top:15px;">
<a href="forgot_password.php" class="forgot-password" style="display:inline-block; margin-top:0;">
Forgot Password?
</a>
</div>

</div>

</div>

</form>

</div>

</div>

</div>


<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script src="plugins/sweetalert2/sweetalert2.min.js"></script>

<?php
 if (isset($_SESSION['temp_password_shown'])) {
 ?>
   <script>
     Swal.fire({
       title: 'Your Temporary Password',
       html: '<div style="background: #f8f9fa; border: 2px dashed #28a745; padding: 20px; border-radius: 10px; margin: 15px 0; position: relative;">' +
             '<span id="tempPassword" style="font-size: 2.5rem; font-family: monospace; color: #1b5e20; letter-spacing: 2px;">' +
             '<?php echo $_SESSION['temp_password_shown']; ?>' +
             '</span>' +
             '<button onclick="copyToClipboard()" class="btn btn-sm btn-outline-success" style="position: absolute; top: 5px; right: 5px;" title="Copy to Clipboard">' +
             '<i class="fas fa-copy"></i>' +
             '</button>' +
             '</div>' +
             '<p class="text-muted">Click the copy icon or manually copy this password.</p>',
       confirmButtonText: 'OK, I copied it',
       confirmButtonColor: '#28a745',
       allowOutsideClick: false,
       allowEscapeKey: false,
       heightAuto: false
     }).then((result) => {
       if (result.isConfirmed) {
         Swal.fire({
           icon: 'success',
           title: 'Password Reset Successful',
           text: 'You can now login with your temporary password.',
           timer: 3000,
           showConfirmButton: false,
           heightAuto: false
         });
       }
     });

     function copyToClipboard() {
        const passText = document.getElementById('tempPassword').innerText;
        navigator.clipboard.writeText(passText).then(() => {
          toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-center",
            "timeOut": "2000"
          };
          toastr.success('Password copied to clipboard!');
          
          // Close SweetAlert and stay on index
          setTimeout(() => {
            Swal.close();
          }, 500);
        }).catch(err => {
          console.error('Failed to copy: ', err);
        });
      }
   </script>
 <?php
   unset($_SESSION['temp_password_shown']);
 }
 ?>

<script>

function togglePassword(){

var pass = document.getElementById("password");

if(pass.type === "password"){
pass.type = "text";
}else{
pass.type = "password";
}

}

// Show PIN field only for default admin
document.querySelector('input[name="txt_fullname_email_username"]').addEventListener('input', function() {
    var pinField = document.getElementById('pin_field');
    var pinInput = document.getElementById('txt_pin');
    if (this.value === 'superadmin') {
        pinField.style.display = 'flex';
        pinInput.setAttribute('required', 'true');
    } else {
        pinField.style.display = 'none';
        pinInput.removeAttribute('required');
        pinInput.value = '';
    }
});

</script>

<?php if(isset($_SESSION['status']) && $_SESSION['status'] != '') { ?>

<script>
  $(function() {
    Swal.fire({
      icon: '<?php echo $_SESSION['status_code']; ?>',
      title: '<?php echo $_SESSION['status']; ?>',
      showConfirmButton: true,
      confirmButtonText: 'OK',
      timer: <?php echo ($_SESSION['status_code'] === 'success') ? 'null' : '5000'; ?>,
      heightAuto: false
    })
  });
</script>

<?php unset($_SESSION['status']); } ?>

</body>
</html>