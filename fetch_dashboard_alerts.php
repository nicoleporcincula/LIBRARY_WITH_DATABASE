<?php
include 'db_connect.php';
header('Content-Type: application/json');

// --- 1. Summary Stats ---
// Total Borrows
$total_borrows = $conn->query("SELECT COUNT(*) as count FROM borrow")->fetch_assoc()['count'];

// Returned
$returned = $conn->query("SELECT COUNT(*) as count FROM borrow_details WHERE status = 'Returned'")->fetch_assoc()['count'];

// Overdue Count
$overdue_count = $conn->query("SELECT COUNT(*) as count FROM borrow_details bd JOIN borrow br ON bd.borrow_id = br.borrow_id WHERE bd.status = 'Borrowed' AND br.due_date < CURDATE()")->fetch_assoc()['count'];

// Total Fines
$total_fines = $conn->query("SELECT SUM(amount) as total FROM fines WHERE status = 'Paid'")->fetch_assoc()['total'] ?? 0;

// --- 2. Most Requested Volumes ---
$most_requested_sql = "SELECT b.title, COUNT(bd.book_id) as times_borrowed, b.status 
                       FROM borrow_details bd 
                       JOIN books b ON bd.book_id = b.book_id 
                       GROUP BY bd.book_id 
                       ORDER BY times_borrowed DESC LIMIT 5";
$most_requested_res = $conn->query($most_requested_sql);

// --- 3. Existing Alerts (Your previous code) ---
$overdue_sql = "SELECT b.title, COALESCE(m.name, g.name) as patron, br.due_date 
                FROM borrow_details bd
                JOIN borrow br ON bd.borrow_id = br.borrow_id
                JOIN books b ON bd.book_id = b.book_id
                LEFT JOIN members m ON br.member_id = m.member_id
                LEFT JOIN GUESTS g ON br.guest_id = g.guest_id
                WHERE bd.status = 'Borrowed' AND br.due_date < CURDATE() LIMIT 5";
$overdue_res = $conn->query($overdue_sql);

$fines_sql = "SELECT COALESCE(m.name, 'Guest') as patron, amount 
              FROM fines f LEFT JOIN members m ON f.member_id = m.member_id
              WHERE f.status = 'Unpaid' LIMIT 5";
$fines_res = $conn->query($fines_sql);

// Package everything together
echo json_encode([
    'stats' => [
        'total_borrows' => $total_borrows,
        'returned' => $returned,
        'overdue_count' => $overdue_count,
        'total_fines' => number_format($total_fines, 2)
    ],
    'most_requested' => $most_requested_res->fetch_all(MYSQLI_ASSOC),
    'overdue' => $overdue_res->fetch_all(MYSQLI_ASSOC),
    'fines' => $fines_res->fetch_all(MYSQLI_ASSOC)
]);
exit;