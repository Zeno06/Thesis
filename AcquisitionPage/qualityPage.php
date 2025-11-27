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
    <style>
    .remove-image-btn {
        position: absolute;
        top: 5px;
        right: 1px;
        background: rgba(220, 53, 69, 0.9);
        color: white;
        border: none;
        border-radius: 50%;
        width: 25px;
        height: 25px;
        font-size: 14px;
        cursor: pointer;
        display: none;
        z-index: 10;
    }

    .image-preview-container {
        position: relative;
        display: inline-block;
    }

    .image-preview-container:hover .remove-image-btn {
        display: block;
    }

    .doc-upload-wrapper .image-preview-container {
        width: 100%;
    }

    .doc-upload-wrapper .image-preview {
        width: 100%;
        max-width: 300px;
        height: 200px;
        object-fit: contain;
    }
    
    /* Remove horizontal scrollbar */
    .modal-dialog {
        max-width: 95vw;
    }
    
    .modal-body {
        overflow-x: hidden;
    }
    .approve-btn:disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

.approve-btn {
    position: relative;
}

.approve-btn:hover::after {
    content: attr(title);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #333;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    z-index: 1000;
}
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
                </button>
                <?php if (!empty($searchQuery) || !empty($modelFilter)): ?>
                    <a href="qualityPage.php" class="btn btn-carmax-primary"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="sap-card-body">
            <table class="sap-table table table-hover">
                <thead class="table-primary">
                    <tr>
                        <th>Plate Number</th>
                        <th>Make</th>
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
                                <td><?= htmlspecialchars($row['make']) ?></td>
                                <td><?= htmlspecialchars($row['vehicle_model']) ?></td>
                                <td><?= htmlspecialchars($row['year_model']) ?></td>
                                <td><?= htmlspecialchars($row['color']) ?></td>
                                <td>₱<?= number_format($row['acquired_price'], 2) ?></td>
                                <td><span class="badge bg-warning">Quality Check</span></td>
                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center">No vehicles in quality check.</td></tr>
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
                        <div class="col-md-3">
                            <label>Supplier</label>
                            <input type="text" class="form-control" name="supplier" value="<?= htmlspecialchars($row['supplier']) ?>" 
                                   pattern="[A-Za-z\s]+" title="Only alphabets and spaces are allowed"
                                   oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')">
                        </div>
                        <div class="col-md-3">
                            <label>Date Acquired</label>
                            <input type="date" class="form-control" name="date_acquired" value="<?= $row['date_acquired'] ?>">
                        </div>
                        <div class="col-md-3">
                            <label>Make</label>
                            <input type="text" class="form-control" name="make" value="<?= htmlspecialchars($row['make']) ?>" 
                                   pattern="[A-Za-z\s]+" title="Only alphabets and spaces are allowed"
                                   oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')">
                        </div>
                        <div class="col-md-3">
                            <label>Plate Number</label>
                            <input type="text" class="form-control" name="plate_number" value="<?= htmlspecialchars($row['plate_number']) ?>" 
                                   pattern="[A-Z]{3}[0-9]{3,4}" title="3 capital letters followed by 3-4 numbers (e.g., ABC123)" 
                                   maxlength="7"
                                   oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, ''); 
                                            if(this.value.length > 7) this.value = this.value.slice(0,7);">
                        </div>
                        <div class="col-md-3">
                            <label>Vehicle Model</label>
                            <input type="text" class="form-control" name="vehicle_model" value="<?= htmlspecialchars($row['vehicle_model']) ?>" 
                                   pattern="[A-Za-z\s]+" title="Only alphabets and spaces are allowed"
                                   oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')">
                        </div>
                        <div class="col-md-3">
                            <label>Year Model</label>
                            <input type="number" class="form-control" name="year_model" value="<?= htmlspecialchars($row['year_model']) ?>" 
                                   min="1900" max="2030" maxlength="4"
                                   oninput="if(this.value.length > 4) this.value = this.value.slice(0,4);">
                        </div>
                        <div class="col-md-3">
                            <label>Variant</label>
                            <input type="text" class="form-control" name="variant" value="<?= htmlspecialchars($row['variant']) ?>"
                                   oninput="this.value = this.value.replace(/[^A-Za-z0-9.\s]/g, '')">
                        </div>
                        <div class="col-md-3">
                            <label>Color</label>
                            <input type="text" class="form-control" name="color" value="<?= htmlspecialchars($row['color']) ?>" 
                                   pattern="[A-Za-z\s]+" title="Only alphabets and spaces are allowed"
                                   oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')">
                        </div>
                        <div class="col-md-3">
                            <label>Fuel Type</label>
                            <select class="form-select" name="fuel_type">
                                <option value="Gasoline" <?= $row['fuel_type'] === 'Gasoline' ? 'selected' : '' ?>>Gasoline</option>
                                <option value="Diesel" <?= $row['fuel_type'] === 'Diesel' ? 'selected' : '' ?>>Diesel</option>
                                <option value="Hybrid" <?= $row['fuel_type'] === 'Hybrid' ? 'selected' : '' ?>>Hybrid</option>
                                <option value="Electric" <?= $row['fuel_type'] === 'Electric' ? 'selected' : '' ?>>Electric</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Odometer</label>
                            <input type="number" class="form-control" name="odometer" value="<?= htmlspecialchars($row['odometer']) ?>" 
                                   min="0" max="999999" maxlength="6"
                                   oninput="if(this.value.length > 6) this.value = this.value.slice(0,6);">
                        </div>
                        <div class="col-md-3">
                            <label>Body Type</label>
                            <select class="form-select" name="body_type">
                                <option value="Sedan" <?= $row['body_type'] === 'Sedan' ? 'selected' : '' ?>>Sedan</option>
                                <option value="SUV" <?= $row['body_type'] === 'SUV' ? 'selected' : '' ?>>SUV</option>
                                <option value="Van" <?= $row['body_type'] === 'Van' ? 'selected' : '' ?>>Van</option>
                                <option value="Pickup" <?= $row['body_type'] === 'Pickup' ? 'selected' : '' ?>>Pickup</option>
                                <option value="Hatchback" <?= $row['body_type'] === 'Hatchback' ? 'selected' : '' ?>>Hatchback</option>
                                <option value="FB body" <?= $row['body_type'] === 'FB body' ? 'selected' : '' ?>>FB body</option>
                                <option value="Coupe" <?= $row['body_type'] === 'Coupe' ? 'selected' : '' ?>>Coupe</option>
                                <option value="MPV" <?= $row['body_type'] === 'MPV' ? 'selected' : '' ?>>MPV</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Transmission</label>
                            <select class="form-select" name="transmission">
                                <option value="Manual" <?= $row['transmission'] === 'Manual' ? 'selected' : '' ?>>Manual</option>
                                <option value="Automatic" <?= $row['transmission'] === 'Automatic' ? 'selected' : '' ?>>Automatic</option>
                            </select>
                        </div>
                    </div>

                    <!-- Vehicle Photos -->
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-images"></i> Vehicle Photos</h6>
                    <div class="photo-grid mb-4">
                        <?php 
                        $photos = ['exterior' => 'Exterior','dashboard' => 'Dashboard','hood' => 'Hood','interior' => 'Interior','trunk' => 'Trunk'];
                        foreach ($photos as $key => $label):
                            $photoField = $key . '_photo';
                            $photoPath = htmlspecialchars($row[$photoField] ?? '');
                        ?>
                        <div class="photo-box">
                            <label><?= $label ?></label>
                            <input type="file" class="form-control" name="<?= $key ?>_photo_update" accept="image/*" onchange="previewImage(this)">
                            <div class="image-preview-container">
                                <?php if ($photoPath): ?>
                                    <img src="../uploads/<?= $photoPath ?>" alt="<?= $label ?>" class="clickable-image image-preview" style="width: 100%; height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <img class="image-preview d-none" alt="Preview" style="width: 100%; height: 200px; object-fit: cover;">
                                <?php endif; ?>
                                <button type="button" class="remove-image-btn" onclick="removeImage(this)">×</button>
                            </div>
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
                                    <th>Receipt Photo</th>
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
                                    <td>
                                        <input type="text" class="form-control" name="issue_names[<?= $issue['issue_id'] ?>]" value="<?= htmlspecialchars($issue['issue_name']) ?>" 
                                            pattern="[A-Za-z\s]+" title="Only alphabets and spaces are allowed"
                                            oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, ''); checkApproveButton(<?= $row['acquisition_id'] ?>)">
                                    </td>
                                  <td>
                                    <div class="issue-photo-container">
                                        <?php if (!empty($issue['issue_photo'])): ?>
                                            <div class="image-preview-container mb-2">
                                                <img src="../uploads/<?= htmlspecialchars($issue['issue_photo']) ?>" 
                                                    style="max-width: 100px; border-radius: 5px;" 
                                                    class="clickable-image">
                                                <button type="button" class="remove-image-btn" 
                                                        onclick="removeIssuePhoto(this, <?= $issue['issue_id'] ?>)">×</button>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <input type="file" class="form-control form-control-sm" 
                                            name="issue_photo_update[<?= $issue['issue_id'] ?>]"
                                            accept="image/*" 
                                            onchange="previewNewIssuePhoto(this, <?= $issue['issue_id'] ?>)">
                                        
                                        <div class="image-preview-container mt-1">
                                            <img class="image-preview d-none" alt="Preview" 
                                                style="max-width: 100px; border-radius: 6px;">
                                            <button type="button" class="remove-image-btn" 
                                                    onclick="removeNewIssuePhoto(this)">×</button>
                                        </div>
                                    </div>
                                </td>
                                    <td> 
                                        <input type="number" step="0.01" class="form-control issue-price" name="issue_price[<?= $issue['issue_id'] ?>]" 
                                            value="<?= htmlspecialchars($issue['issue_price'] ?? '') ?>" placeholder="0.00"
                                            min="0" max="9999999" maxlength="7"
                                            oninput="if(this.value.length > 7) this.value = this.value.slice(0,7); checkApproveButton(<?= $row['acquisition_id'] ?>)">
                                    </td>
                                    <td>
                                        <textarea class="form-control issue-remarks" name="issue_remarks[<?= $issue['issue_id'] ?>]" rows="2" placeholder="Enter remarks"
                                                oninput="checkApproveButton(<?= $row['acquisition_id'] ?>)"><?= htmlspecialchars($issue['issue_remarks'] ?? '') ?></textarea>
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
                                            oninput="checkApproveButton(<?= $row['acquisition_id'] ?>)"
                                            <?= $issue['is_repaired'] ? '' : 'disabled' ?>>
                                    </td>
                                    <td>
                                        <?php 
                                        $receiptPhotos = json_decode($issue['receipt_photos'] ?? '[]', true);
                                        if (!empty($receiptPhotos) && is_array($receiptPhotos)):
                                            foreach ($receiptPhotos as $index => $photo):
                                        ?>
                                            <div class="image-preview-container d-inline-block position-relative">
                                                <img src="../uploads/<?= htmlspecialchars($photo) ?>" style="max-width: 80px; border-radius: 5px; margin: 2px;" class="clickable-image">
                                                <button type="button" class="remove-image-btn" onclick="removeReceiptImage(this, '<?= $issue['issue_id'] ?>', 'issue', <?= $index ?>)">×</button>
                                            </div>
                                        <?php 
                                            endforeach;
                                        endif;
                                        ?>
                                        <input type="file" class="form-control form-control-sm mt-1 receipt-upload"
                                            name="issue_receipt_update[<?= $issue['issue_id'] ?>][]"
                                            accept="image/*" multiple
                                            onchange="previewReceiptImages(this, 'issueReceiptPreview<?= $issue['issue_id'] ?>'); checkApproveButton(<?= $row['acquisition_id'] ?>)">
                                        <div id="issueReceiptPreview<?= $issue['issue_id'] ?>" class="mt-2"></div>
                                        <input type="hidden" name="remove_issue_receipts[<?= $issue['issue_id'] ?>][]" id="removeIssueReceipts<?= $issue['issue_id'] ?>" value="">
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
                                    <th>Receipt Photo</th>
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
                                    <td>
                                        <input type="text" class="form-control" name="part_names[<?= $part['part_id'] ?>]" value="<?= htmlspecialchars($part['part_name']) ?>" 
                                               pattern="[A-Za-z\s]+" title="Only alphabets and spaces are allowed"
                                               oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, ''); checkApproveButton(<?= $row['acquisition_id'] ?>)">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" class="form-control part-price" name="part_price[<?= $part['part_id'] ?>]" 
                                            value="<?= htmlspecialchars($part['part_price'] ?? '') ?>" placeholder="0.00"
                                            min="0" max="9999999" maxlength="7"
                                            oninput="if(this.value.length > 7) this.value = this.value.slice(0,7); checkApproveButton(<?= $row['acquisition_id'] ?>)">
                                    </td>
                                    <td>
                                        <textarea class="form-control part-remarks" name="part_remarks[<?= $part['part_id'] ?>]" rows="2" placeholder="Enter remarks"
                                                  oninput="checkApproveButton(<?= $row['acquisition_id'] ?>)"><?= htmlspecialchars($part['part_remarks'] ?? '') ?></textarea>
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
                                            oninput="checkApproveButton(<?= $row['acquisition_id'] ?>)"
                                            <?= $part['is_ordered'] ? '' : 'disabled' ?>>
                                    </td>
                                    <td>
                                        <?php 
                                        $receiptPhotos = json_decode($part['receipt_photos'] ?? '[]', true);
                                        if (!empty($receiptPhotos) && is_array($receiptPhotos)):
                                            foreach ($receiptPhotos as $index => $photo):
                                        ?>
                                            <div class="image-preview-container d-inline-block position-relative">
                                                <img src="../uploads/<?= htmlspecialchars($photo) ?>" class="clickable-image" style="max-width: 80px; border-radius: 5px; margin: 2px;">
                                                <button type="button" class="remove-image-btn" onclick="removeReceiptImage(this, '<?= $part['part_id'] ?>', 'part', <?= $index ?>)">×</button>
                                            </div>
                                        <?php 
                                            endforeach;
                                        endif;
                                        ?>
                                        <input type="file" class="form-control form-control-sm mt-1"
                                            name="part_receipt_update[<?= $part['part_id'] ?>][]"
                                            accept="image/*" multiple
                                            onchange="previewReceiptImages(this, 'partReceiptPreview<?= $part['part_id'] ?>')">
                                        <div id="partReceiptPreview<?= $part['part_id'] ?>" class="mt-2"></div>
                                        <input type="hidden" name="remove_part_receipts[<?= $part['part_id'] ?>][]" id="removePartReceipts<?= $part['part_id'] ?>" value="">
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
                        <div class="col-md-3">
                            <label>Spare Tires</label>
                            <select class="form-select" name="spare_tires">
                                <option value="Yes" <?= $row['spare_tires'] === 'Yes' ? 'selected' : '' ?>>Yes</option>
                                <option value="No" <?= $row['spare_tires'] === 'No' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Complete Tools</label>
                            <select class="form-select" name="complete_tools">
                                <option value="Yes" <?= $row['complete_tools'] === 'Yes' ? 'selected' : '' ?>>Yes</option>
                                <option value="No" <?= $row['complete_tools'] === 'No' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Original Plate</label>
                            <select class="form-select" name="original_plate">
                                <option value="Yes" <?= $row['original_plate'] === 'Yes' ? 'selected' : '' ?>>Yes</option>
                                <option value="No" <?= $row['original_plate'] === 'No' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Complete Documents</label>
                            <select class="form-select" name="complete_documents">
                                <option value="Yes" <?= $row['complete_documents'] === 'Yes' ? 'selected' : '' ?>>Yes</option>
                                <option value="No" <?= $row['complete_documents'] === 'No' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Spare Key</label>
                            <select class="form-select" name="spare_key">
                                <option value="Yes" <?= $row['spare_key'] === 'Yes' ? 'selected' : '' ?>>Yes</option>
                                <option value="No" <?= $row['spare_key'] === 'No' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                    </div>

                    <!-- Document Photos -->
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-file-contract"></i> Document Photos</h6>
                    <div class="photo-grid mb-4">
                        <?php 
                        $docPhotos = [
                            'orcr' => ['label' => 'OR/CR', 'field' => 'orcr_photo'],
                            'deed_of_sale' => ['label' => 'Deed of Sale', 'field' => 'deed_of_sale_photo'],
                            'insurance' => ['label' => 'Insurance', 'field' => 'insurance_photo']
                        ];
                        foreach ($docPhotos as $key => $doc):
                            $photoField = $doc['field'];
                            $photoPath = htmlspecialchars($row[$photoField] ?? '');
                            $hasExistingPhoto = !empty($photoPath);
                        ?>
                        <div class="photo-box">
                            <label><?= $doc['label'] ?></label>
                            <div class="image-preview-container">
                                <?php if ($hasExistingPhoto): ?>
                                    <img src="../uploads/<?= $photoPath ?>" alt="<?= $doc['label'] ?>" class="clickable-image image-preview" style="width: 100%; height: 200px; object-fit: cover;">
                                    <button type="button" class="remove-image-btn" onclick="removeDocumentPhoto(this, '<?= $key ?>', <?= $row['acquisition_id'] ?>)">×</button>
                                <?php else: ?>
                                    <img class="image-preview d-none" alt="<?= $doc['label'] ?>" style="width: 100%; height: 200px; object-fit: cover;">
                                    <button type="button" class="remove-image-btn" onclick="removeImage(this)" style="display: none;">×</button>
                                <?php endif; ?>
                            </div>
                            <input type="file" class="form-control form-control-sm mt-2" 
                                name="<?= $key ?>_photo_update" 
                                accept="image/*" 
                                onchange="previewImage(this); checkApproveButton(<?= $row['acquisition_id'] ?>)">
                            
                            <input type="hidden" name="remove_doc_photos[]" id="removeDocPhoto<?= $key ?><?= $row['acquisition_id'] ?>" value="">
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Remarks -->
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-comment"></i> Remarks</h6>
                    <textarea class="form-control mb-4" name="remarks" rows="3" 
                            placeholder="Enter any important notes, observations, or special instructions..."
                            oninput="checkApproveButton(<?= $row['acquisition_id'] ?>)"><?= htmlspecialchars($row['remarks'] ?? '') ?>
                    </textarea>

                    <!-- Price -->
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-peso-sign"></i> Acquired Price</h6>
                    <div class="input-group mb-3">
                        <span class="input-group-text">₱</span>
                        <input type="number" step="0.01" class="form-control" name="acquired_price" value="<?= $row['acquired_price'] ?>" 
                               min="0" max="9999999" maxlength="7"
                               oninput="if(this.value.length > 7) this.value = this.value.slice(0,7);">
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
            <div class="modal-body">Are you sure you want to approve this vehicle?</div>
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

