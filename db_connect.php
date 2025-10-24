<?php
$conn = new mysqli('localhost', 'root', '', 'carmax_carmona');
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
