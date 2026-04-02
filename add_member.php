<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $date = date('Y-m-d'); // Automatically set today's date

    $sql = "INSERT INTO members (name, phone, address, membership_date) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $phone, $address, $date);

    if ($stmt->execute()) {
        header("Location: home.php#panel-members");
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
}
?>