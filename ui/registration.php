<?php
include_once 'connectdb.php';
session_start();

/* ================= SESSION CHECK ================= */
if ($_SESSION['useremail'] == "" || $_SESSION['role'] == "User") {
    header('location:../index.php');
    exit();
}

// ================= PASSWORD CHANGE CHECK =================
if (isset($_SESSION['must_change_password'])) {
    header('Location: ../reset_change_password.php');
    exit();
}

/* ================= DELETE USER ================= */
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    if (is_numeric($id)) {
        if ($id == $_SESSION['userid']) {
            $_SESSION['status'] = "You cannot delete your own account.";
            $_SESSION['status_code'] = "warning";
        } else {
            // Fetch name for logging
            $get_user = $pdo->prepare("SELECT fullname FROM tbl_user WHERE userid = :id");
            $get_user->execute([':id' => $id]);
            $user_to_delete = $get_user->fetchColumn();

            $delete = $pdo->prepare("UPDATE tbl_user SET is_archived = 1 WHERE userid = :id");
            $delete->bindParam(':id', $id);
            if ($delete->execute()) {
                logActivity($pdo, "Archived user: $user_to_delete (ID: $id)");
                $_SESSION['status'] = "Account archived successfully";
                $_SESSION['status_code'] = "success";
            } else {
                $_SESSION['status'] = "Account not archived";
                $_SESSION['status_code'] = "error";
            }
        }
    }
    header("Location: registration.php");
    exit();
}

/* ================= DELETE RESET REQUEST ================= */
if (isset($_GET['delete_reset_id'])) {
    $rid = $_GET['delete_reset_id'];
    $delete = $pdo->prepare("DELETE FROM password_reset_requests WHERE request_id = :rid");
    if ($delete->execute([':rid' => $rid])) {
        $_SESSION['status'] = "Request deleted.";
        $_SESSION['status_code'] = "success";
    }
    header("Location: registration.php");
    exit();
}

