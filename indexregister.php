<?php
include_once 'ui/connectdb.php';
session_start();

if (isset($_POST['btn_register'])) {

  $fullname = $_POST['txt_fname'];
  $username = $_POST['txt_username'];
  $useremail = $_POST['txt_email'];
  $userpassword = $_POST['txt_password'];
  $userrole = $_POST['txtselect_option'];
    $recovery_question = $_POST['txt_recovery_question'] ?? null;
    $recovery_answer = $_POST['txt_recovery_answer'] ?? null;

    $select = $pdo->prepare("SELECT useremail FROM tbl_user WHERE useremail=:useremail");
    $select->bindParam(':useremail', $useremail);
    $select->execute();

    if ($select->rowCount() > 0) {
      $_SESSION['status'] = "Account Not Registered. Email already exists. Create Account with a New Email";
      $_SESSION['status_code'] = "warning";
    } else {
      $insert = $pdo->prepare("INSERT INTO tbl_user (fullname, username, useremail, userpassword, role, recovery_question, recovery_answer) VALUES (:fname, :name, :email, :password, :role, :recovery_question, :recovery_answer)");
      $insert->bindParam(':fname', $fullname);
      $insert->bindParam(':name', $username);
      $insert->bindParam(':email', $useremail);
      $insert->bindParam(':password', $userpassword);
      $insert->bindParam(':role', $userrole);
      $insert->bindParam(':recovery_question', $recovery_question);
      $insert->bindParam(':recovery_answer', $recovery_answer);

    if ($insert->execute()) {
      $_SESSION['status'] = "Account Register Successfully";
      $_SESSION['status_code'] = "success";

      header('refresh:1;ui/dashregister.php');
    } else {
      $_SESSION['status'] = "Account Registration Failed";
      $_SESSION['status_code'] = "error";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>PROPERTY Custodian| Registration Page</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">

  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
  <!-- Toastr -->
  <link rel="stylesheet" href="plugins/toastr/toastr.min.css">

  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <!-- Google Font: Source Sans Pro -->
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
</head>

<body class="hold-transition login-page">
  <div class="login-box">
    <!-- /.login-logo -->
    <div class="card card-outline card-primary">
      <div class="card-header text-center">
        <a href="../../index2.html" class="h1"><b>PROPERTY</b><i>Custodian</i></a>
      </div>
      <div class="card-body">
        <p class="login-box-msg">Register a new membership</p>

        <form action="" method="post">
          <div class="form-group">


            <div class="form-group">
              <label for="exampleInputEmail1">Full Name</label>
              <input type="text" class="form-control" placeholder="Enter Full Name" name="txt_fname" required>
            </div>

            <div class="form-group">
              <label for="exampleInputEmail1">User Name</label>
              <input type="text" class="form-control" placeholder="Enter User Name" name="txt_username" required>
            </div>

            <div class="form-group">
              <label for="exampleInputEmail1">Email address</label>
              <input type="email" class="form-control" placeholder="Enter email" name="txt_email" required>
            </div>


            <div class="form-group">
              <label for="exampleInputPassword1">Password</label>
              <input type="password" class="form-control" placeholder="Enter Password" name="txt_password" required>
            </div>

            <div class="form-group">
              <label>Role</label>
              <select class="form-control" name="txtselect_option" id="role_select" required>
                <option value="" disabled selected>Select Role</option>
                <option>Admin</option>
                <option>User</option>
              </select>
            </div>

            <!-- Recovery fields for Admin -->
            <div id="recovery_fields" style="display: none;">
              <div class="form-group">
                <label>Recovery Question (for Forgot Password)</label>
                <div class="input-group">
                  <input type="text" class="form-control" placeholder="Enter Recovery Question" name="txt_recovery_question" id="recovery_question">
                  <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary" onclick="shuffleQuestion('recovery_question')" title="Shuffle Question">
                      <i class="fas fa-dice"></i>
                    </button>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label>Recovery Answer</label>
                <input type="text" class="form-control" placeholder="Enter Recovery Answer" name="txt_recovery_answer" id="recovery_answer">
              </div>
            </div>

            <div class="row">
              <div class="col-8">
                <div class="icheck-primary">
                  <a href="index.php">Already have an Account</a>
                </div>
              </div>
              <!-- /.col -->
              <div class="col-4">
                <button type="submit" class="btn btn-primary btn-block" name="btn_register">Register</button>
              </div>
              <!-- /.col -->
            </div>




          </div>
          <!-- /.form-box -->
      </div><!-- /.card -->
    </div>
    <!-- /.register-box -->

    <script src="plugins/jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="dist/js/adminlte.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="plugins/sweetalert2/sweetalert2.min.js"></script>

    <script>
      const recoveryQuestions = [
        "What is the name of your first pet?",
        "Where was your mother born?",
        "What was your favorite food as a child?",
        "What is the name of your first teacher?",
        "What is your favorite color?",
        "Where did you attend elementary school?",
        "What is the name of your favorite cousin?",
        "What was the first car you drove?",
        "What is your favorite movie?",
        "Where did you first meet your spouse/partner?"
      ];

      function shuffleQuestion(inputId) {
        const randomIndex = Math.floor(Math.random() * recoveryQuestions.length);
        document.getElementById(inputId).value = recoveryQuestions[randomIndex];
      }

      $(document).ready(function() {
        $('#role_select').on('change', function() {
          if (this.value === 'Admin') {
            $('#recovery_fields').show();
            $('#recovery_question').attr('required', true);
            if ($('#recovery_question').val() === "") shuffleQuestion('recovery_question');
            $('#recovery_answer').attr('required', true);
          } else {
            $('#recovery_fields').hide();
            $('#recovery_question').removeAttr('required');
            $('#recovery_answer').removeAttr('required');
          }
        });
      });
    </script>

</body>

</html>

<?php
if (isset($_SESSION['status']) && $_SESSION['status'] != '') {

?>
  <script>
    $(function() {
      Swal.fire({
        icon: '<?php echo $_SESSION['status_code']; ?>',
        title: '<?php echo $_SESSION['status']; ?>',
        showConfirmButton: false,
        timer: 5000
      })
    });
  </script>

<?php
  unset($_SESSION['status']);
}

?>