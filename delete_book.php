<?php
include 'db_connect.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id) {
    die("Error: No ID provided.");
}

try {
    $conn->begin_transaction();

    // 1. Delete FINES linked to any borrow record of this book
    // This uses a subquery to find all borrow_detail_ids for this book_id
    $stmt1 = $conn->prepare("DELETE FROM fines WHERE borrow_detail_id IN (SELECT borrow_detail_id FROM borrow_details WHERE book_id = ?)");
    $stmt1->bind_param("i", $id);
    $stmt1->execute();

    // 2. Delete BORROW RECORDS linked to this book
    $stmt2 = $conn->prepare("DELETE FROM borrow_details WHERE book_id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();

    $stmt3 = $conn->prepare("DELETE FROM books WHERE book_id = ?");
    $stmt3->bind_param("i", $id);
    $stmt3->execute();

    if ($conn->affected_rows > 0) {
        $conn->commit();
       header("Location: home.php#panel-books");
        exit();
    } else {
        $conn->rollback();
        header("Location: home.php#panel-books");
    }

} catch (Exception $e) {
    $conn->rollback();
    echo "Database Error: " . $e->getMessage();
}
?>