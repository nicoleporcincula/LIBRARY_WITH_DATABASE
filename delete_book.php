<?php
include 'db_connect.php';

// Check if ID exists in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "DELETE FROM books WHERE book_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    $stmt->execute();
}

// Redirect back to home.php
header("Location: home.php#panel-books");
exit;
?>