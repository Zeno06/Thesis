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

try {
    // Update issues
    if (isset($_POST['issue_repaired']) && is_array($_POST['issue_repaired'])) {
        foreach ($_POST['issue_repaired'] as $issueId => $value) {
            $issueId = intval($issueId);
            $repairedBy = isset($_POST['issue_repaired_by'][$issueId]) ? 
                         $conn->real_escape_string($_POST['issue_repaired_by'][$issueId]) : '';
            
            $stmt = $conn->prepare("UPDATE acquisition_issues SET is_repaired = 1, repaired_by = ? WHERE issue_id = ?");
            $stmt->bind_param("si", $repairedBy, $issueId);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Update unchecked issues to not repaired
    $allIssues = $conn->query("SELECT issue_id FROM acquisition_issues WHERE acquisition_id = $acquisition_id");
    while ($issue = $allIssues->fetch_assoc()) {
        if (!isset($_POST['issue_repaired'][$issue['issue_id']])) {
            $conn->query("UPDATE acquisition_issues SET is_repaired = 0, repaired_by = NULL WHERE issue_id = {$issue['issue_id']}");
        }
    }
    
    // Update parts
    if (isset($_POST['part_ordered']) && is_array($_POST['part_ordered'])) {
        foreach ($_POST['part_ordered'] as $partId => $value) {
            $partId = intval($partId);
            $orderedBy = isset($_POST['part_ordered_by'][$partId]) ? 
                        $conn->real_escape_string($_POST['part_ordered_by'][$partId]) : '';
            
            $stmt = $conn->prepare("UPDATE acquisition_parts SET is_ordered = 1, ordered_by = ? WHERE part_id = ?");
            $stmt->bind_param("si", $orderedBy, $partId);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Update unchecked parts to not ordered
    $allParts = $conn->query("SELECT part_id FROM acquisition_parts WHERE acquisition_id = $acquisition_id");
    while ($part = $allParts->fetch_assoc()) {
        if (!isset($_POST['part_ordered'][$part['part_id']])) {
            $conn->query("UPDATE acquisition_parts SET is_ordered = 0, ordered_by = NULL WHERE part_id = {$part['part_id']}");
        }
    }
    
    // Update quality check info
    $stmt = $conn->prepare("UPDATE vehicle_acquisition SET quality_checked_by = ?, quality_checked_at = ? WHERE acquisition_id = ?");
    $stmt->bind_param("ssi", $userName, $currentTime, $acquisition_id);
    $stmt->execute();
    $stmt->close();
    
    // If approving, change status
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE vehicle_acquisition SET status = 'Approved', approved_by = ?, approved_at = ? WHERE acquisition_id = ?");
        $stmt->bind_param("ssi", $userName, $currentTime, $acquisition_id);
        $stmt->execute();
        $stmt->close();
    }
    
    echo json_encode(['success' => true, 'message' => 'Quality check updated successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>