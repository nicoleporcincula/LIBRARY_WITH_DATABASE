<?php
include 'db_connect.php';

if (isset($_GET['id'])) {

    $detail_id = (int)$_GET['id'];
    $return_date = date('Y-m-d');

    $info = $conn->query("
        SELECT book_id, borrow_id 
        FROM borrow_details 
        WHERE borrow_detail_id = $detail_id
    ")->fetch_assoc();

    if (!$info) {
        die("Invalid record.");
    }

    $book_id = $info['book_id'];
    $borrow_id = $info['borrow_id'];

    $borrow = $conn->query("
        SELECT b.due_date, s.daily_fine
        FROM borrow b
        LEFT JOIN settings s ON 1=1
        WHERE b.borrow_id = $borrow_id
    ")->fetch_assoc();

    $due_date = $borrow['due_date'];
    $daily_fine = $borrow['daily_fine'] ?? 5;

    $overdue_days = (strtotime($return_date) - strtotime($due_date)) / 86400;
    $overdue_days = max(0, floor($overdue_days));
    $fine = $overdue_days * $daily_fine;

    $conn->query("
        UPDATE borrow_details 
        SET status = 'Returned', return_date = '$return_date', fine = $fine
        WHERE borrow_detail_id = $detail_id
    ");

    $conn->query("
        UPDATE books 
        SET copies = copies + 1 
        WHERE book_id = $book_id
    ");

    $conn->query("
        UPDATE borrow 
        SET status = 'Returned'
        WHERE borrow_id = $borrow_id
    ");

    header("Location: home.php#panel-borrow");
    exit;
}
?>