/* ================= SAVE USER ================= */
if (isset($_POST['btnsave'])) {
    $fullname     = trim($_POST['fname'] ?? '');
    $username     = trim($_POST['txtname'] ?? '');
    $useremail    = trim($_POST['txtemail'] ?? '');
    $contact      = trim($_POST['contact_number'] ?? '');
    $userpassword = $_POST['txtpassword'] ?? '';
    
    // Server-side validation for password length
    if (strlen($userpassword) < 8) {
        $_SESSION['status'] = "Password must be at least 8 characters long.";
        $_SESSION['status_code'] = "error";
        header("Location: registration.php");
        exit();
    }

    $userrole     = $_POST['txtselect_option'] ?? '';

    // Check if user already exists (fullname, username, or email)
    $check_user = $pdo->prepare("SELECT * FROM tbl_user WHERE (fullname = :fname OR username = :uname OR useremail = :email) AND is_archived = 0");
    $check_user->execute([':fname' => $fullname, ':uname' => $username, ':email' => $useremail]);
    $existing_user = $check_user->fetch(PDO::FETCH_ASSOC);

    if ($existing_user) {
        if ($existing_user['fullname'] == $fullname) {
            $_SESSION['status'] = "Full Name already exists!";
        } elseif ($existing_user['username'] == $username) {
            $_SESSION['status'] = "Username already exists!";
        } else {
            $_SESSION['status'] = "Email already exists!";
        }
        $_SESSION['status_code'] = "error";
        header("Location: registration.php");
        exit();
    }

    $course       = $_POST['course'] ?? null;
    $major        = $_POST['major'] ?? null;
    $year_level   = $_POST['year_level'] ?? null;
    $recovery_question = $_POST['recovery_question'] ?? null;
    $recovery_answer   = $_POST['recovery_answer'] ?? null;

    $photo_filename = null;
    if (isset($_FILES['userphoto']) && $_FILES['userphoto']['error'] === 0) {
        $allowed_types = ['jpg','jpeg','png'];
        $ext = strtolower(pathinfo($_FILES['userphoto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext,$allowed_types)) {
            $photo_filename = uniqid().'.'.$ext;
            $upload_dir = "uploads/";
            if (!is_dir($upload_dir)) mkdir($upload_dir,0755,true);
            move_uploaded_file($_FILES['userphoto']['tmp_name'],$upload_dir.$photo_filename);
        }
    }

    $insert = $pdo->prepare("INSERT INTO tbl_user 
        (fullname, username, useremail, userpassword, role, photo, contact_number, course, major, year_level, recovery_question, recovery_answer)
        VALUES
        (:fname, :uname, :email, :pass, :role, :photo, :contact, :course, :major, :year_level, :rq, :ra)");

    if ($insert->execute([
        ':fname'=>$fullname,
        ':uname'=>$username,
        ':email'=>$useremail,
        ':pass'=>$userpassword,
        ':role'=>$userrole,
        ':photo'=>$photo_filename,
        ':contact'=>$contact,
        ':course'=>$course,
        ':major'=>$major,
        ':year_level'=>$year_level,
        ':rq'=>$recovery_question,
        ':ra'=>$recovery_answer
    ])) {
        logActivity($pdo, "Registered new user: $fullname ($userrole)");
        $_SESSION['status'] = "User registered successfully";
        $_SESSION['status_code'] = "success";
    } else {
        $_SESSION['status'] = "Registration failed!";
        $_SESSION['status_code'] = "error";
    }
    header("Location: registration.php");
    exit();
}

/* ================= UPDATE USER ================= */
if (isset($_POST['btnupdate'])) {
    $userid   = $_POST['edit_userid'] ?? '';
    $fullname = trim($_POST['edit_fname'] ?? '');
    $username = trim($_POST['edit_txtname'] ?? '');
    $email    = trim($_POST['edit_txtemail'] ?? '');
    $contact  = trim($_POST['edit_contact_number'] ?? '');
    $role     = $_POST['edit_txtselect_option'] ?? '';

    // Check if updated fullname, username, or email already exists in OTHER accounts
    $check_edit = $pdo->prepare("SELECT * FROM tbl_user WHERE (fullname = :fname OR username = :uname OR useremail = :email) AND userid <> :id AND is_archived = 0");
    $check_edit->execute([':fname' => $fullname, ':uname' => $username, ':email' => $email, ':id' => $userid]);
    $existing_edit = $check_edit->fetch(PDO::FETCH_ASSOC);

    if ($existing_edit) {
        if ($existing_edit['fullname'] == $fullname) {
            $_SESSION['status'] = "Full Name already exists in another account!";
        } elseif ($existing_edit['username'] == $username) {
            $_SESSION['status'] = "Username already exists in another account!";
        } else {
            $_SESSION['status'] = "Email already exists in another account!";
        }
        $_SESSION['status_code'] = "error";
        header("Location: registration.php");
        exit();
    }
    
    // Major, Course, and Year Level only for Intern / Student Assistant
    if ($role === 'Intern' || $role === 'Student Assistant') {
        $course     = $_POST['edit_course'] ?? null;
        $major      = $_POST['edit_major'] ?? null;
        $year_level = $_POST['edit_year_level'] ?? null;
        $recovery_question = null;
        $recovery_answer   = null;
    } elseif ($role === 'Admin') {
        $course     = null;
        $major      = null;
        $year_level = null;
        $recovery_question = $_POST['edit_recovery_question'] ?? null;
        $recovery_answer   = $_POST['edit_recovery_answer'] ?? null;
    } else {
        $course     = null;
        $major      = null;
        $year_level = null;
        $recovery_question = null;
        $recovery_answer   = null;
    }
    $new_password      = $_POST['edit_password'] ?? null;

    // Server-side validation for password length if a new password is provided
    if (!empty($new_password) && strlen($new_password) < 8) {
        $_SESSION['status'] = "New password must be at least 8 characters long.";
        $_SESSION['status_code'] = "error";
        header("Location: registration.php");
        exit();
    }

    $photo_filename = null;
    if (isset($_FILES['edit_userphoto']) && $_FILES['edit_userphoto']['error'] === 0) {
        $allowed_types = ['jpg','jpeg','png'];
        $ext = strtolower(pathinfo($_FILES['edit_userphoto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext,$allowed_types)) {
            $photo_filename = uniqid().'.'.$ext;
            $upload_dir = "uploads/";
            if (!is_dir($upload_dir)) mkdir($upload_dir,0755,true);
            move_uploaded_file($_FILES['edit_userphoto']['tmp_name'],$upload_dir.$photo_filename);
        }
    }

    // Prepare SQL
    $sql = "UPDATE tbl_user SET
        fullname = :fname,
        username = :uname,
        useremail = :email,
        contact_number = :contact,
        role = :role,
        course = :course,
        major = :major,
        year_level = :year_level,
        recovery_question = :rq,
        recovery_answer = :ra";

    $params = [
        ':fname'=>$fullname,
        ':uname'=>$username,
        ':email'=>$email,
        ':contact'=>$contact,
        ':role'=>$role,
        ':course'=>$course,
        ':major'=>$major,
        ':year_level'=>$year_level,
        ':rq'=>$recovery_question,
        ':ra'=>$recovery_answer,
        ':id'=>$userid
    ];

    if ($photo_filename) {
        $sql .= ", photo = :photo";
        $params[':photo'] = $photo_filename;
    }

    // Handle password update if provided (forgot password state)
    if (!empty($new_password)) {
        $sql .= ", userpassword = :pass, must_change_password = 1";
        $params[':pass'] = $new_password;

        // Also complete any pending reset requests for this user
        $pdo->prepare("UPDATE password_reset_requests SET status = 'completed', new_password = :pass WHERE user_id = :uid AND status = 'pending'")
            ->execute([':pass' => $new_password, ':uid' => $userid]);
    }

    $sql .= " WHERE userid = :id";
    
    $update = $pdo->prepare($sql);
    if ($update->execute($params)) {
        logActivity($pdo, "Updated user details: $fullname ($userid)");
        $_SESSION['status'] = "User updated successfully";
        $_SESSION['status_code'] = "success";
    } else {
        $_SESSION['status'] = "Update failed!";
        $_SESSION['status_code'] = "error";
    }
    header("Location: registration.php");
    exit();
}

/* ================= APPROVE RESET REQUEST ================= */
if (isset($_POST['btn_approve_reset'])) {
    $request_id = $_POST['request_id'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    // Update user password and set flag
    $updateUser = $pdo->prepare("UPDATE tbl_user SET userpassword = :pass, must_change_password = 1 WHERE userid = :uid");
    if ($updateUser->execute([':pass' => $new_password, ':uid' => $user_id])) {
        // Mark request as completed
        $updateReq = $pdo->prepare("UPDATE password_reset_requests SET status = 'completed', new_password = :pass WHERE request_id = :rid");
        $updateReq->execute([':pass' => $new_password, ':rid' => $request_id]);
        
        logActivity($pdo, "Approved password reset for User ID: $user_id. New password: $new_password");
        $_SESSION['temp_reset_pass'] = $new_password;
        $_SESSION['status'] = "Password reset successfully!";
        $_SESSION['status_code'] = "success";
    } else {
        $_SESSION['status'] = "Failed to reset password.";
        $_SESSION['status_code'] = "error";
    }
    header("Location: registration.php");
    exit();
}

/* ================= SEARCH FILTER ================= */
$filter_role = $_GET['filter_role'] ?? 'All';
$search = $_GET['search'] ?? '';

$sql = "SELECT *, (SELECT COUNT(*) FROM password_reset_requests WHERE user_id = tbl_user.userid AND status = 'pending') as pending_requests FROM tbl_user WHERE is_archived = 0";
$params = [];

if($filter_role !== 'All'){
    $sql .= " AND role = :role";
    $params[':role'] = $filter_role;
}

if(!empty($search)){
    $sql .= " AND (fullname LIKE :search OR username LIKE :search OR useremail LIKE :search)";
    $params[':search'] = "%".$search."%";
}

$sql .= " ORDER BY userid ASC";
$select = $pdo->prepare($sql);
$select->execute($params);

if ($_SESSION['role'] == "Admin") {
    include_once "header.php";
} else {
    include_once "headeruser.php";
}
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h4 class="m-0 text-dark">
            User Management
          </h4>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <!-- PASSWORD RESET REQUESTS NOTIFICATION -->
      <?php
      $stmtReq = $pdo->prepare("SELECT pr.*, u.fullname, u.username, u.role FROM password_reset_requests pr JOIN tbl_user u ON pr.user_id = u.userid WHERE pr.status = 'pending' ORDER BY pr.requested_at DESC");
      $stmtReq->execute();
      if ($stmtReq->rowCount() > 0):
      ?>
      <div class="card card-warning card-outline shadow-sm mb-4">
        <div class="card-header border-0">
          <h3 class="card-title text-bold"><i class="fas fa-exclamation-triangle mr-2 text-warning"></i> Pending Password Reset Requests</h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
          </div>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            <?php while($req = $stmtReq->fetch(PDO::FETCH_OBJ)): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
              <div>
                <span class="text-bold"><?php echo $req->fullname; ?></span> 
                <span class="badge badge-info ml-1"><?php echo $req->role; ?></span>
                <div class="small text-muted">Requested on <?php echo date('M d, Y h:i A', strtotime($req->requested_at)); ?></div>
              </div>
              <div class="btn-group">
                <button class="btn btn-success btn-sm approveResetBtn" 
                  data-id="<?php echo $req->request_id; ?>" 
                  data-uid="<?php echo $req->user_id; ?>" 
                  data-name="<?php echo $req->fullname; ?>"
                  data-toggle="modal" data-target="#approveResetModal">
                  <i class="fas fa-check mr-1"></i> Reset Password
                </button>
                <a href="registration.php?delete_reset_id=<?php echo $req->request_id; ?>" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-trash"></i>
                      </a>
              </div>
            </li>
            <?php endwhile; ?>
          </ul>
        </div>
      </div>
      <?php endif; ?>

      <!-- FLOATING ACTION BUTTON -->
      <button class="btn btn-primary btn-lg shadow-lg" data-toggle="modal" data-target="#registerModal" style="position: fixed; bottom: 80px; right: 20px; z-index: 1000; border-radius: 50%; width: 60px; height: 60px;">
        <i class="fas fa-plus"></i>
      </button>

      <!-- SEARCH & FILTER -->
      <div class="card card-outline card-info shadow-sm mb-4">
        <div class="card-body py-3">
          <form method="get" id="searchForm">
            <div class="row align-items-center">
              <div class="col-md-4">
                <div class="form-group mb-md-0">
                  <label class="small text-muted mb-1"><i class="fas fa-filter mr-1"></i> Filter by Role</label>
                  <select name="filter_role" id="filter_role" class="form-control form-control-lg">
                    <option value="All" <?php if($filter_role=='All') echo 'selected'; ?>>All Roles</option>
                    <option value="Admin" <?php if($filter_role=='Admin') echo 'selected'; ?>>Admin</option>
                    <option value="Intern" <?php if($filter_role=='Intern') echo 'selected'; ?>>Intern</option>
                    <option value="Student Assistant" <?php if($filter_role=='Student Assistant') echo 'selected'; ?>>Student Assistant</option>
                  </select>
                </div>
              </div>
              <div class="col-md-8">
                <div class="form-group mb-md-0">
                  <label class="small text-muted mb-1"><i class="fas fa-search mr-1"></i> Search User</label>
                  <div class="input-group input-group-lg">
                    <input type="text" name="search" id="search" class="form-control" placeholder="Search by name, username, or email" value="<?php echo htmlspecialchars($search); ?>">
                    <div class="input-group-append">
                      <button class="btn btn-info" type="submit">
                        <i class="fas fa-search"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- USER LIST TABLE -->
      <div class="card card-primary card-outline shadow-sm">
        <div class="card-header border-0 pt-3">
          <h3 class="card-title text-bold"><i class="fas fa-users mr-2 text-primary"></i>Registered Users</h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
              <i class="fas fa-minus"></i>
            </button>
          </div>
        </div>
        <div class="card-body table-responsive p-0">
          <table class="table table-hover table-striped align-middle mb-0" style="font-size: 1rem;">
            <thead class="bg-light text-dark">
              <tr>
                <th class="pl-3 py-3">ID</th>
                <th class="py-3">Photo</th>
                <th class="py-3">Full Name</th>
                <th class="py-3">Username</th>
                <th class="py-3">Email</th>
                <th class="py-3">Role</th>
                <th class="py-3">Contact</th>
                <th class="py-3">Academic Details</th>
                <th class="text-center pr-3 py-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while($row=$select->fetch(PDO::FETCH_OBJ)){ ?>
              <tr data-userid="<?php echo $row->userid; ?>">
                <td class="pl-3 py-3"><?php echo $row->userid; ?></td>
                <td class="py-3 user-photo">
                  <?php if(!empty($row->photo)): ?>
                  <img src="uploads/<?php echo $row->photo; ?>" width="55" height="55" class="rounded-circle shadow-sm" style="object-fit:cover; border: 2px solid #fff;">
                  <?php else: ?>
                  <div class="rounded-circle bg-light d-flex align-items-center justify-content-center shadow-sm" style="width: 55px; height: 55px; border: 2px solid #fff;">
                    <i class="fas fa-user text-muted fa-lg"></i>
                  </div>
                  <?php endif; ?>
                </td>
                <td class="text-dark py-3 user-fullname" style="font-size: 1.1rem;"><?php echo $row->fullname; ?></td>
                <td class="py-3 text-muted user-username"><?php echo $row->username; ?></td>
                <td class="py-3 text-muted user-email"><?php echo $row->useremail; ?></td>
                <td class="py-3">
                  <span class="text-dark user-role" style="font-size: 0.95rem;"><?php echo $row->role; ?></span>
                </td>
                <td class="py-3 text-muted user-contact"><?php echo $row->contact_number ?: '-'; ?></td>
                <td class="py-3 user-academic">
                  <?php if($row->role == 'Intern' || $row->role == 'Student Assistant'): ?>
                  <div class="text-dark" style="line-height: 1.4; font-size: 0.95rem;">
                    <div><i class="fas fa-graduation-cap mr-1 text-muted"></i> <?php echo $row->course ?: '-'; ?></div>
                    <div><i class="fas fa-book mr-1 text-muted"></i> <?php echo $row->major ?: '-'; ?> (Year Level: <?php echo $row->year_level ?: '-'; ?>)</div>
                  </div>
                  <?php elseif($row->role == 'Admin'): ?>
                  <div class="text-muted small">
                    <i class="fas fa-shield-alt mr-1"></i> System Admin
                  </div>
                  <?php else: ?>
                  <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td class="text-center pr-3 py-3">
                  <div class="btn-group shadow-sm">
                    <button class="btn btn-info btn-lg editBtn" 
                      style="padding: 0.5rem 1rem;"
                      data-id="<?php echo $row->userid; ?>" 
                      data-name="<?php echo $row->fullname; ?>" 
                      data-username="<?php echo $row->username; ?>" 
                      data-email="<?php echo $row->useremail; ?>" 
                      data-contact="<?php echo $row->contact_number; ?>" 
                      data-role="<?php echo $row->role; ?>" 
                      data-course="<?php echo $row->course; ?>" 
                      data-major="<?php echo $row->major; ?>" 
                      data-year_level="<?php echo $row->year_level; ?>" 
                      data-rq="<?php echo htmlspecialchars($row->recovery_question); ?>" 
                      data-ra="<?php echo htmlspecialchars($row->recovery_answer); ?>" 
                      data-photo="<?php echo $row->photo; ?>" 
                      data-must_change_password="<?php echo $row->must_change_password; ?>"
                      data-pending_requests="<?php echo $row->pending_requests; ?>"
                      data-toggle="modal" 
                      data-target="#editModal" 
                      title="Edit User">
                      <i class="fas fa-edit"></i>
                    </button>
                    <a href="registration.php?id=<?php echo $row->userid; ?>" 
                        class="btn btn-danger btn-lg" 
                        style="padding: 0.5rem 1rem;"
                        title="Delete User">
                        <i class="fas fa-trash"></i>
                      </a>
                  </div>
                </td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- ================= REGISTER MODAL ================= -->
<div class="modal fade" id="registerModal">
  <div class="modal-dialog">
    <div class="modal-content shadow-lg border-0">
      <form method="post" enctype="multipart/form-data">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title"><i class="fas fa-user-plus mr-2"></i>Register New User</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label class="small text-muted mb-1"><i class="fas fa-id-card mr-1"></i> Full Name</label>
            <input type="text" name="fname" class="form-control" required placeholder="Enter full name">
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="small text-muted mb-1"><i class="fas fa-user mr-1"></i> Username</label>
                <input type="text" name="txtname" class="form-control" required placeholder="Username">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="small text-muted mb-1"><i class="fas fa-envelope mr-1"></i> Email</label>
                <input type="email" name="txtemail" class="form-control" required placeholder="Email address">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="small text-muted mb-1"><i class="fas fa-lock mr-1"></i> Password</label>
                <input type="password" name="txtpassword" class="form-control" required minlength="8" placeholder="At least 8 characters">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="small text-muted mb-1"><i class="fas fa-user-tag mr-1"></i> Role</label>
                <select name="txtselect_option" id="txtselect_option" class="form-control" required>
                  <option value="">Select Role</option>
                  <option value="Admin">Admin</option>
                  <option value="Intern">Intern</option>
                  <option value="Student Assistant">Student Assistant</option>
                </select>
              </div>
            </div>
          </div>

<!-- Admin Recovery Fields -->
          <div id="adminRecoveryFields" style="display:none;" class="p-2 mb-3 rounded bg-light border">
            <p class="small text-bold text-danger mb-2"><i class="fas fa-shield-alt mr-1"></i> Admin Recovery (Required)</p>
            <div class="form-group mb-2">
              <label class="small text-muted mb-0">Recovery Question</label>
              <div class="input-group input-group-sm">
                <input type="text" name="recovery_question" id="recovery_question" class="form-control" placeholder="Any personal question">
                <div class="input-group-append">
                 <button type="button" class="btn btn-outline-secondary" onclick="shuffleQuestion('recovery_question')" title="Shuffle Question">
                 <i class="fas fa-dice"></i>
                 </button>
                </div>
              </div>
            </div>
            <div class="form-group mb-0">
              <label class="small text-muted mb-0">Recovery Answer</label>
              <input type="text" name="recovery_answer" id="recovery_answer" class="form-control form-control-sm" placeholder="Answer">
            </div>
          </div>

          <!-- Course, Major & Year Level Fields -->
          <div id="courseMajorFields" style="display:none;" class="p-2 mb-3 rounded bg-light border">
            <p class="small text-bold text-primary mb-2"><i class="fas fa-graduation-cap mr-1"></i> Academic Details</p>
            <div class="row">
              <div class="col-md-5">
                <div class="form-group mb-2">
                  <label class="small text-muted mb-0">Course</label>
                  <input type="text" name="course" class="form-control form-control-sm" placeholder="BSIT, BSBA, etc.">
                </div>
              </div>
              <div class="col-md-5">
                <div class="form-group mb-2">
                  <label class="small text-muted mb-0">Major</label>
                  <input type="text" name="major" class="form-control form-control-sm" placeholder="Major">
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group mb-2">
                  <label class="small text-muted mb-0">Year</label>
                  <input type="text" name="year_level" class="form-control form-control-sm" placeholder="1-4">
                </div>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label class="small text-muted mb-1"><i class="fas fa-phone mr-1"></i> Contact Number</label>
            <input type="text" name="contact_number" id="contact_number" class="form-control contact-mask" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="09xxxxxxxxx">
          </div>
          <div class="form-group mb-0">
            <label class="small text-muted mb-1"><i class="fas fa-camera mr-1"></i> Profile Photo</label>
            <div class="custom-file">
              <input type="file" name="userphoto" class="custom-file-input" id="userPhotoInput" accept="image/*">
              <label class="custom-file-label" for="userPhotoInput">Choose file</label>
            </div>
            <div class="text-center mt-2" id="registerPhotoPreview" style="display:none;">
              <img src="" class="rounded-circle shadow-sm" style="width: 80px; height: 80px; object-fit: cover; border: 2px solid #fff;">
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light border-0">
          <button type="button" class="btn btn-secondary shadow-sm" data-dismiss="modal">Cancel</button>
          <button type="submit" name="btnsave" class="btn btn-primary px-4 shadow-sm"><i class="fas fa-save mr-2"></i>Register User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ================= EDIT MODAL ================= -->
<div class="modal fade" id="editModal">
  <div class="modal-dialog">
    <div class="modal-content shadow-lg border-0">
      <form method="post" enctype="multipart/form-data" id="editUserForm">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title"><i class="fas fa-user-edit mr-2"></i>Edit User Account</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="edit_userid" id="edit_userid">
          <div class="form-group">
            <label class="small text-muted mb-1"><i class="fas fa-id-card mr-1"></i> Full Name</label>
            <input type="text" name="edit_fname" id="edit_fname" class="form-control" required placeholder="Full name">
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="small text-muted mb-1"><i class="fas fa-user mr-1"></i> Username</label>
                <input type="text" name="edit_txtname" id="edit_txtname" class="form-control" required placeholder="Username">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="small text-muted mb-1"><i class="fas fa-envelope mr-1"></i> Email</label>
                <input type="email" name="edit_txtemail" id="edit_txtemail" class="form-control" required placeholder="Email">
              </div>
            </div>
          </div>
          <div class="form-group">
            <label class="small text-muted mb-1"><i class="fas fa-user-tag mr-1"></i> Role</label>
            <select name="edit_txtselect_option" id="edit_txtselect_option" class="form-control" required>
              <option value="Admin">Admin</option>
              <option value="Intern">Intern</option>
              <option value="Student Assistant">Student Assistant</option>
            </select>
          </div>

<!-- Edit Admin Recovery Fields -->
<div id="editAdminRecoveryFields" style="display:none;" class="p-2 mb-3 rounded bg-light border">
  <p class="small text-bold text-danger mb-2">
    <i class="fas fa-shield-alt mr-1"></i> Admin Recovery
  </p>

  <div class="form-group mb-2">
    <label class="small text-muted mb-0">Recovery Question</label>
    <div class="input-group input-group-sm">
      <input type="text" name="edit_recovery_question" id="edit_recovery_question" class="form-control">
      <div class="input-group-append">
        <button type="button" class="btn btn-outline-secondary"
          onclick="shuffleQuestion('edit_recovery_question')" 
          title="Shuffle Question">
          <i class="fas fa-dice"></i>
        </button>
      </div>
    </div>
  </div>

  <div class="form-group mb-0">
    <label class="small text-muted mb-0">Recovery Answer</label>
    <input type="text" name="edit_recovery_answer" id="edit_recovery_answer" class="form-control form-control-sm">
  </div>
</div>

          <!-- Edit Course, Major & Year Level Fields -->
          <div id="editCourseMajorFields" style="display:none;" class="p-2 mb-3 rounded bg-light border">
            <p class="small text-bold text-info mb-2"><i class="fas fa-graduation-cap mr-1"></i> Academic Details</p>
            <div class="row">
              <div class="col-md-5">
                <div class="form-group mb-2">
                  <label class="small text-muted mb-0">Course</label>
                  <input type="text" name="edit_course" id="edit_course" class="form-control form-control-sm">
                </div>
              </div>
              <div class="col-md-5">
                <div class="form-group mb-2">
                  <label class="small text-muted mb-0">Major</label>
                  <input type="text" name="edit_major" id="edit_major" class="form-control form-control-sm">
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group mb-2">
                  <label class="small text-muted mb-0">Year</label>
                  <input type="text" name="edit_year_level" id="edit_year_level" class="form-control form-control-sm">
                </div>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label class="small text-muted mb-1"><i class="fas fa-phone mr-1"></i> Contact Number</label>
            <input type="text" name="edit_contact_number" id="edit_contact_number" class="form-control contact-mask" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="09xxxxxxxxx">
          </div>

          <!-- Edit Password (Conditional) -->
          <div id="editPasswordSection" style="display:none;" class="p-2 mb-3 rounded bg-light border border-info">
            <p class="small text-bold text-info mb-2">
              <i class="fas fa-key mr-1"></i> Update Password 
              <span class="badge badge-warning ml-1">Forgot Password state</span>
            </p>
            <div class="form-group mb-0">
              <label class="small text-muted mb-1">New Password</label>
              <input type="password" name="edit_password" id="edit_password" class="form-control" minlength="8" placeholder="At least 8 characters">
              <small class="text-muted">User has forgotten their password. You can set a new one here.</small>
            </div>
          </div>

          <div class="form-group mb-0">
            <label class="small text-muted mb-1"><i class="fas fa-camera mr-1"></i> Photo <span class="text-xs font-italic">(Leave blank to keep current)</span></label>
            <div class="custom-file">
              <input type="file" name="edit_userphoto" id="edit_userphoto" class="custom-file-input" accept="image/*">
              <label class="custom-file-label" for="edit_userphoto">Update photo</label>
            </div>
            <div class="text-center mt-2" id="editPhotoPreview">
              <img src="" id="editPhotoPreviewImg" class="rounded-circle shadow-sm" style="width: 80px; height: 80px; object-fit: cover; border: 2px solid #fff;">
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light border-0">
          <button type="button" class="btn btn-secondary shadow-sm" data-dismiss="modal">Cancel</button>
          <button type="button" id="btnUpdateUser" class="btn btn-info px-4 shadow-sm text-white"><i class="fas fa-save mr-2"></i>Update Account</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ================= APPROVE RESET MODAL ================= -->
<div class="modal fade" id="approveResetModal">
  <div class="modal-dialog">
    <div class="modal-content shadow-lg border-0">
      <form method="post">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title"><i class="fas fa-key mr-2"></i>Generate New Password</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="request_id" id="modal_request_id">
          <input type="hidden" name="user_id" id="modal_user_id">
          <p>Generating new password for: <b id="modal_user_name"></b></p>
          <div class="form-group">
            <label>New Password</label>
            <div class="input-group">
              <input type="text" name="new_password" id="new_password_input" class="form-control" required readonly>
              <div class="input-group-append">
                <button type="button" class="btn btn-info" onclick="generateRandomPass()">
                  <i class="fas fa-sync-alt"></i>
                </button>
              </div>
            </div>
            <small class="text-muted">Click the button to generate a random password.</small>
          </div>
        </div>
        <div class="modal-footer bg-light border-0">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" name="btn_approve_reset" class="btn btn-success px-4 shadow-sm"><i class="fas fa-check mr-2"></i>Complete Reset</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// BS Custom File Input Initialization
document.addEventListener('DOMContentLoaded', function() {
  if (typeof bsCustomFileInput !== 'undefined') {
    bsCustomFileInput.init();
  }

  // Handle Register Photo Preview
  document.getElementById('userPhotoInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(event) {
        const previewDiv = document.getElementById('registerPhotoPreview');
        const previewImg = previewDiv.querySelector('img');
        previewImg.src = event.target.result;
        previewDiv.style.display = 'block';
      }
      reader.readAsDataURL(file);
    }
  });

  // Handle Edit Photo Preview
  document.getElementById('edit_userphoto').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(event) {
        document.getElementById('editPhotoPreviewImg').src = event.target.result;
      }
      reader.readAsDataURL(file);
    }
  });
});

