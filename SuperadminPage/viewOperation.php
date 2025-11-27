<?php
require_once '../session_helper.php';
startRoleSession('superadmin');
include '../db_connect.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ../LoginPage/loginPage.php');
    exit();
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$searchQuery = $_GET['search'] ?? '';

// Build query with filters
$query = "SELECT 
    va.*,
    u.firstname as creator_firstname,
    u.lastname as creator_lastname,
    (SELECT SUM(COALESCE(issue_price, 0)) FROM acquisition_issues WHERE acquisition_id = va.acquisition_id) as total_issues_cost,
    (SELECT SUM(COALESCE(part_price, 0)) FROM acquisition_parts WHERE acquisition_id = va.acquisition_id) as total_parts_cost,
    (SELECT COUNT(*) FROM acquisition_issues WHERE acquisition_id = va.acquisition_id) as issue_count,
    (SELECT COUNT(*) FROM acquisition_parts WHERE acquisition_id = va.acquisition_id) as part_count
FROM vehicle_acquisition va 
LEFT JOIN users u ON va.created_by = u.id 
WHERE va.status = 'Sent to Operations'";

if (!empty($searchQuery)) {
    $query .= " AND (va.plate_number LIKE '%$searchQuery%' OR va.vehicle_model LIKE '%$searchQuery%' OR va.make LIKE '%$searchQuery%')";
}

if (!empty($statusFilter)) {
    if ($statusFilter === 'released') {
        $query .= " AND va.is_released = 1";
    } elseif ($statusFilter === 'archived') {
        $query .= " AND va.is_released = 2";
    } elseif ($statusFilter === 'pending') {
        $query .= " AND va.is_released = 0";
    }
}

$query .= " ORDER BY va.sent_to_operations_at DESC";
$operations = $conn->query($query);

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_released = 0 THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN is_released = 1 THEN 1 ELSE 0 END) as released,
    SUM(CASE WHEN is_released = 2 THEN 1 ELSE 0 END) as archived,
    SUM(CASE WHEN selling_price > 0 THEN 1 ELSE 0 END) as priced,
    SUM(acquired_price) as total_acquired_cost,
    SUM(selling_price) as total_selling_price
FROM vehicle_acquisition WHERE status = 'Sent to Operations'";
$stats = $conn->query($statsQuery)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Operations</title>
<link rel="stylesheet" href="../css/superadmin.css">
<link rel="stylesheet" href="../css/acquiPage.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

</head>

<body>
<div class="header">
  <div class="header-left">
    <img src="../Pictures/Carmax_logo.jpg" class="logo" alt="CarMax Logo">
    <div class="header-title h5 mb-0">View Operations</div>
  </div>
  <div class="user-info">
    <i class="fas fa-user-circle" style="font-size: 24px;"></i>
    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?> (Super Admin)</span>
    <a href="../logout.php" style="margin-left: 15px; color: white; text-decoration: none;">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </div>
</div>

<div class="sidebar">
    <a href="superadminPage.php" class="sidebar-item">
        <i class="fas fa-list"></i> View Logs
    </a>
    <a href="manageUsers.php" class="sidebar-item">
        <i class="fas fa-users"></i><span>Manage Accounts</span>
    </a>
    <a href="viewAcquisition.php" class="sidebar-item">
        <i class="fas fa-car"></i><span>View Acquisition</span>
    </a>
    <a href="viewOperation.php" class="sidebar-item active">
        <i class="fas fa-cogs"></i><span>View Operations</span>
    </a>
</div>

