<?php
require_once '../session_helper.php';
startRoleSession('operation'); 

include '../db_connect.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'operation') {
    header('Location: ../LoginPage/loginPage.php');
    exit();
}

$userName = $_SESSION['user_name'];
$userRole = $_SESSION['role'];
$user_id = $_SESSION['id'];

// Check for success/error messages
$successMessage = $_SESSION['success_message'] ?? null;
$errorMessage = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Fetch all acquisitions sent to operations (including archived for viewing)
$query = "SELECT * FROM vehicle_acquisition WHERE status = 'Sent to Operations' ORDER BY acquisition_id ASC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operations Dashboard - CarMax</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/operationPage.css">
    <style>

    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-left">
        <img src="../Pictures/Carmax_logo.jpg" alt="CarMax" class="logo">
        <div class="header-title fs-4 fw-bold">Operations Dashboard</div>
    </div>
    <div class="user-info d-flex align-items-center gap-2">
        <i class="fas fa-user-circle" style="font-size: 24px;"></i>
        <span>
            <?php 
                $title = $userRole === 'operation' ? 'Operation Admin' : 'Super Admin';
                echo htmlspecialchars($userName) . " ($title)";
            ?>
        </span>
        <a href="../logout.php" style="margin-left:15px;color:white;text-decoration:none;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- Sidebar -->
