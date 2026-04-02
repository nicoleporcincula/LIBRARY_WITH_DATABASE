<?php
include 'db_connect.php';

if (isset($_GET['id'])) {
    $detail_id = $_GET['id'];
    $return_date = date('Y-m-d');

    // 1. Get the book_id first so we can update stock
    $info = $conn->query("SELECT book_id FROM BORROW_DETAILS WHERE borrow_detail_id = '$detail_id'")->fetch_assoc();
    $book_id = $info['book_id'];

    // 2. Update the status to 'Returned' and set the return date
    $sql_update = "UPDATE BORROW_DETAILS 
                   SET status = 'Returned', return_date = '$return_date' 
                   WHERE borrow_detail_id = '$detail_id'";

    if ($conn->query($sql_update)) {
        // 3. Put the book back in stock
       $conn->query("UPDATE books SET copies = copies + 1 WHERE book_id = '$book_id'");
        
        // 4. Redirect back to the borrow panel
        header("Location: home.php#panel-borrow");
    } else {
        echo "Error returning book: " . $conn->error;
    }
}
?>