<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id']; 
$member_id = !empty($_POST['member_id']) ? $_POST['member_id'] : null;
$guest_name = !empty($_POST['guest_name']) ? $_POST['guest_name'] : null;
    
    // MATCHING THE NEW NAME IN HOME.PHP
$book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : null;
    $due_date = isset($_POST['due_date']) ? $_POST['due_date'] : null;
    $borrow_date = date('Y-m-d');
    
    // Ensure we have the minimum data required
    if (!$book_id || !$due_date) {
        die("Error: Please select a book and a due date.");
    }

    $guest_id = null;
    // 1. Handle Guest logic
    if ($guest_name && !$member_id) {
        $stmt_guest = $conn->prepare("INSERT INTO GUESTS (name, contact_info, deposit_amount) VALUES (?, 'N/A', 0.00)");
        $stmt_guest->bind_param("s", $guest_name);
        $stmt_guest->execute();
        $guest_id = $conn->insert_id;
    }

    // 2. Create the Parent BORROW record (Matches your schema)
    $stmt = $conn->prepare("INSERT INTO BORROW (member_id, guest_id, user_id, borrow_date, due_date, per_book_fee) VALUES (?, ?, ?, ?, ?, 0.00)");
    $stmt->bind_param("iiiss", $member_id, $guest_id, $user_id, $borrow_date, $due_date);
    
    if ($stmt->execute()) {
        $borrow_id = $conn->insert_id;

        // 3. Insert into BORROW_DETAILS
        $detail_stmt = $conn->prepare("INSERT INTO BORROW_DETAILS (borrow_id, book_id, status) VALUES (?, ?, 'Borrowed')");
        $detail_stmt->bind_param("ii", $borrow_id, $book_id);
        $detail_stmt->execute();

        // 4. Update Inventory (Using 'copies' to match your actual BOOKS table)
        $conn->query("UPDATE BOOKS SET copies = copies - 1 WHERE book_id = '$book_id'");
        
        // Success: Redirect back to the library dashboard
        header("Location: home.php#panel-borrow");
        exit;
    } else {
        echo "Error creating borrow record: " . $conn->error;
    }
}
?>