function removeImage(btn) {
    const container = btn.parentElement;
    const preview = container.querySelector('.image-preview');
    const fileInput = container.parentElement.querySelector('input[type="file"]');
    
    // Clear the preview
    if (preview) {
        preview.src = '';
        preview.classList.add('d-none');
    }
    
    // Hide remove button
    btn.style.display = 'none';
    
    // Clear file input
    if (fileInput) {
        fileInput.value = '';
    }
}

function previewImage(input) {
    const container = input.parentElement.querySelector('.image-preview-container');
    const preview = container.querySelector('.image-preview');
    const removeBtn = container.querySelector('.remove-image-btn');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
            removeBtn.style.display = 'block';
            
            preview.onclick = function() {
                openImageModal(this.src);
            };
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        // If no file selected but there's an existing image, keep it visible
        if (preview.src && preview.src !== '') {
            preview.classList.remove('d-none');
            removeBtn.style.display = 'block';
        }
    }
}

function initializeModalEvents() {
    const qualityModals = document.querySelectorAll('[id^="qualityModal"]');
    qualityModals.forEach(modal => {
        modal.addEventListener('show.bs.modal', function(event) {
            const modalId = this.id;
            const acquisitionId = modalId.replace('qualityModal', '');
            currentAcquisitionId = acquisitionId;
            
            // Wait a bit for the form to render completely
            setTimeout(() => {
                attachValidationEvents(acquisitionId);
                checkApproveButton(acquisitionId);
            }, 100);
        });
    });
}

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

    // Add onclick to all clickable images
    const clickableImages = document.querySelectorAll('.clickable-image');
    clickableImages.forEach(img => {
        img.onclick = function() {
            openImageModal(this.src);
        };
    });
    
    // Initialize modal events for approve button state
    initializeModalEvents();
    
    // Also check approve button state on page load for any open modals
    setTimeout(() => {
        const openModal = document.querySelector('.modal.show');
        if (openModal && openModal.id.startsWith('qualityModal')) {
            const acquisitionId = openModal.id.replace('qualityModal', '');
            checkApproveButton(acquisitionId);
        }
    }, 500);
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
        <td>
            <input type="text" class="form-control new-issue-name" name="new_issue_name[]" placeholder="Issue name" required 
                   pattern="[A-Za-z\s]+" title="Only alphabets and spaces are allowed"
                   oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, ''); checkApproveButton(${acquisitionId})">
        </td>
        <td>
            <input type="file" class="form-control" name="new_issue_photos[]" accept="image/*" 
                onchange="previewImage(this); checkApproveButton(${acquisitionId})">
            <div class="image-preview-container">
                <img class="image-preview d-none mt-2" alt="Preview" style="max-width: 100px; border-radius: 6px;">
                <button type="button" class="remove-image-btn" onclick="removeImage(this)">×</button>
            </div>
        </td>
        <td>
            <input type="number" step="0.01" class="form-control new-issue-price" name="new_issue_price[]" placeholder="0.00" 
                   min="0" max="9999999" maxlength="7"
                   oninput="if(this.value.length > 7) this.value = this.value.slice(0,7); checkApproveButton(${acquisitionId})">
        </td>
        <td>
            <textarea class="form-control new-issue-remarks" name="new_issue_remarks[]" rows="2" placeholder="Enter remarks" 
                      pattern="[A-Za-z\s]+" title="Only alphabets and spaces are allowed"
                      oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, ''); checkApproveButton(${acquisitionId})"></textarea>
        </td>
        <td>
            <input type="checkbox" class="form-check-input issue-checkbox" 
                name="new_issue_repaired[]" value="1"
                onchange="toggleRepairedBy(this, '${newId}'); checkApproveButton(${acquisitionId})">
        </td>
        <td>
            <input type="text" class="form-control repaired-by-input" 
                id="repairedBy${newId}"
                name="new_issue_repaired_by[]" 
                placeholder="Enter name" 
                pattern="[A-Za-z\s]+" title="Only alphabets and spaces are allowed"
                oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, ''); checkApproveButton(${acquisitionId})" 
                disabled>
        </td>
        <td>
            <input type="file" class="form-control form-control-sm"
                name="new_issue_receipts[]"
                accept="image/*" multiple
                onchange="previewReceiptImages(this, 'newIssueReceiptPreview_${newId}'); checkApproveButton(${acquisitionId})">
            <div id="newIssueReceiptPreview_${newId}" class="mt-2"></div>
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

