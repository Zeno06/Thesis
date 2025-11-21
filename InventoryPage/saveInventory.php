<?php
require_once '../db_connect.php';
require_once '../session_helper.php';
startRoleSession('acquisition');

include '../log_activity.php'; 

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'acquisition' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: recentInventory.php');
    exit();
}

$inventory_id = intval($_POST['inventory_id']);
$supplier = $conn->real_escape_string($_POST['supplier']);
$date_acquired = $_POST['date_acquired'];
$year_model = intval($_POST['year_model']);
$make = $conn->real_escape_string($_POST['make']);
$model = $conn->real_escape_string($_POST['model']);
$variant = $conn->real_escape_string($_POST['variant']);
$color = $conn->real_escape_string($_POST['color']);
$plate_number = $conn->real_escape_string($_POST['plate_number']);
$odometer = intval($_POST['odometer']);
$body_type = $conn->real_escape_string($_POST['body_type']);
$projected_repair_cost = floatval($_POST['projected_repair_cost']);
$actual_spend = floatval($_POST['actual_spend']);
$cost_breakdown = floatval($_POST['cost_breakdown']);
$remarks = $conn->real_escape_string($_POST['remarks']);
$approved_checked_by = $conn->real_escape_string($_POST['approved_checked_by']);
$repairsList = $_POST['repairs_list'] ?? '[]';
$reconditionsList = $_POST['reconditions_list'] ?? '[]';
$costBreakdownList = $_POST['costbreakdown_list'] ?? '[]';

$repairsTotal = 0;
$reconditionsTotal = 0;
$costTotal = 0;

foreach (json_decode($repairsList, true) as $r) $repairsTotal += floatval($r['price'] ?? 0);
foreach (json_decode($reconditionsList, true) as $r) $reconditionsTotal += floatval($r['price'] ?? 0);
foreach (json_decode($costBreakdownList, true) as $r) $costTotal += floatval($r['price'] ?? 0);

$projected_repair_cost = $repairsTotal + $reconditionsTotal;
$actual_spend = $costTotal;
$cost_breakdown = $costTotal;

$sql = "UPDATE vehicle_inventory SET 
        supplier='$supplier', 
        date_acquired='$date_acquired', 
        year_model=$year_model, 
        make='$make', 
        model='$model', 
        variant='$variant', 
        color='$color', 
        plate_number='$plate_number', 
        odometer=$odometer, 
        body_type='$body_type',
        projected_repair_cost=$projected_repair_cost, 
        actual_spend=$actual_spend, 
        cost_breakdown=$cost_breakdown,
        repairs_list='$repairsList',
        reconditions_list='$reconditionsList',
        costbreakdown_list='$costBreakdownList',
        remarks='$remarks',
        approved_checked_by='$approved_checked_by'
        WHERE inventory_id=$inventory_id";

if ($conn->query($sql)) {
    $action = "Updated inventory record: $plate_number - $make $model $year_model";
    logActivity($conn, $_SESSION['id'], $action, 'Inventory Management');
    
    $_SESSION['success_message'] = 'Inventory updated successfully!';
} else {
    $_SESSION['error_message'] = 'Error: ' . $conn->error;
}

$conn->close();
header('Location: recentInventory.php');
exit();
?>