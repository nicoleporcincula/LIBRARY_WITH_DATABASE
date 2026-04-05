<?php
session_start();
include 'db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo "User not logged in!";
    exit;
}

$full_name = $_POST['full_name'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Hash password if it’s not empty, otherwise keep old password
if (!empty($password)) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET full_name=?, username=?, password=? WHERE user_id=?");
    $stmt->bind_param("sssi", $full_name, $username, $hashed_password, $user_id);
} else {
    $stmt = $conn->prepare("UPDATE users SET full_name=?, username=? WHERE user_id=?");
    $stmt->bind_param("ssi", $full_name, $username, $user_id);
}

if ($stmt->execute()) {
    echo "Updated successfully!";
} else {
    echo "Error updating: " . $stmt->error;
}
?>