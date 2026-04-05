<?php
include 'db_connect.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $id = $_POST['membership_type_id'];
    $borrow_limit = $_POST['borrow_limit'];
    $duration_days = $_POST['duration_days'];

    $stmt = $conn->prepare("UPDATE membership_types SET borrow_limit=?, duration_days=? WHERE membership_type_id=?");
    $stmt->bind_param("iii", $borrow_limit, $duration_days, $id);

    if($stmt->execute()){
        echo "Membership updated successfully!";
    } else {
        echo "Error updating membership.";
    }
}
?>