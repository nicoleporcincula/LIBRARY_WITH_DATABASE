<?php
include 'db_connect.php';

if (isset($_GET['id'])) {
    $fine_id = $_GET['id'];
    $sql = "UPDATE fines SET status = 'Paid' WHERE fine_id = '$fine_id'";
    
    if ($conn->query($sql)) {
        echo "Success";
    } else {
        echo "Error";
    }
}
?>