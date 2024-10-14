<?php
$servername = "localhost";
$username = "stock";
$password = "your-super-secret-password";
$dbname = "stock";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// XSS and SQL injection prevention functions
function sanitize($data, $conn) {
    return htmlspecialchars(mysqli_real_escape_string($conn, $data));
}
?>
