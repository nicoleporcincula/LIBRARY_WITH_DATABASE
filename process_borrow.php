<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id']; 
    $member_id = !empty($_POST['member_id']) ? $_POST['member_id'] : null;
    $guest_name = !empty($_POST['guest_name']) ? $_POST['guest_name'] : null;
    $due_date = isset($_POST['due_date']) ? $_POST['due_date'] : null;
    $borrow_date = date('Y-m-d');
    
    if (empty($_POST['selected_books']) || !$due_date) {
        die("Error: Please select at least one book and a due date.");
    }

    $guest_id = null;
    if ($guest_name && !$member_id) {
        $stmt_guest = $conn->prepare("INSERT INTO GUESTS (name, contact_info, deposit_amount) VALUES (?, 'N/A', 0.00)");
        $stmt_guest->bind_param("s", $guest_name);
        $stmt_guest->execute();
        $guest_id = $conn->insert_id;
    }

    $stmt = $conn->prepare("INSERT INTO BORROW (member_id, guest_id, user_id, borrow_date, due_date, per_book_fee) VALUES (?, ?, ?, ?, ?, 0.00)");
    $stmt->bind_param("iiiss", $member_id, $guest_id, $user_id, $borrow_date, $due_date);
    
    if ($stmt->execute()) {
        $borrow_id = $conn->insert_id;

        foreach ($_POST['selected_books'] as $book_id) {
            $qty = (int)$_POST["qty_$book_id"];
            
            for ($i = 0; $i < $qty; $i++) {
                $detail_stmt = $conn->prepare("INSERT INTO BORROW_DETAILS (borrow_id, book_id, status) VALUES (?, ?, 'Borrowed')");
                $detail_stmt->bind_param("ii", $borrow_id, $book_id);
                $detail_stmt->execute();
            }

            $conn->query("UPDATE BOOKS SET copies = copies - $qty WHERE book_id = '$book_id'");
        }
        
        header("Location: home.php#panel-borrow");
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
}
?>