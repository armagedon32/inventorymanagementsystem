<?php
include_once 'connectdb.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['useremail']) || $_SESSION['useremail'] == "") {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$userid   = $_POST['edit_userid'] ?? '';
$fullname = trim($_POST['edit_fname'] ?? '');
$username = trim($_POST['edit_txtname'] ?? '');
$email    = trim($_POST['edit_txtemail'] ?? '');
$contact  = trim($_POST['edit_contact_number'] ?? '');
$role     = $_POST['edit_txtselect_option'] ?? '';

if (empty($userid) || empty($fullname) || empty($username) || empty($email) || empty($role)) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit();
}

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
$new_password = $_POST['edit_password'] ?? null;

$photo_filename = null;
if (isset($_FILES['edit_userphoto']) && $_FILES['edit_userphoto']['error'] === 0) {
    $allowed_types = ['jpg','jpeg','png'];
    $ext = strtolower(pathinfo($_FILES['edit_userphoto']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowed_types)) {
        $photo_filename = uniqid().'.'.$ext;
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        move_uploaded_file($_FILES['edit_userphoto']['tmp_name'], $upload_dir.$photo_filename);
    }
}

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

if (!empty($new_password)) {
    $sql .= ", userpassword = :pass, must_change_password = 1";
    $params[':pass'] = $new_password;

    $pdo->prepare("UPDATE password_reset_requests SET status = 'completed', new_password = :pass WHERE user_id = :uid AND status = 'pending'")
        ->execute([':pass' => $new_password, ':uid' => $userid]);
}

$sql .= " WHERE userid = :id";

try {
    $update = $pdo->prepare($sql);
    $success = $update->execute($params);

    if ($success) {
        // Update session if the updated user is the currently logged-in user
        if ($userid == $_SESSION['userid']) {
            $_SESSION['fullname'] = $fullname;
            $_SESSION['username'] = $username;
            $_SESSION['useremail'] = $email;
            $_SESSION['role'] = $role;
            if ($photo_filename) {
                $_SESSION['photo'] = $photo_filename;
            }
        }

        logActivity($pdo, "Updated user details: $fullname ($userid)");
        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => [
                'userid' => $userid,
                'fullname' => $fullname,
                'username' => $username,
                'useremail' => $email,
                'contact_number' => $contact,
                'role' => $role,
                'course' => $course,
                'major' => $major,
                'year_level' => $year_level,
                'photo' => $photo_filename
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>