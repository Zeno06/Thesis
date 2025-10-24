<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../LoginPage/loginPage.php');
    exit();
}

$userName = $_SESSION['user_name'];
$user_id = $_SESSION['id'];

$adminQuery = $conn->query("SELECT firstname, lastname FROM users WHERE role = 'acquisition' LIMIT 1");
$admin = $adminQuery->fetch_assoc();
$adminName = $admin['firstname'] . ' ' . $admin['lastname'];
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
    <a href="/InventoryPage/inventoryPage.php" class="sidebar-item">
       <i class="fas fa-warehouse"></i><span>Inventory</span>
    </a>
    <a href="/AcquisitionPage/recentAcquisition.php" class="sidebar-item">
       <i class="fas fa-history"></i><span>Recent Acquisition</span>
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
                <div class="col-md-4"><label>Whole Car</label><input type="file" class="form-control" name="wholecar" accept="image/*"></div>
                <div class="col-md-4"><label>Dashboard</label><input type="file" class="form-control" name="dashboard" accept="image/*"></div>
                <div class="col-md-4"><label>Hood</label><input type="file" class="form-control" name="hood" accept="image/*"></div>
                <div class="col-md-4"><label>Interior</label><input type="file" class="form-control" name="interior" accept="image/*"></div>
                <div class="col-md-4"><label>Exterior</label><input type="file" class="form-control" name="exterior" accept="image/*"></div>
                <div class="col-md-4"><label>Trunk</label><input type="file" class="form-control" name="trunk" accept="image/*"></div>
            </div>

            <h5 class="section-title">Issues</h5>
            <input type="file" class="form-control mb-3" name="issuePhotos[]" multiple accept="image/*">
            <textarea class="form-control mb-3" name="issueRemarks" rows="3" placeholder="Remarks about vehicle issues..."></textarea>

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
            <div class="row g-3">
                <div class="col-md-3"><label>Spare Tires</label><select class="form-select" name="spareTires"><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label>Complete Tools</label><select class="form-select" name="completeTools"><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label>Original Plate</label><select class="form-select" name="originalPlate"><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label>Complete Documents</label><select class="form-select" name="completeDocuments"><option>Yes</option><option>No</option></select></div>
            </div>

            <h5 class="section-title">Document Photos</h5>
            <input type="file" class="form-control mb-3" name="documentPhotos[]" multiple accept="image/*,application/pdf">

            <h5 class="section-title">Remarks</h5>
            <textarea class="form-control mb-3" name="remarks" rows="3" placeholder="Enter additional remarks or notes..."></textarea>

            <h5 class="section-title">Projected Recon Price</h5>
            <div class="input-group mb-3">
                <span class="input-group-text">₱</span>
                <input type="number" step="0.01" class="form-control" name="projectedPrice" placeholder="0.00" required>
            </div>

            <div class="mt-3 d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-carmax-primary"><i class="fas fa-save"></i> Save as Draft</button>
            </div>
        </div>
    </div>
</form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
function addPartRow() {
    const table = document.getElementById('partsTable').getElementsByTagName('tbody')[0];
    const newRow = table.insertRow();
    newRow.innerHTML = `
        <td><input type="text" class="form-control" name="parts_needed[]" placeholder="Enter part name"></td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class='fas fa-trash'></i></button></td>
    `;
}
function removeRow(btn) {
    btn.closest('tr').remove();
}
</script>
</body>
</html>