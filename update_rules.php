<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the keys actually exist before trying to use them
    if (isset($_POST['default_days']) && isset($_POST['daily_fine'])) {
        
        $default_days = intval($_POST['default_days']);
        $daily_fine = floatval($_POST['daily_fine']);

        // Update the settings table (using LIMIT 1 for safety)
        $stmt = $conn->prepare("UPDATE settings SET default_days = ?, daily_fine = ? LIMIT 1");
        $stmt->bind_param("id", $default_days, $daily_fine);

        if ($stmt->execute()) {
            echo "Rules updated successfully!";
        } else {
            echo "Failed to update rules: " . $conn->error;
        }
    } else {
        echo "Error: Data labels missing. Check HTML name attributes.";
    }
}
?>