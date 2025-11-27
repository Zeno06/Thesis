<?php
date_default_timezone_set('Asia/Manila');
$conn = new mysqli('localhost', 'carmax_carmona', 'carmax_carmona', 'carmax_carmona');
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
$conn->query("SET time_zone = '+08:00'");
?>
