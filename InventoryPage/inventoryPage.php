<?php
session_start();

// Check if logged in
if (!isset($_SESSION['id'])) {
    header('Location: ../LoginPage/loginPage.php');
    exit();
}

require_once '../db_connect.php';

// Get the logged-in user's full name for approved_checked_by
$userQuery = $conn->query("SELECT firstname, lastname FROM users WHERE id = " . $_SESSION['id']);
$currentUser = $userQuery->fetch_assoc();
$approvedCheckedBy = $currentUser['firstname'] . ' ' . $currentUser['lastname'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier = $conn->real_escape_string($_POST['supplier']);
    $dateAcquired = $_POST['dateAcquired'];
    $year = intval($_POST['year']);
    $make = $conn->real_escape_string($_POST['make']);
    $model = $conn->real_escape_string($_POST['model']);
    $variant = $conn->real_escape_string($_POST['variant']);
    $color = $conn->real_escape_string($_POST['color']);
    $plateNumber = $conn->real_escape_string($_POST['plateNumber']);
    $fuel = $_POST['fuel'];
    $odometer = intval($_POST['odometer']);
    $bodyType = $conn->real_escape_string($_POST['bodyType']);
    $spareKey = $_POST['spareKey'];
    $transmission = $_POST['transmission'];
    $actualSpend = !empty($_POST['actualSpend']) ? floatval($_POST['actualSpend']) : NULL;
    $costBreakdown = !empty($_POST['costBreakdown']) ? floatval($_POST['costBreakdown']) : NULL;
    $remarks = $conn->real_escape_string($_POST['remarks']);
    $createdBy = $_SESSION['id'];
    $repairsList = $_POST['repairs_list'] ?? '[]';
    $reconditionsList = $_POST['reconditions_list'] ?? '[]';
    $costBreakdownList = $_POST['costbreakdown_list'] ?? '[]';

    $repairsTotal = 0;
    $reconditionsTotal = 0;
    $costTotal = 0;

    foreach (json_decode($repairsList, true) as $r) $repairsTotal += floatval($r['price'] ?? 0);
    foreach (json_decode($reconditionsList, true) as $r) $reconditionsTotal += floatval($r['price'] ?? 0);
    foreach (json_decode($costBreakdownList, true) as $r) $costTotal += floatval($r['price'] ?? 0);

    $projectedCost = $repairsTotal + $reconditionsTotal;
    $costBreakdown = $costTotal;
    // Handle file uploads
    $receiptPhotos = [];
    if (isset($_FILES['receipts']) && !empty($_FILES['receipts']['name'][0])) {
        $uploadDir = '../uploads/receipts/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        foreach ($_FILES['receipts']['tmp_name'] as $key => $tmp_name) {
            $fileName = time() . '_' . $key . '_' . $_FILES['receipts']['name'][$key];
            $filePath = $uploadDir . $fileName;
            if (move_uploaded_file($tmp_name, $filePath)) {
                $receiptPhotos[] = $fileName;
            }
        }
    }
    $receiptPhotosJson = json_encode($receiptPhotos);
    
    // Insert into database with approved_checked_by
     $sql = "INSERT INTO vehicle_inventory (
                supplier, date_acquired, year_model, make, model, variant, color, plate_number, 
                fuel_type, odometer, body_type, spare_key, transmission, projected_repair_cost, 
                cost_breakdown, repairs_list, reconditions_list, costbreakdown_list, 
                receipt_photos, remarks, approved_checked_by, created_by
            ) VALUES (
                '$supplier', '$dateAcquired', $year, '$make', '$model', '$variant', '$color', '$plateNumber',
                '$fuel', $odometer, '$bodyType', '$spareKey', '$transmission',
                $projectedCost, $costBreakdown,
                '$repairsList', '$reconditionsList', '$costBreakdownList',
                '$receiptPhotosJson', '$remarks', '$approvedCheckedBy', $createdBy
            )";
    
    if ($conn->query($sql)) {
        $_SESSION['success_message'] = "Inventory saved successfully!";
        header('Location: inventoryPage.php');
        exit();
    } else {
        $error = "Error: " . $conn->error;
    }
}

