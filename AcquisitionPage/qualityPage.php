<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../LoginPage/loginPage.php');
    exit();
}

$userName = $_SESSION['user_name'];
$user_id = $_SESSION['id'];

// Get all Quality Check vehicles
$query = "SELECT * FROM vehicle_acquisition WHERE status = 'Quality Check' ORDER BY created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quality Check - CarMax</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/acquiPage.css">
    <style>
  
    </style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <img src="../Pictures/Carmax_logo.jpg" class="logo" alt="CarMax Logo">
        <div class="header-title h5 mb-0">Quality Check Management</div>
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
    <a href="/AcquisitionPage/qualityPage.php" class="sidebar-item active">
        <i class="fas fa-list"></i><span>Quality Check</span>
    </a>
    <a href="/AcquisitionPage/approvePage.php" class="sidebar-item">
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
            <i class="fas fa-clipboard-check"></i> Quality Check List</div>
        <div class="sap-card-body">
            <table class="sap-table table table-hover">
                <thead class="table-primary">
                    <tr>
                        <th>Plate Number</th>
                        <th>Model</th>
                        <th>Year</th>
                        <th>Color</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#qualityModal<?= $row['acquisition_id'] ?>">
                                <td><?= htmlspecialchars($row['plate_number']) ?></td>
                                <td><?= htmlspecialchars($row['vehicle_model']) ?></td>
                                <td><?= htmlspecialchars($row['year_model']) ?></td>
                                <td><?= htmlspecialchars($row['color']) ?></td>
                                <td>₱<?= number_format($row['projected_recon_price'], 2) ?></td>
                                <td><span class="badge bg-warning">Quality Check</span></td>
                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">No vehicles in quality check.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
// 🔹 Reset result pointer to reuse $result for modals
if ($result && $result->num_rows > 0):
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()):
?>

