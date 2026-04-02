<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['member_id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $type_id = $_POST['membership_type_id'];

    $sql = "UPDATE members SET name=?, phone=?, address=?, membership_type_id=? WHERE member_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $name, $phone, $address, $type_id, $id);

    if ($stmt->execute()) {
        header("Location: home.php#panel-members");
        exit;
    } else {
        echo "Error updating member: " . $conn->error;
    }
}
?>