function previewReceiptImages(input, previewContainerId) {
    const container = document.getElementById(previewContainerId);
    container.innerHTML = ""; 

    if (!input.files || input.files.length === 0) return;

    Array.from(input.files).forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const imgContainer = document.createElement("div");
            imgContainer.className = "image-preview-container d-inline-block position-relative";
            
            const img = document.createElement("img");
            img.src = e.target.result;
            img.style.maxWidth = "100px";
            img.style.borderRadius = "5px";
            img.style.margin = "2px";
            img.className = "clickable-image";
            img.onclick = function() { openImageModal(this.src); };
            
            const removeBtn = document.createElement("button");
            removeBtn.type = "button";
            removeBtn.className = "remove-image-btn";
            removeBtn.innerHTML = "×";
            removeBtn.onclick = function() {
                // Remove this specific preview
                imgContainer.remove();
                // Remove the file from the input
                const dt = new DataTransfer();
                const files = Array.from(input.files);
                files.splice(index, 1);
                files.forEach(file => dt.items.add(file));
                input.files = dt.files;
            };
            
            imgContainer.appendChild(img);
            imgContainer.appendChild(removeBtn);
            container.appendChild(imgContainer);
        };
        reader.readAsDataURL(file);
    });
}

function addPartRow(acquisitionId) {
    const tbody = document.getElementById('partsTableBody' + acquisitionId);
    const newId = 'new_part_' + (++newPartCounter);
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <input type="text" class="form-control new-part-name" name="new_part_name[]" placeholder="Part name" required 
                   pattern="[A-Za-z\s]+" title="Only alphabets and spaces are allowed"
                   oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, ''); checkApproveButton(${acquisitionId})">
        </td>
        <td>
            <input type="number" step="0.01" class="form-control new-part-price" name="new_part_price[]" placeholder="0.00" 
                   min="0" max="9999999" maxlength="7"
                   oninput="if(this.value.length > 7) this.value = this.value.slice(0,7); checkApproveButton(${acquisitionId})">
        </td>
        <td>
            <textarea class="form-control new-part-remarks" name="new_part_remarks[]" rows="2" placeholder="Enter remarks"
                      pattern="[A-Za-z\s]+" title="Only alphabets and spaces are allowed"
                      oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, ''); checkApproveButton(${acquisitionId})"></textarea>
        </td>
        <td>
            <input type="checkbox" class="form-check-input part-checkbox" 
                name="new_part_ordered[]" value="1"
                onchange="toggleOrderedBy(this, '${newId}'); checkApproveButton(${acquisitionId})">
        </td>
        <td>
            <input type="text" class="form-control ordered-by-input" 
                id="orderedBy${newId}"
                name="new_part_ordered_by[]" 
                placeholder="Enter name" 
                pattern="[A-Za-z\s]+" title="Only alphabets and spaces are allowed"
                oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, ''); checkApproveButton(${acquisitionId})" 
                disabled>
        </td>
        <td>
            <input type="file" class="form-control form-control-sm"
                name="new_part_receipts[]"
                accept="image/*" multiple
                onchange="previewReceiptImages(this, 'newPartReceiptPreview_${newId}'); checkApproveButton(${acquisitionId})">
            <div id="newPartReceiptPreview_${newId}" class="mt-2"></div>
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
    const missingFields = [];

    // Check Basic Information fields
    const requiredBasicFields = [
        'supplier', 'date_acquired', 'make', 'plate_number', 
        'vehicle_model', 'year_model', 'color', 'acquired_price'
    ];
    
    requiredBasicFields.forEach(field => {
        const input = form.querySelector(`[name="${field}"]`);
        if (input && (!input.value || input.value.trim() === '')) {
            canApprove = false;
            missingFields.push(field.replace('_', ' '));
        }
    });

    // Check Vehicle Condition fields
    const conditionFields = [
        'spare_tires', 'complete_tools', 'original_plate', 
        'complete_documents', 'spare_key'
    ];
    
    conditionFields.forEach(field => {
        const select = form.querySelector(`select[name="${field}"]`);
        if (select && (!select.value || select.value.trim() === '')) {
            canApprove = false;
            missingFields.push(field.replace('_', ' '));
        }
    });

    // Check Acquired Price
    const acquiredPrice = form.querySelector('[name="acquired_price"]');
    if (!acquiredPrice || !acquiredPrice.value || parseFloat(acquiredPrice.value) <= 0) {
        canApprove = false;
        missingFields.push('acquired price');
    }

    // Check Remarks
    const remarks = form.querySelector('[name="remarks"]');
    if (!remarks || !remarks.value || remarks.value.trim() === '') {
        canApprove = false;
        missingFields.push('remarks');
    }

    // Check Vehicle Photos - FIXED
    const vehiclePhotoTypes = ['exterior', 'dashboard', 'hood', 'interior', 'trunk'];
    let hasAllVehiclePhotos = true;
    
    vehiclePhotoTypes.forEach(photoType => {
        // Find the photo box for this vehicle photo
        const photoBoxes = form.querySelectorAll('.photo-box');
        let photoFound = false;
        
        photoBoxes.forEach(box => {
            const label = box.querySelector('label');
            if (label && label.textContent.toLowerCase().includes(photoType.toLowerCase())) {
                // Check for existing uploaded photo
                const existingImg = box.querySelector('img[src*="../uploads/"]');
                // Check for new file upload
                const fileInput = box.querySelector('input[type="file"]');
                const hasNewPhoto = fileInput && fileInput.files && fileInput.files.length > 0;
                // Check for preview image (visible)
                const previewImg = box.querySelector('img.image-preview:not(.d-none)');
                
                if (existingImg || hasNewPhoto || (previewImg && previewImg.src && previewImg.src !== '')) {
                    photoFound = true;
                }
            }
        });
        
        if (!photoFound) {
            hasAllVehiclePhotos = false;
            missingFields.push(photoType + ' photo');
        }
    });

    if (!hasAllVehiclePhotos) {
        canApprove = false;
    }

    // Check Document Photos - FIXED (required in quality page)
    const docPhotoTypes = ['orcr', 'deed_of_sale', 'insurance'];
    let hasAllDocumentPhotos = true;
    
    docPhotoTypes.forEach(docType => {
        // Find the document photo box
        const photoBoxes = form.querySelectorAll('.photo-box');
        let docFound = false;
        
        photoBoxes.forEach(box => {
            const label = box.querySelector('label');
            let labelText = label ? label.textContent.toLowerCase() : '';
            
            // Match document types with their labels
            if ((docType === 'orcr' && labelText.includes('or/cr')) ||
                (docType === 'deed_of_sale' && labelText.includes('deed of sale')) ||
                (docType === 'insurance' && labelText.includes('insurance'))) {
                
                // Check for existing uploaded document
                const existingImg = box.querySelector('img[src*="../uploads/"]');
                // Check for new file upload
                const fileInput = box.querySelector('input[type="file"]');
                const hasNewDoc = fileInput && fileInput.files && fileInput.files.length > 0;
                // Check for preview image (visible)
                const previewImg = box.querySelector('img:not(.d-none)');
                
                if (existingImg || hasNewDoc || (previewImg && previewImg.src && previewImg.src !== '')) {
                    docFound = true;
                }
            }
        });
        
        if (!docFound) {
            hasAllDocumentPhotos = false;
            missingFields.push(docType.replace('_', ' ') + ' document');
        }
    });

    if (!hasAllDocumentPhotos) {
        canApprove = false;
    }

    // Check EXISTING Issues
    const existingIssueRows = form.querySelectorAll('tr[data-issue-id]');
    existingIssueRows.forEach(row => {
        if (row.style.display === 'none') return;

        const issueName = row.querySelector('input[name^="issue_names"]');
        const priceInput = row.querySelector('.issue-price');
        const remarksInput = row.querySelector('.issue-remarks');
        const checkbox = row.querySelector('.issue-checkbox');
        const repairedByInput = row.querySelector('.repaired-by-input');

        // Only validate if issue name has content
        if (issueName && issueName.value.trim()) {
            if (!priceInput || !priceInput.value.trim() || parseFloat(priceInput.value) <= 0) {
                canApprove = false;
                missingFields.push('issue price for: ' + issueName.value);
            }
            if (!remarksInput || !remarksInput.value.trim()) {
                canApprove = false;
                missingFields.push('issue remarks for: ' + issueName.value);
            }
            if (!checkbox || !checkbox.checked) {
                canApprove = false;
                missingFields.push('issue repaired status for: ' + issueName.value);
            }
            if (checkbox && checkbox.checked && (!repairedByInput || !repairedByInput.value.trim())) {
                canApprove = false;
                missingFields.push('repaired by for: ' + issueName.value);
            }
            
            // Check if issue has photo
            const existingPhoto = row.querySelector('.issue-photo-container img[src*="../uploads/"]');
            const fileInput = row.querySelector('input[type="file"][name^="issue_photo_update"]');
            const previewImg = row.querySelector('.issue-photo-container img.image-preview:not(.d-none)');
            const hasIssuePhoto = existingPhoto || (fileInput && fileInput.files && fileInput.files.length > 0) || (previewImg && previewImg.src && previewImg.src !== '');
            
            if (!hasIssuePhoto) {
                canApprove = false;
                missingFields.push('issue photo for: ' + issueName.value);
            }
        }
    });

    // Check NEW Issues
    const newIssueNames = form.querySelectorAll('.new-issue-name');
    newIssueNames.forEach((nameInput, index) => {
        const row = nameInput.closest('tr');
        if (!row || row.style.display === 'none') return;

        const priceInput = row.querySelector('.new-issue-price');
        const remarksInput = row.querySelector('.new-issue-remarks');
        const checkbox = row.querySelector('.issue-checkbox');
        const repairedByInput = row.querySelector('.repaired-by-input');
        const fileInput = row.querySelector('input[type="file"][name^="new_issue_photos"]');

        if (nameInput.value.trim()) {
            if (!priceInput || !priceInput.value.trim() || parseFloat(priceInput.value) <= 0) {
                canApprove = false;
                missingFields.push('new issue price');
            }
            if (!remarksInput || !remarksInput.value.trim()) {
                canApprove = false;
                missingFields.push('new issue remarks');
            }
            if (!checkbox || !checkbox.checked) {
                canApprove = false;
                missingFields.push('new issue repaired status');
            }
            if (checkbox && checkbox.checked && (!repairedByInput || !repairedByInput.value.trim())) {
                canApprove = false;
                missingFields.push('new issue repaired by');
            }
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                canApprove = false;
                missingFields.push('new issue photo');
            }
        }
    });

    // Check EXISTING Parts
    const existingPartRows = form.querySelectorAll('tr[data-part-id]');
    existingPartRows.forEach(row => {
        if (row.style.display === 'none') return;

        const partName = row.querySelector('input[name^="part_names"]');
        const priceInput = row.querySelector('.part-price');
        const remarksInput = row.querySelector('.part-remarks');
        const checkbox = row.querySelector('.part-checkbox');
        const orderedByInput = row.querySelector('.ordered-by-input');

        if (partName && partName.value.trim()) {
            if (!priceInput || !priceInput.value.trim() || parseFloat(priceInput.value) <= 0) {
                canApprove = false;
                missingFields.push('part price for: ' + partName.value);
            }
            if (!remarksInput || !remarksInput.value.trim()) {
                canApprove = false;
                missingFields.push('part remarks for: ' + partName.value);
            }
            if (!checkbox || !checkbox.checked) {
                canApprove = false;
                missingFields.push('part ordered status for: ' + partName.value);
            }
            if (checkbox && checkbox.checked && (!orderedByInput || !orderedByInput.value.trim())) {
                canApprove = false;
                missingFields.push('ordered by for: ' + partName.value);
            }
        }
    });

    // Check NEW Parts
    const newPartNames = form.querySelectorAll('.new-part-name');
    newPartNames.forEach((nameInput, index) => {
        const row = nameInput.closest('tr');
        if (!row || row.style.display === 'none') return;

        const priceInput = row.querySelector('.new-part-price');
        const remarksInput = row.querySelector('.new-part-remarks');
        const checkbox = row.querySelector('.part-checkbox');
        const orderedByInput = row.querySelector('.ordered-by-input');

        if (nameInput.value.trim()) {
            if (!priceInput || !priceInput.value.trim() || parseFloat(priceInput.value) <= 0) {
                canApprove = false;
                missingFields.push('new part price');
            }
            if (!remarksInput || !remarksInput.value.trim()) {
                canApprove = false;
                missingFields.push('new part remarks');
            }
            if (!checkbox || !checkbox.checked) {
                canApprove = false;
                missingFields.push('new part ordered status');
            }
            if (checkbox && checkbox.checked && (!orderedByInput || !orderedByInput.value.trim())) {
                canApprove = false;
                missingFields.push('new part ordered by');
            }
        }
    });

    // Update approve button state and tooltip
    approveBtn.disabled = !canApprove;
    
    if (!canApprove) {
        const uniqueMissingFields = [...new Set(missingFields)];
        approveBtn.title = 'Missing required fields: ' + uniqueMissingFields.join(', ');
        console.log('Cannot approve. Missing:', uniqueMissingFields);
    } else {
        approveBtn.title = 'All required fields are filled. Ready to approve.';
        console.log('All requirements met. Ready to approve.');
    }
    
    return canApprove;
}