<div class="sidebar">
    <a href="operationPage.php" class="sidebar-item active">
        <i class="fas fa-inbox"></i><span>Received Acquisitions</span>
    </a>
    <a href="reconCost.php" class="sidebar-item">
        <i class="fas fa-wrench"></i><span>Recon Cost</span>
    </a>
    <a href="partsOrder.php" class="sidebar-item">
        <i class="fas fa-tools"></i><span>Parts Needed/Order</span>
    </a>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="sap-card">
        <div class="sap-card-header">
            <i class="fas fa-clipboard-list"></i> Received Acquisitions 
        </div>
        <div class="sap-card-body">
            <table class="sap-table table table-hover">
                <thead class="table-success">
                    <tr>
                        <th>Plate Number</th>
                        <th>Make</th>
                        <th>Model</th>
                        <th>Year</th>
                        <th>Color</th>
                        <th>Acquired Price</th>
                        <th>Selling Price</th>
                        <th>Status</th>
                        <th>Sent By</th>
                        <th>Received Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['acquisition_id'] ?>" style="cursor:pointer;">
                                <td><?= htmlspecialchars($row['plate_number']) ?></td>
                                <td><?= htmlspecialchars($row['make']) ?></td>
                                <td><?= htmlspecialchars($row['vehicle_model']) ?></td>
                                <td><?= htmlspecialchars($row['year_model']) ?></td>
                                <td><?= htmlspecialchars($row['color']) ?></td>
                                <td>₱<?= number_format($row['acquired_price'], 2) ?></td>
                                <td>
                                    <?php if ($row['selling_price'] > 0): ?>
                                        <span class="badge bg-success">₱<?= number_format($row['selling_price'], 2) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Not Set</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['is_released'] == 2): ?>
                                        <span class="badge bg-secondary"><i class="fas fa-archive"></i> Archived</span>
                                    <?php elseif ($row['is_released'] == 1): ?>
                                        <span class="badge bg-primary"><i class="fas fa-globe"></i> Released</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Pending Release</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['sent_to_operations_by'] ?? 'N/A') ?></td>
                                <td><?= $row['sent_to_operations_at'] ? date('M d, Y h:i A', strtotime($row['sent_to_operations_at'])) : 'N/A' ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="10" class="text-center">No vehicles received from acquisition team yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Modals -->
            <?php
            $result->data_seek(0); 
            while ($row = $result->fetch_assoc()): 
                // Calculate costs
                $issuesQuery = $conn->query("SELECT SUM(COALESCE(issue_price, 0)) as issues_total FROM acquisition_issues WHERE acquisition_id = {$row['acquisition_id']}");
                $issuesTotal = $issuesQuery->fetch_assoc()['issues_total'] ?? 0;
                
                $partsQuery = $conn->query("SELECT SUM(COALESCE(part_price, 0)) as parts_total FROM acquisition_parts WHERE acquisition_id = {$row['acquisition_id']}");
                $partsTotal = $partsQuery->fetch_assoc()['parts_total'] ?? 0;
                
                $acquiredPrice = $row['acquired_price'] ?? 0;
                $totalReconCost = $acquiredPrice + $issuesTotal + $partsTotal;
                $markupPercentage = $row['markup_percentage'] ?? 0;
                $markupValue = ($totalReconCost * $markupPercentage) / 100;
                $sellingPrice = $totalReconCost + $markupValue;
            ?>
            <div class="modal fade" id="viewModal<?= $row['acquisition_id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-car"></i> Vehicle Details - <?= htmlspecialchars($row['vehicle_model']) ?>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <form method="POST" action="saveOperationsData.php" id="operationsForm<?= $row['acquisition_id'] ?>">
                            <input type="hidden" name="acquisition_id" value="<?= $row['acquisition_id'] ?>">
                            
                            <div class="modal-body">
                                <!-- Basic Info -->
                                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-info-circle"></i> Basic Information</h6>
                                <div class="mb-4">
                                    <div class="info-row"><div class="info-label">Supplier:</div><div class="info-value"><?= htmlspecialchars($row['supplier']) ?></div></div>
                                    <div class="info-row"><div class="info-label">Date Acquired:</div><div class="info-value"><?= $row['date_acquired'] ? date('M d, Y', strtotime($row['date_acquired'])) : 'N/A' ?></div></div>
                                    <div class="info-row"><div class="info-label">Make:</div><div class="info-value"><?= htmlspecialchars($row['make']) ?></div></div>
                                    <div class="info-row"><div class="info-label">Plate Number:</div><div class="info-value"><?= htmlspecialchars($row['plate_number']) ?></div></div>
                                    <div class="info-row"><div class="info-label">Vehicle Model:</div><div class="info-value"><?= htmlspecialchars($row['vehicle_model']) ?></div></div>
                                    <div class="info-row"><div class="info-label">Year Model:</div><div class="info-value"><?= htmlspecialchars($row['year_model']) ?></div></div>
                                    <div class="info-row"><div class="info-label">Variant:</div><div class="info-value"><?= htmlspecialchars($row['variant']) ?></div></div>
                                    <div class="info-row"><div class="info-label">Color:</div><div class="info-value"><?= htmlspecialchars($row['color']) ?></div></div>
                                    <div class="info-row"><div class="info-label">Fuel Type:</div><div class="info-value"><?= htmlspecialchars($row['fuel_type']) ?></div></div>
                                    <div class="info-row"><div class="info-label">Odometer:</div><div class="info-value"><?= number_format($row['odometer']) ?> km</div></div>
                                    <div class="info-row"><div class="info-label">Body Type:</div><div class="info-value"><?= htmlspecialchars($row['body_type']) ?></div></div>
                                    <div class="info-row"><div class="info-label">Transmission:</div><div class="info-value"><?= htmlspecialchars($row['transmission']) ?></div></div>
                                </div>

                                <!-- Cost Breakdown & Markup -->
                                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-calculator"></i> Cost Calculation & Markup</h6>
                                <div class="cost-summary">
                                    <div class="cost-row">
                                        <span>Acquired Price:</span>
                                        <span>₱<?= number_format($acquiredPrice, 2) ?></span>
                                    </div>
                                    
                                    <div class="cost-row">
                                        <span>Issues Cost (Auto-calculated):</span>
                                        <span id="issuesCost<?= $row['acquisition_id'] ?>">₱<?= number_format($issuesTotal, 2) ?></span>
                                    </div>
                                    
                                    <div class="cost-row">
                                        <span>Parts Cost (Auto-calculated):</span>
                                        <span id="partsCost<?= $row['acquisition_id'] ?>">₱<?= number_format($partsTotal, 2) ?></span>
                                    </div>
                                    
                                    <div class="cost-row total">
                                        <span>Total Recon Cost:</span>
                                        <span id="totalReconCost<?= $row['acquisition_id'] ?>">₱<?= number_format($totalReconCost, 2) ?></span>
                                    </div>
                                    
                                    <div class="cost-row mt-3">
                                        <span>Markup Percentage:</span>
                                        <div class="input-group input-group-sm" style="width: 200px;">
                                            <input type="number" step="0.01" class="form-control markup-input" 
                                                name="markup_percentage" 
                                                value="<?= number_format($markupPercentage, 2, '.', '') ?>"
                                                onchange="calculateCosts(<?= $row['acquisition_id'] ?>)">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    
                                    <div class="cost-row">
                                        <span>Markup Value:</span>
                                        <span id="markupValue<?= $row['acquisition_id'] ?>">₱<?= number_format($markupValue, 2) ?></span>
                                    </div>
                                    
                                    <div class="cost-row selling">
                                        <span><i class="fas fa-tag"></i> SELLING PRICE:</span>
                                        <span id="sellingPrice<?= $row['acquisition_id'] ?>">₱<?= number_format($sellingPrice, 2) ?></span>
                                    </div>
                                </div>

                                <!-- Vehicle Photos -->
                                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-images"></i> Vehicle Photos</h6>
                                <div class="photo-grid mb-4">
                                    <?php 
                                    $photos = ['exterior'=>'Exterior','dashboard'=>'Dashboard','hood'=>'Hood','interior'=>'Interior','trunk'=>'Trunk'];
                                    foreach ($photos as $key => $label):
                                        $photoPath = htmlspecialchars($row[$key.'_photo'] ?? '');
                                    ?>
                                    <div class="photo-box">
                                        <label><?= $label ?></label>
                                        <?php if (!empty($photoPath)): ?>
                                            <img src="../uploads/<?= $photoPath ?>" alt="<?= $label ?>" class="clickable-image">
                                        <?php else: ?>
                                            <div class="text-muted">No image</div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Issues Section -->
                                <?php 
                                $issuesQuery = $conn->query("SELECT * FROM acquisition_issues WHERE acquisition_id = {$row['acquisition_id']}");
                                if ($issuesQuery && $issuesQuery->num_rows > 0): ?>
                                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-exclamation-triangle"></i> Issues (Resolved)</h6>
                                <div class="table-responsive mb-4">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr><th>Issue Name</th><th>Photo</th><th>Price</th><th>Remarks</th><th>Status</th><th>Repaired By</th><th>Receipts</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($issue = $issuesQuery->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($issue['issue_name']) ?></td>
                                                <td>
                                                    <?php if (!empty($issue['issue_photo'])): ?>
                                                        <img src="../uploads/<?= htmlspecialchars($issue['issue_photo']) ?>" style="max-width: 100px; border-radius: 5px;" class="clickable-image">
                                                    <?php endif; ?>
                                                </td>
                                                <td>₱<?= $issue['issue_price'] ? number_format($issue['issue_price'], 2) : 'N/A' ?></td>
                                                <td><?= htmlspecialchars($issue['issue_remarks'] ?? 'N/A') ?></td>
                                                <td><span class="badge bg-success"><i class="fas fa-check"></i> Repaired</span></td>
                                                <td><?= htmlspecialchars($issue['repaired_by'] ?? 'N/A') ?></td>
                                                <td>
                                                    <?php 
                                                    $receipts = json_decode($issue['receipt_photos'] ?? '[]', true);
                                                    if (!empty($receipts) && is_array($receipts)):
                                                        foreach ($receipts as $receipt):
                                                    ?>
                                                        <img src="../uploads/<?= htmlspecialchars($receipt) ?>" style="max-width: 60px; border-radius: 5px; margin: 2px;" class="clickable-image">
                                                    <?php 
                                                        endforeach;
                                                    else:
                                                        echo '<span class="text-muted">No receipts</span>';
                                                    endif;
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>

                                <!-- Parts Section -->
                                <?php 
                                $partsQuery2 = $conn->query("SELECT * FROM acquisition_parts WHERE acquisition_id = {$row['acquisition_id']}");
                                if ($partsQuery2 && $partsQuery2->num_rows > 0): 
                                ?>
                                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-tools"></i> Parts (Ordered)</h6>
                                <div class="table-responsive mb-4">
                                    <table class="table table-bordered">
                                        <thead><tr><th>Part Name</th><th>Price</th><th>Remarks</th><th>Status</th><th>Ordered By</th><th>Receipts</th></tr></thead>
                                        <tbody>
                                            <?php while ($part = $partsQuery2->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($part['part_name']) ?></td>
                                                <td>₱<?= $part['part_price'] ? number_format($part['part_price'], 2) : 'N/A' ?></td>
                                                <td><?= htmlspecialchars($part['part_remarks'] ?? 'N/A') ?></td>
                                                <td><span class="badge bg-success"><i class="fas fa-check"></i> Ordered</span></td>
                                                <td><?= htmlspecialchars($part['ordered_by'] ?? 'N/A') ?></td>
                                                <td>
                                                    <?php 
                                                    $receipts = json_decode($part['receipt_photos'] ?? '[]', true);
                                                    if (!empty($receipts) && is_array($receipts)):
                                                        foreach ($receipts as $receipt):
                                                    ?>
                                                        <img src="../uploads/<?= htmlspecialchars($receipt) ?>" style="max-width: 60px; border-radius: 5px; margin: 2px;" class="clickable-image">
                                                    <?php 
                                                        endforeach;
                                                    else:
                                                        echo '<span class="text-muted">No receipts</span>';
                                                    endif;
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>

                                <!-- Vehicle Condition -->
                                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-clipboard-check"></i> Vehicle Condition</h6>
                                <div class="mb-4">
                                    <div class="info-row"><div class="info-label">Spare Tires:</div><div class="info-value"><?= htmlspecialchars($row['spare_tires']) ?></div></div>
                                    <div class="info-row"><div class="info-label">Complete Tools:</div><div class="info-value"><?= htmlspecialchars($row['complete_tools']) ?></div></div>
                                    <div class="info-row"><div class="info-label">Original Plate:</div><div class="info-value"><?= htmlspecialchars($row['original_plate']) ?></div></div>
                                    <div class="info-row"><div class="info-label">Complete Documents:</div><div class="info-value"><?= htmlspecialchars($row['complete_documents']) ?></div></div>
                                    <div class="info-row"><div class="info-label">Spare Key:</div><div class="info-value"><?= htmlspecialchars($row['spare_key']) ?></div></div>
                                </div>

                                <!-- Document Photos -->
                                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-file-contract"></i> Document Photos</h6>
                                <div class="photo-grid mb-4">
                                    <?php 
                                    $docPhotos = ['orcr' => 'OR/CR', 'deed_of_sale' => 'Deed of Sale', 'insurance' => 'Insurance'];
                                    foreach ($docPhotos as $key => $label):
                                        $photoField = $key . '_photo';
                                        $photoPath = htmlspecialchars($row[$photoField] ?? '');
                                    ?>
                                    <div class="photo-box">
                                        <label><?= $label ?></label>
                                        <?php if ($photoPath): ?>
                                            <img src="../uploads/<?= $photoPath ?>" alt="<?= $label ?>" class="clickable-image">
                                        <?php else: ?>
                                            <div class="text-muted">No image</div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Remarks -->
                               <h6 class="text-primary fw-bold mb-3"><i class="fas fa-comment"></i> Remarks</h6>
                               <div class="alert alert-info"><?= nl2br(htmlspecialchars($row['remarks'] ?? 'No remarks')) ?></div>

                                <!-- Timeline -->
                                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-history"></i> Process Timeline</h6>
                                <div class="mb-4">
                                    <div class="info-row"><div class="info-label">Quality Checked By:</div><div class="info-value"><?= htmlspecialchars($row['quality_checked_by'] ?? 'N/A') ?></div></div>
                                    <div class="info-row"><div class="info-label">Quality Checked At:</div><div class="info-value"><?= $row['quality_checked_at'] ? date('M d, Y h:i A', strtotime($row['quality_checked_at'])) : 'N/A' ?></div></div>
                                    <div class="info-row"><div class="info-label">Approved By:</div><div class="info-value"><?= htmlspecialchars($row['approved_by'] ?? 'N/A') ?></div></div>
                                    <div class="info-row"><div class="info-label">Approved At:</div><div class="info-value"><?= $row['approved_at'] ? date('M d, Y h:i A', strtotime($row['approved_at'])) : 'N/A' ?></div></div>
                                    <div class="info-row"><div class="info-label">Sent to Operations By:</div><div class="info-value"><?= htmlspecialchars($row['sent_to_operations_by'] ?? 'N/A') ?></div></div>
                                    <div class="info-row"><div class="info-label">Sent to Operations At:</div><div class="info-value"><?= $row['sent_to_operations_at'] ? date('M d, Y h:i A', strtotime($row['sent_to_operations_at'])) : 'N/A' ?></div></div>
                                    
                                    <?php if ($row['is_released'] == 2): ?>
                                    <div class="info-row"><div class="info-label">Archived By:</div><div class="info-value"><?= htmlspecialchars($row['archived_by'] ?? 'N/A') ?></div></div>
                                    <div class="info-row"><div class="info-label">Archived At:</div><div class="info-value"><?= $row['archived_at'] ? date('M d, Y h:i A', strtotime($row['archived_at'])) : 'N/A' ?></div></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times"></i> Close
                                </button>
                                
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Save Pricing
                                </button>
                                
                                <?php if ($row['is_released'] == 0): ?>
                                    <!-- Not Released Yet -->
                                    <button type="button" class="btn btn-primary" 
                                        onclick="confirmRelease(<?= $row['acquisition_id'] ?>)">
                                        <i class="fas fa-rocket"></i> Release to Public
                                    </button>
                                    
                                <?php elseif ($row['is_released'] == 1): ?>
                                    <!-- Released - Show Archive Button -->
                                    <span class="badge bg-success p-2 me-2">
                                        <i class="fas fa-check-circle"></i> Released to Public
                                    </span>
                                    <button type="button" class="btn btn-danger" 
                                        onclick="confirmArchive(<?= $row['acquisition_id'] ?>)">
                                        <i class="fas fa-archive"></i> Archive Vehicle
                                    </button>
                                    
                                <?php elseif ($row['is_released'] == 2): ?>
                                    <!-- Archived -->
                                    <span class="badge bg-secondary p-2">
                                        <i class="fas fa-archive"></i> Archived (Removed from Public)
                                    </span>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="image-modal">
    <span class="image-modal-close" onclick="closeImageModal()">&times;</span>
    <img class="image-modal-content" id="modalImage">
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-check-circle"></i> Success</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="successMessageText"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Error</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="errorMessageText"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Release Modal -->
<div class="modal fade" id="confirmReleaseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-rocket"></i> Confirm Release</h5>
            </div>
            <div class="modal-body">
                Are you sure you want to release this vehicle for viewing?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="releaseForm" method="POST" action="releaseVehicle.php" style="display:inline;">
                    <input type="hidden" name="acquisition_id" id="releaseAcquisitionId">
                    <button type="submit" class="btn btn-success">Yes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Archive Modal -->
<div class="modal fade" id="confirmArchiveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-archive"></i> Confirm Archive</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <strong>Are you sure you want to archive this vehicle?</strong>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="archiveForm" method="POST" action="archiveVehicle.php" style="display:inline;">
                    <input type="hidden" name="acquisition_id" id="archiveAcquisitionId">
                    <button type="submit" class="btn btn-success">Yes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
let confirmReleaseModal;
let confirmArchiveModal;

function openImageModal(imgSrc) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    modal.style.display = 'block';
    modalImg.src = imgSrc;
}

function closeImageModal() {
    document.getElementById('imageModal').style.display = 'none';
}

// Close modal when clicking outside the image
document.getElementById('imageModal').onclick = function(event) {
    if (event.target === this) {
        closeImageModal();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    confirmReleaseModal = new bootstrap.Modal(document.getElementById('confirmReleaseModal'));
    confirmArchiveModal = new bootstrap.Modal(document.getElementById('confirmArchiveModal'));

    // Add onclick to all clickable images
    const clickableImages = document.querySelectorAll('.clickable-image');
    clickableImages.forEach(img => {
        img.onclick = function() {
            openImageModal(this.src);
        };
    });

    // Show success or error modal if message exists
    <?php if ($successMessage): ?>
        document.getElementById('successMessageText').textContent = '<?= addslashes($successMessage) ?>';
        new bootstrap.Modal(document.getElementById('successModal')).show();
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        document.getElementById('errorMessageText').textContent = '<?= addslashes($errorMessage) ?>';
        new bootstrap.Modal(document.getElementById('errorModal')).show();
    <?php endif; ?>
});

function calculateCosts(acquisitionId) {
    const form = document.getElementById('operationsForm' + acquisitionId);
    const markupPercentage = parseFloat(form.querySelector('.markup-input').value) || 0;

    const acquiredPrice = parseFloat(form.closest('.modal-content')
        .querySelector('.cost-summary .cost-row:first-child span:last-child')
        .textContent.replace('₱', '').replace(/,/g, '')) || 0;

    const issuesCost = parseFloat(document.getElementById('issuesCost' + acquisitionId).textContent.replace('₱', '').replace(/,/g, '')) || 0;
    const partsCost = parseFloat(document.getElementById('partsCost' + acquisitionId).textContent.replace('₱', '').replace(/,/g, '')) || 0;

    const totalReconCost = acquiredPrice + issuesCost + partsCost;
    const markupValue = (totalReconCost * markupPercentage) / 100;
    const sellingPrice = totalReconCost + markupValue;

    document.getElementById('totalReconCost' + acquisitionId).textContent =
        '₱' + totalReconCost.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    document.getElementById('markupValue' + acquisitionId).textContent =
        '₱' + markupValue.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    document.getElementById('sellingPrice' + acquisitionId).textContent =
        '₱' + sellingPrice.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function confirmRelease(acquisitionId) {
    document.getElementById('releaseAcquisitionId').value = acquisitionId;
    confirmReleaseModal.show();
}

function confirmArchive(acquisitionId) {
    document.getElementById('archiveAcquisitionId').value = acquisitionId;
    confirmArchiveModal.show();
}
</script>
</body>
</html>