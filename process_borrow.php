<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = $_SESSION['user_id'];

    $member_id = !empty($_POST['member_id']) ? (int)$_POST['member_id'] : null;
    $guest_id = !empty($_POST['guest_id']) ? (int)$_POST['guest_id'] : null;

    if (!$member_id && !$guest_id) {
        die("Error: Please select a member or guest.");
    }

    if (empty($_POST['selected_books'])) {
        die("Error: Please select at least one book.");
    }

    $borrow_date = date('Y-m-d');

    $borrow_limit = 1;
    $duration_days = 3;

    if ($member_id) {

        $stmt = $conn->prepare("
            SELECT mt.borrow_limit, mt.duration_days
            FROM members m
            JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id
            WHERE m.member_id = ?
        ");
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $rules = $result->fetch_assoc();

        if ($rules) {
            $borrow_limit = $rules['borrow_limit'];
            $duration_days = $rules['duration_days'];
        }

        $check = $conn->prepare("
            SELECT COUNT(DISTINCT b.borrow_id) as total
            FROM borrow b
            JOIN borrow_details bd ON b.borrow_id = bd.borrow_id
            WHERE b.member_id = ?
            AND bd.status = 'Borrowed'
        ");
        $check->bind_param("i", $member_id);
        $check->execute();
        $count = $check->get_result()->fetch_assoc()['total'];

        if ($count >= $borrow_limit) {
            die("Borrow limit reached for this member.");
        }

    } else {
        $member_id = null;
    }

    if (!$guest_id) {
        $guest_id = null;
    }

    $due_date = date('Y-m-d', strtotime("+$duration_days days"));

    $stmt = $conn->prepare("
        INSERT INTO borrow (member_id, guest_id, user_id, borrow_date, due_date, per_book_fee)
        VALUES (?, ?, ?, ?, ?, 0.00)
    ");

    $stmt->bind_param("iiiss", $member_id, $guest_id, $user_id, $borrow_date, $due_date);

    if ($stmt->execute()) {

        $borrow_id = $conn->insert_id;

        foreach ($_POST['selected_books'] as $book_id) {

            $book_id = (int)$book_id;
            $qty = isset($_POST["qty_$book_id"]) ? (int)$_POST["qty_$book_id"] : 1;

            if ($qty < 1) {
                $qty = 1;
            }

            for ($i = 0; $i < $qty; $i++) {

                $detail_stmt = $conn->prepare("
                    INSERT INTO borrow_details (borrow_id, book_id, status)
                    VALUES (?, ?, 'Borrowed')
                ");
                $detail_stmt->bind_param("ii", $borrow_id, $book_id);
                $detail_stmt->execute();
            }

            $update_stmt = $conn->prepare("
                UPDATE books 
                SET copies = copies - ? 
                WHERE book_id = ?
            ");
            $update_stmt->bind_param("ii", $qty, $book_id);
            $update_stmt->execute();
        }

        header("Location: home.php#panel-borrow");
        exit;

    } else {
        echo "Error: " . $conn->error;
    }
}
?>