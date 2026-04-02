<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = $_POST['book_id'];
    $title = $_POST['title'];
    $isbn = $_POST['isbn'];
    $year = $_POST['year'];
    $copies = $_POST['copies'];

    // Update using book_id and publish_year to match your schema
    $sql = "UPDATE books SET title=?, isbn=?, publish_year=?, copies=? WHERE book_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiii", $title, $isbn, $year, $copies, $book_id);

    if ($stmt->execute()) {
        header("Location: home.php#panel-books");
        exit;
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>