$userName = $_SESSION['user_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - CarMax</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/inventory.css" rel="stylesheet">
</head>

<body>
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <img src="../Pictures/Carmax_logo.jpg" alt="CarMax" class="logo">
            <div class="header-title">Inventory Management</div>
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

    <!-- Sidebar -->
<div class="sidebar">
    <a href="/AcquisitionPage/acquiPage.php" class="sidebar-item ">
        <i class="fas fa-car"></i><span>Acquisition</span>
    </a>
     <a href="/AcquisitionPage/qualityPage.php" class="sidebar-item">
        <i class="fas fa-list"></i><span>Quality Check</span>
    </a>
    <a href="/AcquisitionPage/approvePage.php" class="sidebar-item">
        <i class="fas fa-check-square"></i><span>Approved Acquisition</span>
    </a>
    <a href="/InventoryPage/inventoryPage.php" class="sidebar-item active">
       <i class="fas fa-warehouse"></i><span>Inventory</span>
    </a>
        <a href="/InventoryPage/recentInventory.php" class="sidebar-item">
       <i class="fas fa-history"></i><span>Recent Inventory</span>
    </a>
</div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Add New Inventory Form -->
        <div class="sap-card">
            <div class="sap-card-header">
                <div><i class="fas fa-plus-circle"></i> Add New Inventory</div>
            </div>
            <div class="sap-card-body">
                <form method="POST" enctype="multipart/form-data" id="inventoryForm">
                    <!-- Basic Information -->
                    <h6 style="color: var(--carmax-blue); font-weight: 600; margin-bottom: 15px;">
                        <i class="fas fa-info-circle"></i> Basic Information
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Supplier</label>
                            <input type="text" class="form-control" name="supplier" placeholder="Enter supplier name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date Acquired</label>
                            <input type="date" class="form-control" name="dateAcquired" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="section-divider"></div>

                    <!-- Vehicle Details -->
                    <h6 style="color: var(--carmax-blue); font-weight: 600; margin-bottom: 15px;">
                        <i class="fas fa-car"></i> Vehicle Details
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Year Model</label>
                            <input type="text" class="form-control" name="year" placeholder="e.g., 2021" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Make</label>
                            <input type="text" class="form-control" name="make" placeholder="e.g., Honda, Toyota" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Model</label>
                            <input type="text" class="form-control" name="model" placeholder="e.g., Civic, Vios" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Variant</label>
                            <input type="text" class="form-control" name="variant" placeholder="e.g., 1.8 EL, 1.5 G">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Color</label>
                            <input type="text" class="form-control" name="color" placeholder="e.g., White, Black" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Plate Number</label>
                            <input type="text" class="form-control" name="plateNumber" placeholder="e.g., NEM1034" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fuel Type</label>
                            <select class="form-select" name="fuel" required>
                                <option value="">Select Fuel Type</option>
                                <option value="Gasoline">Gasoline</option>
                                <option value="Diesel">Diesel</option>
                                <option value="Hybrid">Hybrid</option>
                                <option value="Electric">Electric</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Odometer (km)</label>
                            <input type="number" class="form-control" name="odometer" placeholder="e.g., 50000" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Body Type</label>
                            <input type="text" class="form-control" name="bodyType" placeholder="e.g., Sedan, SUV" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Spare Key</label>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="spareKey" value="Yes" required> Yes
                                </label>
                                <label>
                                    <input type="radio" name="spareKey" value="No"> No
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Transmission</label>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="transmission" value="Manual" required> Manual
                                </label>
                                <label>
                                    <input type="radio" name="transmission" value="Automatic"> Automatic
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="section-divider"></div>

                <!-- Financial Details -->
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-peso-sign"></i> Financial Details</h6>

                <h5>Projected Repair Cost (Repairs)</h5>
                <table class="table table-bordered" id="repairsTable">
                    <thead><tr><th>Repair Name</th><th>Price (₱)</th><th>Action</th></tr></thead>
                    <tbody></tbody>
                </table>
                <button type="button" class="btn btn-sm btn-carmax-secondary mb-3" onclick="addRepairRow()">
                    <i class="fas fa-plus"></i> Add Repair
                </button>

                <h5>Reconditions</h5>
                <table class="table table-bordered" id="reconditionTable">
                    <thead><tr><th>Recondition Name</th><th>Price (₱)</th><th>Action</th></tr></thead>
                    <tbody></tbody>
                </table>
                <button type="button" class="btn btn-sm btn-carmax-secondary mb-3" onclick="addReconditionRow()">
                    <i class="fas fa-plus"></i> Add Recondition
                </button>

                <h5>Cost Breakdown</h5>
                <table class="table table-bordered" id="costBreakdownTable">
                    <thead><tr><th>Item</th><th>Category</th><th>Price (₱)</th></tr></thead>
                    <tbody id="costBreakdownBody"></tbody>
                    <tfoot><tr><th colspan="2" class="text-end">Total:</th><th id="costBreakdownTotal">₱0.00</th></tr></tfoot>
                </table>

                <input type="hidden" name="repairs_list" id="repairs_list">
                <input type="hidden" name="reconditions_list" id="reconditions_list">
                <input type="hidden" name="costbreakdown_list" id="costbreakdown_list">

                   <!-- Receipts Upload -->
                    <div class="mb-4">
                        <label class="form-label">Picture of Receipts</label>
                        <input type="file" class="form-control" name="receipts[]" accept="image/*" multiple onchange="previewReceipts(this)">
                        
                        <!-- Preview container -->
                        <div id="receiptsPreview" class="d-flex flex-wrap gap-3 mt-2"></div>
                    </div>


                    <!-- Remarks -->
                    <div class="mb-4">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" name="remarks" rows="4" placeholder="Enter any additional remarks or notes..."></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="submit" class="btn btn-carmax-primary">
                            <i class="fas fa-save"></i> Save Inventory
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
       function previewReceipts(input) {
    const previewContainer = document.getElementById('receiptsPreview');
    previewContainer.innerHTML = ''; 

    if (input.files && input.files.length > 0) {
        Array.from(input.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'image-preview';
                previewContainer.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    }
}

        function addRepairRow() {
    const tbody = document.querySelector('#repairsTable tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" class="form-control repair-name" placeholder="Repair name"></td>
        <td><input type="number" class="form-control repair-price" placeholder="₱" min="0"></td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove();updateCostBreakdown();"><i class="fas fa-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
}

function addReconditionRow() {
    const tbody = document.querySelector('#reconditionTable tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" class="form-control recondition-name" placeholder="Recondition name"></td>
        <td><input type="number" class="form-control recondition-price" placeholder="₱" min="0"></td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove();updateCostBreakdown();"><i class="fas fa-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
}

function updateCostBreakdown() {
    const repairs = [...document.querySelectorAll('.repair-name')].map((el, i) => ({
        name: el.value, price: document.querySelectorAll('.repair-price')[i].value || 0
    }));
    const recons = [...document.querySelectorAll('.recondition-name')].map((el, i) => ({
        name: el.value, price: document.querySelectorAll('.recondition-price')[i].value || 0
    }));

    const allCosts = [...repairs.map(r => ({item: r.name, category: 'Repair', price: parseFloat(r.price)})),
                      ...recons.map(r => ({item: r.name, category: 'Recondition', price: parseFloat(r.price)}))];

    document.getElementById('costBreakdownBody').innerHTML = allCosts.map(r =>
        `<tr><td>${r.item}</td><td>${r.category}</td><td>₱${r.price.toFixed(2)}</td></tr>`).join('');

    const total = allCosts.reduce((sum, r) => sum + r.price, 0);
    document.getElementById('costBreakdownTotal').innerText = '₱' + total.toFixed(2);

    document.getElementById('repairs_list').value = JSON.stringify(repairs);
    document.getElementById('reconditions_list').value = JSON.stringify(recons);
    document.getElementById('costbreakdown_list').value = JSON.stringify(allCosts);
}

document.addEventListener('input', e => {
    if (e.target.matches('.repair-name, .repair-price, .recondition-name, .recondition-price')) {
        updateCostBreakdown();
    }
});
    </script>
</body>
</html>