<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $isbn = $_POST['isbn'];
    $year = $_POST['year'];
    $copies = $_POST['copies'];

    $sql = "INSERT INTO books (title, isbn, publish_year, copies) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $title, $isbn, $year, $copies);

    if ($stmt->execute()) {
        // Change this to home.php so you go back to your main page
        header("Location: home.php#panel-books");
        exit; 
    } else {
        echo "Error adding book: " . $conn->error;
    }
}
?>