<?php
include 'db_connect.php';

$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// 1. Start the query WITHOUT the ORDER BY yet
$sql = "SELECT f.*, COALESCE(m.name, 'Guest') as patron_name, b.title 
        FROM fines f
        LEFT JOIN members m ON f.member_id = m.member_id
        JOIN borrow_details bd ON f.borrow_detail_id = bd.borrow_detail_id
        JOIN books b ON bd.book_id = b.book_id
        WHERE 1=1"; // WHERE 1=1 is a trick to allow adding "AND" easily

// 2. Add filters
if ($status == 'unpaid') {
    $sql .= " AND f.status = 'Unpaid'";
} elseif ($status == 'paid') {
    $sql .= " AND f.status = 'Paid'";
}

if (!empty($search)) {
    // Sanitize search to prevent SQL injection
    $searchSafe = $conn->real_escape_string($search);
    $sql .= " AND (m.name LIKE '%$searchSafe%' OR b.title LIKE '%$searchSafe%')";
}

// 3. NOW add the ORDER BY at the very end
$sql .= " ORDER BY f.status DESC, f.fine_id DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $badge = (strtolower($row['status']) == 'unpaid') ? 'badge-danger' : 'badge-success';
        
        echo "<tr>";
        echo "<td>" . $row['fine_id'] . "</td>";
        echo "<td class='gold-text'>" . htmlspecialchars($row['patron_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td class='gold-text'>₱" . number_format($row['amount'], 2) . "</td>";
        echo "<td>Overdue Penalty</td>";
        echo "<td><span class='badge $badge'>" . strtoupper($row['status']) . "</span></td>";
        echo "<td>";
        
        if(strtolower($row['status']) == 'unpaid') {
            echo "<button class='btn-small' onclick='payFine(" . $row['fine_id'] . ")'>Mark Paid</button>";
        } else {
            echo "<span style='color: #888; font-style: italic; font-size: 12px;'>Settled</span>";
        }
        
        echo "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='7' style='text-align:center;'>No fine records found.</td></tr>";
}
?>