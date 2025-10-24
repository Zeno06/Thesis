<?php
session_start();
if (!isset($_SESSION['id'])) {
    header('Location: ../LoginPage/loginPage.php');
    exit();
}
require_once '../db_connect.php';

$userName = $_SESSION['user_name'];
$userRole = $_SESSION['role'];
$canEdit = ($userRole === 'acquisition' || $userRole === 'superadmin');

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$searchQuery = '';
if (!empty($search)) {
    $searchQuery = " WHERE plate_number LIKE '%$search%' 
                     OR make LIKE '%$search%' 
                     OR model LIKE '%$search%' 
                     OR supplier LIKE '%$search%' 
                     OR color LIKE '%$search%'";
}

$sql = "SELECT * FROM vehicle_inventory $searchQuery ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recent Inventory - CarMax</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/inventory.css" rel="stylesheet">
    <style>
    .modal-dialog-scrollable .modal-body {
    max-height: calc(100vh - 180px); /* Adjust to fit your header/footer */
    overflow-y: auto !important;
    overflow-x: hidden;

    .approved-section {
  margin-top: 20px;
}
.approved-section label {
  font-weight: 600;
}
    .photo-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        justify-content: flex-start;
    }
    .photo-box {
        flex: 1 1 calc(33.333% - 15px);
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .photo-box img {
        width: 100%;
        max-height: 200px;
        object-fit: cover;
        border-radius: 10px;
        border: 1px solid #ddd;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
}
    </style>
</head>

<body>
    <div class="header">
        <div class="header-left">
            <img src="../Pictures/Carmax_logo.jpg" alt="CarMax" class="logo">
            <div class="header-title">Recent Inventory</div>
        </div>
        <div class="user-info">
            <i class="fas fa-user-circle" style="font-size: 24px;"></i>
            <span>
                <?php 
                    $title = match($userRole) {
                        'acquisition' => 'Acquisition Admin',
                        'operation' => 'Operation Admin',
                        'superadmin' => 'Super Admin',
                        default => ucfirst($userRole)
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
        <a href="../AcquisitionPage/acquiPage.php" class="sidebar-item">
            <i class="fas fa-car"></i><span>Acquisition</span>
        </a>
        <a href="inventoryPage.php" class="sidebar-item">
            <i class="fas fa-plus-circle"></i><span>Add Inventory</span>
        </a>
        <a href="recentInventory.php" class="sidebar-item active">
            <i class="fas fa-history"></i><span>Recent Inventory</span>
        </a>
    </div>
<div class="main-content">
    <div class="sap-card">
        <div class="sap-card-header">
            <div><i class="fas fa-list"></i> Inventory History</div>
            <div>
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control" placeholder="Search..." 
                        value="<?php echo htmlspecialchars($search); ?>" style="width: 300px;">
                    <button type="submit" class="btn-carmax-secondary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="recentInventory.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

<div class="sap-card-body">
    <table class="sap-table table table-hover">
        <thead class="table-dark">
            <tr>
                <th>Date Acquired</th>
                <th>Plate Number</th>
                <th>Make/Model</th>
                <th>Year</th>
                <th>Supplier</th>
                <th>Projected Cost</th>
                <th>Checked By</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['inventory_id'] ?>">
                        <td><?= $row['date_acquired'] != '0000-00-00' ? date('M d, Y', strtotime($row['date_acquired'])) : 'N/A' ?></td>
                        <td><?= htmlspecialchars($row['plate_number']) ?></td>
                        <td><?= htmlspecialchars($row['make'] . ' ' . $row['model']) ?></td>
                        <td><?= htmlspecialchars($row['year_model']) ?></td>
                        <td><?= htmlspecialchars($row['supplier']) ?></td>
                        <td>₱<?= number_format($row['projected_repair_cost'], 2) ?></td>
                        <td><?= htmlspecialchars($row['approved_checked_by']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">No inventory records found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php 
// Render all modals outside the table
if ($result && $result->num_rows > 0):
    $result->data_seek(0); // rewind the result pointer
    while ($row = $result->fetch_assoc()):
?>
<!-- Inventory Modal -->
<div class="modal fade" id="viewModal<?= $row['inventory_id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" data-id="<?= $row['inventory_id'] ?>">

            <!-- Modal Header -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-warehouse"></i> Inventory Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Modal Form -->
            <form method="POST" action="saveInventory.php" enctype="multipart/form-data">
                <input type="hidden" name="inventory_id" value="<?= $row['inventory_id'] ?>">
                <input type="hidden" name="repairs_list" id="repairs_list_<?= $row['inventory_id'] ?>" value='<?= htmlspecialchars($row['repairs_list'] ?? '[]') ?>'>
                <input type="hidden" name="reconditions_list" id="reconditions_list_<?= $row['inventory_id'] ?>" value='<?= htmlspecialchars($row['reconditions_list'] ?? '[]') ?>'>
                <input type="hidden" name="costbreakdown_list" id="costbreakdown_list_<?= $row['inventory_id'] ?>" value='<?= htmlspecialchars($row['costbreakdown_list'] ?? '[]') ?>'>

                <!-- Modal Body -->
                <div class="modal-body">
                    <!-- Basic Info -->
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-info-circle"></i> Basic Information</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label>Supplier</label>
                            <input type="text" class="form-control" name="supplier" value="<?= htmlspecialchars($row['supplier']) ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label>Date Acquired</label>
                            <input type="date" class="form-control" name="date_acquired" value="<?= $row['date_acquired'] ?>" disabled>
                        </div>
                    </div>

                    <!-- Vehicle Details -->
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-car"></i> Vehicle Details</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label>Year Model</label>
                            <input type="number" class="form-control" name="year_model" value="<?= $row['year_model'] ?>" disabled>
                        </div>
                        <div class="col-md-3">
                            <label>Make</label>
                            <input type="text" class="form-control" name="make" value="<?= htmlspecialchars($row['make']) ?>" disabled>
                        </div>
                        <div class="col-md-3">
                            <label>Model</label>
                            <input type="text" class="form-control" name="model" value="<?= htmlspecialchars($row['model']) ?>" disabled>
                        </div>
                        <div class="col-md-3">
                            <label>Variant</label>
                            <input type="text" class="form-control" name="variant" value="<?= htmlspecialchars($row['variant']) ?>" disabled>
                        </div>
                        <div class="col-md-4">
                            <label>Color</label>
                            <input type="text" class="form-control" name="color" value="<?= htmlspecialchars($row['color']) ?>" disabled>
                        </div>
                        <div class="col-md-4">
                            <label>Plate Number</label>
                            <input type="text" class="form-control" name="plate_number" value="<?= htmlspecialchars($row['plate_number']) ?>" disabled>
                        </div>
                        <div class="col-md-4">
                            <label>Fuel Type</label>
                            <select class="form-select" name="fuel_type" disabled>
                                <option <?= $row['fuel_type']=='Gasoline'?'selected':'' ?>>Gasoline</option>
                                <option <?= $row['fuel_type']=='Diesel'?'selected':'' ?>>Diesel</option>
                                <option <?= $row['fuel_type']=='Hybrid'?'selected':'' ?>>Hybrid</option>
                                <option <?= $row['fuel_type']=='Electric'?'selected':'' ?>>Electric</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Odometer (km)</label>
                            <input type="number" class="form-control" name="odometer" value="<?= $row['odometer'] ?>" disabled>
                        </div>
                        <div class="col-md-4">
                            <label>Body Type</label>
                            <input type="text" class="form-control" name="body_type" value="<?= htmlspecialchars($row['body_type']) ?>" disabled>
                        </div>
                        <div class="col-md-4">
                            <label>Transmission</label>
                            <select class="form-select" name="transmission" disabled>
                                <option <?= $row['transmission']=='Manual'?'selected':'' ?>>Manual</option>
                                <option <?= $row['transmission']=='Automatic'?'selected':'' ?>>Automatic</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Spare Key</label>
                            <select class="form-select" name="spare_key" disabled>
                                <option <?= $row['spare_key']=='Yes'?'selected':'' ?>>Yes</option>
                                <option <?= $row['spare_key']=='No'?'selected':'' ?>>No</option>
                            </select>
                        </div>
                    </div>

                    <!-- Repairs -->
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-wrench"></i> Projected Repair Cost (Repairs)</h6>
                    <table class="table table-bordered mb-3" id="repairsTable_<?= $row['inventory_id'] ?>">
                        <thead class="table-light">
                            <tr>
                                <th>Repair Name</th>
                                <th>Price (₱)</th>
                                <th class="edit-only" style="display:none;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $repairs = json_decode($row['repairs_list'] ?? '[]', true);
                            if (!empty($repairs) && is_array($repairs)):
                                foreach ($repairs as $repair): ?>
                                <tr>
                                    <td><input type="text" class="form-control repair-name" value="<?= htmlspecialchars($repair['name'] ?? '') ?>" disabled></td>
                                    <td><input type="number" class="form-control repair-price" value="<?= $repair['price'] ?? 0 ?>" disabled></td>
                                    <td class="edit-only" style="display:none;">
                                        <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove();updateCostBreakdown(<?= $row['inventory_id'] ?>);"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>

                    <button type="button" class="btn btn-sm btn-carmax-secondary mb-3 edit-only" style="display:none;" onclick="addRepairRow(<?= $row['inventory_id'] ?>)">
                        <i class="fas fa-plus"></i> Add Repair
                    </button>

                    <!-- Reconditions -->
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-tools"></i> Reconditions</h6>
                    <table class="table table-bordered mb-3" id="reconditionTable_<?= $row['inventory_id'] ?>">
                        <thead class="table-light">
                            <tr>
                                <th>Recondition Name</th>
                                <th>Price (₱)</th>
                                <th class="edit-only" style="display:none;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $reconditions = json_decode($row['reconditions_list'] ?? '[]', true);
                            if (!empty($reconditions) && is_array($reconditions)):
                                foreach ($reconditions as $recon): ?>
                                <tr>
                                    <td><input type="text" class="form-control recondition-name" value="<?= htmlspecialchars($recon['name'] ?? '') ?>" disabled></td>
                                    <td><input type="number" class="form-control recondition-price" value="<?= $recon['price'] ?? 0 ?>" disabled></td>
                                    <td class="edit-only" style="display:none;">
                                        <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove();updateCostBreakdown(<?= $row['inventory_id'] ?>);"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-carmax-secondary mb-3 edit-only" style="display:none;" onclick="addReconditionRow(<?= $row['inventory_id'] ?>)">
                    <i class="fas fa-plus"></i> Add Recondition
                    </button>

                    <!-- Cost Breakdown -->
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-calculator"></i> Cost Breakdown</h6>
                    <table class="table table-bordered mb-4" id="costBreakdownTable_<?= $row['inventory_id'] ?>">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Price (₱)</th>
                            </tr>
                        </thead>
                        <tbody id="costBreakdownBody_<?= $row['inventory_id'] ?>">
                            <?php 
                            $costBreakdown = json_decode($row['costbreakdown_list'] ?? '[]', true);
                            $totalBreakdown = 0;
                            if (!empty($costBreakdown) && is_array($costBreakdown)):
                                foreach ($costBreakdown as $cost): 
                                    $totalBreakdown += floatval($cost['price'] ?? 0);
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($cost['item'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($cost['category'] ?? '') ?></td>
                                    <td>₱<?= number_format($cost['price'] ?? 0, 2) ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="2" class="text-end">Total:</td>
                                <td id="costBreakdownTotal_<?= $row['inventory_id'] ?>">₱<?= number_format($totalBreakdown, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- Receipt Photos -->
                    <?php $receiptPhotos = json_decode($row['receipt_photos'] ?? '[]', true); ?>
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-receipt"></i> Receipt Photos</h6>
                    <?php if (!empty($receiptPhotos) && is_array($receiptPhotos)): ?>
                        <div class="photo-grid mb-3">
                            <?php foreach ($receiptPhotos as $photo): if (!empty($photo)): ?>
                                <div class="photo-box">
                                    <img src="../uploads/receipts/<?= htmlspecialchars($photo) ?>" alt="Receipt" class="receipt-thumb">
                                </div>
                            <?php endif; endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No receipts uploaded</p>
                    <?php endif; ?>
                     <!-- Upload Receipts -->
                    <div class="edit-only" style="display:none;">
                        <label class="form-label">Upload New Receipts</label>
                        <input type="file" class="form-control mb-2" name="receipts[]" accept="image/*" multiple>
                        <small class="text-muted">Select new files to replace existing receipts</small>
                    </div>

                    <!-- Approved/Checked By -->
                    <div class="approved-section">
                        <label><i class="fas fa-user-check text-primary"></i> Checked / Approved By</label>
                        <input type="text" class="form-control" name="approved_checked_by" value="<?= htmlspecialchars($row['approved_checked_by']) ?>" disabled>
                    </div>

                    <!-- Remarks -->
                    <h6 class="text-primary fw-bold mb-3 mt-4"><i class="fas fa-comment"></i> Remarks</h6>
                    <textarea class="form-control mb-4" name="remarks" rows="3" disabled><?= htmlspecialchars($row['remarks']) ?></textarea>
                    
                    

                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <?php if ($canEdit): ?>
                        <button type="button" class="btn btn-warning" onclick="enableEdit(this, <?= $row['inventory_id'] ?>)">Edit</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endwhile; endif; ?>



<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
function enableEdit(btn, inventoryId) {
    const modal = btn.closest('.modal-content');
    const form = modal.querySelector('form');
    const inputs = modal.querySelectorAll('input:not([type="hidden"]), select, textarea');
    const editOnly = modal.querySelectorAll('.edit-only');
    const isEditing = btn.dataset.editing === "true";

    if (!isEditing) {
        // ✅ Enter edit mode
        inputs.forEach(el => el.disabled = false);
        editOnly.forEach(el => el.style.display = '');
        btn.textContent = 'Save Changes';
        btn.classList.replace('btn-warning', 'btn-success');
        btn.dataset.editing = "true";

        // Attach dynamic recalculation
        modal.addEventListener('input', function(e) {
            if (e.target.matches('.repair-price, .recondition-price, .repair-name, .recondition-name')) {
                updateCostBreakdown(inventoryId);
            }
        });
    } else {
        // ✅ Save mode: update and submit
        updateCostBreakdown(inventoryId);
        form.submit();
    }
}

// ✅ Add Repair Row
function addRepairRow(inventoryId) {
    const tbody = document.querySelector(`#repairsTable_${inventoryId} tbody`);
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" class="form-control repair-name" placeholder="Repair name"></td>
        <td><input type="number" class="form-control repair-price" placeholder="₱" min="0" step="0.01"></td>
        <td class="edit-only"><button type="button" class="btn btn-sm btn-danger" 
            onclick="this.closest('tr').remove();updateCostBreakdown(${inventoryId});">
            <i class="fas fa-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
}

// ✅ Add Recondition Row
function addReconditionRow(inventoryId) {
    const tbody = document.querySelector(`#reconditionTable_${inventoryId} tbody`);
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" class="form-control recondition-name" placeholder="Recondition name"></td>
        <td><input type="number" class="form-control recondition-price" placeholder="₱" min="0" step="0.01"></td>
        <td class="edit-only"><button type="button" class="btn btn-sm btn-danger" 
            onclick="this.closest('tr').remove();updateCostBreakdown(${inventoryId});">
            <i class="fas fa-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
}

// ✅ Update cost breakdown dynamically
function updateCostBreakdown(inventoryId) {
    const modal = document.querySelector(`[data-id="${inventoryId}"]`);
    if (!modal) return;

    const repairs = [...modal.querySelectorAll('.repair-name')].map((el, i) => ({
        name: el.value || '',
        price: parseFloat(modal.querySelectorAll('.repair-price')[i].value) || 0
    }));

    const recons = [...modal.querySelectorAll('.recondition-name')].map((el, i) => ({
        name: el.value || '',
        price: parseFloat(modal.querySelectorAll('.recondition-price')[i].value) || 0
    }));

    const allCosts = [
        ...repairs.map(r => ({ item: r.name, category: 'Repair', price: r.price })),
        ...recons.map(r => ({ item: r.name, category: 'Recondition', price: r.price }))
    ];

    // Update cost breakdown table
    const tbody = modal.querySelector(`#costBreakdownBody_${inventoryId}`);
    tbody.innerHTML = allCosts.map(r => `
        <tr><td>${r.item}</td><td>${r.category}</td><td>₱${r.price.toFixed(2)}</td></tr>
    `).join('');

    const total = allCosts.reduce((sum, r) => sum + r.price, 0);
    modal.querySelector(`#costBreakdownTotal_${inventoryId}`).textContent = '₱' + total.toFixed(2);

    // Sync hidden fields
    modal.querySelector(`#repairs_list_${inventoryId}`).value = JSON.stringify(repairs);
    modal.querySelector(`#reconditions_list_${inventoryId}`).value = JSON.stringify(recons);
    modal.querySelector(`#costbreakdown_list_${inventoryId}`).value = JSON.stringify(allCosts);

    // Update total fields
    const proj = modal.querySelector('input[name="projected_repair_cost"]');
    const breakdown = modal.querySelector('input[name="cost_breakdown"]');
    if (proj) proj.value = total.toFixed(2);
    if (breakdown) breakdown.value = total.toFixed(2);
}
</script>

</body>
</html>