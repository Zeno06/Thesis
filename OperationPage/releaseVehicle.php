<?php
require_once '../session_helper.php';
startRoleSession('operation');  

include '../db_connect.php';


if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'operation') {
    header('Location: operationPage.php');
    exit();
}

$userName = $_SESSION['user_name'];
$userRole = $_SESSION['role'];
$user_id = $_SESSION['id'];
$acquisition_id = intval($_POST['acquisition_id']);
$currentTime = date('Y-m-d H:i:s');

// Check if vehicle has selling price set
$checkQuery = $conn->query("SELECT selling_price FROM vehicle_acquisition WHERE acquisition_id = $acquisition_id");
$vehicle = $checkQuery->fetch_assoc();

if (!$vehicle || $vehicle['selling_price'] <= 0) {
    echo "<script>alert('⚠️ Please set the selling price before releasing this vehicle!'); window.location.href='operationPage.php';</script>";
    exit();
}

// Mark vehicle as released
$stmt = $conn->prepare("UPDATE vehicle_acquisition SET is_released = 1, released_by = ?, released_at = ? WHERE acquisition_id = ?");
$stmt->bind_param("ssi", $userName, $currentTime, $acquisition_id);

if ($stmt->execute()) {
    echo "<script>alert('✅ Vehicle released to public successfully! It will now appear on the landing page.'); window.location.href='operationPage.php';</script>";
} else {
    echo "<script>alert('❌ Error releasing vehicle: " . $conn->error . "'); window.location.href='operationPage.php';</script>";
}

$stmt->close();
$conn->close();
?>