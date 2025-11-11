<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../LoginPage/loginPage.php');
    exit();
}

$userName = $_SESSION['user_name'];
$user_id = $_SESSION['id'];

// Get all approved vehicles
$query = "SELECT * FROM vehicle_acquisition WHERE status = 'Approved' ORDER BY approved_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approved Acquisitions - CarMax</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/acquiPage.css">
</head>
<body>

<div class="header">
    <div class="header-left">
        <img src="../Pictures/Carmax_logo.jpg" class="logo" alt="CarMax Logo">
        <div class="header-title h5 mb-0">Approved Acquisitions</div>
    </div>
    
    <div class="user-info">
        <i class="fas fa-user-circle" style="font-size: 24px;"></i>
        <span>
            <?php 
                $role = $_SESSION['role'];
                $title = match($role) {
                    'acquisition' => 'Acquisition Admin',
                    'operation' => 'Operation Admin',
                    'superadmin' => 'Super Admin',
                    default => ucfirst($role)
                };
                echo htmlspecialchars($userName) . " ($title)";
            ?>
        </span>
        <a href="../logout.php" style="margin-left: 15px; color: white; text-decoration: none;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<div class="sidebar">
    <a href="/AcquisitionPage/acquiPage.php" class="sidebar-item">
        <i class="fas fa-car"></i><span>Acquisition</span>
    </a>
    <a href="/AcquisitionPage/qualityPage.php" class="sidebar-item">
        <i class="fas fa-list"></i><span>Quality Check</span>
    </a>
    <a href="/AcquisitionPage/approvePage.php" class="sidebar-item active">
        <i class="fas fa-check-square"></i><span>Approved Acquisition</span>
    </a>
    <a href="/InventoryPage/inventoryPage.php" class="sidebar-item">
       <i class="fas fa-warehouse"></i><span>Inventory</span>
    </a>
    <a href="/InventoryPage/recentInventory.php" class="sidebar-item">
       <i class="fas fa-history"></i><span>Recent Inventory</span>
    </a>
</div>

<div class="main-content">
    <div class="sap-card">
        <div class="sap-card-header">
            <i class="fas fa-check-circle"></i> Approved Vehicle Acquisitions
        </div>

        <div class="sap-card-body">
            <table class="sap-table table table-hover">
                <thead class="table-success">
                    <tr>
                        <th>Plate Number</th>
                        <th>Model</th>
                        <th>Year</th>
                        <th>Color</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Approved By</th>
                        <th>Approved Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#approveModal<?= $row['acquisition_id'] ?>">
                                <td><?= htmlspecialchars($row['plate_number']) ?></td>
                                <td><?= htmlspecialchars($row['vehicle_model']) ?></td>
                                <td><?= htmlspecialchars($row['year_model']) ?></td>
                                <td><?= htmlspecialchars($row['color']) ?></td>
                                <td>₱<?= number_format($row['acquired_price'], 2) ?></td>
                                <td><span class="badge bg-success">Approved</span></td>
                                <td><?= htmlspecialchars($row['approved_by'] ?? 'N/A') ?></td>
                                <td><?= $row['approved_at'] ? date('M d, Y', strtotime($row['approved_at'])) : 'N/A' ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center">No approved vehicles yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
