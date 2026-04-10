<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize inputs
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact_info']);
    $deposit = $_POST['deposit_amount'];

    // Default contact if empty
    if(empty($contact)) { $contact = 'N/A'; }

    $sql = "INSERT INTO guests (name, contact_info, deposit_amount) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssd", $name, $contact, $deposit);

    if ($stmt->execute()) {
        // Redirect back to the guest panel
        header("Location: home.php#panel-members");
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
}
?>