// Delete with SweetAlert2
function confirmDelete(id, name) {
    window.location.href = 'registration.php?id=' + id;
}

// Search debounce
let timer;
document.getElementById("search").addEventListener("keyup", function(){
    clearTimeout(timer);
    timer = setTimeout(function(){
        document.getElementById("searchForm").submit();
    }, 400);
});

document.getElementById("filter_role").addEventListener("change", function(){
    document.getElementById("searchForm").submit();
});

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

function generateRandomPass() {
    const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
    let pass = "";
    for (let i = 0; i < 8; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('new_password_input').value = pass;
}

// Function to toggle course/major based on role
function toggleCourseMajor(role, fieldsId) {
    const fields = document.getElementById(fieldsId);
    if (role === 'Intern' || role === 'Student Assistant') {
        fields.style.display = 'block';
    } else {
        fields.style.display = 'none';
    }
}

// Function to toggle Admin recovery fields
function toggleAdminRecovery(role, fieldsId, qId, aId) {
    const fields = document.getElementById(fieldsId);
    const qInput = document.getElementById(qId);
    const aInput = document.getElementById(aId);
    if (role === 'Admin') {
        fields.style.display = 'block';
        if(qInput) {
            qInput.setAttribute('required', true);
            if(qInput.value === "") shuffleQuestion(qId);
        }
        if(aInput) aInput.setAttribute('required', true);
    } else {
        fields.style.display = 'none';
        if(qInput) qInput.removeAttribute('required');
        if(aInput) aInput.removeAttribute('required');
    }
}

// Edit modal
document.querySelectorAll('.editBtn').forEach(button => {
    button.addEventListener('click',function(){
        document.getElementById('edit_userid').value = this.dataset.id;
        document.getElementById('edit_fname').value = this.dataset.name;
        document.getElementById('edit_txtname').value = this.dataset.username;
        document.getElementById('edit_txtemail').value = this.dataset.email;
        document.getElementById('edit_contact_number').value = this.dataset.contact;
        document.getElementById('edit_txtselect_option').value = this.dataset.role;
        document.getElementById('edit_course').value = this.dataset.course;
        document.getElementById('edit_major').value = this.dataset.major;
        document.getElementById('edit_year_level').value = this.dataset.year_level;
        document.getElementById('edit_recovery_question').value = this.dataset.rq;
        document.getElementById('edit_recovery_answer').value = this.dataset.ra;

        // Show password field only if they've forgotten their password
        const mustChange = this.dataset.must_change_password;
        const pendingReq = this.dataset.pending_requests;
        const passSection = document.getElementById('editPasswordSection');
        
        if (mustChange == "1" || pendingReq > 0) {
            passSection.style.display = 'block';
            document.getElementById('edit_password').setAttribute('required', 'true');
        } else {
            passSection.style.display = 'none';
            document.getElementById('edit_password').removeAttribute('required');
            document.getElementById('edit_password').value = '';
        }
        
        toggleCourseMajor(this.dataset.role, 'editCourseMajorFields');
        toggleAdminRecovery(this.dataset.role, 'editAdminRecoveryFields', 'edit_recovery_question', 'edit_recovery_answer');

        // Set current photo preview
        const photo = this.dataset.photo;
        const previewImg = document.getElementById('editPhotoPreviewImg');
        if (photo) {
            previewImg.src = 'uploads/' + photo;
        } else {
            previewImg.src = '../dist/img/avatar.png'; // Fallback to a default avatar if available
        }
    });
});

// Toggle course/major on role change in edit modal
document.getElementById('edit_txtselect_option').addEventListener('change', function() {
    toggleCourseMajor(this.value, 'editCourseMajorFields');
    toggleAdminRecovery(this.value, 'editAdminRecoveryFields', 'edit_recovery_question', 'edit_recovery_answer');
});

// Approve Reset Modal data
document.querySelectorAll('.approveResetBtn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('modal_request_id').value = this.dataset.id;
        document.getElementById('modal_user_id').value = this.dataset.uid;
        document.getElementById('modal_user_name').innerText = this.dataset.name;
        generateRandomPass();
    });
});