<main class="main-content">
  <!-- Statistics Dashboard -->
  <div class="stats-grid">
    <div class="stat-box">
      <div class="stat-number"><?= $stats['total'] ?? 0 ?></div>
      <div class="stat-label"><i class="fas fa-car"></i> Total Vehicles</div>
    </div>
    <div class="stat-box">
      <div class="stat-number" style="color: #ffc107;"><?= $stats['pending'] ?? 0 ?></div>
      <div class="stat-label"><i class="fas fa-clock"></i> Pending Release</div>
    </div>
    <div class="stat-box">
      <div class="stat-number" style="color: #28a745;"><?= $stats['released'] ?? 0 ?></div>
      <div class="stat-label"><i class="fas fa-globe"></i> Released</div>
    </div>
    <div class="stat-box">
      <div class="stat-number" style="color: #6c757d;"><?= $stats['archived'] ?? 0 ?></div>
      <div class="stat-label"><i class="fas fa-archive"></i> Archived</div>
    </div>
    <div class="stat-box">
      <div class="stat-number" style="color: #17a2b8;"><?= $stats['priced'] ?? 0 ?></div>
      <div class="stat-label"><i class="fas fa-tag"></i> Priced</div>
    </div>
    <div class="stat-box">
      <div class="stat-number" style="color: #dc3545;">₱<?= number_format($stats['total_selling_price'] ?? 0, 2) ?></div>
      <div class="stat-label"><i class="fas fa-chart-line"></i> Total Selling Price</div>
    </div>
  </div>

  <div class="sap-card">
    <div class="sap-card-header">
      <span><i class="fas fa-cogs"></i> Operations Management</span>
      <form method="GET" class="filter-form-horizontal">
        <input type="text" name="search" class="form-control" placeholder="Search plate or model..." value="<?= htmlspecialchars($searchQuery) ?>">
        
        <select name="status" class="form-select">
          <option value="">All Status</option>
          <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Release</option>
          <option value="released" <?= $statusFilter === 'released' ? 'selected' : '' ?>>Released</option>
          <option value="archived" <?= $statusFilter === 'archived' ? 'selected' : '' ?>>Archived</option>
        </select>

        <button type="submit" class="btn-carmax-secondary"><i class="fas fa-filter"></i> Filter</button>
        <?php if (!empty($searchQuery) || !empty($statusFilter)): ?>
          <a href="viewOperation.php" class="btn-carmax-primary"><i class="fas fa-times"></i> Clear</a>
        <?php endif; ?>
      </form>
    </div>
    <div class="sap-card-body">
      <div class="table-responsive">
        <table class="sap-table">
          <thead>
            <tr>
              <th><i class="fas fa-hashtag"></i> ID</th>
              <th><i class="fas fa-car-side"></i> Model</th>
              <th><i class="fas fa-id-card"></i> Plate</th>
              <th><i class="fas fa-calendar"></i> Year</th>
              <th><i class="fas fa-dollar-sign"></i> Acquired Price</th>
              <th><i class="fas fa-tag"></i> Selling Price</th>
              <th><i class="fas fa-info-circle"></i> Status</th>
              <th><i class="fas fa-user"></i> Updated By</th>
              <th><i class="fas fa-clock"></i> Updated At</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($operations && $operations->num_rows > 0): ?>
              <?php while($row = $operations->fetch_assoc()): ?>
                <tr data-bs-toggle="modal" data-bs-target="#operationModal<?= $row['acquisition_id'] ?>">
                  <td><?= $row['acquisition_id']; ?></td>
                  <td><?= htmlspecialchars($row['vehicle_model']); ?></td>
                  <td><?= htmlspecialchars($row['plate_number']); ?></td>
                  <td><?= htmlspecialchars($row['year_model']); ?></td>
                  <td>₱<?= number_format($row['acquired_price'], 2); ?></td>
                  <td>
                    <?php if ($row['selling_price'] > 0): ?>
                      <span class="badge bg-success">₱<?= number_format($row['selling_price'], 2); ?></span>
                    <?php else: ?>
                      <span class="badge bg-secondary">Not Set</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($row['is_released'] == 2): ?>
                      <span class="badge bg-secondary"><i class="fas fa-archive"></i> Archived</span>
                    <?php elseif ($row['is_released'] == 1): ?>
                      <span class="badge bg-success"><i class="fas fa-globe"></i> Released</span>
                    <?php else: ?>
                      <span class="badge bg-warning"><i class="fas fa-clock"></i> Pending</span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($row['operations_updated_by'] ?? 'N/A'); ?></td>
                  <td><?= $row['operations_updated_at'] ? date('M d, Y h:i A', strtotime($row['operations_updated_at'])) : 'N/A'; ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="text-center">
                  <div class="no-data">
                    <i class="fas fa-inbox fa-3x"></i>
                    <p>No vehicles in operations.</p>
                  </div>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<?php 
