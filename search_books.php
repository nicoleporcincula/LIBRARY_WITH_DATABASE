<?php
include 'db_connect.php';

$q = isset($_GET['q']) ? $_GET['q'] : '';
$sql = "SELECT book_id, title, author FROM books 
        WHERE (title LIKE ? OR book_id LIKE ?) AND copies > 0 
        LIMIT 10";

$stmt = $conn->prepare($sql);
$searchTerm = "%$q%";
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$books = [];
while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}
echo json_encode($books);