// Show Course & Major for Intern / Student Assistant in registration modal
document.getElementById('txtselect_option').addEventListener('change', function() {
    toggleCourseMajor(this.value, 'courseMajorFields');
    toggleAdminRecovery(this.value, 'adminRecoveryFields', 'recovery_question', 'recovery_answer');
});

// AJAX Update User
document.getElementById('btnUpdateUser').addEventListener('click', function() {
    const form = document.getElementById('editUserForm');
    const formData = new FormData(form);
    
    fetch('ajax_update_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#editModal').modal('hide');
            
            const userData = data.data;
            const row = document.querySelector(`tr[data-userid="${userData.userid}"]`);
            
            if (row) {
                row.querySelector('.user-fullname').textContent = userData.fullname;
                row.querySelector('.user-username').textContent = userData.username;
                row.querySelector('.user-email').textContent = userData.useremail;
                row.querySelector('.user-role').textContent = userData.role;
                row.querySelector('.user-contact').textContent = userData.contact_number || '-';
                
                let academicDetails = '-';
                if (userData.role === 'Intern' || userData.role === 'Student Assistant') {
                    academicDetails = `<div style="line-height: 1.4; font-size: 0.95rem;">
                        <div><i class="fas fa-graduation-cap mr-1 text-muted"></i> ${userData.course || '-'}</div>
                        <div><i class="fas fa-book mr-1 text-muted"></i> ${userData.major || '-'} (Year Level: ${userData.year_level || '-'})</div>
                    </div>`;
                } else if (userData.role === 'Admin') {
                    academicDetails = '<div class="text-muted small"><i class="fas fa-shield-alt mr-1"></i> System Admin</div>';
                }
                row.querySelector('.user-academic').innerHTML = academicDetails;
                
                if (userData.photo) {
                    const img = row.querySelector('.user-photo img');
                    if (img) {
                        img.src = 'uploads/' + userData.photo;
                    } else {
                        row.querySelector('.user-photo').innerHTML = `<img src="uploads/${userData.photo}" width="55" height="55" class="rounded-circle shadow-sm" style="object-fit:cover; border: 2px solid #fff;">`;
                    }
                }
                
                const editBtn = row.querySelector('.editBtn');
                editBtn.dataset.name = userData.fullname;
                editBtn.dataset.username = userData.username;
                editBtn.dataset.email = userData.useremail;
                editBtn.dataset.contact = userData.contact_number;
                editBtn.dataset.role = userData.role;
                editBtn.dataset.course = userData.course || '';
                editBtn.dataset.major = userData.major || '';
                editBtn.dataset.year_level = userData.year_level || '';
            }

            // Real-time update for sidebar if the updated user is the current user
            if (userData.userid == window.currentUserId) {
                const sidebarName = document.getElementById('sidebarUserFullname');
                const sidebarRole = document.getElementById('sidebarUserRole');
                const sidebarPhoto = document.getElementById('sidebarUserPhoto');

                if (sidebarName) sidebarName.textContent = userData.fullname;
                if (sidebarRole) sidebarRole.innerHTML = `<i class="fas fa-user-shield mr-1"></i>${userData.role}`;
                if (sidebarPhoto && userData.photo) {
                    sidebarPhoto.src = '../ui/uploads/' + userData.photo;
                }
            }
            
            Toast.fire({
                icon: 'success',
                title: 'User updated successfully'
            });
        } else {
            Toast.fire({
                icon: 'error',
                title: data.message || 'Update failed'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Toast.fire({
            icon: 'error',
            title: 'An error occurred'
        });
    });
});

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
if(isset($_SESSION['status']) && $_SESSION['status']!='') {
  if (isset($_SESSION['temp_reset_pass'])) {
?>
<script>
 Swal.fire({
     title: 'Generated Password',
     html: '<div style="background: #f8f9fa; border: 2px dashed #28a745; padding: 20px; border-radius: 10px; margin: 15px 0; position: relative;">' +
           '<span id="tempResetPass" style="font-size: 2.5rem; font-family: monospace; color: #1b5e20; letter-spacing: 2px;">' +
           '<?php echo $_SESSION['temp_reset_pass']; ?>' +
           '</span>' +
           '<button onclick="copyResetPass()" class="btn btn-sm btn-outline-success" style="position: absolute; top: 5px; right: 5px;" title="Copy to Clipboard">' +
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
             title: '<?php echo $_SESSION['status']; ?>',
             timer: 3000,
             showConfirmButton: false,
             heightAuto: false
         });
     }
 });

 function copyResetPass() {
     const passText = document.getElementById('tempResetPass').innerText;
     navigator.clipboard.writeText(passText).then(() => {
         toastr.options = {
             "closeButton": true,
             "progressBar": true,
             "positionClass": "toast-top-center",
             "timeOut": "2000"
         };
         toastr.success('Password copied to clipboard!');

         // Close SweetAlert
         setTimeout(() => {
             Swal.close();
         }, 500);
     });
 }
 </script>
<?php
    unset($_SESSION['temp_reset_pass']);
  } else {
?>
<script>
Swal.fire({
    icon: '<?php echo $_SESSION['status_code']; ?>',
    title: '<?php echo $_SESSION['status']; ?>',
    showConfirmButton: true,
    confirmButtonText: 'OK',
    timer: <?php echo ($_SESSION['status_code'] == 'success') ? 'null' : '3000'; ?>,
    heightAuto: false
});
</script>
<?php
  }
unset($_SESSION['status']);
unset($_SESSION['status_code']);
}
?>

<?php include_once "footer.php"; ?>
