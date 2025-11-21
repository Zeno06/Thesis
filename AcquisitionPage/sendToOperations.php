<?php
require_once '../session_helper.php';
startRoleSession('acquisition');
include '../db_connect.php';

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
$currentTime = date('Y-m-d H:i:s');

// Update status to Sent to Operations and track who sent it
$stmt = $conn->prepare("UPDATE vehicle_acquisition SET status = 'Sent to Operations', sent_to_operations_by = ?, sent_to_operations_at = ? WHERE acquisition_id = ?");
$stmt->bind_param("ssi", $userName, $currentTime, $acquisition_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = 'Vehicle sent to operations successfully!';
} else {
    $_SESSION['error_message'] = 'Error sending to operations: ' . $conn->error;
}

$stmt->close();
$conn->close();

header('Location: approvePage.php');
exit();
?>