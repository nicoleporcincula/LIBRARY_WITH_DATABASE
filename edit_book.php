<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = $_POST['book_id'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $isbn = $_POST['isbn'];
    $year = $_POST['year'];
    $copies = $_POST['copies'];
    $category = $_POST['category_id'];

    $sql = "UPDATE books SET title=?, author=?, isbn=?, publish_year=?, copies=?, category_id=? WHERE book_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssiiii", $title, $author, $isbn, $year, $copies, $category, $book_id);

    if ($stmt->execute()) {
        header("Location: home.php#panel-books");
        exit;
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>