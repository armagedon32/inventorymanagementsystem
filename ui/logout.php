<?php
include_once "connectdb.php";
session_start();

if (isset($_SESSION['userid'])) {
    $userRole = $_SESSION['role'] ?? "User";
    $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
    $stmtLog->execute([$_SESSION['userid'], "$userRole Logged Out"]);
}

session_destroy();
header('location:../index.php');
?>