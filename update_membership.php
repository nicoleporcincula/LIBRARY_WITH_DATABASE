<?php
include 'db_connect.php';

// Check if we even got a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Error: No POST request received.");
}

// Log what was received for debugging
$id = $_POST['membership_type_id'] ?? null;
$limit = $_POST['borrow_limit'] ?? null;
$duration = $_POST['duration_days'] ?? null;

if (!$id || $limit === null || $duration === null) {
    die("Error: Missing data. ID: $id, Limit: $limit, Duration: $duration");
}

$stmt = $conn->prepare("UPDATE membership_types SET borrow_limit=?, duration_days=? WHERE membership_type_id=?");
$stmt->bind_param("iii", $limit, $duration, $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "Success: Database Updated.";
    } else {
        echo "Notice: Query ran, but 0 rows changed. (Maybe values are the same?)";
    }
} else {
    echo "SQL Error: " . $stmt->error;
}
?>