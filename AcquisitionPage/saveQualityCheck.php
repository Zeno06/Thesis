<?php
require_once '../session_helper.php';
startRoleSession('acquisition');

include '../db_connect.php';
include '../log_activity.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'acquisition' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../LoginPage/loginPage.php');
    exit();
}

header('Content-Type: application/json');

$userName = $_SESSION['user_name'];
$userRole = $_SESSION['role'];
$user_id = $_SESSION['id'];

$acquisition_id = intval($_POST['acquisition_id']);
$action = $_POST['action'] ?? 'save';
$remarks = $_POST['remarks'] ?? null; // Get editable remarks
$currentTime = date('Y-m-d H:i:s');

// Get vehicle info for logging
$vehicleQuery = $conn->query("SELECT plate_number, vehicle_model, year_model, remarks FROM vehicle_acquisition WHERE acquisition_id = $acquisition_id");
$vehicle = $vehicleQuery->fetch_assoc();
$vehicleInfo = "{$vehicle['plate_number']} - {$vehicle['vehicle_model']} {$vehicle['year_model']}";
$folderName = preg_replace('/[^A-Za-z0-9_\-]/', '_', "{$vehicle['plate_number']}_{$vehicle['vehicle_model']}_{$vehicle['year_model']}");
$issuesDir = __DIR__ . "/../uploads/{$folderName}/issues/";
$receiptsDir = __DIR__ . "/../uploads/{$folderName}/receipts/";

if (!file_exists($issuesDir)) mkdir($issuesDir, 0777, true);
if (!file_exists($receiptsDir)) mkdir($receiptsDir, 0777, true);

