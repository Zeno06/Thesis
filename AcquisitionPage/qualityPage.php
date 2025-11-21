<?php
require_once '../session_helper.php';
startRoleSession('acquisition');  

include '../db_connect.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'acquisition') {
    header('Location: ../LoginPage/loginPage.php');
    exit();
}

$userName = $_SESSION['user_name'];
$userRole = $_SESSION['role'];
$user_id = $_SESSION['id'];

// Get filter parameters
$searchQuery = $_GET['search'] ?? '';
$modelFilter = $_GET['model'] ?? '';

// Build query with filters
$query = "SELECT * FROM vehicle_acquisition WHERE status = 'Quality Check'";

if (!empty($searchQuery)) {
    $escSearch = $conn->real_escape_string($searchQuery);
    $query .= " AND (plate_number LIKE '%$escSearch%' OR vehicle_model LIKE '%$escSearch%')";
}

if (!empty($modelFilter)) {
    $escModel = $conn->real_escape_string($modelFilter);
    $query .= " AND vehicle_model = '$escModel'";
}

$query .= " ORDER BY created_at DESC";
$result = $conn->query($query);

// Get unique models for filter
$modelsQuery = "SELECT DISTINCT vehicle_model FROM vehicle_acquisition WHERE status = 'Quality Check' ORDER BY vehicle_model";
$models = $conn->query($modelsQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quality Check</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/acquiPage.css">
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
            <span><i class="fas fa-clipboard-check"></i> Quality Check List</span>
            <form method="GET" class="filter-form-horizontal">
                <input type="text" name="search" class="form-control" placeholder="Search plate or model..." value="<?= htmlspecialchars($searchQuery) ?>">
                
                <select name="model" class="form-select">
                    <option value="">All Models</option>
                    <?php 
                        if ($models) {
                            $models->data_seek(0);
                            while($model = $models->fetch_assoc()):
                    ?>
                        <option value="<?= htmlspecialchars($model['vehicle_model']) ?>" <?= $modelFilter === $model['vehicle_model'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($model['vehicle_model']) ?>
                        </option>
                    <?php 
                            endwhile;
                        }
                    ?>
                </select>

                    <button type="submit" class="btn-carmax-secondary">
                        <i class="fas fa-filter"></i> Filter
                    </button>                <?php if (!empty($searchQuery) || !empty($modelFilter)): ?>
                    <a href="qualityPage.php" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </form>
        </div>
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
                                <td>₱<?= number_format($row['acquired_price'], 2) ?></td>
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

            <form method="POST" action="saveQualityCheck.php" enctype="multipart/form-data" id="qualityForm<?= $row['acquisition_id'] ?>">
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
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-exclamation-triangle"></i> Issues</h6>
                    <div class="table-responsive mb-3" id="issuesSection<?= $row['acquisition_id'] ?>">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Issue Name</th>
                                    <th>Photo</th>
                                    <th>Price</th>
                                    <th>Remarks</th>
                                    <th>Repaired</th>
                                    <th>Repaired By</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="issuesTableBody<?= $row['acquisition_id'] ?>">
                                <?php 
                                $issuesQuery = $conn->query("SELECT * FROM acquisition_issues WHERE acquisition_id = {$row['acquisition_id']}");
                                if ($issuesQuery && $issuesQuery->num_rows > 0):
                                    while ($issue = $issuesQuery->fetch_assoc()): 
                                ?>
                                <tr data-issue-id="<?= $issue['issue_id'] ?>">
                                    <td><?= htmlspecialchars($issue['issue_name']) ?></td>
                                    <td>
                                        <?php if (!empty($issue['issue_photo'])): ?>
                                            <img src="../uploads/<?= htmlspecialchars($issue['issue_photo']) ?>" style="max-width: 100px; border-radius: 5px;">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" class="form-control issue-price" name="issue_price[<?= $issue['issue_id'] ?>]" 
                                            value="<?= htmlspecialchars($issue['issue_price'] ?? '') ?>" placeholder="0.00"
                                            onchange="checkApproveButton(<?= $row['acquisition_id'] ?>)">
                                    </td>
                                    <td>
                                        <textarea class="form-control issue-remarks" name="issue_remarks[<?= $issue['issue_id'] ?>]" rows="2" placeholder="Enter remarks"
                                            onchange="checkApproveButton(<?= $row['acquisition_id'] ?>)"><?= htmlspecialchars($issue['issue_remarks'] ?? '') ?></textarea>
                                    </td>
                                    <td>
                                        <input type="checkbox" class="form-check-input issue-checkbox" 
                                            name="issue_repaired[<?= $issue['issue_id'] ?>]" 
                                            value="1" <?= $issue['is_repaired'] ? 'checked' : '' ?>
                                            onchange="toggleRepairedBy(this, <?= $issue['issue_id'] ?>); checkApproveButton(<?= $row['acquisition_id'] ?>)">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control repaired-by-input" 
                                            id="repairedBy<?= $issue['issue_id'] ?>"
                                            name="issue_repaired_by[<?= $issue['issue_id'] ?>]" 
                                            value="<?= htmlspecialchars($issue['repaired_by'] ?? '') ?>"
                                            placeholder="Enter name"
                                            onchange="checkApproveButton(<?= $row['acquisition_id'] ?>)"
                                            <?= $issue['is_repaired'] ? '' : 'disabled' ?>>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteIssue(<?= $issue['issue_id'] ?>, <?= $row['acquisition_id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <input type="hidden" name="delete_issue[]" value="" id="deleteIssue<?= $issue['issue_id'] ?>">
                                    </td>
                                </tr>
                                <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-success mb-4" onclick="addIssueRow(<?= $row['acquisition_id'] ?>)">
                        <i class="fas fa-plus"></i> Add Issue
                    </button>

                    <!-- Parts Needed Section -->
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-tools"></i> Parts Needed</h6>
                    <div class="table-responsive mb-3" id="partsSection<?= $row['acquisition_id'] ?>">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Part Name</th>
                                    <th>Price</th>
                                    <th>Remarks</th>
                                    <th>Ordered</th>
                                    <th>Ordered By</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="partsTableBody<?= $row['acquisition_id'] ?>">
                                <?php 
                                $partsQuery = $conn->query("SELECT * FROM acquisition_parts WHERE acquisition_id = {$row['acquisition_id']}");
                                if ($partsQuery && $partsQuery->num_rows > 0):
                                    while ($part = $partsQuery->fetch_assoc()): 
                                ?>
                                <tr data-part-id="<?= $part['part_id'] ?>">
                                    <td><?= htmlspecialchars($part['part_name']) ?></td>
                                    <td>
                                        <input type="number" step="0.01" class="form-control part-price" name="part_price[<?= $part['part_id'] ?>]" 
                                            value="<?= htmlspecialchars($part['part_price'] ?? '') ?>" placeholder="0.00"
                                            onchange="checkApproveButton(<?= $row['acquisition_id'] ?>)">
                                    </td>
                                    <td>
                                        <textarea class="form-control part-remarks" name="part_remarks[<?= $part['part_id'] ?>]" rows="2" placeholder="Enter remarks"
                                            onchange="checkApproveButton(<?= $row['acquisition_id'] ?>)"><?= htmlspecialchars($part['part_remarks'] ?? '') ?></textarea>
                                    </td>
                                    <td>
                                        <input type="checkbox" class="form-check-input part-checkbox" 
                                            name="part_ordered[<?= $part['part_id'] ?>]" 
                                            value="1" <?= $part['is_ordered'] ? 'checked' : '' ?>
                                            onchange="toggleOrderedBy(this, <?= $part['part_id'] ?>); checkApproveButton(<?= $row['acquisition_id'] ?>)">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control ordered-by-input" 
                                            id="orderedBy<?= $part['part_id'] ?>"
                                            name="part_ordered_by[<?= $part['part_id'] ?>]" 
                                            value="<?= htmlspecialchars($part['ordered_by'] ?? '') ?>"
                                            placeholder="Enter name"
                                            onchange="checkApproveButton(<?= $row['acquisition_id'] ?>)"
                                            <?= $part['is_ordered'] ? '' : 'disabled' ?>>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deletePart(<?= $part['part_id'] ?>, <?= $row['acquisition_id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <input type="hidden" name="delete_part[]" value="" id="deletePart<?= $part['part_id'] ?>">
                                    </td>
                                </tr>
                                <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-success mb-4" onclick="addPartRow(<?= $row['acquisition_id'] ?>)">
                        <i class="fas fa-plus"></i> Add Part
                    </button>

                    <!-- Vehicle Condition -->
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-clipboard-check"></i> Vehicle Condition</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3"><label>Spare Tires</label><input type="text" class="form-control" value="<?= htmlspecialchars($row['spare_tires']) ?>" disabled></div>
                        <div class="col-md-3"><label>Complete Tools</label><input type="text" class="form-control" value="<?= htmlspecialchars($row['complete_tools']) ?>" disabled></div>
                        <div class="col-md-3"><label>Original Plate</label><input type="text" class="form-control" value="<?= htmlspecialchars($row['original_plate']) ?>" disabled></div>
                        <div class="col-md-3"><label>Complete Documents</label><input type="text" class="form-control" value="<?= htmlspecialchars($row['complete_documents']) ?>" disabled></div>
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
                    <textarea class="form-control mb-4" rows="3" disabled><?= htmlspecialchars($row['remarks'] ?? '') ?></textarea>

                    <!-- Price -->
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-peso-sign"></i> Acquired Price</h6>
                    <div class="input-group mb-3">
                        <span class="input-group-text">₱</span>
                        <input type="text" class="form-control" value="<?= number_format($row['acquired_price'], 2) ?>" disabled>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> Close</button>
                    <button type="button" class="btn btn-primary" onclick="confirmSaveQuality(<?= $row['acquisition_id'] ?>)"><i class="fas fa-save"></i> Save</button>
                    <button type="button" class="btn btn-success approve-btn" id="approveBtn<?= $row['acquisition_id'] ?>" onclick="confirmApproveQuality(<?= $row['acquisition_id'] ?>)" disabled>
                        <i class="fas fa-check"></i> Approve
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endwhile; endif; ?>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-check-circle"></i> Success</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="successMessage"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" onclick="location.reload()">OK</button>
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
            <div class="modal-body" id="errorMessage"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modals -->
<div class="modal fade" id="confirmSaveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-question-circle"></i> Confirm Save</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">Are you sure you want to save the quality check progress?</div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-success" id="confirmSaveBtn">Yes</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmApproveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-question-circle"></i> Confirm Approval</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
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
let successModal = null;
let errorModal = null;
let newIssueCounter = 0;
let newPartCounter = 0;

document.addEventListener('DOMContentLoaded', function() {
    confirmSaveModal = new bootstrap.Modal(document.getElementById('confirmSaveModal'));
    confirmApproveModal = new bootstrap.Modal(document.getElementById('confirmApproveModal'));
    successModal = new bootstrap.Modal(document.getElementById('successModal'));
    errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
    
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

function showSuccess(message) {
    document.getElementById('successMessage').textContent = message;
    successModal.show();
}

function showError(message) {
    document.getElementById('errorMessage').textContent = message;
    errorModal.show();
}

function addIssueRow(acquisitionId) {
    const tbody = document.getElementById('issuesTableBody' + acquisitionId);
    const newId = 'new_issue_' + (++newIssueCounter);
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="text" class="form-control new-issue-name" name="new_issue_name[]" placeholder="Issue name" required onchange="checkApproveButton(${acquisitionId})"></td>
        <td>
            <input type="file" class="form-control" name="new_issue_photos[]" accept="image/*" 
                onchange="previewIssueImage(this, '${newId}')">
            <div class="mt-2">
                <img id="preview_${newId}" src="" alt="Preview" style="max-width: 100px; display: none; border-radius: 5px;">
            </div>
        </td>
        <td><input type="number" step="0.01" class="form-control new-issue-price" name="new_issue_price[]" placeholder="0.00" onchange="checkApproveButton(${acquisitionId})"></td>
        <td><textarea class="form-control new-issue-remarks" name="new_issue_remarks[]" rows="2" placeholder="Enter remarks" onchange="checkApproveButton(${acquisitionId})"></textarea></td>
        <td>
            <input type="checkbox" class="form-check-input issue-checkbox" 
                name="new_issue_repaired[]" value="1"
                onchange="toggleRepairedBy(this, '${newId}'); checkApproveButton(${acquisitionId})">
        </td>
        <td>
            <input type="text" class="form-control repaired-by-input" 
                id="repairedBy${newId}"
                name="new_issue_repaired_by[]" 
                placeholder="Enter name" disabled onchange="checkApproveButton(${acquisitionId})">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger" 
                onclick="this.closest('tr').remove(); checkApproveButton(${acquisitionId})">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(row);
    checkApproveButton(acquisitionId);
}

function previewIssueImage(input, id) {
    const preview = document.getElementById('preview_' + id);
    const file = input.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.src = '';
        preview.style.display = 'none';
    }
}

function addPartRow(acquisitionId) {
    const tbody = document.getElementById('partsTableBody' + acquisitionId);
    const newId = 'new_part_' + (++newPartCounter);
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="text" class="form-control new-part-name" name="new_part_name[]" placeholder="Part name" required onchange="checkApproveButton(${acquisitionId})"></td>
        <td><input type="number" step="0.01" class="form-control new-part-price" name="new_part_price[]" placeholder="0.00" onchange="checkApproveButton(${acquisitionId})"></td>
        <td><textarea class="form-control new-part-remarks" name="new_part_remarks[]" rows="2" placeholder="Enter remarks" onchange="checkApproveButton(${acquisitionId})"></textarea></td>
        <td>
            <input type="checkbox" class="form-check-input part-checkbox" 
                name="new_part_ordered[]" value="1"
                onchange="toggleOrderedBy(this, '${newId}'); checkApproveButton(${acquisitionId})">
        </td>
        <td>
            <input type="text" class="form-control ordered-by-input" 
                id="orderedBy${newId}"
                name="new_part_ordered_by[]" 
                placeholder="Enter name" disabled onchange="checkApproveButton(${acquisitionId})">
        </td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove(); checkApproveButton(${acquisitionId})"><i class="fas fa-trash"></i></button></td>
    `;
    tbody.appendChild(row);
    checkApproveButton(acquisitionId);
}

function deleteIssue(issueId, acquisitionId) {
    if (confirm('Are you sure you want to delete this issue?')) {
        document.getElementById('deleteIssue' + issueId).value = issueId;
        document.querySelector('[data-issue-id="' + issueId + '"]').style.display = 'none';
        checkApproveButton(acquisitionId);
    }
}

function deletePart(partId, acquisitionId) {
    if (confirm('Are you sure you want to delete this part?')) {
        document.getElementById('deletePart' + partId).value = partId;
        document.querySelector('[data-part-id="' + partId + '"]').style.display = 'none';
        checkApproveButton(acquisitionId);
    }
}

function confirmSaveQuality(acquisitionId) {
    currentAcquisitionId = acquisitionId;
    confirmSaveModal.show();
}

function confirmApproveQuality(acquisitionId) {
    const approveBtn = document.getElementById('approveBtn' + acquisitionId);
    if (approveBtn.disabled) {
        showError('⚠️ Please complete all issues and parts before approving!\n\nMake sure:\n• All issues have price, remarks, are checked as repaired, and have "Repaired By" filled\n• All parts have price, remarks, are checked as ordered, and have "Ordered By" filled');
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
}

function toggleOrderedBy(checkbox, partId) {
    const input = document.getElementById('orderedBy' + partId);
    input.disabled = !checkbox.checked;
    if (!checkbox.checked) {
        input.value = '';
    }
}

function checkApproveButton(acquisitionId) {
    const form = document.getElementById('qualityForm' + acquisitionId);
    const approveBtn = document.getElementById('approveBtn' + acquisitionId);

    if (!form || !approveBtn) return;

    let canApprove = true;

    // --- Check EXISTING Issues ---
    const existingIssueRows = form.querySelectorAll('tr[data-issue-id]');
    existingIssueRows.forEach(row => {
        // Skip if row is deleted (hidden)
        if (row.style.display === 'none') return;

        const checkbox = row.querySelector('.issue-checkbox');
        const priceInput = row.querySelector('.issue-price');
        const remarksInput = row.querySelector('.issue-remarks');
        const repairedByInput = row.querySelector('.repaired-by-input');

        // All existing issues must be completed
        if (!priceInput || !priceInput.value.trim()) canApprove = false;
        if (!remarksInput || !remarksInput.value.trim()) canApprove = false;
        if (!checkbox || !checkbox.checked) canApprove = false;
        if (checkbox && checkbox.checked && (!repairedByInput || !repairedByInput.value.trim())) canApprove = false;
    });

    // --- Check NEW Issues ---
    const newIssueNames = form.querySelectorAll('.new-issue-name');
    newIssueNames.forEach((nameInput, index) => {
        const row = nameInput.closest('tr');
        if (!row || row.style.display === 'none') return;

        const priceInput = row.querySelector('.new-issue-price');
        const remarksInput = row.querySelector('.new-issue-remarks');
        const checkbox = row.querySelector('.issue-checkbox');
        const repairedByInput = row.querySelector('.repaired-by-input');

        // If name is filled, all fields must be filled
        if (nameInput.value.trim()) {
            if (!priceInput || !priceInput.value.trim()) canApprove = false;
            if (!remarksInput || !remarksInput.value.trim()) canApprove = false;
            if (!checkbox || !checkbox.checked) canApprove = false;
            if (checkbox && checkbox.checked && (!repairedByInput || !repairedByInput.value.trim())) canApprove = false;
        }
    });

    // --- Check EXISTING Parts ---
    const existingPartRows = form.querySelectorAll('tr[data-part-id]');
    existingPartRows.forEach(row => {
        // Skip if row is deleted (hidden)
        if (row.style.display === 'none') return;

        const checkbox = row.querySelector('.part-checkbox');
        const priceInput = row.querySelector('.part-price');
        const remarksInput = row.querySelector('.part-remarks');
        const orderedByInput = row.querySelector('.ordered-by-input');

        // All existing parts must be completed
        if (!priceInput || !priceInput.value.trim()) canApprove = false;
        if (!remarksInput || !remarksInput.value.trim()) canApprove = false;
        if (!checkbox || !checkbox.checked) canApprove = false;
        if (checkbox && checkbox.checked && (!orderedByInput || !orderedByInput.value.trim())) canApprove = false;
    });

    // --- Check NEW Parts ---
    const newPartNames = form.querySelectorAll('.new-part-name');
    newPartNames.forEach((nameInput, index) => {
        const row = nameInput.closest('tr');
        if (!row || row.style.display === 'none') return;

        const priceInput = row.querySelector('.new-part-price');
        const remarksInput = row.querySelector('.new-part-remarks');
        const checkbox = row.querySelector('.part-checkbox');
        const orderedByInput = row.querySelector('.ordered-by-input');

        // If name is filled, all fields must be filled
        if (nameInput.value.trim()) {
            if (!priceInput || !priceInput.value.trim()) canApprove = false;
            if (!remarksInput || !remarksInput.value.trim()) canApprove = false;
            if (!checkbox || !checkbox.checked) canApprove = false;
            if (checkbox && checkbox.checked && (!orderedByInput || !orderedByInput.value.trim())) canApprove = false;
        }
    });

    approveBtn.disabled = !canApprove;
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
            showSuccess('Quality check saved successfully!');
            // Re-check approve button after save
            setTimeout(() => {
                checkApproveButton(acquisitionId);
            }, 100);
        } else {
            showError('❌ Error: ' + data.message);
        }
    })
    .catch(error => {
        showError('❌ Error saving quality check');
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
            showSuccess('Vehicle approved successfully!');
        } else {
            showError('Error: ' + data.message);
        }
    })
    .catch(error => {
        showError('❌ Error approving vehicle');
        console.error(error);
    });
}
</script>

</body>
</html>