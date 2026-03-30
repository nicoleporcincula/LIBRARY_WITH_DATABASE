<?php
session_start();
include 'db_connect.php'; // connect to database

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Trim input to remove spaces
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);


    // Check if user exists
    $sql = "SELECT * FROM users WHERE username=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        //  Verify password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

            // Log login action with timestamp
            $log_sql = "INSERT INTO audit_log (user_id, action) VALUES (?, 'login')";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("i", $user['user_id']);
            $log_stmt->execute();

            header("Location: home.html"); // must be PHP to access session
            exit;

        } else {
            $_SESSION['error'] = "Incorrect password.";
           header("Location: login.html?error=" . urlencode("Incorrect password."));
            exit;
        }
    } else {
        $_SESSION['error'] = "User not found.";
        header("Location: login.html?error=" . urlencode("User not found."));
        exit;
    }
}
?>