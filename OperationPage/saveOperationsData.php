<?php
session_start();
include '../db_connect.php';
include '../log_activity.php'; 

if (!isset($_SESSION['id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: operationPage.php');
    exit();
}

$acquisition_id = intval($_POST['acquisition_id']);
$markup_percentage = floatval($_POST['markup_percentage'] ?? 0);
$userName = $_SESSION['user_name'];
$user_id = $_SESSION['id'];
$currentTime = date('Y-m-d H:i:s');

// Get vehicle info
$vehicleQuery = $conn->query("SELECT plate_number, vehicle_model, year_model, acquired_price FROM vehicle_acquisition WHERE acquisition_id = $acquisition_id");
$vehicle = $vehicleQuery->fetch_assoc();
$acquiredPrice = $vehicle['acquired_price'] ?? 0;

// Calculate issues cost
$issuesQuery = $conn->query("SELECT SUM(COALESCE(issue_price, 0)) as issues_total FROM acquisition_issues WHERE acquisition_id = $acquisition_id");
$issuesTotal = $issuesQuery->fetch_assoc()['issues_total'] ?? 0;

// Calculate parts cost
$partsQuery = $conn->query("SELECT SUM(COALESCE(part_price, 0)) as parts_total FROM acquisition_parts WHERE acquisition_id = $acquisition_id");
$partsTotal = $partsQuery->fetch_assoc()['parts_total'] ?? 0;

// Calculate totals
$totalReconCost = $acquiredPrice + $issuesTotal + $partsTotal;
$markupValue = ($totalReconCost * $markup_percentage) / 100;
$sellingPrice = $totalReconCost + $markupValue;

// Update vehicle_acquisition with all calculated values
$stmt = $conn->prepare("
    UPDATE vehicle_acquisition SET 
        issues_cost = ?,
        parts_cost = ?,
        total_recon_cost = ?,
        markup_percentage = ?,
        markup_value = ?,
        selling_price = ?,
        operations_updated_by = ?,
        operations_updated_at = ?
    WHERE acquisition_id = ?
");

$stmt->bind_param(
    "ddddddssi",
    $issuesTotal,
    $partsTotal,
    $totalReconCost,
    $markup_percentage,
    $markupValue,
    $sellingPrice,
    $userName,
    $currentTime,
    $acquisition_id
);

if ($stmt->execute()) {
    
    $action = "Updated pricing for vehicle: {$vehicle['plate_number']} - {$vehicle['vehicle_model']} {$vehicle['year_model']} (Selling Price: ₱" . number_format($sellingPrice, 2) . ", Markup: {$markup_percentage}%)";
    logActivity($conn, $user_id, $action, 'Operations');
    
    echo "<script>alert('✅ Pricing saved successfully!'); window.location.href='operationPage.php';</script>";
} else {
    echo "<script>alert('❌ Error saving pricing: " . $conn->error . "'); window.location.href='operationPage.php';</script>";
}

$stmt->close();
$conn->close();
?>