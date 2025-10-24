<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: recentAcquisition.php');
    exit();
}

// 🧹 Sanitize Inputs
$acquisition_id = intval($_POST['acquisition_id']);
$plate_number = $conn->real_escape_string($_POST['plate_number']);
$vehicle_model = $conn->real_escape_string($_POST['vehicle_model']);
$year_model = intval($_POST['year_model']);
$color = $conn->real_escape_string($_POST['color']);
$projected_recon_price = floatval($_POST['projected_recon_price']);
$status = isset($_POST['status']) ? $conn->real_escape_string($_POST['status']) : 'Draft';
$approved_checked_by = $conn->real_escape_string($_POST['approved_checked_by']);
$remarks = $conn->real_escape_string($_POST['remarks'] ?? '');

// ✅ Track user
$updatedBy = $conn->real_escape_string($_SESSION['user_name']);
$updatedAt = date('Y-m-d H:i:s');

// 🗂 Create unique upload directory (per vehicle)
$folderName = preg_replace('/[^A-Za-z0-9_\-]/', '_', "{$vehicle_model}_{$plate_number}_{$year_model}");
$uploadDir = __DIR__ . "/../uploads/{$folderName}/";
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

// 🧰 Helper functions
function uploadFile($field, $uploadDir, $oldValue) {
    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
        $filename = time() . '_' . basename($_FILES[$field]['name']);
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES[$field]['tmp_name'], $targetPath)) {
            return $filename;
        }
    }
    return $oldValue;
}

function uploadMultiple($field, $uploadDir, $oldValue) {
    $files = [];
    if (isset($_FILES[$field]) && !empty($_FILES[$field]['name'][0])) {
        foreach ($_FILES[$field]['tmp_name'] as $index => $tmpName) {
            $filename = time() . "_{$index}_" . basename($_FILES[$field]['name'][$index]);
            $targetPath = $uploadDir . $filename;
            if (move_uploaded_file($tmpName, $targetPath)) {
                $files[] = $filename;
            }
        }
        return json_encode($files);
    }
    return $oldValue;
}

// 🧾 Get old data
$old = $conn->query("SELECT * FROM vehicle_acquisition WHERE acquisition_id=$acquisition_id")->fetch_assoc();

// 🖼 Handle file uploads (keep old if not replaced)
$photoFields = ['wholecar_photo', 'dashboard_photo', 'hood_photo', 'interior_photo', 'exterior_photo', 'trunk_photo'];
foreach ($photoFields as $field) {
    $old[$field] = uploadFile($field, $uploadDir, $old[$field]);
}
$issue_photos = uploadMultiple('issue_photos', $uploadDir, $old['issue_photos']);
$document_photos = uploadMultiple('document_photos', $uploadDir, $old['document_photos']);

// 💾 Update query
$sql = "UPDATE vehicle_acquisition SET 
    plate_number='$plate_number',
    vehicle_model='$vehicle_model',
    year_model=$year_model,
    color='$color',
    projected_recon_price=$projected_recon_price,
    status='$status',
    approved_checked_by='$approved_checked_by',
    remarks='$remarks',
    wholecar_photo='{$old['wholecar_photo']}',
    dashboard_photo='{$old['dashboard_photo']}',
    hood_photo='{$old['hood_photo']}',
    interior_photo='{$old['interior_photo']}',
    exterior_photo='{$old['exterior_photo']}',
    trunk_photo='{$old['trunk_photo']}',
    issue_photos='$issue_photos',
    document_photos='$document_photos',
    last_updated_by='$updatedBy',
    last_updated_at='$updatedAt'
    WHERE acquisition_id=$acquisition_id";

if ($conn->query($sql)) {
    $message = $status === 'Sent to Operations'
        ? '✅ Sent to Operations successfully!'
        : '✅ Acquisition updated successfully!';
    echo "<script>alert('$message'); window.location.href='recentAcquisition.php';</script>";
} else {
    echo "<script>alert('❌ Error updating acquisition: " . $conn->error . "'); window.location.href='recentAcquisition.php';</script>";
}

$conn->close();
?>
