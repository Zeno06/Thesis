<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: approvePage.php');
    exit();
}

$acquisition_id = intval($_POST['acquisition_id']);
$userName = $_SESSION['user_name'];
$currentTime = date('Y-m-d H:i:s');

// Update status to Sent to Operations and track who sent it
$stmt = $conn->prepare("UPDATE vehicle_acquisition SET status = 'Sent to Operations', sent_to_operations_by = ?, sent_to_operations_at = ? WHERE acquisition_id = ?");
$stmt->bind_param("ssi", $userName, $currentTime, $acquisition_id);

if ($stmt->execute()) {
    echo "<script>alert('Vehicle sent to operations successfully!'); window.location.href='approvePage.php';</script>";
} else {
    echo "<script>alert('Error sending to operations: " . $conn->error . "'); window.location.href='approvePage.php';</script>";
}

$stmt->close();
$conn->close();
?>