<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $isbn = $_POST['isbn'];
    $year = $_POST['year'];
    $copies = $_POST['copies'];
    $category = $_POST['category_id'];

    $sql = "INSERT INTO books (title, author, isbn, publish_year, copies, category_id) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssiii", $title, $author, $isbn, $year, $copies, $category);

    if ($stmt->execute()) {
        header("Location: home.php#panel-books");
        exit; 
    } else {
        echo "Error adding book: " . $conn->error;
    }
}
?>