function saveQuality(acquisitionId) {
    const form = document.getElementById('qualityForm' + acquisitionId);
    const formData = new FormData(form);
    formData.append('action', 'save');
    
    // Log what's being sent for debugging
    console.log('Saving quality check with form data:');
    for (let [key, value] of formData.entries()) {
        if (key.includes('photo') || key.includes('remove')) {
            console.log(key + ':', value);
        }
    }
    
    fetch('saveQualityCheck.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Quality check saved successfully!');
            // Re-check approval status after save
            setTimeout(() => {
                checkApproveButton(acquisitionId);
            }, 300);
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

function removeReceiptImage(btn, itemId, type, index) {
    const container = btn.parentElement;
    const hiddenInput = document.getElementById(`remove${type.charAt(0).toUpperCase() + type.slice(1)}Receipts${itemId}`);
    
    // Add the index to the removal list
    let currentRemovals = hiddenInput.value ? hiddenInput.value.split(',') : [];
    currentRemovals.push(index);
    hiddenInput.value = currentRemovals.join(',');
    
    // Remove the image preview
    container.remove();
    
    checkApproveButton(currentAcquisitionId);
}

function removeIssuePhoto(btn, issueId) {
    const container = btn.parentElement;
    
    // Create hidden input to mark this photo for removal
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'remove_issue_photo[]';
    hiddenInput.value = issueId;
    
    // Find the form and append the hidden input
    const form = document.querySelector('form[id^="qualityForm"]');
    if (form) {
        form.appendChild(hiddenInput);
    }
    
    // Remove the image preview
    container.remove();
    checkApproveButton(currentAcquisitionId);
}

function previewNewIssuePhoto(input, issueId) {
    const container = input.nextElementSibling;
    if (!container || !container.classList.contains('image-preview-container')) return;
    
    const preview = container.querySelector('.image-preview');
    const removeBtn = container.querySelector('.remove-image-btn');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
            preview.style.maxWidth = "100px";
            preview.style.maxHeight = "80px";
            preview.style.borderRadius = "5px";
            preview.style.objectFit = "cover";
            preview.style.margin = "2px";
            
            removeBtn.style.display = 'block';
            
            preview.onclick = function() {
                openImageModal(this.src);
            };
        }
        reader.readAsDataURL(input.files[0]);
    }
    
    const acquisitionId = input.closest('form').id.replace('qualityForm', '');
    if (acquisitionId) {
        checkApproveButton(acquisitionId);
    }
}