if ($result && $result->num_rows > 0):
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()):
?>
<!-- Approved Vehicle Modal -->
<div class="modal fade" id="approveModal<?= $row['acquisition_id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-white">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle"></i> Approved Vehicle - <?= htmlspecialchars($row['vehicle_model']) ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <!-- Basic Information -->
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-info-circle"></i> Basic Information</h6>
                <div class="mb-4">
                    <div class="info-row"><div class="info-label">Plate Number:</div><div class="info-value"><?= htmlspecialchars($row['plate_number']) ?></div></div>
                    <div class="info-row"><div class="info-label">Vehicle Model:</div><div class="info-value"><?= htmlspecialchars($row['vehicle_model']) ?></div></div>
                    <div class="info-row"><div class="info-label">Year Model:</div><div class="info-value"><?= htmlspecialchars($row['year_model']) ?></div></div>
                    <div class="info-row"><div class="info-label">Color:</div><div class="info-value"><?= htmlspecialchars($row['color']) ?></div></div>
                    <div class="info-row"><div class="info-label">Acquired Price:</div><div class="info-value">₱<?= number_format($row['acquired_price'], 2) ?></div></div>
                </div>

                <!-- Vehicle Photos -->
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-images"></i> Vehicle Photos</h6>
                <div class="photo-grid mb-4">
                    <?php 
                    $photos = ['wholecar'=>'Whole Car','dashboard'=>'Dashboard','hood'=>'Hood','interior'=>'Interior','exterior'=>'Exterior','trunk'=>'Trunk'];
                    foreach ($photos as $key => $label):
                        $photoPath = htmlspecialchars($row[$key.'_photo'] ?? '');
                    ?>
                    <div class="photo-box">
                        <label><?= $label ?></label>
                        <?php if ($photoPath): ?>
                            <img src="../uploads/<?= $photoPath ?>" alt="<?= $label ?>">
                        <?php else: ?>
                            <div class="text-muted">No image</div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Issues -->
                <?php 
                $issuesQuery = $conn->query("SELECT * FROM acquisition_issues WHERE acquisition_id = {$row['acquisition_id']}");
                if ($issuesQuery && $issuesQuery->num_rows > 0): ?>
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-exclamation-triangle"></i> Issues (Repaired)</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead>
                            <tr><th>Issue Name</th><th>Photo</th><th>Price</th><th>Remarks</th><th>Status</th><th>Repaired By</th></tr>
                        </thead>
                        <tbody>
                            <?php while ($issue = $issuesQuery->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($issue['issue_name']) ?></td>
                                <td>
                                    <?php if (!empty($issue['issue_photo'])): ?>
                                        <img src="../uploads/<?= htmlspecialchars($issue['issue_photo']) ?>" style="max-width: 100px; border-radius: 5px;">
                                    <?php endif; ?>
                                </td>
                                <td>₱<?= $issue['issue_price'] ? number_format($issue['issue_price'], 2) : 'N/A' ?></td>
                                <td><?= htmlspecialchars($issue['issue_remarks'] ?? 'N/A') ?></td>
                                <td><span class="badge bg-success"><i class="fas fa-check"></i> Repaired</span></td>
                                <td><?= htmlspecialchars($issue['repaired_by'] ?? 'N/A') ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- Parts -->
                <?php 
                $partsQuery = $conn->query("SELECT * FROM acquisition_parts WHERE acquisition_id = {$row['acquisition_id']}");
                if ($partsQuery && $partsQuery->num_rows > 0): ?>
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-tools"></i> Parts (Ordered)</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead>
                            <tr><th>Part Name</th><th>Price</th><th>Remarks</th><th>Status</th><th>Ordered By</th></tr>
                        </thead>
                        <tbody>
                            <?php while ($part = $partsQuery->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($part['part_name']) ?></td>
                                <td>₱<?= $part['part_price'] ? number_format($part['part_price'], 2) : 'N/A' ?></td>
                                <td><?= htmlspecialchars($part['part_remarks'] ?? 'N/A') ?></td>
                                <td><span class="badge bg-success"><i class="fas fa-check"></i> Ordered</span></td>
                                <td><?= htmlspecialchars($part['ordered_by'] ?? 'N/A') ?></td>
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
                            <img src="../uploads/<?= $photoPath ?>" alt="<?= $label ?>">
                        <?php else: ?>
                            <div class="text-muted">No image</div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Remarks -->
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-comment"></i> Remarks</h6>
                <div class="alert alert-info"><?= nl2br(htmlspecialchars($row['remarks'] ?? 'No remarks')) ?></div>

                <!-- Approval Info -->
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-user-check"></i> Approval Information</h6>
                <div class="mb-4">
                    <div class="info-row"><div class="info-label">Quality Checked By:</div><div class="info-value"><?= htmlspecialchars($row['quality_checked_by'] ?? 'N/A') ?></div></div>
                    <div class="info-row"><div class="info-label">Quality Checked At:</div><div class="info-value"><?= $row['quality_checked_at'] ? date('M d, Y h:i A', strtotime($row['quality_checked_at'])) : 'N/A' ?></div></div>
                    <div class="info-row"><div class="info-label">Approved By:</div><div class="info-value"><?= htmlspecialchars($row['approved_by'] ?? 'N/A') ?></div></div>
                    <div class="info-row"><div class="info-label">Approved At:</div><div class="info-value"><?= $row['approved_at'] ? date('M d, Y h:i A', strtotime($row['approved_at'])) : 'N/A' ?></div></div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <?php if ($_SESSION['role'] === 'acquisition' || $_SESSION['role'] === 'superadmin'): ?>
                    <button type="button" class="btn btn-primary" 
                        data-bs-toggle="modal" 
                        data-bs-target="#confirmSendModal" 
                        data-id="<?= $row['acquisition_id'] ?>">
                        <i class="fas fa-paper-plane"></i> Send to Operations
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endwhile; endif; ?>

<!-- Confirm Send to Operations Modal -->
<div class="modal fade" id="confirmSendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-paper-plane"></i> Confirm Send</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to send this vehicle to the Operations department?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="confirmSendForm" method="POST" action="sendToOperations.php" style="display:inline;">
                    <input type="hidden" name="acquisition_id" id="confirmSendId">
                    <button type="submit" class="btn btn-primary">Yes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmSendModal = document.getElementById('confirmSendModal');
    const confirmSendId = document.getElementById('confirmSendId');

    confirmSendModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const acquisitionId = button.getAttribute('data-id');
        confirmSendId.value = acquisitionId;
    });
});
</script>
</body>
</html>