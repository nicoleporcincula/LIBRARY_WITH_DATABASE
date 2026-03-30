<?php
$servername = "localhost"; // usually localhost
$username = "root";        // default XAMPP username
$password = "";            // default XAMPP password is empty
$dbname = "candlefern_library_db"; // your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>