function removeNewIssuePhoto(btn) {
    const container = btn.parentElement;
    const preview = container.querySelector('.image-preview');
    const fileInput = container.parentElement.querySelector('input[type="file"]');
    
    if (preview) {
        preview.src = '';
        preview.classList.add('d-none');
    }
    
    btn.style.display = 'none';
    
    if (fileInput) {
        fileInput.value = '';
    }
    
    const acquisitionId = container.closest('form').id.replace('qualityForm', '');
    if (acquisitionId) {
        checkApproveButton(acquisitionId);
    }
}

function attachValidationEvents(acquisitionId) {
    const form = document.getElementById('qualityForm' + acquisitionId);
    if (!form) return;

    const inputs = form.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('input', () => checkApproveButton(acquisitionId));
        input.addEventListener('change', () => checkApproveButton(acquisitionId));
    });

    const fileInputs = form.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', () => checkApproveButton(acquisitionId));
    });

    const checkboxes = form.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => checkApproveButton(acquisitionId));
    });
}

// Enhanced debug function
function debugPhotos(acquisitionId) {
    console.log('=== DEBUG PHOTOS ===');
    const form = document.getElementById('qualityForm' + acquisitionId);
    
    // Check vehicle photos
    console.log('--- VEHICLE PHOTOS ---');
    const vehiclePhotos = ['exterior', 'dashboard', 'hood', 'interior', 'trunk'];
    vehiclePhotos.forEach(photo => {
        const photoBoxes = form.querySelectorAll('.photo-box');
        let found = false;
        
        photoBoxes.forEach(box => {
            const label = box.querySelector('label');
            if (label && label.textContent.toLowerCase().includes(photo.toLowerCase())) {
                const existingImg = box.querySelector('img[src*="../uploads/"]');
                const fileInput = box.querySelector('input[type="file"]');
                const previewImg = box.querySelector('img.image-preview:not(.d-none)');
                
                console.log(`${photo}:`, {
                    label: label.textContent,
                    existingImg: !!existingImg,
                    hasNewPhoto: fileInput && fileInput.files && fileInput.files.length > 0,
                    previewImg: previewImg ? previewImg.src.substring(0, 50) + '...' : 'none'
                });
                
                if (existingImg || (fileInput && fileInput.files.length > 0) || previewImg) {
                    found = true;
                }
            }
        });
        
        console.log(`${photo} FOUND:`, found);
    });
    
    // Check document photos
    console.log('--- DOCUMENT PHOTOS ---');
    const docPhotos = ['orcr', 'deed_of_sale', 'insurance'];
    docPhotos.forEach(doc => {
        const photoBoxes = form.querySelectorAll('.photo-box');
        let found = false;
        
        photoBoxes.forEach(box => {
            const label = box.querySelector('label');
            let labelText = label ? label.textContent.toLowerCase() : '';
            
            if ((doc === 'orcr' && labelText.includes('or/cr')) ||
                (doc === 'deed_of_sale' && labelText.includes('deed of sale')) ||
                (doc === 'insurance' && labelText.includes('insurance'))) {
                
                const existingImg = box.querySelector('img[src*="../uploads/"]');
                const fileInput = box.querySelector('input[type="file"]');
                const previewImg = box.querySelector('img:not(.d-none)');
                
                console.log(`${doc}:`, {
                    label: label.textContent,
                    existingImg: !!existingImg,
                    hasNewDoc: fileInput && fileInput.files && fileInput.files.length > 0,
                    previewImg: previewImg ? previewImg.src.substring(0, 50) + '...' : 'none'
                });
                
                if (existingImg || (fileInput && fileInput.files.length > 0) || previewImg) {
                    found = true;
                }
            }
        });
        
        console.log(`${doc} FOUND:`, found);
    });
    
    checkApproveButton(acquisitionId);
}
// Function to remove existing document photo from database
function removeDocumentPhoto(btn, docType, acquisitionId) {
    const container = btn.parentElement;
    const preview = container.querySelector('.image-preview');
    const hiddenInput = document.getElementById(`removeDocPhoto${docType}${acquisitionId}`);
    
    // Mark this document photo for removal in database
    hiddenInput.value = docType;
    
    // Clear the preview but keep the container structure
    preview.src = '';
    preview.classList.add('d-none');
    
    // Hide remove button
    btn.style.display = 'none';
    
    // Re-check approval status
    checkApproveButton(acquisitionId);
    
    console.log(`Marked ${docType} document photo for removal`);
}

