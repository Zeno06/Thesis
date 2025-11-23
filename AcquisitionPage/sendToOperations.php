<?php
require_once '../session_helper.php';
startRoleSession('acquisition');
include '../db_connect.php';
include '../log_activity.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'acquisition') {
    header('Location: approvePage.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: approvePage.php');
    exit();
}

$acquisition_id = intval($_POST['acquisition_id']);
$userName = $_SESSION['user_name'];
$user_id = $_SESSION['id'];
$currentTime = date('Y-m-d H:i:s');

// Get vehicle info for logging
$vehicleQuery = $conn->query("SELECT plate_number, vehicle_model, year_model FROM vehicle_acquisition WHERE acquisition_id = $acquisition_id");
$vehicle = $vehicleQuery->fetch_assoc();

if (!$vehicle) {
    $_SESSION['error_message'] = 'Vehicle not found!';
    header('Location: approvePage.php');
    exit();
}

$vehicleInfo = "{$vehicle['plate_number']} - {$vehicle['vehicle_model']} {$vehicle['year_model']}";

// Update status to Sent to Operations and track who sent it
$stmt = $conn->prepare("UPDATE vehicle_acquisition SET status = 'Sent to Operations', sent_to_operations_by = ?, sent_to_operations_at = ? WHERE acquisition_id = ?");
$stmt->bind_param("ssi", $userName, $currentTime, $acquisition_id);

if ($stmt->execute()) {
    // Log activity
    $logAction = "Sent vehicle to operations: $vehicleInfo (Status changed to Sent to Operations)";
    logActivity($conn, $user_id, $logAction, 'Approved Acquisition');
    
    $_SESSION['success_message'] = 'Vehicle sent to operations successfully!';
} else {
    $_SESSION['error_message'] = 'Error sending to operations: ' . $conn->error;
}

$stmt->close();
$conn->close();

header('Location: approvePage.php');
exit();
?>