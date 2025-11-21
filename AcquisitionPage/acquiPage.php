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
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vehicle Acquisition</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/acquiPage.css">
</head>
<body>

<div class="header">
    <div class="header-left">
        <img src="../Pictures/Carmax_logo.jpg" class="logo" alt="CarMax Logo">
        <div class="header-title h5 mb-0">Vehicle Acquisition Management</div>
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
    <a href="/AcquisitionPage/acquiPage.php" class="sidebar-item active">
        <i class="fas fa-car"></i><span>Acquisition</span>
    </a>
    <a href="/AcquisitionPage/qualityPage.php" class="sidebar-item">
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
<form id="vehicleForm" enctype="multipart/form-data" method="POST" action="saveacquisition.php">
    <div class="sap-card">
        <div class="sap-card-header"><i class="fas fa-car"></i> Vehicle Information</div>
        <div class="sap-card-body">
            
            <div class="row g-3 mb-4 align-items-end">
                <div class="col-md-3">
                    <label>Vehicle Model</label>
                    <input type="text" class="form-control" name="vehicleModel" placeholder="e.g., Honda BR-V" required>
                </div>
                <div class="col-md-3">
                    <label>Plate Number</label>
                    <input type="text" class="form-control" name="plateNumber" placeholder="e.g., NEM1034" required>
                </div>
                <div class="col-md-3">
                    <label>Year Model</label>
                    <input type="number" class="form-control" name="year" placeholder="e.g., 2021" required>
                </div>
                <div class="col-md-3">
                    <label>Color</label>
                    <input type="text" class="form-control" name="color" placeholder="e.g., White Pearl" required>
                </div>
            </div>

            <h5 class="section-title">Pictures of Car</h5>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label>Whole Car</label>
                    <input type="file" class="form-control" name="wholecar" accept="image/*" onchange="previewImage(this)" required>
                    <img class="image-preview d-none" alt="Preview">
                </div>
                <div class="col-md-4">
                    <label>Dashboard</label>
                    <input type="file" class="form-control" name="dashboard" accept="image/*" onchange="previewImage(this)" required>
                    <img class="image-preview d-none" alt="Preview">
                </div>
                <div class="col-md-4">
                    <label>Hood</label>
                    <input type="file" class="form-control" name="hood" accept="image/*" onchange="previewImage(this)" required>
                    <img class="image-preview d-none" alt="Preview">
                </div>
                <div class="col-md-4">
                    <label>Interior</label>
                    <input type="file" class="form-control" name="interior" accept="image/*" onchange="previewImage(this)" required>
                    <img class="image-preview d-none" alt="Preview">
                </div>
                <div class="col-md-4">
                    <label>Exterior</label>
                    <input type="file" class="form-control" name="exterior" accept="image/*" onchange="previewImage(this)" required>
                    <img class="image-preview d-none" alt="Preview">
                </div>
                <div class="col-md-4">
                    <label>Trunk</label>
                    <input type="file" class="form-control" name="trunk" accept="image/*" onchange="previewImage(this)" required>
                    <img class="image-preview d-none" alt="Preview">
                </div>
            </div>

            <h5 class="section-title">Issues</h5>
            <table class="table table-bordered mb-3" id="issuesTable">
                <thead>
                    <tr>
                        <th>Issue Name</th>
                        <th>Issue Photo</th>
                        <th style="width: 15%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="text" class="form-control" name="issue_names[]" placeholder="e.g., Dent on door"></td>
                        <td>
                            <input type="file" class="form-control" name="issue_photos[]" accept="image/*" onchange="previewImage(this)">
                            <img class="image-preview d-none mt-2" alt="Preview" style="max-width: 300px; border-radius: 6px;">
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeIssueRow(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-carmax-secondary mb-3" onclick="addIssueRow()">
                <i class="fas fa-plus"></i> Add Issue
            </button>

            <h5 class="section-title">Parts Needed</h5>
            <table class="table table-bordered mb-3" id="partsTable">
                <thead>
                    <tr>
                        <th>Part Name</th>
                        <th style="width: 15%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="text" class="form-control" name="parts_needed[]" placeholder="Enter part name"></td>
                        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-carmax-secondary mb-3" onclick="addPartRow()"><i class="fas fa-plus"></i> Add Part</button>

            <h5 class="section-title">Vehicle Condition</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label>Spare Tires</label><select class="form-select" name="spareTires" required><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label>Complete Tools</label><select class="form-select" name="completeTools" required><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label>Original Plate</label><select class="form-select" name="originalPlate" required><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label>Complete Documents</label><select class="form-select" name="completeDocuments" required><option>Yes</option><option>No</option></select></div>
            </div>

            <h5 class="section-title">Document Photos</h5>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label>OR/CR Photo</label>
                    <input type="file" class="form-control" name="orcrPhoto" accept="image/*,application/pdf" onchange="previewImage(this)" required>
                    <img class="image-preview d-none mt-2" alt="Preview" style="max-width: 300px; border-radius: 6px;">
                </div>
                <div class="col-md-4">
                    <label>Deed of Sale Photo</label>
                    <input type="file" class="form-control" name="deedOfSalePhoto" accept="image/*,application/pdf" onchange="previewImage(this)" required>
                    <img class="image-preview d-none mt-2" alt="Preview" style="max-width: 300px; border-radius: 6px;">
                </div>
                <div class="col-md-4">
                    <label>Insurance Photo</label>
                    <input type="file" class="form-control" name="insurancePhoto" accept="image/*,application/pdf" onchange="previewImage(this)" required>
                    <img class="image-preview d-none mt-2" alt="Preview" style="max-width: 300px; border-radius: 6px;">
                </div>
            </div>

            <h5 class="section-title">Remarks</h5>
            <textarea class="form-control mb-3" name="remarks" rows="3" placeholder="Enter additional remarks or notes..." required></textarea>

            <h5 class="section-title">Acquired Price</h5>
            <div class="input-group mb-3">
                <span class="input-group-text">₱</span>
                <input type="number" step="0.01" class="form-control" name="acquiredPrice" placeholder="0.00" required>
            </div>

            <div class="mt-3 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-carmax-primary" onclick="confirmSaveDraft()">
                    <i class="fas fa-save"></i> Save as Draft
                </button>
            </div>
        </div>
    </div>
</form>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title"><i class="fas fa-question-circle"></i> Confirm Save</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to save this as draft and send to Quality Check?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitForm()">Yes</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-check-circle"></i> Success</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Vehicle acquisition saved and sent to Quality Check!
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
let confirmModalInstance;

document.addEventListener('DOMContentLoaded', function() {
    confirmModalInstance = new bootstrap.Modal(document.getElementById('confirmModal'));
});

function previewImage(input) {
    const preview = input.parentElement.querySelector('.image-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function addIssueRow() {
    const table = document.getElementById('issuesTable').querySelector('tbody');
    const newRow = document.createElement('tr');

    newRow.innerHTML = `
        <td>
            <input type="text" class="form-control" name="issue_names[]" placeholder="e.g., Dent on door">
        </td>
        <td>
            <input type="file" class="form-control" name="issue_photos[]" accept="image/*" onchange="previewImage(this)">
            <img class="image-preview d-none mt-2" alt="Preview" style="max-width: 100px; border-radius: 6px;">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeIssueRow(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;

    table.appendChild(newRow);
}

function removeIssueRow(btn) {
    const tbody = btn.closest('tbody');
    if (tbody.querySelectorAll('tr').length > 1) {
        btn.closest('tr').remove();
    } else {
        alert('At least one issue row must remain');
    }
}

function addPartRow() {
    const table = document.getElementById('partsTable').getElementsByTagName('tbody')[0];
    const newRow = table.insertRow();
    newRow.innerHTML = `
        <td><input type="text" class="form-control" name="parts_needed[]" placeholder="Enter part name"></td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class='fas fa-trash'></i></button></td>
    `;
}

function removeRow(btn) {
    const tbody = btn.closest('tbody');
    if (tbody.querySelectorAll('tr').length > 1) {
        btn.closest('tr').remove();
    } else {
        alert('At least one part row must remain');
    }
}

function confirmSaveDraft() {
    const form = document.getElementById('vehicleForm');
    if (form.checkValidity()) {
        confirmModalInstance.show();
    } else {
        form.reportValidity();
    }
}

function submitForm() {
    confirmModalInstance.hide();
    document.getElementById('vehicleForm').submit();
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === '1') {
        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();

        window.history.replaceState({}, document.title, window.location.pathname);
    }

    if (urlParams.get('error')) {
        alert("Error: " + urlParams.get('error'));
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
</script>
</body>
</html>