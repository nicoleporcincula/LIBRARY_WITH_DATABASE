<?php
session_start();
include 'db_connect.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Trim input values
    $name = trim($_POST['signup_name']);
    $email = trim($_POST['signup_email']);
    $password = trim($_POST['signup_password']);

    // Check if any field is empty
    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: index.php");
        exit;
    }

    // Check if user already exists
    $sql = "SELECT * FROM users WHERE username=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "User already exists.";
        header("Location: login.html");
        exit;
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user including full_name
    $sql = "INSERT INTO users (username, password, role, full_name) VALUES (?, ?, 'user', ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $email, $hashed_password, $name);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Account created! You can now log in.";
        header("Location: login.html");
        exit;
    } else {
        // Debug message for database errors
        $_SESSION['error'] = "Failed to create account. Error: " . $stmt->error;
        header("Location: login.html");
        exit;
    }
}
?>