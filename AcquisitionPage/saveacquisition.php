<?php
require_once '../session_helper.php';
startRoleSession('acquisition');  

include '../db_connect.php';
include '../log_activity.php'; 

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'acquisition' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../LoginPage/loginPage.php');
    exit();
}

$userName = $_SESSION['user_name'];
$userRole = $_SESSION['role'];
$user_id = $_SESSION['id'];

// Get form data
$supplier = $_POST['supplier'] ?? '';
$dateAcquired = $_POST['dateAcquired'] ?? date('Y-m-d');
$make = $_POST['make'] ?? '';
$model = $_POST['vehicleModel'] ?? '';
$variant = $_POST['variant'] ?? '';
$plate = $_POST['plateNumber'] ?? '';
$year = $_POST['year'] ?? '';
$color = $_POST['color'] ?? '';
$fuelType = $_POST['fuelType'] ?? '';
$odometer = $_POST['odometer'] ?? 0;
$bodyType = $_POST['bodyType'] ?? '';
$transmission = $_POST['transmission'] ?? '';
$spareKey = $_POST['spareKey'] ?? '';
$acquiredPrice = $_POST['acquiredPrice'] ?? 0.00;
$spareTires = $_POST['spareTires'] ?? '';
$completeTools = $_POST['completeTools'] ?? '';
$originalPlate = $_POST['originalPlate'] ?? '';
$completeDocuments = $_POST['completeDocuments'] ?? '';
$remarks = $_POST['remarks'] ?? '';

// Create organized folder structure
$folderName = preg_replace('/[^A-Za-z0-9_\-]/', '_', "{$plate}_{$model}_{$year}");
$vehicleBaseDir = __DIR__ . "/../uploads/{$folderName}/";
$vehiclePhotosDir = $vehicleBaseDir . 'vehicle_photos/';
$issuesDir = $vehicleBaseDir . 'issues/';
$documentsDir = $vehicleBaseDir . 'documents/';

// Create directories
if (!file_exists($vehiclePhotosDir)) mkdir($vehiclePhotosDir, 0777, true);
if (!file_exists($issuesDir)) mkdir($issuesDir, 0777, true);
if (!file_exists($documentsDir)) mkdir($documentsDir, 0777, true);

function uploadFile($field, $uploadDir, $folderName) {
    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
        $extension = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . basename($_FILES[$field]['name']);
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES[$field]['tmp_name'], $targetPath)) {
            return $folderName . '/' . basename($uploadDir) . '/' . $filename;
        }
    }
    return null;
}

// Upload vehicle photos
$exterior = uploadFile('exterior', $vehiclePhotosDir, $folderName);
$dashboard = uploadFile('dashboard', $vehiclePhotosDir, $folderName);
$hood = uploadFile('hood', $vehiclePhotosDir, $folderName);
$interior = uploadFile('interior', $vehiclePhotosDir, $folderName);
$trunk = uploadFile('trunk', $vehiclePhotosDir, $folderName);

// Upload document photos
$orcrPhoto = uploadFile('orcrPhoto', $documentsDir, $folderName);
$deedOfSalePhoto = uploadFile('deedOfSalePhoto', $documentsDir, $folderName);
$insurancePhoto = uploadFile('insurancePhoto', $documentsDir, $folderName);

$status = 'Quality Check';

// Insert main vehicle acquisition with all new fields
$stmt = $conn->prepare("
    INSERT INTO vehicle_acquisition (
        supplier, date_acquired, make, vehicle_model, variant, plate_number, year_model, color,
        fuel_type, odometer, body_type, transmission, spare_key,
        exterior_photo, dashboard_photo, hood_photo, interior_photo, trunk_photo,
        orcr_photo, deed_of_sale_photo, insurance_photo,
        spare_tires, complete_tools, original_plate, complete_documents,
        remarks, acquired_price, created_by, status
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
");

$stmt->bind_param(
    "ssssssississssssssssssssssdis",
    $supplier,$dateAcquired,$make,$model,$variant,$plate,              
    $year,$color,$fuelType,$odometer,$bodyType,$transmission,$spareKey,           
    $exterior,$dashboard,$hood,$interior,$trunk,$orcrPhoto,$deedOfSalePhoto,
    $insurancePhoto,$spareTires,$completeTools,$originalPlate,$completeDocuments,
    $remarks,$acquiredPrice,$user_id,$status             
);

if ($stmt->execute()) {
   $acquisition_id = $conn->insert_id;
    
    // Insert issues with photos
    if (isset($_POST['issue_names']) && is_array($_POST['issue_names'])) {
        $issueStmt = $conn->prepare("INSERT INTO acquisition_issues (acquisition_id, issue_name, issue_photo) VALUES (?,?,?)");
        
        foreach ($_POST['issue_names'] as $index => $issueName) {
            if (!empty(trim($issueName))) {
                $issuePhoto = null;
                if (isset($_FILES['issue_photos']['tmp_name'][$index]) && 
                    $_FILES['issue_photos']['error'][$index] === UPLOAD_ERR_OK) {
                    $extension = pathinfo($_FILES['issue_photos']['name'][$index], PATHINFO_EXTENSION);
                    $filename = time() . '_issue_' . $index . '.' . $extension;
                    $targetPath = $issuesDir . $filename;
                    if (move_uploaded_file($_FILES['issue_photos']['tmp_name'][$index], $targetPath)) {
                        $issuePhoto = $folderName . '/issues/' . $filename;
                    }
                }
                $issueStmt->bind_param("iss", $acquisition_id, $issueName, $issuePhoto);
                $issueStmt->execute();
            }
        }
        $issueStmt->close();
    }
    
    // Insert parts needed
    if (isset($_POST['parts_needed']) && is_array($_POST['parts_needed'])) {
        $partStmt = $conn->prepare("INSERT INTO acquisition_parts (acquisition_id, part_name) VALUES (?,?)");
        
        foreach ($_POST['parts_needed'] as $partName) {
            if (!empty(trim($partName))) {
                $partStmt->bind_param("is", $acquisition_id, $partName);
                $partStmt->execute();
            }
        }
        $partStmt->close();
    }
    
    // Log activity
    $action = "Created new vehicle acquisition: $plate - $make $model $year (Status: Quality Check, Price: ₱" . number_format($acquiredPrice, 2) . ")";
    logActivity($conn, $user_id, $action, 'Vehicle Acquisition');
    
    header("Location: acquiPage.php?success=1");
    exit();
} else {
    $errorMsg = urlencode($stmt->error);
    header("Location: acquiPage.php?error={$errorMsg}");
    exit();
}

$stmt->close();
$conn->close();
?>