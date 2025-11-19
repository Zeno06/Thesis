<?php
session_start();
include '../db_connect.php';
include '../log_activity.php'; 

if (!isset($_SESSION['id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: operationPage.php');
    exit();
}

$acquisition_id = intval($_POST['acquisition_id']);
$userName = $_SESSION['user_name'];
$user_id = $_SESSION['id'];
$currentTime = date('Y-m-d H:i:s');


$vehicleQuery = $conn->query("SELECT plate_number, vehicle_model, year_model, is_released FROM vehicle_acquisition WHERE acquisition_id = $acquisition_id");
$vehicle = $vehicleQuery->fetch_assoc();

if (!$vehicle) {
    echo "<script>alert('❌ Vehicle not found!'); window.location.href='operationPage.php';</script>";
    exit();
}

if ($vehicle['is_released'] != 1) {
    echo "<script>alert('⚠️ Only released vehicles can be archived!'); window.location.href='operationPage.php';</script>";
    exit();
}

// Archive the vehicle 
$stmt = $conn->prepare("UPDATE vehicle_acquisition SET is_released = 2, archived_by = ?, archived_at = ? WHERE acquisition_id = ?");
$stmt->bind_param("ssi", $userName, $currentTime, $acquisition_id);

if ($stmt->execute()) {
    
    $action = "Archived vehicle (sold/removed from public): {$vehicle['plate_number']} - {$vehicle['vehicle_model']} {$vehicle['year_model']}";
    logActivity($conn, $user_id, $action, 'Operations');
    
    echo "<script>alert('✅ Vehicle archived successfully! It has been removed from the landing page.'); window.location.href='operationPage.php';</script>";
} else {
    echo "<script>alert('❌ Error archiving vehicle: " . $conn->error . "'); window.location.href='operationPage.php';</script>";
}

$stmt->close();
$conn->close();
?>