<!-- Quality Check Modal -->
<div class="modal fade" id="qualityModal<?= $row['acquisition_id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-white">
                <h5 class="modal-title"><i class="fas fa-clipboard-check"></i> Quality Check - <?= htmlspecialchars($row['vehicle_model']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" action="saveQualityCheck.php" id="qualityForm<?= $row['acquisition_id'] ?>">
                <input type="hidden" name="acquisition_id" value="<?= $row['acquisition_id'] ?>">

                <div class="modal-body">
                    <!-- Basic Information -->
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-info-circle"></i> Basic Information</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3"><label>Plate Number</label><input type="text" class="form-control" value="<?= htmlspecialchars($row['plate_number']) ?>" disabled></div>
                        <div class="col-md-3"><label>Vehicle Model</label><input type="text" class="form-control" value="<?= htmlspecialchars($row['vehicle_model']) ?>" disabled></div>
                        <div class="col-md-3"><label>Year Model</label><input type="number" class="form-control" value="<?= htmlspecialchars($row['year_model']) ?>" disabled></div>
                        <div class="col-md-3"><label>Color</label><input type="text" class="form-control" value="<?= htmlspecialchars($row['color']) ?>" disabled></div>
                    </div>

                    <!-- Vehicle Photos -->
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-images"></i> Vehicle Photos</h6>
                    <div class="photo-grid mb-4">
                        <?php 
                        $photos = ['wholecar' => 'Whole Car','dashboard' => 'Dashboard','hood' => 'Hood','interior' => 'Interior','exterior' => 'Exterior','trunk' => 'Trunk'];
                        foreach ($photos as $key => $label):
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

                    <!-- Issues Section -->
                    <?php 
                    $issuesQuery = $conn->query("SELECT * FROM acquisition_issues WHERE acquisition_id = {$row['acquisition_id']}");
                    if ($issuesQuery && $issuesQuery->num_rows > 0): ?>
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-exclamation-triangle"></i> Issues</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead>
                                <tr><th>Issue Name</th><th>Photo</th><th>Repaired</th><th>Repaired By</th></tr>
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
                                    <td>
                                        <input type="checkbox" class="form-check-input issue-checkbox" 
                                            name="issue_repaired[<?= $issue['issue_id'] ?>]" 
                                            value="1" <?= $issue['is_repaired'] ? 'checked' : '' ?>
                                            onchange="toggleRepairedBy(this, <?= $issue['issue_id'] ?>)">
                                        <label>Yes</label>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control repaired-by-input" 
                                            id="repairedBy<?= $issue['issue_id'] ?>"
                                            name="issue_repaired_by[<?= $issue['issue_id'] ?>]" 
                                            value="<?= htmlspecialchars($issue['repaired_by'] ?? '') ?>"
                                            placeholder="Enter name"
                                            <?= $issue['is_repaired'] ? '' : 'disabled' ?>>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                    <!-- Parts Needed Section -->
                    <?php 
                    $partsQuery = $conn->query("SELECT * FROM acquisition_parts WHERE acquisition_id = {$row['acquisition_id']}");
                    if ($partsQuery && $partsQuery->num_rows > 0): ?>
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-tools"></i> Parts Needed</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead><tr><th>Part Name</th><th>Ordered</th><th>Ordered By</th></tr></thead>
                            <tbody>
                                <?php while ($part = $partsQuery->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($part['part_name']) ?></td>
                                    <td>
                                        <input type="checkbox" class="form-check-input part-checkbox" 
                                            name="part_ordered[<?= $part['part_id'] ?>]" 
                                            value="1" <?= $part['is_ordered'] ? 'checked' : '' ?>
                                            onchange="toggleOrderedBy(this, <?= $part['part_id'] ?>)">
                                        <label>Yes</label>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control ordered-by-input" 
                                            id="orderedBy<?= $part['part_id'] ?>"
                                            name="part_ordered_by[<?= $part['part_id'] ?>]" 
                                            value="<?= htmlspecialchars($part['ordered_by'] ?? '') ?>"
                                            placeholder="Enter name"
                                            <?= $part['is_ordered'] ? '' : 'disabled' ?>>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                    <!-- Vehicle Condition -->
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-clipboard-check"></i> Vehicle Condition</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3"><label>Spare Tires</label><input type="text" class="form-control" value="<?= htmlspecialchars($row['spare_tires']) ?>" disabled></div>
                        <div class="col-md-3"><label>Complete Tools</label><input type="text" class="form-control" value="<?= htmlspecialchars($row['complete_tools']) ?>" disabled></div>
                        <div class="col-md-3"><label>Original Plate</label><input type="text" class="form-control" value="<?= htmlspecialchars($row['original_plate']) ?>" disabled></div>
                        <div class="col-md-3"><label>Complete Documents</label><input type="text" class="form-control" value="<?= htmlspecialchars($row['complete_documents']) ?>" disabled></div>
                    </div>

                    <!-- Document Photos -->
                    <?php 
                    if (!empty($row['document_photos'])):
                        $docPhotos = json_decode($row['document_photos'], true);
                        if (!is_array($docPhotos)) $docPhotos = explode(',', $row['document_photos']);
                    ?>
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-file-contract"></i> Document Photos</h6>
                    <div class="photo-grid mb-4">
                        <?php foreach ($docPhotos as $photo): $photo = trim($photo); if (!empty($photo)): ?>
                        <div class="photo-box"><img src="../uploads/<?= htmlspecialchars($photo) ?>" alt="Document"></div>
                        <?php endif; endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Remarks -->
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-comment"></i> Remarks</h6>
                    <textarea class="form-control mb-4" rows="3" disabled><?= htmlspecialchars($row['remarks'] ?? '') ?></textarea>

                    <!-- Price -->
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-peso-sign"></i> Projected Recon Price</h6>
                    <div class="input-group mb-3">
                        <span class="input-group-text">₱</span>
                        <input type="text" class="form-control" value="<?= number_format($row['projected_recon_price'], 2) ?>" disabled>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> Close</button>
                    <button type="button" class="btn btn-primary" onclick="confirmSaveQuality(<?= $row['acquisition_id'] ?>)"><i class="fas fa-save"></i> Save</button>
                    <button type="button" class="btn btn-success approve-btn" id="approveBtn<?= $row['acquisition_id'] ?>" onclick="confirmApproveQuality(<?= $row['acquisition_id'] ?>)"><i class="fas fa-check"></i> Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endwhile; endif; ?>

<!-- Confirmation Modals -->
<div class="modal fade" id="confirmSaveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-question-circle"></i> Confirm Save</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">Are you sure you want to save the quality check progress?</div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="confirmSaveBtn">Yes</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmApproveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="fas fa-question-circle"></i> Confirm Approval</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">Are you sure you want to approve this vehicle? This action will move it to the Approved page.</div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-success" id="confirmApproveBtn">Yes</button></div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
let currentAcquisitionId = null;
let confirmSaveModal = null;
let confirmApproveModal = null;

document.addEventListener('DOMContentLoaded', function() {
    confirmSaveModal = new bootstrap.Modal(document.getElementById('confirmSaveModal'));
    confirmApproveModal = new bootstrap.Modal(document.getElementById('confirmApproveModal'));
    
    document.getElementById('confirmSaveBtn').addEventListener('click', function() {
        if (currentAcquisitionId) {
            saveQuality(currentAcquisitionId);
            confirmSaveModal.hide();
        }
    });
    
    document.getElementById('confirmApproveBtn').addEventListener('click', function() {
        if (currentAcquisitionId) {
            approveQuality(currentAcquisitionId);
            confirmApproveModal.hide();
        }
    });
});

function confirmSaveQuality(acquisitionId) {
    currentAcquisitionId = acquisitionId;
    confirmSaveModal.show();
}

function confirmApproveQuality(acquisitionId) {
    const approveBtn = document.getElementById('approveBtn' + acquisitionId);
    if (approveBtn.disabled) {
        alert('⚠️ Please complete all issues and parts before approving!');
        return;
    }
    currentAcquisitionId = acquisitionId;
    confirmApproveModal.show();
}

function toggleRepairedBy(checkbox, issueId) {
    const input = document.getElementById('repairedBy' + issueId);
    input.disabled = !checkbox.checked;
    if (!checkbox.checked) {
        input.value = '';
    }
    
    const form = checkbox.closest('form');
    const acquisitionId = form.querySelector('input[name="acquisition_id"]').value;
    checkApproveButton(acquisitionId);
}

function toggleOrderedBy(checkbox, partId) {
    const input = document.getElementById('orderedBy' + partId);
    input.disabled = !checkbox.checked;
    if (!checkbox.checked) {
        input.value = '';
    }
    
    const form = checkbox.closest('form');
    const acquisitionId = form.querySelector('input[name="acquisition_id"]').value;
    checkApproveButton(acquisitionId);
}

function checkApproveButton(acquisitionId) {
    const form = document.getElementById('qualityForm' + acquisitionId);
    const approveBtn = document.getElementById('approveBtn' + acquisitionId);
    
    if (!form || !approveBtn) return;
    
    const issueCheckboxes = form.querySelectorAll('.issue-checkbox');
    const partCheckboxes = form.querySelectorAll('.part-checkbox');
    
    const hasCheckboxes = issueCheckboxes.length > 0 || partCheckboxes.length > 0;
    
    if (!hasCheckboxes) {
        approveBtn.disabled = false;
        return;
    }
    
    const allIssuesChecked = Array.from(issueCheckboxes).every(cb => cb.checked);
    const allPartsChecked = Array.from(partCheckboxes).every(cb => cb.checked);
    
    approveBtn.disabled = !(allIssuesChecked && allPartsChecked);
}

function saveQuality(acquisitionId) {
    const form = document.getElementById('qualityForm' + acquisitionId);
    const formData = new FormData(form);
    formData.append('action', 'save');
    
    fetch('saveQualityCheck.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Quality check saved successfully!');
        } else {
            alert('❌ Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ Error saving quality check');
        console.error(error);
    });
}

function approveQuality(acquisitionId) {
    const form = document.getElementById('qualityForm' + acquisitionId);
    const formData = new FormData(form);
    formData.append('action', 'approve');
    
    fetch('saveQualityCheck.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Vehicle approved successfully!');
            window.location.reload();
        } else {
            alert('❌ Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ Error approving vehicle');
        console.error(error);
    });
}
</script>

</body>
</html>