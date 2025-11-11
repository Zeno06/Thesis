<?php
session_start();
include '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$acquisition_id = intval($_POST['acquisition_id']);
$action = $_POST['action'] ?? 'save';
$userName = $_SESSION['user_name'];
$currentTime = date('Y-m-d H:i:s');

// Get vehicle folder for uploads
$vehicleQuery = $conn->query("SELECT plate_number, vehicle_model, year_model FROM vehicle_acquisition WHERE acquisition_id = $acquisition_id");
$vehicle = $vehicleQuery->fetch_assoc();
$folderName = preg_replace('/[^A-Za-z0-9_\-]/', '_', "{$vehicle['plate_number']}_{$vehicle['vehicle_model']}_{$vehicle['year_model']}");
$issuesDir = __DIR__ . "/../uploads/{$folderName}/issues/";

if (!file_exists($issuesDir)) {
    mkdir($issuesDir, 0777, true);
}

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
    
    // Update existing issues (CHECKED - uses issue_price)
    if (isset($_POST['issue_repaired']) && is_array($_POST['issue_repaired'])) {
        foreach ($_POST['issue_repaired'] as $issueId => $value) {
            $issueId = intval($issueId);
            $repairedBy = isset($_POST['issue_repaired_by'][$issueId]) ? 
                         $conn->real_escape_string($_POST['issue_repaired_by'][$issueId]) : '';
            $price = isset($_POST['issue_price'][$issueId]) ? floatval($_POST['issue_price'][$issueId]) : null;
            $remarks = isset($_POST['issue_remarks'][$issueId]) ? 
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
            
            $conn->query("UPDATE acquisition_issues SET 
                is_repaired = 1, 
                repaired_by = '$repairedBy', 
                issue_price = " . ($price !== null ? $price : 'NULL') . ", 
                issue_remarks = '$remarks' 
                $photoUpdate
                WHERE issue_id = $issueId");
        }
    }
    
    // Update unchecked issues (uses issue_price)
    if (isset($_POST['issue_price']) && is_array($_POST['issue_price'])) {
        foreach ($_POST['issue_price'] as $issueId => $price) {
            if (!isset($_POST['issue_repaired'][$issueId])) {
                $issueId = intval($issueId);
                $price = $price ? floatval($price) : null;
                $remarks = isset($_POST['issue_remarks'][$issueId]) ? 
                          $conn->real_escape_string($_POST['issue_remarks'][$issueId]) : '';
                
                $conn->query("UPDATE acquisition_issues SET 
                    is_repaired = 0, 
                    repaired_by = NULL, 
                    issue_price = " . ($price !== null ? $price : 'NULL') . ", 
                    issue_remarks = '$remarks' 
                    WHERE issue_id = $issueId");
            }
        }
    }
    
    // Add new issues (uses issue_price)
    if (isset($_POST['new_issue_name']) && is_array($_POST['new_issue_name'])) {
        foreach ($_POST['new_issue_name'] as $index => $issueName) {
            if (!empty(trim($issueName))) {
                $price = isset($_POST['new_issue_price'][$index]) && !empty($_POST['new_issue_price'][$index]) ? 
                        floatval($_POST['new_issue_price'][$index]) : null;
                $remarks = isset($_POST['new_issue_remarks'][$index]) ? 
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
                
                $issueNameEsc = $conn->real_escape_string($issueName);
                $repairedByValue = $repairedBy !== null ? "'$repairedBy'" : 'NULL';
                $photoValue = $issuePhoto !== null ? "'$issuePhoto'" : 'NULL';
                
                $conn->query("INSERT INTO acquisition_issues 
                    (acquisition_id, issue_name, issue_photo, issue_price, issue_remarks, is_repaired, repaired_by) 
                    VALUES ($acquisition_id, '$issueNameEsc', $photoValue, " . ($price !== null ? $price : 'NULL') . ", '$remarks', $isRepaired, $repairedByValue)");
            }
        }
    }
    
    // Update existing parts (CHECKED - uses part_price)
    if (isset($_POST['part_ordered']) && is_array($_POST['part_ordered'])) {
        foreach ($_POST['part_ordered'] as $partId => $value) {
            $partId = intval($partId);
            $orderedBy = isset($_POST['part_ordered_by'][$partId]) ? 
                        $conn->real_escape_string($_POST['part_ordered_by'][$partId]) : '';
            $price = isset($_POST['part_price'][$partId]) ? floatval($_POST['part_price'][$partId]) : null;
            $remarks = isset($_POST['part_remarks'][$partId]) ? 
                      $conn->real_escape_string($_POST['part_remarks'][$partId]) : '';
            
            $conn->query("UPDATE acquisition_parts SET 
                is_ordered = 1, 
                ordered_by = '$orderedBy', 
                part_price = " . ($price !== null ? $price : 'NULL') . ", 
                part_remarks = '$remarks' 
                WHERE part_id = $partId");
        }
    }
    
    // Update unchecked parts (uses part_price)
    if (isset($_POST['part_price']) && is_array($_POST['part_price'])) {
        foreach ($_POST['part_price'] as $partId => $price) {
            if (!isset($_POST['part_ordered'][$partId])) {
                $partId = intval($partId);
                $price = $price ? floatval($price) : null;
                $remarks = isset($_POST['part_remarks'][$partId]) ? 
                          $conn->real_escape_string($_POST['part_remarks'][$partId]) : '';
                
                $conn->query("UPDATE acquisition_parts SET 
                    is_ordered = 0, 
                    ordered_by = NULL, 
                    part_price = " . ($price !== null ? $price : 'NULL') . ", 
                    part_remarks = '$remarks' 
                    WHERE part_id = $partId");
            }
        }
    }
    
    // Add new parts (uses part_price)
    if (isset($_POST['new_part_name']) && is_array($_POST['new_part_name'])) {
        foreach ($_POST['new_part_name'] as $index => $partName) {
            if (!empty(trim($partName))) {
                $price = isset($_POST['new_part_price'][$index]) && !empty($_POST['new_part_price'][$index]) ? 
                        floatval($_POST['new_part_price'][$index]) : null;
                $remarks = isset($_POST['new_part_remarks'][$index]) ? 
                          $conn->real_escape_string($_POST['new_part_remarks'][$index]) : '';
                $isOrdered = isset($_POST['new_part_ordered'][$index]) ? 1 : 0;
                $orderedBy = isset($_POST['new_part_ordered_by'][$index]) && $isOrdered ? 
                            $conn->real_escape_string($_POST['new_part_ordered_by'][$index]) : null;
                
                $partNameEsc = $conn->real_escape_string($partName);
                $orderedByValue = $orderedBy !== null ? "'$orderedBy'" : 'NULL';
                
                $conn->query("INSERT INTO acquisition_parts 
                    (acquisition_id, part_name, part_price, part_remarks, is_ordered, ordered_by) 
                    VALUES ($acquisition_id, '$partNameEsc', " . ($price !== null ? $price : 'NULL') . ", '$remarks', $isOrdered, $orderedByValue)");
            }
        }
    }
    
    // Update quality check info
    $conn->query("UPDATE vehicle_acquisition SET 
        quality_checked_by = '$userName', 
        quality_checked_at = '$currentTime' 
        WHERE acquisition_id = $acquisition_id");
    
    // If approving, change status
    if ($action === 'approve') {
        $conn->query("UPDATE vehicle_acquisition SET 
            status = 'Approved', 
            approved_by = '$userName', 
            approved_at = '$currentTime' 
            WHERE acquisition_id = $acquisition_id");
    }
    
    echo json_encode(['success' => true, 'message' => 'Quality check updated successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>