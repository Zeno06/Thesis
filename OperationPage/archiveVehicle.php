<?php
require_once '../session_helper.php';
startRoleSession('operation'); 
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
    $_SESSION['error_message'] = 'Vehicle not found!';
    header('Location: operationPage.php');
    exit();
}

if ($vehicle['is_released'] != 1) {
    $_SESSION['error_message'] = 'Only released vehicles can be archived!';
    header('Location: operationPage.php');
    exit();
}

// Archive the vehicle 
$stmt = $conn->prepare("UPDATE vehicle_acquisition SET is_released = 2, archived_by = ?, archived_at = ? WHERE acquisition_id = ?");
$stmt->bind_param("ssi", $userName, $currentTime, $acquisition_id);

if ($stmt->execute()) {
    $action = "Archived vehicle (sold/removed from public): {$vehicle['plate_number']} - {$vehicle['vehicle_model']} {$vehicle['year_model']}";
    logActivity($conn, $user_id, $action, 'Operations');
    
    $_SESSION['success_message'] = 'Vehicle archived successfully! It has been removed from the landing page.';
} else {
    $_SESSION['error_message'] = 'Error archiving vehicle: ' . $conn->error;
}

$stmt->close();
$conn->close();

header('Location: operationPage.php');
exit();
?>