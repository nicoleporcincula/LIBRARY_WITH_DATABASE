<?php
include 'db_connect.php'; // Using your confirmed filename

$detail_id = $_GET['id'];
$book_id = $_GET['book_id'];

try {
    $conn->begin_transaction();

    // 1. Update the status to Returned
    $stmt = $conn->prepare("UPDATE borrow_details SET status = 'Returned', return_date = NOW() WHERE borrow_detail_id = ?");
    $stmt->bind_param("i", $detail_id);
    $stmt->execute();

    // 2. Increment the 'copies' (Changed from total_copies to copies)
    $stmt2 = $conn->prepare("UPDATE books SET copies = copies + 1 WHERE book_id = ?");
    $stmt2->bind_param("i", $book_id);
    $stmt2->execute();

    $conn->commit();
    echo "Success";
} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}
?>