<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'pos_db'; // Change to your DB name

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
?> 