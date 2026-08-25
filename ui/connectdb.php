<?php
$pdo = new PDO('mysql:host=Localhost;dbname=custodian_db','root','');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ================= AUTO-MIGRATION: Ensure 'must_change_password' exists =================
try {
    $pdo->query("SELECT must_change_password FROM tbl_user LIMIT 1");
} catch (PDOException $e) {
    if ($e->getCode() == '42S22') { // Column not found
        $pdo->exec("ALTER TABLE tbl_user ADD COLUMN must_change_password TINYINT(1) DEFAULT 0 AFTER userpassword");
    }
}

// ================= CENTRAL FUNCTION: LOG ACTIVITY =================
if (!function_exists('logActivity')) {
    function logActivity($pdo, $action){
        // Ensure session is started if not already
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $user_id = $_SESSION['userid'] ?? null;
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $action]);
    }
}