try {
    // Handle issue deletions
    if (isset($_POST['delete_issue']) && is_array($_POST['delete_issue'])) {
        foreach ($_POST['delete_issue'] as $issueId) {
            if (!empty($issueId)) {
                $issueId = intval($issueId);
                $conn->query("DELETE FROM acquisition_issues WHERE issue_id = $issueId");
            }
        }
    }
    
    // Handle part deletions
    if (isset($_POST['delete_part']) && is_array($_POST['delete_part'])) {
        foreach ($_POST['delete_part'] as $partId) {
            if (!empty($partId)) {
                $partId = intval($partId);
                $conn->query("DELETE FROM acquisition_parts WHERE part_id = $partId");
            }
        }
    }
    
    // Update existing issues (CHECKED)
    if (isset($_POST['issue_repaired']) && is_array($_POST['issue_repaired'])) {
        foreach ($_POST['issue_repaired'] as $issueId => $value) {
            $issueId = intval($issueId);
            $repairedBy = isset($_POST['issue_repaired_by'][$issueId]) ? 
                         $conn->real_escape_string($_POST['issue_repaired_by'][$issueId]) : '';
            $price = isset($_POST['issue_price'][$issueId]) ? floatval($_POST['issue_price'][$issueId]) : null;
            $issueRemarks = isset($_POST['issue_remarks'][$issueId]) ? 
                      $conn->real_escape_string($_POST['issue_remarks'][$issueId]) : '';
            
            // Handle photo update
            $photoUpdate = '';
            if (isset($_FILES['issue_photo_update']['tmp_name'][$issueId]) && 
                $_FILES['issue_photo_update']['error'][$issueId] === UPLOAD_ERR_OK) {
                $extension = pathinfo($_FILES['issue_photo_update']['name'][$issueId], PATHINFO_EXTENSION);
                $filename = time() . '_issue_' . $issueId . '.' . $extension;
                $targetPath = $issuesDir . $filename;
                if (move_uploaded_file($_FILES['issue_photo_update']['tmp_name'][$issueId], $targetPath)) {
                    $issuePhoto = $folderName . '/issues/' . $filename;
                    $photoUpdate = ", issue_photo = '$issuePhoto'";
                }
            }
            
            // Handle receipt photos
            $receiptUpdate = '';
            if (isset($_FILES['issue_receipt_update']['tmp_name'][$issueId]) && is_array($_FILES['issue_receipt_update']['tmp_name'][$issueId])) {
                $receiptPhotos = [];
                foreach ($_FILES['issue_receipt_update']['tmp_name'][$issueId] as $key => $tmp_name) {
                    if ($_FILES['issue_receipt_update']['error'][$issueId][$key] === UPLOAD_ERR_OK) {
                        $extension = pathinfo($_FILES['issue_receipt_update']['name'][$issueId][$key], PATHINFO_EXTENSION);
                        $filename = time() . '_receipt_' . $issueId . '_' . $key . '.' . $extension;
                        $targetPath = $receiptsDir . $filename;
                        if (move_uploaded_file($tmp_name, $targetPath)) {
                            $receiptPhotos[] = $folderName . '/receipts/' . $filename;
                        }
                    }
                }
                if (!empty($receiptPhotos)) {
                    $receiptJson = $conn->real_escape_string(json_encode($receiptPhotos));
                    $receiptUpdate = ", receipt_photos = '$receiptJson'";
                }
            }
            
            $conn->query("UPDATE acquisition_issues SET 
                is_repaired = 1, 
                repaired_by = '$repairedBy', 
                issue_price = " . ($price !== null ? $price : 'NULL') . ", 
                issue_remarks = '$issueRemarks' 
                $photoUpdate
                $receiptUpdate
                WHERE issue_id = $issueId");
        }
    }
    
    // Update unchecked issues
    if (isset($_POST['issue_price']) && is_array($_POST['issue_price'])) {
        foreach ($_POST['issue_price'] as $issueId => $price) {
            if (!isset($_POST['issue_repaired'][$issueId])) {
                $issueId = intval($issueId);
                $price = $price ? floatval($price) : null;
                $issueRemarks = isset($_POST['issue_remarks'][$issueId]) ? 
                          $conn->real_escape_string($_POST['issue_remarks'][$issueId]) : '';
                
                $conn->query("UPDATE acquisition_issues SET 
                    is_repaired = 0, 
                    repaired_by = NULL, 
                    issue_price = " . ($price !== null ? $price : 'NULL') . ", 
                    issue_remarks = '$issueRemarks' 
                    WHERE issue_id = $issueId");
            }
        }
    }
    
    // Add new issues
    if (isset($_POST['new_issue_name']) && is_array($_POST['new_issue_name'])) {
        foreach ($_POST['new_issue_name'] as $index => $issueName) {
            if (!empty(trim($issueName))) {
                $price = isset($_POST['new_issue_price'][$index]) && !empty($_POST['new_issue_price'][$index]) ? 
                        floatval($_POST['new_issue_price'][$index]) : null;
                $issueRemarks = isset($_POST['new_issue_remarks'][$index]) ? 
                          $conn->real_escape_string($_POST['new_issue_remarks'][$index]) : '';
                $isRepaired = isset($_POST['new_issue_repaired'][$index]) ? 1 : 0;
                $repairedBy = isset($_POST['new_issue_repaired_by'][$index]) && $isRepaired ? 
                             $conn->real_escape_string($_POST['new_issue_repaired_by'][$index]) : null;
                
                // Handle photo upload for new issue
                $issuePhoto = null;
                if (isset($_FILES['new_issue_photos']['tmp_name'][$index]) && 
                    $_FILES['new_issue_photos']['error'][$index] === UPLOAD_ERR_OK) {
                    $extension = pathinfo($_FILES['new_issue_photos']['name'][$index], PATHINFO_EXTENSION);
                    $filename = time() . '_new_issue_' . $index . '.' . $extension;
                    $targetPath = $issuesDir . $filename;
                    if (move_uploaded_file($_FILES['new_issue_photos']['tmp_name'][$index], $targetPath)) {
                        $issuePhoto = $folderName . '/issues/' . $filename;
                    }
                }
                
                // Handle receipt photos for new issue
                $receiptPhotos = [];
                if (isset($_FILES['new_issue_receipts']['tmp_name'][$index]) && is_array($_FILES['new_issue_receipts']['tmp_name'][$index])) {
                    foreach ($_FILES['new_issue_receipts']['tmp_name'][$index] as $key => $tmp_name) {
                        if ($_FILES['new_issue_receipts']['error'][$index][$key] === UPLOAD_ERR_OK) {
                            $extension = pathinfo($_FILES['new_issue_receipts']['name'][$index][$key], PATHINFO_EXTENSION);
                            $filename = time() . '_new_receipt_' . $index . '_' . $key . '.' . $extension;
                            $targetPath = $receiptsDir . $filename;
                            if (move_uploaded_file($tmp_name, $targetPath)) {
                                $receiptPhotos[] = $folderName . '/receipts/' . $filename;
                            }
                        }
                    }
                }
                
                $issueNameEsc = $conn->real_escape_string($issueName);
                $repairedByValue = $repairedBy !== null ? "'$repairedBy'" : 'NULL';
                $photoValue = $issuePhoto !== null ? "'$issuePhoto'" : 'NULL';
                $receiptValue = !empty($receiptPhotos) ? "'" . $conn->real_escape_string(json_encode($receiptPhotos)) . "'" : 'NULL';
                
                $conn->query("INSERT INTO acquisition_issues 
                    (acquisition_id, issue_name, issue_photo, issue_price, issue_remarks, is_repaired, repaired_by, receipt_photos) 
                    VALUES ($acquisition_id, '$issueNameEsc', $photoValue, " . ($price !== null ? $price : 'NULL') . ", '$issueRemarks', $isRepaired, $repairedByValue, $receiptValue)");
            }
        }
    }
    
    // Update existing parts (CHECKED)
    if (isset($_POST['part_ordered']) && is_array($_POST['part_ordered'])) {
        foreach ($_POST['part_ordered'] as $partId => $value) {
            $partId = intval($partId);
            $orderedBy = isset($_POST['part_ordered_by'][$partId]) ? 
                        $conn->real_escape_string($_POST['part_ordered_by'][$partId]) : '';
            $price = isset($_POST['part_price'][$partId]) ? floatval($_POST['part_price'][$partId]) : null;
            $partRemarks = isset($_POST['part_remarks'][$partId]) ? 
                      $conn->real_escape_string($_POST['part_remarks'][$partId]) : '';
            
            // Handle receipt photos
            $receiptUpdate = '';
            if (isset($_FILES['part_receipt_update']['tmp_name'][$partId]) && is_array($_FILES['part_receipt_update']['tmp_name'][$partId])) {
                $receiptPhotos = [];
                foreach ($_FILES['part_receipt_update']['tmp_name'][$partId] as $key => $tmp_name) {
                    if ($_FILES['part_receipt_update']['error'][$partId][$key] === UPLOAD_ERR_OK) {
                        $extension = pathinfo($_FILES['part_receipt_update']['name'][$partId][$key], PATHINFO_EXTENSION);
                        $filename = time() . '_part_receipt_' . $partId . '_' . $key . '.' . $extension;
                        $targetPath = $receiptsDir . $filename;
                        if (move_uploaded_file($tmp_name, $targetPath)) {
                            $receiptPhotos[] = $folderName . '/receipts/' . $filename;
                        }
                    }
                }
                if (!empty($receiptPhotos)) {
                    $receiptJson = $conn->real_escape_string(json_encode($receiptPhotos));
                    $receiptUpdate = ", receipt_photos = '$receiptJson'";
                }
            }
            
            $conn->query("UPDATE acquisition_parts SET 
                is_ordered = 1, 
                ordered_by = '$orderedBy', 
                part_price = " . ($price !== null ? $price : 'NULL') . ", 
                part_remarks = '$partRemarks' 
                $receiptUpdate
                WHERE part_id = $partId");
        }
    }
    
    // Update unchecked parts
    if (isset($_POST['part_price']) && is_array($_POST['part_price'])) {
        foreach ($_POST['part_price'] as $partId => $price) {
            if (!isset($_POST['part_ordered'][$partId])) {
                $partId = intval($partId);
                $price = $price ? floatval($price) : null;
                $partRemarks = isset($_POST['part_remarks'][$partId]) ? 
                          $conn->real_escape_string($_POST['part_remarks'][$partId]) : '';
                
                $conn->query("UPDATE acquisition_parts SET 
                    is_ordered = 0, 
                    ordered_by = NULL, 
                    part_price = " . ($price !== null ? $price : 'NULL') . ", 
                    part_remarks = '$partRemarks' 
                    WHERE part_id = $partId");
            }
        }
    }
    
    // Add new parts
    if (isset($_POST['new_part_name']) && is_array($_POST['new_part_name'])) {
        foreach ($_POST['new_part_name'] as $index => $partName) {
            if (!empty(trim($partName))) {
                $price = isset($_POST['new_part_price'][$index]) && !empty($_POST['new_part_price'][$index]) ? 
                        floatval($_POST['new_part_price'][$index]) : null;
                $partRemarks = isset($_POST['new_part_remarks'][$index]) ? 
                          $conn->real_escape_string($_POST['new_part_remarks'][$index]) : '';
                $isOrdered = isset($_POST['new_part_ordered'][$index]) ? 1 : 0;
                $orderedBy = isset($_POST['new_part_ordered_by'][$index]) && $isOrdered ? 
                            $conn->real_escape_string($_POST['new_part_ordered_by'][$index]) : null;
                
                // Handle receipt photos for new part
                $receiptPhotos = [];
                if (isset($_FILES['new_part_receipts']['tmp_name'][$index]) && is_array($_FILES['new_part_receipts']['tmp_name'][$index])) {
                    foreach ($_FILES['new_part_receipts']['tmp_name'][$index] as $key => $tmp_name) {
                        if ($_FILES['new_part_receipts']['error'][$index][$key] === UPLOAD_ERR_OK) {
                            $extension = pathinfo($_FILES['new_part_receipts']['name'][$index][$key], PATHINFO_EXTENSION);
                            $filename = time() . '_new_part_receipt_' . $index . '_' . $key . '.' . $extension;
                            $targetPath = $receiptsDir . $filename;
                            if (move_uploaded_file($tmp_name, $targetPath)) {
                                $receiptPhotos[] = $folderName . '/receipts/' . $filename;
                            }
                        }
                    }
                }
                
                $partNameEsc = $conn->real_escape_string($partName);
                $orderedByValue = $orderedBy !== null ? "'$orderedBy'" : 'NULL';
                $receiptValue = !empty($receiptPhotos) ? "'" . $conn->real_escape_string(json_encode($receiptPhotos)) . "'" : 'NULL';
                
                $conn->query("INSERT INTO acquisition_parts 
                    (acquisition_id, part_name, part_price, part_remarks, is_ordered, ordered_by, receipt_photos) 
                    VALUES ($acquisition_id, '$partNameEsc', " . ($price !== null ? $price : 'NULL') . ", '$partRemarks', $isOrdered, $orderedByValue, $receiptValue)");
            }
        }
    }

    $docPhotos = [
    'orcr_photo_update' => 'orcr_photo',
    'deed_of_sale_photo_update' => 'deed_of_sale_photo', 
    'insurance_photo_update' => 'insurance_photo'
];

foreach ($docPhotos as $fileKey => $dbField) {
    if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
        $documentsDir = __DIR__ . "/../uploads/{$folderName}/documents/";
        if (!file_exists($documentsDir)) mkdir($documentsDir, 0777, true);
        
        $extension = pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . $dbField . '.' . $extension;
        $targetPath = $documentsDir . $filename;
        
        if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetPath)) {
            $docPhotoPath = $folderName . '/documents/' . $filename;
            $conn->query("UPDATE vehicle_acquisition SET $dbField = '$docPhotoPath' WHERE acquisition_id = $acquisition_id");
        }
    }
}
    
    // Update editable remarks in vehicle_acquisition
    if ($remarks !== null) {
        $remarksEsc = $conn->real_escape_string($remarks);
        $conn->query("UPDATE vehicle_acquisition SET remarks = '$remarksEsc' WHERE acquisition_id = $acquisition_id");
    }
    
    // Update quality check tracking
    $conn->query("UPDATE vehicle_acquisition SET 
        quality_checked_by = '$userName', 
        quality_checked_at = '$currentTime'
        WHERE acquisition_id = $acquisition_id");
    
    $conn->query("INSERT INTO quality_check_tracking 
        (acquisition_id, quality_checked_by, quality_checked_at) 
        VALUES ($acquisition_id, '$userName', '$currentTime')
        ON DUPLICATE KEY UPDATE 
        quality_checked_by = '$userName', 
        quality_checked_at = '$currentTime'");
    
    // If approving, change status
    if ($action === 'approve') {
        $conn->query("UPDATE vehicle_acquisition SET 
            status = 'Approved',
            approved_by = '$userName',
            approved_at = '$currentTime'
            WHERE acquisition_id = $acquisition_id");
        
        // Insert into approved tracking
        $conn->query("INSERT INTO approved_acquisitions_tracking 
            (acquisition_id, approved_by, approved_at) 
            VALUES ($acquisition_id, '$userName', '$currentTime')");
        
        // Log activity
        $logAction = "Approved vehicle acquisition: $vehicleInfo (Status changed to Approved)";
        logActivity($conn, $user_id, $logAction, 'Quality Check', $remarks);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Vehicle approved and moved to Approved page successfully!'
        ]);
    } else {
        // Log activity for save
        $logAction = "Updated quality check for vehicle: $vehicleInfo (Issues and parts updated)";
        logActivity($conn, $user_id, $logAction, 'Quality Check', $remarks);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Quality check progress saved successfully!'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>