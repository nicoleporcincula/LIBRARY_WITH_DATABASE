<?php
include 'db_connect.php';

if (isset($_GET['id']) && isset($_GET['book_id'])) {
    $detail_id = $_GET['id'];
    $book_id = $_GET['book_id'];
    $today = date('Y-m-d');

    // 1. Fetch due date and patron info
    $sql = "SELECT br.due_date, br.member_id FROM borrow_details bd 
            JOIN borrow br ON bd.borrow_id = br.borrow_id 
            WHERE bd.borrow_detail_id = '$detail_id'";
    $res = $conn->query($sql);
    $data = $res->fetch_assoc();
    $due_date = $data['due_date'];

    // 2. Calculate fine (₱10 per day)
    $fine_amount = 0;
    if ($today > $due_date) {
        $diff = strtotime($today) - strtotime($due_date);
        $days_late = floor($diff / (60 * 60 * 24));
        $fine_amount = $days_late * 10; 
    }

    // 3. Process the return
    $conn->query("UPDATE borrow_details SET status = 'Returned', return_date = '$today' WHERE borrow_detail_id = '$detail_id'");
    $conn->query("UPDATE books SET copies = copies + 1 WHERE book_id = '$book_id'");

    // 4. Insert fine record if overdue
    if ($fine_amount > 0) {
        $member_id = $data['member_id'] ? $data['member_id'] : "NULL";
        $conn->query("INSERT INTO fines (member_id, borrow_detail_id, amount, status) 
                      VALUES ($member_id, '$detail_id', '$fine_amount', 'Unpaid')");
    }

    echo "Success";
}
?>