<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $default_days = intval($_POST['default_days']);
    $daily_fine = floatval($_POST['daily_fine']);

    // Update the settings table (assuming only one row)
    $stmt = $conn->prepare("UPDATE settings SET default_days = ?, daily_fine = ?");
    $stmt->bind_param("id", $default_days, $daily_fine);

    if ($stmt->execute()) {
        echo "Rules updated successfully!";
    } else {
        echo "Failed to update rules.";
    }
}
?>