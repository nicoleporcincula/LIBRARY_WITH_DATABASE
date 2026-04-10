<?php
include 'db_connect.php';

$sql = "SELECT bd.borrow_detail_id, 
               COALESCE(m.name, g.name) AS patron_name, 
               b.title, 
               b.book_id,
               br.borrow_date, 
               br.due_date, 
               bd.status 
        FROM borrow_details bd
        JOIN borrow br ON bd.borrow_id = br.borrow_id
        JOIN books b ON bd.book_id = b.book_id
        LEFT JOIN members m ON br.member_id = m.member_id
        LEFT JOIN guests g ON br.guest_id = g.guest_id
        ORDER BY bd.borrow_detail_id DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $statusClass = (strtolower($row['status']) == 'borrowed') ? 'status-borrowed' : 'status-returned';
        
        echo "<tr>";
        echo "<td>" . $row['borrow_detail_id'] . "</td>";
        echo "<td class='gold-text'>" . htmlspecialchars($row['patron_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . $row['borrow_date'] . "</td>";
        echo "<td>" . $row['due_date'] . "</td>";
        echo "<td><span class='badge " . $statusClass . "'>" . $row['status'] . "</span></td>";
        echo "<td>";
        if (strtolower($row['status']) === 'borrowed') {
            echo "<button class='btn-small' onclick='returnBook(" . $row['borrow_detail_id'] . ", " . $row['book_id'] . ")'>Return</button>";
        } else {
            echo "<span style='color: #888; font-style: italic; font-size: 11px;'>Archived</span>";
        }
        echo "</td>";
        echo "</tr>";
    }
}
?>