if ($operations && $operations->num_rows > 0):
    $operations->data_seek(0);
    while ($row = $operations->fetch_assoc()):
        
    // Get detailed issues and parts
    $issuesQuery = $conn->query("SELECT * FROM acquisition_issues WHERE acquisition_id = {$row['acquisition_id']}");
    $partsQuery = $conn->query("SELECT * FROM acquisition_parts WHERE acquisition_id = {$row['acquisition_id']}");
    
    // Calculate costs
    $totalReconCost = $row['acquired_price'] + ($row['total_issues_cost'] ?? 0) + ($row['total_parts_cost'] ?? 0);
    $markupValue = $row['markup_value'] ?? 0;
    $sellingPrice = $row['selling_price'] ?? 0;
    $potentialProfit = $sellingPrice - $totalReconCost;
    
?>
<!-- Operations Details Modal -->
<div class="modal fade" id="operationModal<?= $row['acquisition_id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-white">
                <h5 class="modal-title">
                    <i class="fas fa-cogs"></i> Operations Vehicle Details - <?= htmlspecialchars($row['vehicle_model']) ?>
                    <?php if ($row['is_released'] == 2): ?>
                        <span class="badge bg-secondary ms-2"><i class="fas fa-archive"></i> Archived</span>
                    <?php elseif ($row['is_released'] == 1): ?>
                        <span class="badge bg-success ms-2"><i class="fas fa-globe"></i> Released</span>
                    <?php else: ?>
                        <span class="badge bg-warning ms-2"><i class="fas fa-clock"></i> Pending</span>
                    <?php endif; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <!-- Basic Information -->
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-info-circle"></i> Vehicle Information</h6>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="info-row"><div class="info-label">Supplier:</div><div class="info-value"><?= htmlspecialchars($row['supplier'] ?? 'N/A') ?></div></div>
                        <div class="info-row"><div class="info-label">Make:</div><div class="info-value"><?= htmlspecialchars($row['make'] ?? 'N/A') ?></div></div>
                        <div class="info-row"><div class="info-label">Plate Number:</div><div class="info-value"><?= htmlspecialchars($row['plate_number']) ?></div></div>
                        <div class="info-row"><div class="info-label">Vehicle Model:</div><div class="info-value"><?= htmlspecialchars($row['vehicle_model']) ?></div></div>
                        <div class="info-row"><div class="info-label">Year Model:</div><div class="info-value"><?= htmlspecialchars($row['year_model']) ?></div></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row"><div class="info-label">Variant:</div><div class="info-value"><?= htmlspecialchars($row['variant'] ?? 'N/A') ?></div></div>
                        <div class="info-row"><div class="info-label">Color:</div><div class="info-value"><?= htmlspecialchars($row['color']) ?></div></div>
                        <div class="info-row"><div class="info-label">Fuel Type:</div><div class="info-value"><?= htmlspecialchars($row['fuel_type'] ?? 'N/A') ?></div></div>
                        <div class="info-row"><div class="info-label">Odometer:</div><div class="info-value"><?= number_format($row['odometer'] ?? 0) ?> km</div></div>
                        <div class="info-row"><div class="info-label">Body Type:</div><div class="info-value"><?= htmlspecialchars($row['body_type'] ?? 'N/A') ?></div></div>
                        <div class="info-row"><div class="info-label">Transmission:</div><div class="info-value"><?= htmlspecialchars($row['transmission'] ?? 'N/A') ?></div></div>
                    </div>
                </div>

                <!-- Pricing Information -->
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-dollar-sign"></i> Pricing Information</h6>
                <div class="cost-breakdown mb-4">
                    <div class="cost-item">
                        <span>Acquired Price:</span>
                        <span>₱<?= number_format($row['acquired_price'], 2) ?></span>
                    </div>
                    <div class="cost-item">
                        <span>Issues Cost:</span>
                        <span>₱<?= number_format($row['total_issues_cost'] ?? 0, 2) ?></span>
                    </div>
                    <div class="cost-item">
                        <span>Parts Cost:</span>
                        <span>₱<?= number_format($row['total_parts_cost'] ?? 0, 2) ?></span>
                    </div>
                    <div class="cost-item cost-total">
                        <span>Total Recon Cost:</span>
                        <span>₱<?= number_format($totalReconCost, 2) ?></span>
                    </div>
                    <div class="cost-item">
                        <span>Markup Percentage:</span>
                        <span><?= number_format($row['markup_percentage'] ?? 0, 2) ?>%</span>
                    </div>
                    <div class="cost-item">
                        <span>Markup Value:</span>
                        <span>₱<?= number_format($markupValue, 2) ?></span>
                    </div>
                    <div class="cost-item cost-total" style="color: #dc3545; border-color: #dc3545;">
                        <span><i class="fas fa-tag"></i> SELLING PRICE:</span>
                        <span><strong>₱<?= number_format($sellingPrice, 2) ?></strong></span>
                    </div>
                    <?php if ($sellingPrice > 0): ?>
                    <div class="cost-item <?= $potentialProfit >= 0 ? 'text-success' : 'text-danger' ?>">
                        <span>Potential Profit/Loss:</span>
                        <span><strong>₱<?= number_format($potentialProfit, 2) ?></strong></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Vehicle Condition -->
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-clipboard-check"></i> Vehicle Condition</h6>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="info-row"><div class="info-label">Spare Tires:</div><div class="info-value"><?= htmlspecialchars($row['spare_tires']) ?></div></div>
                        <div class="info-row"><div class="info-label">Complete Tools:</div><div class="info-value"><?= htmlspecialchars($row['complete_tools']) ?></div></div>
                        <div class="info-row"><div class="info-label">Original Plate:</div><div class="info-value"><?= htmlspecialchars($row['original_plate']) ?></div></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row"><div class="info-label">Complete Documents:</div><div class="info-value"><?= htmlspecialchars($row['complete_documents']) ?></div></div>
                        <div class="info-row"><div class="info-label">Spare Key:</div><div class="info-value"><?= htmlspecialchars($row['spare_key'] ?? 'N/A') ?></div></div>
                        <div class="info-row"><div class="info-label">Missing Documents:</div><div class="info-value"><?= htmlspecialchars($row['missing_documents'] ?? 'None') ?></div></div>
                    </div>
                </div>

                <!-- Enhanced Vehicle Photos with Carousel -->
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-images"></i> Vehicle Photos Gallery</h6>
                <div id="carousel<?= $row['acquisition_id'] ?>" class="carousel slide mb-4" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php
                        $photos = [
                            'exterior_photo' => 'Exterior View',
                            'dashboard_photo' => 'Dashboard',
                            'hood_photo' => 'Hood/Engine',
                            'interior_photo' => 'Interior',
                            'trunk_photo' => 'Trunk'
                        ];
                        $active = true;
                        foreach ($photos as $field => $label):
                            if (!empty($row[$field])):
                        ?>
                        <div class="carousel-item <?= $active ? 'active' : '' ?>">
                            <img src="../uploads/<?= $row[$field] ?>" class="d-block w-100 rounded" alt="<?= $label ?>" style="max-height: 500px; object-fit: contain;">
                            <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded">
                                <h5><?= $label ?></h5>
                            </div>
                        </div>
                        <?php
                                $active = false;
                            endif;
                        endforeach;
                        ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#carousel<?= $row['acquisition_id'] ?>" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#carousel<?= $row['acquisition_id'] ?>" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>

                <!-- Document Photos -->
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-file-contract"></i> Document Photos</h6>
                <div class="photo-grid mb-4">
                    <?php
                    $documents = [
                        'orcr_photo' => 'OR/CR',
                        'deed_of_sale_photo' => 'Deed of Sale',
                        'insurance_photo' => 'Insurance'
                    ];
                    foreach ($documents as $field => $label):
                        if (!empty($row[$field])):
                    ?>
                    <div class="photo-box">
                        <label><?= $label ?></label>
                        <img src="../uploads/<?= $row[$field] ?>" alt="<?= $label ?>" class="clickable-image" style="max-height: 200px;">
                    </div>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </div>

                <!-- Issues and Parts Summary -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-primary fw-bold mb-3"><i class="fas fa-exclamation-triangle"></i> Issues Summary</h6>
                        <div class="alert alert-<?= $row['issue_count'] > 0 ? 'warning' : 'success' ?>">
                            <i class="fas fa-<?= $row['issue_count'] > 0 ? 'exclamation-triangle' : 'check-circle' ?>"></i>
                            <?= $row['issue_count'] ?> issues recorded (Total: ₱<?= number_format($row['total_issues_cost'] ?? 0, 2) ?>)
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary fw-bold mb-3"><i class="fas fa-tools"></i> Parts Summary</h6>
                        <div class="alert alert-<?= $row['part_count'] > 0 ? 'info' : 'success' ?>">
                            <i class="fas fa-<?= $row['part_count'] > 0 ? 'tools' : 'check-circle' ?>"></i>
                            <?= $row['part_count'] ?> parts recorded (Total: ₱<?= number_format($row['total_parts_cost'] ?? 0, 2) ?>)
                        </div>
                    </div>
                </div>

                <!-- Detailed Issues -->
                <?php if ($issuesQuery && $issuesQuery->num_rows > 0): ?>
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-exclamation-triangle"></i> Detailed Issues</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-striped">
                        <thead class="table-warning">
                            <tr>
                                <th>Issue Name</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Repaired By</th>
                                <th>Remarks</th>
                                <th>Photo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($issue = $issuesQuery->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($issue['issue_name']) ?></td>
                                <td>₱<?= $issue['issue_price'] ? number_format($issue['issue_price'], 2) : '0.00' ?></td>
                                <td>
                                    <?php if ($issue['is_repaired']): ?>
                                        <span class="badge bg-success"><i class="fas fa-check"></i> Repaired</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning"><i class="fas fa-clock"></i> Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($issue['repaired_by'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($issue['issue_remarks'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if (!empty($issue['issue_photo'])): ?>
                                        <img src="../uploads/<?= htmlspecialchars($issue['issue_photo']) ?>" style="max-width: 80px; border-radius: 5px;" class="clickable-image">
                                    <?php else: ?>
                                        <span class="text-muted">No photo</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- Detailed Parts -->
                <?php if ($partsQuery && $partsQuery->num_rows > 0): ?>
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-tools"></i> Detailed Parts</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-striped">
                        <thead class="table-info">
                            <tr>
                                <th>Part Name</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Ordered By</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($part = $partsQuery->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($part['part_name']) ?></td>
                                <td>₱<?= $part['part_price'] ? number_format($part['part_price'], 2) : '0.00' ?></td>
                                <td>
                                    <?php if ($part['is_ordered']): ?>
                                        <span class="badge bg-success"><i class="fas fa-check"></i> Ordered</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning"><i class="fas fa-clock"></i> Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($part['ordered_by'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($part['part_remarks'] ?? 'N/A') ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- Operations Status -->
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-cogs"></i> Operations Status</h6>
                <div class="mb-4">
                    <div class="info-row">
                        <div class="info-label">Release Status:</div>
                        <div class="info-value">
                            <?php if ($row['is_released'] == 2): ?>
                                <span class="badge bg-secondary"><i class="fas fa-archive"></i> Archived</span>
                            <?php elseif ($row['is_released'] == 1): ?>
                                <span class="badge bg-success"><i class="fas fa-globe"></i> Released</span>
                            <?php else: ?>
                                <span class="badge bg-warning"><i class="fas fa-clock"></i> Pending Release</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($row['operations_updated_by']): ?>
                        <div class="info-row"><div class="info-label">Updated By:</div><div class="info-value"><?= htmlspecialchars($row['operations_updated_by']) ?></div></div>
                        <div class="info-row"><div class="info-label">Updated At:</div><div class="info-value"><?= $row['operations_updated_at'] ? date('M d, Y h:i A', strtotime($row['operations_updated_at'])) : 'N/A' ?></div></div>
                    <?php endif; ?>
                    <?php if ($row['released_by']): ?>
                        <div class="info-row"><div class="info-label">Released By:</div><div class="info-value"><?= htmlspecialchars($row['released_by']) ?></div></div>
                        <div class="info-row"><div class="info-label">Released At:</div><div class="info-value"><?= $row['released_at'] ? date('M d, Y h:i A', strtotime($row['released_at'])) : 'N/A' ?></div></div>
                    <?php endif; ?>
                    <?php if ($row['archived_by']): ?>
                        <div class="info-row"><div class="info-label">Archived By:</div><div class="info-value"><?= htmlspecialchars($row['archived_by']) ?></div></div>
                        <div class="info-row"><div class="info-label">Archived At:</div><div class="info-value"><?= $row['archived_at'] ? date('M d, Y h:i A', strtotime($row['archived_at'])) : 'N/A' ?></div></div>
                    <?php endif; ?>
                </div>

                <!-- Process Timeline -->
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-history"></i> Process Timeline</h6>
                <div class="timeline mb-4">
                    <?php if ($row['created_at']): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-primary"></div>
                        <div class="timeline-content">
                            <h6>Acquisition Created</h6>
                            <p class="text-muted mb-0">By: <?= htmlspecialchars($row['creator_firstname'] ?? 'Unknown') ?> <?= htmlspecialchars($row['creator_lastname'] ?? 'User') ?></p>
                            <small class="text-muted"><?= date('M d, Y h:i A', strtotime($row['created_at'])) ?></small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($row['quality_checked_at']): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-info"></div>
                        <div class="timeline-content">
                            <h6>Quality Check Completed</h6>
                            <p class="text-muted mb-0">By: <?= htmlspecialchars($row['quality_checked_by']) ?></p>
                            <small class="text-muted"><?= date('M d, Y h:i A', strtotime($row['quality_checked_at'])) ?></small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($row['approved_at']): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6>Approved</h6>
                            <p class="text-muted mb-0">By: <?= htmlspecialchars($row['approved_by']) ?></p>
                            <small class="text-muted"><?= date('M d, Y h:i A', strtotime($row['approved_at'])) ?></small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($row['sent_to_operations_at']): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-warning"></div>
                        <div class="timeline-content">
                            <h6>Sent to Operations</h6>
                            <p class="text-muted mb-0">By: <?= htmlspecialchars($row['sent_to_operations_by']) ?></p>
                            <small class="text-muted"><?= date('M d, Y h:i A', strtotime($row['sent_to_operations_at'])) ?></small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($row['operations_updated_at']): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-purple"></div>
                        <div class="timeline-content">
                            <h6>Pricing Updated</h6>
                            <p class="text-muted mb-0">By: <?= htmlspecialchars($row['operations_updated_by']) ?></p>
                            <small class="text-muted"><?= date('M d, Y h:i A', strtotime($row['operations_updated_at'])) ?></small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($row['released_at']): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6>Released to Public</h6>
                            <p class="text-muted mb-0">By: <?= htmlspecialchars($row['released_by']) ?></p>
                            <small class="text-muted"><?= date('M d, Y h:i A', strtotime($row['released_at'])) ?></small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($row['archived_at']): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-secondary"></div>
                        <div class="timeline-content">
                            <h6>Archived</h6>
                            <p class="text-muted mb-0">By: <?= htmlspecialchars($row['archived_by']) ?></p>
                            <small class="text-muted"><?= date('M d, Y h:i A', strtotime($row['archived_at'])) ?></small>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Remarks -->
                <?php if (!empty($row['remarks'])): ?>
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-comment"></i> Remarks & Notes</h6>
                <div class="alert alert-info">
                    <i class="fas fa-sticky-note"></i>
                    <?= nl2br(htmlspecialchars($row['remarks'])) ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>
<?php endwhile; endif; ?>

<!-- Image Modal -->
<div id="imageModal" class="image-modal">
    <span class="image-modal-close" onclick="closeImageModal()">&times;</span>
    <img class="image-modal-content" id="modalImage">
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
function openImageModal(imgSrc) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    modal.style.display = 'block';
    modalImg.src = imgSrc;
}

function closeImageModal() {
    document.getElementById('imageModal').style.display = 'none';
}

document.getElementById('imageModal').onclick = function(event) {
    if (event.target === this) {
        closeImageModal();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const clickableImages = document.querySelectorAll('.clickable-image');
    clickableImages.forEach(img => {
        img.onclick = function() {
            openImageModal(this.src);
        };
    });
});
</script>
</body>
</html>