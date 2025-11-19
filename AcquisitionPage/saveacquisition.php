<?php
session_start();
include '../db_connect.php';
include '../log_activity.php'; 

if (!isset($_SESSION['id'])) {
    header('Location: ../LoginPage/loginPage.php');
    exit();
}

$user_id = $_SESSION['id'];

// Get form data first to create folder name
$model = $_POST['vehicleModel'] ?? '';
$plate = $_POST['plateNumber'] ?? '';
$year = $_POST['year'] ?? '';

// Create organized folder structure: uploads/PLATE_MODEL_YEAR/
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
            // Return path relative to uploads folder
            return $folderName . '/' . basename($uploadDir) . '/' . $filename;
        }
    }
    return null;
}

// Get form data
$color = $_POST['color'] ?? '';
$acquiredPrice = $_POST['acquiredPrice'] ?? 0.00;
$spareTires = $_POST['spareTires'] ?? '';
$completeTools = $_POST['completeTools'] ?? '';
$originalPlate = $_POST['originalPlate'] ?? '';
$completeDocuments = $_POST['completeDocuments'] ?? '';
$remarks = $_POST['remarks'] ?? '';

// Upload vehicle photos to vehicle_photos folder
$wholecar = uploadFile('wholecar', $vehiclePhotosDir, $folderName);
$dashboard = uploadFile('dashboard', $vehiclePhotosDir, $folderName);
$hood = uploadFile('hood', $vehiclePhotosDir, $folderName);
$interior = uploadFile('interior', $vehiclePhotosDir, $folderName);
$exterior = uploadFile('exterior', $vehiclePhotosDir, $folderName);
$trunk = uploadFile('trunk', $vehiclePhotosDir, $folderName);

// Upload document photos to documents folder
$orcrPhoto = uploadFile('orcrPhoto', $documentsDir, $folderName);
$deedOfSalePhoto = uploadFile('deedOfSalePhoto', $documentsDir, $folderName);
$insurancePhoto = uploadFile('insurancePhoto', $documentsDir, $folderName);

$status = 'Quality Check';

// Insert main vehicle acquisition
$stmt = $conn->prepare("
    INSERT INTO vehicle_acquisition (
        vehicle_model, plate_number, year_model, color,
        wholecar_photo, dashboard_photo, hood_photo, interior_photo, exterior_photo, trunk_photo,
        orcr_photo, deed_of_sale_photo, insurance_photo,
        spare_tires, complete_tools, original_plate, complete_documents,
        remarks, acquired_price, created_by, status
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
");

$stmt->bind_param(
    "ssisssssssssssssssdis",
    $model, $plate, $year, $color,
    $wholecar, $dashboard, $hood, $interior, $exterior, $trunk,
    $orcrPhoto, $deedOfSalePhoto, $insurancePhoto,
    $spareTires, $completeTools, $originalPlate, $completeDocuments,
    $remarks, $acquiredPrice, $user_id, $status
);

if ($stmt->execute()) {
    $acquisition_id = $conn->insert_id;
    
    // Insert issues with photos to issues folder
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
                        // Store relative path
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
    
    $action = "Created new vehicle acquisition: $plate - $model $year (Status: Quality Check)";
    logActivity($conn, $user_id, $action, 'Vehicle Acquisition');
    
    echo "<script>alert('✅ Vehicle acquisition saved and sent to Quality Check!'); window.location.href='qualityPage.php';</script>";
} else {
    echo "❌ SQL Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>