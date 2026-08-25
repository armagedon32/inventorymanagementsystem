<?php
include_once 'connectdb.php';
session_start();

// Only Admin access
if (!isset($_SESSION['useremail']) || ($_SESSION['role'] ?? '') == 'User') {
    header('location:../index.php');
    exit;
}

// Check if ID is provided
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Delete related items first (to avoid foreign key errors)
        $stmtItems = $pdo->prepare("DELETE FROM incident_items WHERE incident_id = ?");
        $stmtItems->execute([$id]);

        // Delete main incident report
        $stmt = $pdo->prepare("DELETE FROM incident_reports WHERE id = ?");
        $stmt->execute([$id]);

        // Commit transaction
        $pdo->commit();

        // Redirect with success
        header("Location: incident_reports.php?msg=deleted");
        exit;

    } catch (PDOException $e) {
        // Rollback in case of error
        $pdo->rollBack();

        // Redirect with error message
        $error = urlencode($e->getMessage());
        header("Location: incident_reports.php?msg=error&error=$error");
        exit;
    }

} else {
    // If no ID provided, just go back
    header("Location: incident_reports.php");
    exit;
}