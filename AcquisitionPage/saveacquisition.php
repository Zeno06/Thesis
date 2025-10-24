<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../LoginPage/loginPage.php');
    exit();
}

$user_id = $_SESSION['id'];

$adminQuery = $conn->query("SELECT firstname, lastname FROM users WHERE role = 'acquisition' LIMIT 1");
$admin = $adminQuery->fetch_assoc();
$approvedCheckedBy = $admin ? $admin['firstname'] . ' ' . $admin['lastname'] : 'Acquisition Admin';

$uploadDir = __DIR__ . '/../uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

function uploadFile($field, $uploadDir) {
    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
        $filename = time() . '_' . basename($_FILES[$field]['name']);
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES[$field]['tmp_name'], $targetPath)) {
            return $filename;
        }
    }
    return null;
}

function uploadMultiple($field, $uploadDir) {
    $files = [];
    if (isset($_FILES[$field]) && !empty($_FILES[$field]['name'][0])) {
        foreach ($_FILES[$field]['tmp_name'] as $index => $tmpName) {
            $filename = time() . '_' . $index . '_' . basename($_FILES[$field]['name'][$index]);
            $targetPath = $uploadDir . $filename;
            if (move_uploaded_file($tmpName, $targetPath)) {
                $files[] = $filename;
            }
        }
    }
    return json_encode($files);
}

$model = $_POST['vehicleModel'] ?? '';
$plate = $_POST['plateNumber'] ?? '';
$year = $_POST['year'] ?? '';
$color = $_POST['color'] ?? '';
$projectedReconPrice = $_POST['projectedPrice'] ?? 0.00;
$issueRemarks = $_POST['issueRemarks'] ?? '';
$partsNeeded = isset($_POST['parts_needed']) ? json_encode($_POST['parts_needed']) : json_encode([]);
$spareTires = $_POST['spareTires'] ?? '';
$completeTools = $_POST['completeTools'] ?? '';
$originalPlate = $_POST['originalPlate'] ?? '';
$completeDocuments = $_POST['completeDocuments'] ?? '';
$remarks = $_POST['remarks'] ?? '';
$wholecar = uploadFile('wholecar', $uploadDir);
$dashboard = uploadFile('dashboard', $uploadDir);
$hood = uploadFile('hood', $uploadDir);
$interior = uploadFile('interior', $uploadDir);
$exterior = uploadFile('exterior', $uploadDir);
$trunk = uploadFile('trunk', $uploadDir);
$issuePhotos = uploadMultiple('issuePhotos', $uploadDir);
$documentPhotos = uploadMultiple('documentPhotos', $uploadDir);

$status = 'Draft';

$stmt = $conn->prepare("
    INSERT INTO vehicle_acquisition (
        vehicle_model, plate_number, year_model, color,
        wholecar_photo, dashboard_photo, hood_photo, interior_photo, exterior_photo, trunk_photo,
        issue_photos, document_photos, issue_remarks, parts_needed,
        spare_tires, complete_tools, original_plate, complete_documents,
        remarks, projected_recon_price, approved_checked_by, created_by, status
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
");

$stmt->bind_param(
    "sssssssssssssssssssssis",
    $model, $plate, $year, $color,
    $wholecar, $dashboard, $hood, $interior, $exterior, $trunk,
    $issuePhotos, $documentPhotos, $issueRemarks, $partsNeeded,
    $spareTires, $completeTools, $originalPlate, $completeDocuments,
    $remarks, $projectedReconPrice, $approvedCheckedBy, $user_id, $status
);

if ($stmt->execute()) {
    echo "<script>alert('✅ Vehicle acquisition saved as draft!'); window.location.href='acquiPage.php';</script>";
} else {
    echo "❌ SQL Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>