<?php
include 'db_connect.php';

$from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');

$total_borrows = $conn->query("
    SELECT COUNT(*) as count 
    FROM borrow_details
")->fetch_assoc()['count'] ?? 0;

$returned = $conn->query("
    SELECT COUNT(*) as count 
    FROM borrow_details 
    WHERE status = 'Returned'
")->fetch_assoc()['count'] ?? 0;

$active = $conn->query("
    SELECT COUNT(*) as count 
    FROM borrow_details 
    WHERE status = 'Borrowed'
")->fetch_assoc()['count'] ?? 0;

$overdue = $conn->query("
    SELECT COUNT(*) as count 
    FROM borrow_details bd
    JOIN borrow b ON bd.borrow_id = b.borrow_id
    WHERE bd.status = 'Borrowed'
    AND b.due_date < CURDATE()
")->fetch_assoc()['count'] ?? 0;

$fines_sum = $conn->query("
    SELECT SUM(amount) as total 
    FROM fines 
    WHERE created_at BETWEEN '$from 00:00:00' AND '$to 23:59:59'
")->fetch_assoc()['total'] ?? 0;

$books_sql = "
SELECT 
    b.title, 
    COUNT(bd.book_id) as borrow_count, 
    b.copies 
FROM borrow_details bd 
JOIN books b ON bd.book_id = b.book_id
GROUP BY bd.book_id 
ORDER BY borrow_count DESC 
LIMIT 5
";

$books_result = $conn->query($books_sql);

$popular_books = [];
while ($row = $books_result->fetch_assoc()) {
    $popular_books[] = $row;
}

echo json_encode([
    'total_borrows' => $total_borrows,
    'returned' => $returned,
    'active' => $active,
    'overdue' => $overdue,
    'total_fines' => number_format($fines_sum, 2),
    'popular_books' => $popular_books
]);
?>