// removeImage function for new document photos
function removeImage(btn) {
    const container = btn.parentElement;
    const preview = container.querySelector('.image-preview');
    const fileInput = container.parentElement.querySelector('input[type="file"]');
    
    // Clear the preview
    if (preview) {
        preview.src = '';
        preview.classList.add('d-none');
    }
    
    // Hide remove button
    btn.style.display = 'none';
    
    // Clear file input
    if (fileInput) {
        fileInput.value = '';
    }
    
    // Find acquisition ID and check approval status
    const form = btn.closest('form');
    if (form) {
        const acquisitionId = form.id.replace('qualityForm', '');
        if (acquisitionId) {
            checkApproveButton(acquisitionId);
        }
    }
}

// Enhanced previewImage function for better document photo handling
function previewImage(input) {
    const container = input.parentElement.querySelector('.image-preview-container');
    const preview = container.querySelector('.image-preview');
    const removeBtn = container.querySelector('.remove-image-btn');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
            removeBtn.style.display = 'block';
            
            preview.onclick = function() {
                openImageModal(this.src);
            };
        }
        reader.readAsDataURL(input.files[0]);
        
        // Clear any removal markers when new file is selected
        const form = input.closest('form');
        if (form) {
            const acquisitionId = form.id.replace('qualityForm', '');
            const docType = input.name.replace('_photo_update', '');
            const hiddenInput = document.getElementById(`removeDocPhoto${docType}${acquisitionId}`);
            if (hiddenInput) {
                hiddenInput.value = ''; // Clear removal marker
            }
        }
    } else {
        // If no file selected but there's an existing image, keep it visible
        if (preview.src && preview.src !== '' && !preview.src.includes('data:')) {
            preview.classList.remove('d-none');
            removeBtn.style.display = 'block';
        }
    }
    
    // Check approval status
    const form = input.closest('form');
    if (form) {
        const acquisitionId = form.id.replace('qualityForm', '');
        if (acquisitionId) {
            checkApproveButton(acquisitionId);
        }
    }
}
</script>

</body>
</html>