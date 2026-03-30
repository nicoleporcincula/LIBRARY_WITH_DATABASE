<?php
session_start();
include 'db_connect.php'; // connect to DB

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Log logout action with timestamp
    $log_sql = "INSERT INTO audit_log (user_id, action, timestamp) VALUES (?, 'logout', NOW())";
    $log_stmt = $conn->prepare($log_sql);
    $log_stmt->bind_param("i", $user_id);
    $log_stmt->execute();

    // Destroy session
    session_unset();
    session_destroy();
}

// Redirect to login page
header("Location: login.html");
exit;
?>