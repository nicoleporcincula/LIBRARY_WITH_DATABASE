  <?php
session_start();
include 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, full_name, role FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$user_name = htmlspecialchars($user['full_name']);

$rulesQuery = $conn->query("SELECT default_days, daily_fine FROM settings LIMIT 1");
$rules = $rulesQuery ? $rulesQuery->fetch_assoc() : ['default_days'=>7, 'daily_fine'=>10];

$result_tiers = $conn->query("SELECT * FROM membership_types ORDER BY membership_type_id ASC");

$result_members = $conn->query("
    SELECT m.*, mt.type_name 
    FROM members m
    LEFT JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id
    ORDER BY m.member_id ASC
");

$total_books = $conn->query("SELECT SUM(copies) AS total FROM books")->fetch_assoc()['total'] ?? 0;
$total_members = $conn->query("SELECT COUNT(*) AS total FROM members")->fetch_assoc()['total'] ?? 0;
$total_guests = $conn->query("SELECT COUNT(*) AS total FROM guests")->fetch_assoc()['total'] ?? 0;

$total_borrowed = $conn->query("SELECT COUNT(*) AS total FROM borrow_details WHERE status = 'Borrowed'")->fetch_assoc()['total'] ?? 0;

$total_overdue = $conn->query("
    SELECT COUNT(*) AS total
    FROM borrow_details bd
    JOIN borrow b ON bd.borrow_id = b.borrow_id
    WHERE bd.status = 'Borrowed' AND b.due_date < CURDATE()
")->fetch_assoc()['total'] ?? 0;

$result_books = $conn->query("
    SELECT b.*, c.category_name
    FROM books b
    LEFT JOIN categories c ON b.category_id = c.category_id
    ORDER BY b.book_id ASC
");

$sql_borrowed = "
    SELECT 
        bd.borrow_detail_id,
        COALESCE(m.name, g.name) AS borrower_name,
        bk.title AS book_title,
        b.due_date,
        bd.status
    FROM borrow_details bd
    JOIN borrow b ON bd.borrow_id = b.borrow_id
    JOIN books bk ON bd.book_id = bk.book_id
    LEFT JOIN members m ON b.member_id = m.member_id
    LEFT JOIN guests g ON b.guest_id = g.guest_id
    ORDER BY b.borrow_date DESC
";
$result_borrowed = $conn->query($sql_borrowed);

$categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
$category_options = "";
while ($cat = $categories->fetch_assoc()) {
    $category_options .= "<option value='{$cat['category_id']}'>{$cat['category_name']}</option>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Chronicle | Candlefern Library</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
  * { margin: 0; padding: 0; box-sizing: border-box; }

body {
    height: 100vh;
    overflow: hidden;
    font-family: 'Inter', sans-serif;
    color: #f5e6c8;
    background-color: #0a0502;
}

body::before {
    content: ""; position: fixed; inset: 0;
    background: url('images/home_bg.avif') no-repeat center/cover;
    filter: blur(5px); transform: scale(1.1); z-index: -2;
}

body::after {
    content: ""; position: fixed; inset: 0;
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.7) 0%, rgba(20, 10, 5, 0.9) 100%);
    z-index: -1;
}

.container { display: flex; height: 100vh; }

.sidebar {
    width: 280px;
    background: rgba(15, 8, 5, 0.95);
    padding: 40px 25px;
    border-right: 1px solid rgba(255, 215, 0, 0.1);
    display: flex;
    flex-direction: column;
    backdrop-filter: blur(10px);
}

.logo {
    font-family: 'Cinzel', serif;
    font-size: 22px;
    letter-spacing: 3px;
    margin-bottom: 50px;
    color: #ffd700;
    text-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
    line-height: 1.3;
}

.sidebar ul { list-style: none; }

.nav-item {
    margin: 12px 0;
    padding: 12px 15px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    color: #f5e6c8;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 15px;
    border: 1px solid transparent;
}

.nav-item:hover {
    background: rgba(255, 215, 0, 0.05);
    color: #ffd700;
    transform: translateX(8px);
}

.nav-item.active {
    background: rgba(255, 215, 0, 0.1);
    color: #ffd700;
    border: 1px solid rgba(255, 215, 0, 0.2);
    font-weight: 600;
}

.main { flex: 1; padding: 40px 60px; overflow-y: auto; position: relative; }

.welcome {
    font-family: 'Cinzel', serif;
    color: #ffd700;
    font-size: 28px;
    margin-bottom: 40px;
    padding-bottom: 15px;
    border-bottom: 1px solid rgba(255, 215, 0, 0.15);
}

.profile { position: absolute; top: 40px; right: 60px; z-index: 1000; }

.profile-circle {
    width: 48px; height: 48px; border-radius: 50%;
    border: 2px solid #ffd700; background: rgba(255, 215, 0, 0.1);
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; cursor: pointer; transition: 0.3s;
}

.profile-circle:hover { box-shadow: 0 0 20px rgba(255, 215, 0, 0.3); transform: scale(1.05); }

.dropdown {
    display: none; position: absolute; top: 60px; right: 0;
    background: rgba(20, 10, 5, 0.98); backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 215, 0, 0.3); padding: 10px;
    border-radius: 10px; min-width: 140px;
}

.dropdown p {
    color: #f5e6c8; font-size: 13px; padding: 10px 15px;
    text-align: center; text-transform: uppercase; cursor: pointer;
    transition: 0.2s; border-radius: 6px;
}

.dropdown p:hover { background: rgba(255, 215, 0, 0.1); color: #ffd700; }

.cards { display: flex; gap: 15px; margin-bottom: 35px; }

.card {
    flex: 1; padding: 20px 10px; text-align: center;
    background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 215, 0, 0.1); border-radius: 12px;
    transition: transform 0.3s ease;
}

.card:hover { transform: translateY(-5px); border-color: rgba(255, 215, 0, 0.4); }

.card-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: rgba(255, 215, 0, 0.6); margin-bottom: 12px; display: block; font-weight: 600; }
.card-value { font-family: 'Cinzel', serif; font-size: 48px; font-weight: 700; color: #ffd700; line-height: 1; text-shadow: 0 0 15px rgba(255, 215, 0, 0.4); }

button {
    font-family: 'Cinzel', serif; cursor: pointer;
    transition: all 0.3s ease; text-transform: uppercase;
    letter-spacing: 1px; outline: none; border: 1px solid #ffd700;
    border-radius: 8px; background: transparent; color: #ffd700;
}

button:not(.btn-secondary):not(.btn-danger):hover {
    background: #ffd700;
    color: #0a0502;
    box-shadow: 0 0 20px rgba(255, 215, 0, 0.4);
}

.actions { display: flex; gap: 20px; margin-bottom: 50px; }
.actions button { flex: 1; padding: 12px; font-size: 11px; }

.btn-small { padding: 8px 15px; font-size: 10px; background: rgba(255, 215, 0, 0.05); }

.btn-danger { border-color: #ff4d4d; color: #ff4d4d; }
.btn-danger:hover { background: #ff4d4d; color: #fff; box-shadow: 0 0 10px rgba(255, 77, 77, 0.3); border-color: #ff4d4d; }

.btn-secondary { background: transparent; border: 1px solid rgba(245, 230, 200, 0.3); color: #f5e6c8; flex: 1; }
.btn-secondary:hover { background: rgba(245, 230, 200, 0.1); }

.btn-promote, .btn-enroll {
    background-color: #d4af37;
    color: #1a1a1a;
    border: none;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 6px 15px;
}

.btn-promote:hover, .btn-enroll:hover {
    background-color: #f1c40f;
    box-shadow: 0 0 10px rgba(212, 175, 55, 0.5);
    transform: translateY(-1px);
}

.btn-group { display: flex; gap: 8px; justify-content: flex-end; }

.borrow-table, .activity { 
    background: rgba(15, 8, 5, 0.6); padding: 30px; 
    border-radius: 16px; margin-bottom: 30px; border: 1px solid rgba(255, 215, 0, 0.05); 
}

table { width: 100%; border-collapse: collapse; }
th { text-align: left; padding: 15px; color: rgba(255, 215, 0, 0.8); border-bottom: 2px solid rgba(255, 215, 0, 0.2); font-size: 12px; text-transform: uppercase; }
td { padding: 18px 15px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); font-size: 14px; word-wrap: break-word; white-space: normal; }

h3 { font-family: 'Cinzel', serif; color: #ffd700; margin-bottom: 25px; font-size: 18px; letter-spacing: 1px; }
.gold-text { color: #d4af37; font-weight: bold; }

.form-row { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
.panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; width: 100%; }

.col-id { width: 10%; }
.col-patron { width: 15%; }
.col-books { width: 25%; }
.col-date { width: 15%; }
.col-status { width: 15%; }
.col-action { width: 20%; }
.action-cell { text-align: right; }

.activity li { padding: 10px 0; border-left: 2px solid #ffd700; padding-left: 20px; margin-bottom: 12px; font-size: 14px; list-style: none; }

.search-box input { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 215, 0, 0.2); padding: 10px 20px; border-radius: 20px; color: #f5e6c8; outline: none; transition: 0.3s; width: 250px; }
.search-box input:focus { border-color: #ffd700; width: 300px; }

.modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 2000; align-items: center; justify-content: center; }
.modal-content { background: #140a05; padding: 40px; border: 1px solid #ffd700; border-radius: 15px; width: 400px; box-shadow: 0 0 25px rgba(255, 215, 0, 0.15); }

.archive-form input, .archive-select, .settings-form input { width: 100%; padding: 12px; margin-bottom: 15px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,215,0,0.3); color: white; border-radius: 5px; outline: none; }
.archive-select { background: #1a0f0a; color: #f5e6c8; }

select[multiple] { height: 100px; scrollbar-width: thin; scrollbar-color: #ffd700 transparent; }

.form-actions { display: flex; gap: 10px; margin-top: 20px; }
.form-actions button[type="submit"] { background: #ffd700; color: #0a0502; flex: 2; padding: 10px 20px; font-size: 11px; letter-spacing: 1px; }

.badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase; font-weight: 600; }
.badge-regular { background: rgba(255, 215, 0, 0.1); color: #ffd700; border: 1px solid rgba(255, 215, 0, 0.3); }
.badge-success { background: rgba(0, 255, 100, 0.1); color: #00ff64; border: 1px solid rgba(0, 255, 100, 0.3); }
.badge-danger { background: rgba(255, 77, 77, 0.1); color: #ff4d4d; border: 1px solid rgba(255, 77, 77, 0.3); }

.main::-webkit-scrollbar, #volumeList::-webkit-scrollbar { width: 6px; }
.main::-webkit-scrollbar-thumb, #volumeList::-webkit-scrollbar-thumb { background: #ffd700; border-radius: 10px; border: 1px solid rgba(15, 8, 5, 0.9); }
.main::-webkit-scrollbar-thumb:hover, #volumeList::-webkit-scrollbar-thumb:hover { background: #c5a059; }

.report-controls { display: flex; align-items: center; gap: 12px; background: rgba(255, 255, 255, 0.03); padding: 10px 20px; border-radius: 12px; border: 1px solid rgba(255, 215, 0, 0.1); }
.report-controls input[type="date"] { background: rgba(10, 5, 2, 0.6); border: 1px solid rgba(255, 215, 0, 0.3); color: #f5e6c8; padding: 8px 12px; border-radius: 6px; outline: none; transition: 0.3s; }
.report-controls input[type="date"]:focus { border-color: #ffd700; box-shadow: 0 0 10px rgba(255, 215, 0, 0.2); }
.report-controls input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(0.8) sepia(1) saturate(5) hue-rotate(15deg); cursor: pointer; opacity: 0.6; }

.date-label { font-family: 'Cinzel', serif; font-size: 11px; color: #ffd700; letter-spacing: 1px; }

.settings-grid { display: flex; gap: 25px; }
.settings-section { flex: 1; background: rgba(15, 8, 5, 0.6); padding: 25px; border-radius: 16px; border: 1px solid rgba(255, 215, 0, 0.05); }
.settings-section h4 { font-family: 'Cinzel', serif; color: #ffd700; font-size: 14px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255, 215, 0, 0.1); padding-bottom: 10px; }

.settings-form label { display: block; font-size: 11px; color: rgba(245, 230, 200, 0.7); margin-bottom: 5px; text-transform: uppercase; letter-spacing: 1px; }

select[name="category_id"] {
    width: 100%; background-color: #1a140f; border: 1px solid #3d3024; border-radius: 8px; padding: 12px; margin-bottom: 15px; color: #8c7d70; font-family: 'Georgia', serif; font-size: 14px; cursor: pointer; appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238c7d70' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 15px center;
}

select[name="category_id"]:focus { border-color: #d4af37; outline: none; }

.upgrade-modal { max-width: 420px; padding: 30px 35px; }
.highlight-name { font-size: 18px; font-family: 'Cinzel', serif; margin-top: 5px; }
   </style>
</head>

<body>
    <div class="container">
        <div class="sidebar">
            <h1 class="logo">CANDLEFERN<br>LIBRARY</h1>
            <ul>
                <li class="nav-item active" data-target="panel-dashboard">🏠 Dashboard</li>
                <li class="nav-item" data-target="panel-books">📚 Books</li>
                <li class="nav-item" data-target="panel-members">👤 Members</li>
                <li class="nav-item" data-target="panel-borrow">🔄 Borrow / Return</li>
                <li class="nav-item" data-target="panel-fines">💰 Fines</li>
                <li class="nav-item" data-target="panel-reports">📊 Reports</li>
                <li class="nav-item" data-target="panel-settings">⚙ Settings</li>
            </ul>
        </div>

        <div class="main">
            <div class="profile" id="profile">
                <div class="profile-circle">👤</div>
                <div class="dropdown" id="profileDropdown">
                    <p id="logoutBtn">Logout</p>
                </div>
            </div>

            <div class="welcome">Welcome, <?php echo htmlspecialchars($user_name); ?>!</div>

            <div id="panel-dashboard" class="content-panel">
        <div class="cards">
    <div class="card"><p class="card-label">Total Books</p><span class="card-value"><?php echo $total_books; ?></span></div>
    <div class="card"><p class="card-label">Members</p><span class="card-value"><?php echo $total_members; ?></span></div>
    <div class="card"><p class="card-label">Borrowed</p><span class="card-value"><?php echo $total_borrowed; ?></span></div>
    <div class="card"><p class="card-label">Overdue</p><span class="card-value"><?php echo $total_overdue; ?></span></div>
</div>

<div class="actions">
<button onclick="showPanel('panel-books')">Add Book</button>
<button onclick="showPanel('panel-members')">Add Member</button>
<button onclick="showPanel('panel-borrow')">Borrow Book</button>
<button onclick="showPanel('panel-borrow')">Return Book</button>
</div>

<div class="borrow-table">
    <h3>Current Borrowed Books</h3>
    <table>
        <thead>
           <tr>
        <th>ID</th>
        <th>Member/Guest</th> <th>Book Title</th>   <th>Borrow Date</th>  <th>Due Date</th>     <th>Status</th>       <th>Action</th>       </tr>
        </thead>
       <tbody id="dashboardLoanTable">
<?php
if ($result_borrowed->num_rows > 0) {
    while ($row = $result_borrowed->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($row['borrower_name']) . "</td>
                <td class='gold-text'>" . htmlspecialchars($row['book_title']) . "</td>
                <td>" . $row['due_date'] . "</td>
                <td>" . $row['status'] . "</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='4' style='text-align:center;'>No borrowed books found</td></tr>";
}
?>
</tbody>
    </table>
</div>
            </div>

            <div id="panel-books" class="content-panel" style="display: none;">
                <div class="panel-header">
                    <h3>Library Archive</h3>
                    <div class="search-box">
    <input type="text" id="bookSearch" onkeyup="searchBooks()" placeholder="Search the archives...">
</div>
                </div>
                <div class="actions" style="margin-bottom: 20px;">
                    <button onclick="toggleModal('addBookModal')" style="width: 100%;">+ Add New Volume</button>
                </div>
                <div class="borrow-table">
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Title</th><th>Author</th><th>ISBN</th><th>Year</th><th>Copies</th><th>Actions</th><th>Category</th> </tr>
                        </thead>
                        <tbody>
<?php
if ($result_books->num_rows > 0) {
    while ($row = $result_books->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['book_id'] . "</td>"; 
        echo "<td class='gold-text'>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . htmlspecialchars($row['author'] ?? 'Unknown') . "</td>";
        echo "<td>" . $row['isbn'] . "</td>";
        echo "<td>" . $row['publish_year'] . "</td>"; 
        echo "<td>" . $row['copies'] . "</td>";
        echo "<td>" . htmlspecialchars($row['category_name'] ?? '-') . "</td>"; 
        echo "<td style='text-align: right;'>
                <div class='btn-group' style='display: flex; justify-content: flex-end; gap: 5px;'>
                    <button class='btn-small' onclick=\"openEditBookModal(
                        '{$row['book_id']}', 
                        '" . addslashes($row['title']) . "', 
                        '" . addslashes($row['author'] ?? 'Unknown') . "', 
                        '{$row['isbn']}', 
                        '{$row['publish_year']}', 
                        '{$row['copies']}', 
                        '{$row['category_id']}'
                    )\">Edit</button>
                    <button class='btn-small btn-danger' onclick=\"if(confirm('Are you sure?')) window.location.href='delete_book.php?id=" . $row['book_id'] . "'\">Delete</button>
                </div>
              </td>";
        echo "</tr>";
    }
}
?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="panel-members" class="content-panel" style="display: none;">
                <div class="panel-header">
                    <h3>Member Registry</h3>
                    <div class="search-box">
    <input type="text" id="memberSearch" oninput="searchMembers()" placeholder="Search members...">
</div>
                </div>
                <div class="actions" style="margin-bottom: 20px;">
                   
                </div>
                <div class="borrow-table"> <table class="styled-table">
                    <table>
                        <thead>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Phone</th>
        <th>Joined Date</th>
        <th>Type</th>
        <th class="action-cell">Management</th> </tr>
</thead>
                        <tbody>
<?php
if ($result_members && $result_members->num_rows > 0) {
    while ($m_row = $result_members->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $m_row['member_id'] . "</td>";
        echo "<td class='gold-text'>" . htmlspecialchars($m_row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($m_row['phone']) . "</td>";
        echo "<td>" . $m_row['membership_date'] . "</td>";
        
        echo "<td>";
        if (!empty($m_row['type_name'])) {
            echo "<span class='badge badge-regular'>" . htmlspecialchars($m_row['type_name']) . "</span>";
        } else {
            echo "<span class='badge' style='opacity: 0.5;'>Guest/None</span>";
        }
        echo "</td>";

        echo "<td>
                <div class='btn-group'>
                    <button class='btn-small' onclick=\"openEditMemberModal(
                        '{$m_row['member_id']}', 
                        '" . addslashes($m_row['name']) . "', 
                        '{$m_row['phone']}', 
                        '" . addslashes($m_row['address']) . "',
                        '{$m_row['membership_type_id']}'
                    )\">Edit</button>
                    <button class='btn-small btn-danger' onclick=\"if(confirm('Revoke membership?')) window.location.href='delete_member.php?id=" . $m_row['member_id'] . "'\">Delete</button>
                </div>
              </td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='6'>No members registered.</td></tr>";
}
?>
                        </tbody>
                    </table>

                </div>
                <br><br>
                <div class="borrow-table"> <table class="styled-table">
                    <table>
               <div class="panel-header">
    <h3>Visitor Registry</h3>
    <button onclick="toggleModal('addGuestModal')" class="btn-small">
        + Register New Visitor
    </button>
</div>

    <table class="styled-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Contact</th>
                <th>Deposit</th>
                <th class="action-cell">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $result_guests = $conn->query("SELECT * FROM guests ORDER BY guest_id ASC");
            while ($row = $result_guests->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['guest_id']; ?></td>
                <td class="gold-text"><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['contact_info']); ?></td>
                <td>₱<?php echo number_format($row['deposit_amount'], 2); ?></td>
                <td class="action-cell">
                    <button class="btn-small" onclick="openUpgradeModal(<?php echo $row['guest_id']; ?>, '<?php echo addslashes($row['name']); ?>')">
                        Enroll Member
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
            </div>
           
<div id="panel-borrow" class="content-panel" style="display: none;">
    <div class="panel-header"><h3>BORROWING & CIRCULATION</h3></div>
    <div class="activity">
        <h3 style="font-size: 14px; margin-bottom: 15px;">LOG NEW TRANSACTION</h3>
        <form id="borrowForm" class="archive-form" method="POST" action="process_borrow.php">
            
            <label style="font-size: 10px; color: #ffd700; letter-spacing: 1px;">PATRON NAME (ENROLLED)</label>
            <input id="memberInput" list="memberOptions" name="member_id" class="archive-select" placeholder="Search members...">
            <datalist id="memberOptions">
                <?php
                $res_m = $conn->query("SELECT member_id, name FROM members ORDER BY name ASC");
                while($m = $res_m->fetch_assoc()) {
                    echo "<option value='{$m['member_id']}'>" . htmlspecialchars($m['name']) . "</option>";
                }
                ?>
            </datalist>

            <div style="text-align: center; margin-bottom: 15px; margin-top: 10px;">
                <span style="color: #ffd700; font-family: 'Cinzel'; font-size: 10px;">— OR SELECT GUEST —</span>
            </div>

            <label style="font-size: 10px; color: #ffd700; letter-spacing: 1px;">VISITOR NAME (TEMPORARY)</label>
            <input id="guestInput" list="guestOptions" name="guest_id" class="archive-select" placeholder="Search registered guests...">
            <datalist id="guestOptions">
                <?php
                $res_g = $conn->query("SELECT guest_id, name FROM guests ORDER BY name ASC");
                while($g = $res_g->fetch_assoc()) {
                    // Using guest_id as the value so process_borrow.php knows exactly which guest it is
                    echo "<option value='{$g['guest_id']}'>" . htmlspecialchars($g['name']) . "</option>";
                }
                ?>
            </datalist>

            <div style="position: relative; margin-top: 20px;">
                <label style="font-size: 10px; color: #ffd700; letter-spacing: 1px;">SELECT VOLUMES</label>
                <div class="volume-selector-container" style="border: 1px solid #c5a059; background: rgba(20, 10, 5, 0.8); border-radius: 4px; padding: 10px;">
                    <input type="text" id="volumeSearch" placeholder="Search volumes..." style="margin-bottom: 10px; font-size: 12px; height: 30px;">
                    
                    <div id="volumeList" style="max-height: 150px; overflow-y: auto; padding-right: 5px;">
                        <?php
                        $res_b = $conn->query("SELECT book_id, title, copies FROM books WHERE copies > 0 ORDER BY title ASC");
                        while($b = $res_b->fetch_assoc()) {
                            echo "
                            <div class='book-item' data-title='".strtolower(htmlspecialchars($b['title']))."' style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; padding-bottom: 5px; border-bottom: 1px solid rgba(197, 160, 89, 0.2);'>
                                <div style='flex: 1;'>
                                    <span style='font-size: 12px; color: #f5e6c8;'>".htmlspecialchars($b['title'])."</span>
                                    <br><small style='color: #ffd700; font-size: 9px;'>Available: {$b['copies']}</small>
                                </div>
                                <div style='display: flex; align-items: center; gap: 5px;'>
                                    <input type='checkbox' name='selected_books[]' value='{$b['book_id']}' class='book-check'>
                                    <input type='number' name='qty_{$b['book_id']}' value='1' min='1' max='{$b['copies']}' style='width: 40px; height: 25px; font-size: 12px; text-align: center; padding: 0;'>
                                </div>
                            </div>";
                        }
                        ?>
                    </div>
                </div>
                <input type="hidden" name="book_id" id="submitted_book_id">
            </div>

            <div class="form-row" style="margin-top: 20px;">
                
                <button type="submit" style="flex: 1; height: 45px; background: #ffd700; color: #0a0502; align-self: flex-end; font-weight: bold;">BORROW</button>
            </div>
        </form>
    </div>

    <div class="borrow-table">
        <div class="panel-header" style="margin-bottom: 15px;">
            <h3 style="margin-bottom: 0;">BORROWED BOOKS</h3>
            <div class="search-box">
                <input type="text" id="borrowSearch" onkeyup="searchBorrows()" placeholder="Filter loans..." style="width: 200px;">
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th class="col-id">ID</th>
                    <th class="col-patron">MEMBER/GUEST</th>
                    <th class="col-books">BOOK</th>
                    <th class="col-date">BORROWED</th>
                    <th class="col-date">DUE DATE</th>
                    <th class="col-status">STATUS</th>
                    <th class="col-action">ACTION</th>
                </tr>
            </thead>
            <tbody id="loanTableBody">
                <tr><td colspan="7" style="text-align:center;">Loading Archive...</td></tr>
            </tbody>
        </table>
    </div>
</div>
            <div id="panel-fines" class="content-panel" style="display: none;">
    <div class="panel-header">
        <h3>💰 Fines & Penalties</h3>
        <div class="search-box">
           <input type="text" id="fineSearchInput" onkeyup="loadFinesTable()" placeholder="Search member or book...">
        </div>
    </div>

    <div class="actions" style="margin-bottom: 20px;">
   <button onclick="filterFines('all')">ALL RECORDS</button>
<button onclick="filterFines('unpaid')">UNPAID ONLY</button>
<button onclick="filterFines('paid')">SETTLED FINES</button>
    </div>

    <div class="borrow-table">
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">ID</th>
                    <th style="width: 15%;">Member/Guest</th>
                    <th style="width: 20%;">Book Volume</th>
                    <th style="width: 12%;">Amount</th>
                    <th style="width: 20%;">Reason</th>
                    <th style="width: 12%;">Status</th>
                    <th style="width: 13%;">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>001</td>
                    <td class="gold-text">John Doe</td>
                    <td>Java Basics</td>
                    <td class="gold-text">₱50.00</td>
                    <td style="font-style: italic; opacity: 0.8;">Late return (5 days)</td>
                    <td><span class="badge badge-danger">Unpaid</span></td>
                    <td>
                        <button class="btn-small" title="Update to Paid">Mark Paid</button>
                    </td>
                </tr>
                <tr>
                    <td>002</td>
                    <td class="gold-text">Nicole P.</td>
                    <td>Architecture of Dreams</td>
                    <td class="gold-text">₱25.00</td>
                    <td style="font-style: italic; opacity: 0.8;">Damaged Page</td>
                    <td><span class="badge badge-success">Paid</span></td>
                    <td>
                        <button class="btn-small btn-secondary" disabled>Settled</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
          <div id="panel-reports" class="content-panel" style="display: none;">
    <div class="panel-header" style="align-items: flex-start; flex-direction: column; gap: 20px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 24px;">📜</span>
            <h3 style="margin-bottom: 0;">THE CHRONICLE REPORTS</h3>
        </div>
        
        <div class="report-controls">
<label>FROM</label>
<input type="date" id="reportFrom" value="2026-03-01">

<label>TO</label>
<input type="date" id="reportTo" value="2026-04-04">
            
<button id="generateReportBtn" class="btn-gold" onclick="loadReports()">GENERATE REPORT</button>
        </div>
    </div>

    <div class="cards">
<div class="card">
    <h3>TOTAL BORROWS</h3>
    <h1 id="total-borrows-val">0</h1> 
</div>
<div class="card">
    <h3>RETURNED</h3>
    <h1 id="returned-val">0</h1>
</div>
<div class="card">
    <h3>OVERDUE</h3>
    <h1 id="overdue-val">0</h1>
</div>
<div class="card">
    <h3>TOTAL FINES</h3>
    <h1 id="total-fines-val">₱0.00</h1>
</div>
    </div>

    <div class="borrow-table">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
            <span style="font-size: 18px;">📊</span>
            <h3 style="margin-bottom: 0; font-size: 16px;">MOST REQUESTED VOLUMES</h3>
        </div>
        <table id="most-requested-table">
            <thead>
                <tr>
                    <th>BOOK TITLE</th>
                    <th>TIMES BORROWED</th>
                    <th>CURRENT STATUS</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="gold-text">The Whispering Oaks</td>
                    <td>45</td>
                    <td><span class="badge badge-success">Available</span></td>
                </tr>
                <tr>
                    <td class="gold-text">Java Basics</td>
                    <td>32</td>
                    <td><span class="badge badge-danger">Out of Stock</span></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="borrow-table" style="flex: 1;">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
        <span style="font-size: 16px;">⚠️</span>
        <h3 style="margin-bottom: 0; font-size: 14px;">OVERDUE ALERTS</h3>
    </div>
    <table>
        <thead>
            <tr><th>MEMBER</th><th>DUE DATE</th></tr>
        </thead>
     <tbody id="overdue-alerts-container">
    <tr><td colspan="2" style="text-align:center; opacity:0.5;">Searching archives...</td></tr>
</tbody>
    </table>
</div>

<div class="borrow-table" style="flex: 1;">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
        <span style="font-size: 16px;">💰</span>
        <h3 style="margin-bottom: 0; font-size: 14px;">PENDING FINES</h3>
    </div>
    <table>
        <thead>
            <tr><th>MEMBER</th><th>AMOUNT</th></tr>
        </thead>
<tbody id="pending-fines-container">
    <tr><td colspan="2" style="text-align:center; opacity:0.5;">Calculating fines...</td></tr>
</tbody>
    </table>
</div>
</div>
            
<div id="panel-settings" class="content-panel" style="display: none;">
    <div class="panel-header">
        <h3>⚙️ SYSTEM CONFIGURATION</h3>
    </div>

    <div class="settings-grid">
        <div class="left-col">
            <div class="settings-section" style="margin-bottom: 25px;">
                <h4>👤 ACCOUNT PRIVILEGES</h4>
               <form class="settings-form" id="accountForm">
    <label>Administrative Username</label>
    <input type="text" id="adminUsername" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>">
    
    <label>Email Address</label>
    <input type="email" id="adminEmail" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
    
    <label>Update Secret Cipher (Password)</label>
    <input type="password" id="adminPassword" name="password" placeholder="••••••••">
    
    <button type="submit" class="btn-small" style="width: 100%;">Update Credentials</button>
</form>
<p id="updateMsg" style="color: green; font-size: 12px;"></p>
            </div>

            <div class="settings-section">
                <h4>📜 CIRCULATION RULES</h4>
               <form class="settings-form" id="rulesForm">
            <div style="display: flex; gap: 15px;">
                <div style="flex: 1;">
                    <label>Default Borrow Days</label>
                    <input type="number" id="defaultDays" name="default_days" value="<?php echo htmlspecialchars($rules['default_days']); ?>">
                </div>
                <div style="flex: 1;">
                    <label>Daily Fine (₱)</label>
                    <input type="number" step="0.01" id="dailyFine" name="daily_fine" value="<?php echo htmlspecialchars($rules['daily_fine']); ?>">
                </div>
            </div>
            <button type="submit" class="btn-small" style="width: 100%;">Save Rules</button>
        </form>
<p id="rulesMsg" style="color: green; font-size: 12px; display: none;"></p>
            </div>
        </div>

        <div class="right-col">
            <div class="settings-section">
    <h4>🎖️ MEMBERSHIP TIERS</h4>
    <table style="font-size: 12px;">
        <thead>
            <tr>
                <th>TYPE</th>
                <th>BORROW LIMIT</th>
                <th>DURATION (DAYS)</th>
                <th>ACTION</th>
            </tr>
        </thead>
        <tbody id="membershipTbody">
            <?php
            $sql = "SELECT * FROM membership_types ORDER BY membership_type_id ASC";
            $result = $conn->query($sql);
            while($tier = $result->fetch_assoc()){
                echo "<tr data-id='{$tier['membership_type_id']}'>
                        <td class='gold-text'>{$tier['type_name']}</td>
                        <td>{$tier['borrow_limit']}</td>
                        <td>{$tier['duration_days']}</td>
                        <td><button class='btn-small editTier' style='padding: 4px 10px;'>Edit</button></td>
                    </tr>";
            }
            ?>
        </tbody>
    </table>
</div>
<br><br>
            <div class="settings-section">
                <h4>🏛️ ARCHIVE METADATA</h4>
                <div style="padding: 10px 0;">
                    <p class="info-text"><strong>System Version:</strong> <span class="gold-text">v1.0.4-Beta</span></p>
                    <p class="info-text"><strong>Core Engine:</strong> PHP 8.2 / MySQL</p>
                    <p class="info-text"><strong>Developed By:</strong> <span style="font-family: 'Cinzel'; color: #ffd700;">The Chronicle Team</span></p>
                    <hr style="border: 0; border-top: 1px solid rgba(255, 215, 0, 0.1); margin: 15px 0;">
                    <p style="font-size: 11px; opacity: 0.5; font-style: italic;">© 2026 Candlefern Library Management System. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>
</div>
        </div>
    </div>

   <div id="addBookModal" class="modal">
    <div class="modal-content">
        <h3>Catalog New Volume</h3>
        <form class="archive-form" method="POST" action="add_book.php">
            <input type="text" name="title" placeholder="Title" required>
            <input type="text" name="isbn" placeholder="ISBN" required>
            <input type="text" name="author" placeholder="Author" required>
<select name="category_id" required>
    <option value="" disabled selected>-- Select Category --</option>
    <?php
    $res_cat = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
    if ($res_cat) {
        while($cat = $res_cat->fetch_assoc()) {
            echo "<option value='" . htmlspecialchars($cat['category_id']) . "'>" 
                 . htmlspecialchars($cat['category_name']) . 
                 "</option>";
        }
    }
    ?>
</select>
            <div class="form-row">
                <input type="number" name="year" placeholder="Year" required>
                <input type="number" name="copies" placeholder="Copies" required>
                
            </div>
            <div class="form-actions">
                <button type="submit">Add to Collection</button>
                <button type="button" onclick="toggleModal('addBookModal')" class="btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="editBookModal" class="modal">
    <div class="modal-content">
        <h3>Edit Volume Details</h3>
        <form class="archive-form" method="POST" action="edit_book.php">
            <input type="hidden" name="book_id" id="edit_book_id">
            <input type="text" name="title" id="edit_title" placeholder="Title" required>
            <input type="text" name="isbn" id="edit_isbn" placeholder="ISBN" required>
            <input type="text" name="author" placeholder="Author" required>
            <select name="category_id" id="edit-category">
                    <option value="">-- Select Category --</option>
                    <?php
                    $res_cat = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
                    while($cat = $res_cat->fetch_assoc()) {
                        echo "<option value='{$cat['category_id']}'>" . htmlspecialchars($cat['category_name']) . "</option>";
                    }
                    ?>
                </select>
            <div class="form-row">
                <input type="number" name="year" id="edit_year" placeholder="Year" required>
                <input type="number" name="copies" id="edit_copies" placeholder="Copies" required>
            </div>
            <div class="form-actions">
                <button type="submit">Save Changes</button>
                <button type="button" onclick="toggleModal('editBookModal')" class="btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>
<div id="addGuestModal" class="modal">
    <div class="modal-content">
        <h3>Register New Visitor</h3>

        <form class="archive-form" method="POST" action="add_guest.php">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="text" name="contact_info" placeholder="Phone">
            <input type="number" name="deposit_amount" placeholder="Deposit (₱)" step="1" value="0.00">

             <div class="form-actions">
                <button type="submit">Register Guest</button>
                <button type="button" onclick="toggleModal('addGuestModal')" class="btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>
<div id="upgradeModal" class="modal">
    <div class="modal-content upgrade-modal">
        <h3 class="modal-title">Complete Enrollment</h3>
        
        <form class="archive-form" action="promote_guest.php" method="POST">
            <input type="hidden" name="guest_id" id="upgrade_guest_id">

            <div class="form-group">
                <label>Enrolling Visitor</label>
                <p id="upgrade_guest_name" class="gold-text highlight-name"></p>
            </div>

            <div class="form-group">
                <label>Select Membership Tier</label>
                <select name="membership_type_id" class="archive-select" required>
                    <option value="" disabled selected>Choose a tier...</option>
                    <option value="1">Student</option>
                    <option value="2">Regular</option>
                    <option value="3">Premium</option>
                </select>
            </div>

            <div class="form-actions centered-actions">
                <button type="submit">Finalize Enrollment</button>
                <button type="button" onclick="toggleModal('upgradeModal')" class="btn-secondary">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<div id="editMemberModal" class="modal">
    <div class="modal-content">
        <h3>Update Member Scroll</h3>
        <form class="archive-form" method="POST" action="edit_member.php">
            <input type="hidden" name="member_id">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="text" name="phone" placeholder="Phone" required>
            <textarea name="address" placeholder="Address" 
                      style="background: rgba(20, 10, 5, 0.8); color: #f5e6c8; border: 1px solid #c5a059; padding: 10px; margin-bottom: 15px; border-radius: 4px; width: 100%;"></textarea>
            
            <select name="membership_type_id" class="archive-select">
                <option value="1">Student</option>
                <option value="2">Regular</option>
                <option value="3">Premium</option>
            </select>

            <div class="form-actions">
                <button type="submit">Save Changes</button>
                <button type="button" onclick="toggleModal('editMemberModal')" class="btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="addBorrowModal" class="modal">
    <div class="modal-content" style="width: 500px;">
        <h3>Log New Transaction</h3>
        <form class="archive-form" method="POST" action="process_borrow.php">
            <label style="font-size: 10px; color: #ffd700;">SELECT PATRON</label>
            <select name="member_id" class="archive-select" required>
                <option value="" disabled selected>-- Select Member --</option>
                <?php
                // Fetch members for the dropdown
                $res_m = $conn->query("SELECT member_id, name FROM members ORDER BY name ASC");
                while($m = $res_m->fetch_assoc()) {
                    echo "<option value='{$m['member_id']}'>" . htmlspecialchars($m['name']) . "</option>";
                }
                ?>
            </select>

            <label style="font-size: 10px; color: #ffd700;">SELECT VOLUME</label>
            <select name="book_id" class="archive-select" required>
                <option value="" disabled selected>-- Select Book --</option>
                <?php
                // Only show books that actually have copies available
                $res_b = $conn->query("SELECT book_id, title FROM books WHERE copies > 0 ORDER BY title ASC");
                while($b = $res_b->fetch_assoc()) {
                    echo "<option value='{$b['book_id']}'>" . htmlspecialchars($b['title']) . "</option>";
                }
                ?>
            </select>

            <div style="margin-bottom: 15px;">
                <label style="font-size: 10px; color: #ffd700;">DUE DATE</label>
                <input type="date" name="due_date" required style="margin-top: 5px;">
            </div>

            <div class="form-actions">
                <button type="submit">Confirm Borrow</button>
                <button type="button" onclick="toggleModal('addBorrowModal')" class="btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>
<script>
    const profile = document.getElementById('profile');
    const dropdown = document.getElementById('profileDropdown');

    profile.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', () => { dropdown.style.display = 'none'; });

    document.getElementById('logoutBtn').addEventListener('click', () => {
        window.location.href = 'logout.php';
    });

    const navItems = document.querySelectorAll('.nav-item');
    const panels = document.querySelectorAll('.content-panel');

    const showPanel = (panelId) => {
        panels.forEach(p => p.style.display = (p.id === panelId) ? 'block' : 'none');
        navItems.forEach(i => i.classList.toggle('active', i.dataset.target === panelId));

        if (panelId === 'panel-dashboard') {
            loadDashboardTable();
            loadDashboardData();
        }
        if (panelId === 'panel-borrow') loadBorrowTable();
        if (panelId === 'panel-fines') loadFinesTable();
        if (panelId === 'panel-reports') loadReports();
    };

    navItems.forEach(item => {
        item.addEventListener('click', () => {
            const targetId = item.dataset.target;
            if (targetId && document.getElementById(targetId)) {
                showPanel(targetId);
            }
        });
    });

    function toggleModal(id) {
        const modal = document.getElementById(id);
        modal.style.display = (modal.style.display === 'flex') ? 'none' : 'flex';
    }

    window.onclick = function(event) {
        if (event.target.className === 'modal') event.target.style.display = "none";
    };

    function loadBorrowTable() {
        fetch('fetch_loans.php')
            .then(response => response.text())
            .then(data => {
                const container = document.getElementById('loanTableBody');
                if (container) container.innerHTML = data;
            })
            .catch(err => console.error(err));
    }

    function loadDashboardTable() {
        fetch('fetch_loans.php')
            .then(response => response.text())
            .then(data => {
                const container = document.getElementById('dashboardLoanTable');
                if (container) container.innerHTML = data;
            })
            .catch(err => console.error(err));
    }

    function searchBooks() {
        let input = document.getElementById('bookSearch');
        let filter = input.value.toLowerCase();
        let table = document.querySelector("#panel-books table");
        let tr = table.getElementsByTagName("tr");

        for (let i = 1; i < tr.length; i++) {
            let titleCell = tr[i].getElementsByTagName("td")[1];
            let isbnCell = tr[i].getElementsByTagName("td")[2];

            if (titleCell || isbnCell) {
                let titleText = titleCell.textContent || titleCell.innerText;
                let isbnText = isbnCell.textContent || isbnCell.innerText;
                tr[i].style.display = (titleText.toLowerCase().includes(filter) || isbnText.toLowerCase().includes(filter)) ? "" : "none";
            }
        }
    }

    function openEditBookModal(book_id, title, author, isbn, year, copies, category_id) {
        const modal = document.getElementById('editBookModal');
        modal.querySelector('input[name="book_id"]').value = book_id;
        modal.querySelector('input[name="title"]').value = title;
        const authorInput = modal.querySelector('input[name="author"]');
        
        if (author === 'Unknown') {
            authorInput.value = '';
            authorInput.placeholder = 'Enter author name'; 
        } else {
            authorInput.value = author;
        }

        modal.querySelector('input[name="isbn"]').value = isbn;
        modal.querySelector('input[name="year"]').value = year;
        modal.querySelector('input[name="copies"]').value = copies;
        
        const categorySelect = modal.querySelector('select[name="category_id"]');
        if (categorySelect) categorySelect.value = category_id;

        toggleModal('editBookModal'); 
    }

    function openEditMemberModal(id, name, phone, address, typeId) {
        const modal = document.getElementById('editMemberModal');
        modal.querySelector('input[name="member_id"]').value = id;
        modal.querySelector('input[name="name"]').value = name;
        modal.querySelector('input[name="phone"]').value = phone;
        modal.querySelector('textarea[name="address"]').value = address;
        modal.querySelector('select[name="membership_type_id"]').value = typeId;
        modal.style.display = 'flex';
    }

function searchMembers() {
    const input = document.getElementById('memberSearch');
    const filter = input.value.toLowerCase();
    const panel = document.getElementById('panel-members');
    
    const rows = panel.querySelectorAll('table tbody tr');

    rows.forEach(row => {
        const nameText = row.cells[1] ? row.cells[1].textContent.toLowerCase() : "";
        const contactText = row.cells[2] ? row.cells[2].textContent.toLowerCase() : "";

        if (row.cells.length < 2) return;

        if (nameText.includes(filter) || contactText.includes(filter)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}
    function searchBorrows() {
        let filter = document.getElementById('borrowSearch').value.toLowerCase();
        let tr = document.getElementById("loanTableBody").getElementsByTagName("tr");

        for (let i = 0; i < tr.length; i++) {
            let name = tr[i].children[1]?.innerText.toLowerCase() || "";
            let book = tr[i].children[2]?.innerText.toLowerCase() || "";
            let status = tr[i].children[5]?.innerText.toLowerCase() || "";
            tr[i].style.display = (name.includes(filter) || book.includes(filter) || status.includes(filter)) ? "" : "none";
        }
    }

    const memberInput = document.getElementById('memberInput');
    const guestInput = document.getElementById('guestInput');

    memberInput.addEventListener('input', () => {
        guestInput.value = "";
        guestInput.disabled = memberInput.value.trim() !== "";
    });

    guestInput.addEventListener('input', () => {
        memberInput.value = "";
        memberInput.disabled = guestInput.value.trim() !== "";
    });

    document.getElementById('borrowForm').addEventListener('submit', function(e) {
        if (!memberInput.value.trim() && !guestInput.value.trim()) {
            e.preventDefault();
            alert("Provide Member ID or Guest Name");
        } else if (document.querySelectorAll('.book-check:checked').length === 0) {
            e.preventDefault();
            alert("Select at least one book");
        }
    });

    const bookSearch = document.getElementById('bookSearch');
    const resultsDiv = document.getElementById('searchResults');
    const hiddenId = document.getElementById('submitted_book_id');

    bookSearch.addEventListener('input', function() {
        const query = this.value;
        if (query.length < 1) {
            resultsDiv.style.display = 'none';
            return;
        }

        fetch(`search_books.php?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                resultsDiv.innerHTML = '';
                resultsDiv.style.display = data.length ? 'block' : 'none';
                data.forEach(book => {
                    const div = document.createElement('div');
                    div.innerHTML = `#${book.book_id} - ${book.title}`;
                    div.onclick = () => {
                        bookSearch.value = book.title;
                        hiddenId.value = book.book_id;
                        resultsDiv.style.display = 'none';
                    };
                    resultsDiv.appendChild(div);
                });
            });
    });

    document.getElementById('volumeSearch').addEventListener('input', function() {
        const filter = this.value.toLowerCase();
        document.querySelectorAll('.book-item').forEach(item => {
            item.style.display = item.getAttribute('data-title').includes(filter) ? 'flex' : 'none';
        });
    });

    function returnBook(detailId, bookId) {
        if (!confirm("Return this book?")) return;
        fetch(`return_book.php?id=${detailId}&book_id=${bookId}`)
            .then(res => res.text())
            .then(data => {
                if (data.trim() === "Success") {
                    loadBorrowTable();
                    loadDashboardTable();
                    loadDashboardData();
                } else {
                    alert(data);
                }
            });
    }

    let currentFineStatus = 'all';
    function loadFinesTable() {
        const searchVal = document.getElementById('fineSearchInput')?.value || '';
        fetch(`fetch_fines.php?status=${currentFineStatus}&search=${encodeURIComponent(searchVal)}`)
            .then(res => res.text())
            .then(data => {
                const container = document.querySelector("#panel-fines tbody");
                if (container) container.innerHTML = data;
            });
    }

    function filterFines(status) {
        currentFineStatus = status;
        loadFinesTable();
    }

    function payFine(fineId) {
        if (!confirm("Mark as paid?")) return;
        fetch(`pay_fine.php?id=${fineId}`)
            .then(res => res.text())
            .then(data => {
                if (data.trim() === "Success") loadFinesTable();
            });
    }

    document.getElementById('fineSearchInput')?.addEventListener('input', loadFinesTable);

    function loadReports() {
        const fromDate = document.getElementById('reportFrom').value;
        const toDate = document.getElementById('reportTo').value;

        if(!fromDate || !toDate) {
            alert("Please select both dates first!");
            return;
        }

        localStorage.setItem('reportFrom', fromDate);
        localStorage.setItem('reportTo', toDate);

        fetch(`fetch_reports.php?from=${fromDate}&to=${toDate}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('total-borrows-val').innerText = data.total_borrows;
                document.getElementById('returned-val').innerText = data.returned;
                document.getElementById('overdue-val').innerText = data.overdue;
                document.getElementById('total-fines-val').innerText = '₱' + data.total_fines;

                let rows = "";
                data.popular_books.forEach(book => {
                    const badge = book.copies > 0 ? 'badge-success' : 'badge-danger';
                    const status = book.copies > 0 ? 'AVAILABLE' : 'OUT OF STOCK';
                    rows += `<tr>
                        <td>${book.title}</td>
                        <td>${book.borrow_count}</td>
                        <td><span class="badge ${badge}">${status}</span></td>
                    </tr>`;
                });
                document.querySelector('#most-requested-table tbody').innerHTML = rows;
            });

        fetch('fetch_dashboard_alerts.php')
            .then(res => res.json())
            .then(data => {
                let overdueHTML = "";
                data.overdue.forEach(item => {
                    overdueHTML += `<tr><td class="gold-text">${item.patron}</td><td style="color: #ff4d4d;">${item.due_date}</td></tr>`;
                });
                document.getElementById('overdue-alerts-container').innerHTML = overdueHTML;

                let finesHTML = "";
                data.fines.forEach(item => {
                    finesHTML += `<tr><td class="gold-text">${item.patron}</td><td class="gold-text">₱${item.amount}</td></tr>`;
                });
                document.getElementById('pending-fines-container').innerHTML = finesHTML;
            });
    }

    function loadDashboardData() {
        fetch('fetch_dashboard_alerts.php')
            .then(res => res.json())
            .then(data => {
                if (data.stats) {
                    document.getElementById('stat-borrows').innerText = data.stats.total_borrows;
                    document.getElementById('stat-returned').innerText = data.stats.returned;
                    document.getElementById('stat-overdue').innerText = data.stats.overdue_count;
                    document.getElementById('stat-fines').innerText = '₱' + data.stats.total_fines;
                }
            });
    }

    window.addEventListener('load', () => {
        const hash = window.location.hash.substring(1);
        showPanel((hash && document.getElementById(hash)) ? hash : 'panel-dashboard');

        const savedFrom = localStorage.getItem('reportFrom');
        const savedTo = localStorage.getItem('reportTo');
        if (savedFrom && savedTo) {
            document.getElementById('reportFrom').value = savedFrom;
            document.getElementById('reportTo').value = savedTo;
            loadReports(); 
        }
    });

    document.getElementById('accountForm').addEventListener('submit', function(e){
        e.preventDefault();
        const formData = new FormData(this);
        fetch('update_account.php', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(data => {
            const msgEl = document.getElementById('updateMsg');
            msgEl.innerText = data;
            msgEl.style.display = 'block';
            document.getElementById('adminPassword').value = ''; 
            setTimeout(() => { msgEl.style.display = 'none'; }, 3000);
        });
    });

    document.getElementById('rulesForm').addEventListener('submit', function(e){
        e.preventDefault();
        const formData = new FormData(this);
        fetch('update_rules.php', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(data => {
            const msgEl = document.getElementById('rulesMsg');
            msgEl.innerText = data;
            msgEl.style.display = 'block';
            setTimeout(() => { msgEl.style.display = 'none'; }, 3000);
        });
    });
document.addEventListener('DOMContentLoaded', function() {
    const membershipTbody = document.getElementById('membershipTbody');

    if (membershipTbody) {
        membershipTbody.addEventListener('click', function(e) {
            if (e.target.classList.contains('editTier')) {
                const btn = e.target;
                const row = btn.closest('tr');
                const id = row.dataset.id;

                if (btn.innerText.trim().toLowerCase() === 'save') {
                    const limitInput = row.querySelector('.inputBorrow');
                    const durationInput = row.querySelector('.inputDuration');
                    
                    if (!limitInput || !durationInput) return;

                    const valLimit = limitInput.value;
                    const valDuration = durationInput.value;

                    const formData = new FormData();
                    formData.append('membership_type_id', id);
                    formData.append('borrow_limit', valLimit);
                    formData.append('duration_days', valDuration);

                    fetch('update_membership.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.text())
                    .then(msg => {
                        alert(msg);
                        if (msg.includes("Success")) {
                            row.children[1].innerText = valLimit;
                            row.children[2].innerText = valDuration;
                            btn.innerText = 'Edit';
                        }
                    })
                    .catch(err => console.error(err));
                } else {
                    const currentLimit = row.children[1].innerText.trim();
                    const currentDuration = row.children[2].innerText.trim();

                    row.children[1].innerHTML = `<input type="number" class="inputBorrow" value="${currentLimit}" style="width:65px; color: black !important; background: white !important; border: 1px solid #ffd700;">`;
                    row.children[2].innerHTML = `<input type="number" class="inputDuration" value="${currentDuration}" style="width:65px; color: black !important; background: white !important; border: 1px solid #ffd700;">`;
                    
                    btn.innerText = 'Save';
                }
            }
        });
    }
});
    function openUpgradeModal(id, name) {
        document.getElementById('upgrade_guest_id').value = id;
        document.getElementById('upgrade_guest_name').innerText = name;
        toggleModal('upgradeModal');
    }
</script>
    </body>
    </html>