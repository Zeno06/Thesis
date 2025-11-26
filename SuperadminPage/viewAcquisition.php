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
$query = "SELECT * FROM vehicle_acquisition WHERE 1=1";

if (!empty($searchQuery)) {
    $escSearch = $conn->real_escape_string($searchQuery);
    $query .= " AND (plate_number LIKE '%$escSearch%' OR vehicle_model LIKE '%$escSearch%')";
}

if (!empty($statusFilter)) {
    $escStatus = $conn->real_escape_string($statusFilter);
    $query .= " AND status = '$escStatus'";
}

$query .= " ORDER BY acquisition_id ASC";
$acquisitions = $conn->query($query);

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Quality Check' THEN 1 ELSE 0 END) as quality_check,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Sent to Operations' THEN 1 ELSE 0 END) as sent_operations,
    SUM(acquired_price) as total_acquired_cost
FROM vehicle_acquisition";
$stats = $conn->query($statsQuery)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Acquisitions</title>
<link rel="stylesheet" href="../css/superadmin.css">
<link rel="stylesheet" href="../css/acquiPage.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    .stat-box {
        background: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: #667eea;
    }
    .stat-label {
        color: #666;
        font-size: 0.9rem;
        margin-top: 5px;
    }
    .filter-form-horizontal {
        display: flex;
        gap: 10px;
        align-items: center;
        margin: 0;
    }
    .filter-form-horizontal .form-control,
    .filter-form-horizontal .form-select {
        width: 220px;
    }
    .sap-table tbody tr {
        cursor: pointer;
        transition: all 0.2s;
    }
    .sap-table tbody tr:hover {
        background: #f0f7ff !important;
        transform: scale(1.01);
    }
</style>
</head>

<body>
<div class="header">
  <div class="header-left">
    <img src="../Pictures/Carmax_logo.jpg" class="logo" alt="CarMax Logo">
    <div class="header-title h5 mb-0">View Acquisitions</div>
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
    <a href="viewAcquisition.php" class="sidebar-item active">
        <i class="fas fa-car"></i><span>View Acquisition</span>
    </a>
    <a href="viewOperation.php" class="sidebar-item">
        <i class="fas fa-cogs"></i><span>View Operations</span>
    </a>
</div>

<main class="main-content">
  <!-- Statistics Dashboard -->
  <div class="stats-grid">
    <div class="stat-box">
      <div class="stat-number"><?= number_format($stats['total'] ?? 0) ?></div>
      <div class="stat-label"><i class="fas fa-car"></i> Total Acquisitions</div>
    </div>
    <div class="stat-box">
      <div class="stat-number" style="color: #ffc107;"><?= number_format($stats['pending'] ?? 0) ?></div>
      <div class="stat-label"><i class="fas fa-clock"></i> Pending</div>
    </div>
    <div class="stat-box">
      <div class="stat-number" style="color: #17a2b8;"><?= number_format($stats['quality_check'] ?? 0) ?></div>
      <div class="stat-label"><i class="fas fa-clipboard-check"></i> Quality Check</div>
    </div>
    <div class="stat-box">
      <div class="stat-number" style="color: #28a745;"><?= number_format($stats['approved'] ?? 0) ?></div>
      <div class="stat-label"><i class="fas fa-check-circle"></i> Approved</div>
    </div>
    <div class="stat-box">
      <div class="stat-number" style="color: #6610f2;"><?= number_format($stats['sent_operations'] ?? 0) ?></div>
      <div class="stat-label"><i class="fas fa-paper-plane"></i> Sent to Operations</div>
    </div>
    <div class="stat-box">
      <div class="stat-number" style="color: #dc3545;">₱<?= number_format($stats['total_acquired_cost'] ?? 0, 2) ?></div>
      <div class="stat-label"><i class="fas fa-dollar-sign"></i> Total Acquired Cost</div>
    </div>
  </div>

  <div class="sap-card">
    <div class="sap-card-header">
      <span><i class="fas fa-car"></i> Vehicle Acquisitions</span>
      <form method="GET" class="filter-form-horizontal">
        <input type="text" name="search" class="form-control" placeholder="Search plate or model..." value="<?= htmlspecialchars($searchQuery) ?>">
        
        <select name="status" class="form-select">
          <option value="">All Status</option>
          <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
          <option value="Quality Check" <?= $statusFilter === 'Quality Check' ? 'selected' : '' ?>>Quality Check</option>
          <option value="Approved" <?= $statusFilter === 'Approved' ? 'selected' : '' ?>>Approved</option>
          <option value="Sent to Operations" <?= $statusFilter === 'Sent to Operations' ? 'selected' : '' ?>>Sent to Operations</option>
        </select>

        <button type="submit" class="btn-carmax-secondary"><i class="fas fa-filter"></i> Filter</button>
        <?php if (!empty($searchQuery) || !empty($statusFilter)): ?>
          <a href="viewAcquisition.php" class="btn-carmax-primary"><i class="fas fa-times"></i> Clear</a>
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
              <th><i class="fas fa-palette"></i> Color</th>
              <th><i class="fas fa-dollar-sign"></i> Acquired Price</th>
              <th><i class="fas fa-info-circle"></i> Status</th>
              <th><i class="fas fa-user"></i> Created/Approved By</th>
              <th><i class="fas fa-clock"></i> Date</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($acquisitions && $acquisitions->num_rows > 0): ?>
              <?php while($row = $acquisitions->fetch_assoc()): ?>
              <tr data-bs-toggle="modal" data-bs-target="#vehicleModal<?= $row['acquisition_id'] ?>">
                <td><?= $row['acquisition_id']; ?></td>
                <td><?= htmlspecialchars($row['vehicle_model']); ?></td>
                <td><?= htmlspecialchars($row['plate_number']); ?></td>
                <td><?= htmlspecialchars($row['year_model']); ?></td>
                <td><?= htmlspecialchars($row['color']); ?></td>
                <td>₱<?= number_format($row['acquired_price'], 2); ?></td>
                <td>
                  <?php
                    $statusBadge = match($row['status']) {
                        'Pending' => '<span class="badge bg-warning text-dark">Pending</span>',
                        'Quality Check' => '<span class="badge bg-info">Quality Check</span>',
                        'Approved' => '<span class="badge bg-success">Approved</span>',
                        'Sent to Operations' => '<span class="badge bg-primary">Sent to Operations</span>',
                        default => '<span class="badge bg-secondary">' . htmlspecialchars($row['status']) . '</span>'
                    };
                    echo $statusBadge;
                  ?>
                </td>
                <td><?= htmlspecialchars($row['approved_by'] ?? $row['quality_checked_by'] ?? 'N/A'); ?></td>
                <td><?= !empty($row['created_at']) ? date('M d, Y', strtotime($row['created_at'])) : 'N/A'; ?></td>
              </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="text-center">
                  <div class="no-data">
                    <i class="fas fa-inbox fa-3x"></i>
                    <p>No acquisition records found.</p>
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
if ($acquisitions && $acquisitions->num_rows > 0):
    $acquisitions->data_seek(0);
    while ($row = $acquisitions->fetch_assoc()):
?>
<!-- Vehicle Details Modal -->
<div class="modal fade" id="vehicleModal<?= $row['acquisition_id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-white">
                <h5 class="modal-title">
                    <i class="fas fa-car"></i> Vehicle Details - <?= htmlspecialchars($row['vehicle_model']) ?>
                    <?php
                        $statusBadge = match($row['status']) {
                            'Pending' => '<span class="badge bg-warning text-dark ms-2">Pending</span>',
                            'Quality Check' => '<span class="badge bg-info ms-2">Quality Check</span>',
                            'Approved' => '<span class="badge bg-success ms-2">Approved</span>',
                            'Sent to Operations' => '<span class="badge bg-primary ms-2">Sent to Operations</span>',
                            default => ''
                        };
                        echo $statusBadge;
                    ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <!-- Basic Information -->
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
                    <div class="info-row"><div class="info-label">Acquired Price:</div><div class="info-value">₱<?= number_format($row['acquired_price'], 2) ?></div></div>
                    <div class="info-row"><div class="info-label">Status:</div><div class="info-value"><?= htmlspecialchars($row['status']) ?></div></div>
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
                        <?php if ($photoPath): ?>
                            <img src="../uploads/<?= $photoPath ?>" alt="<?= $label ?>" class="clickable-image">
                        <?php else: ?>
                            <div class="text-muted">No image</div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Remarks -->
                <?php if (!empty($row['remarks'])): ?>
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-comment"></i> Remarks</h6>
                <div class="alert alert-info mb-4"><?= nl2br(htmlspecialchars($row['remarks'])) ?></div>
                <?php endif; ?>

                <!-- Process Information -->
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-user-check"></i> Process Information</h6>
                <div class="mb-4">
                    <?php if ($row['quality_checked_by']): ?>
                        <div class="info-row"><div class="info-label">Quality Checked By:</div><div class="info-value"><?= htmlspecialchars($row['quality_checked_by']) ?></div></div>
                        <div class="info-row"><div class="info-label">Quality Checked At:</div><div class="info-value"><?= $row['quality_checked_at'] ? date('M d, Y h:i A', strtotime($row['quality_checked_at'])) : 'N/A' ?></div></div>
                    <?php endif; ?>
                    <?php if ($row['approved_by']): ?>
                        <div class="info-row"><div class="info-label">Approved By:</div><div class="info-value"><?= htmlspecialchars($row['approved_by']) ?></div></div>
                        <div class="info-row"><div class="info-label">Approved At:</div><div class="info-value"><?= $row['approved_at'] ? date('M d, Y h:i A', strtotime($row['approved_at'])) : 'N/A' ?></div></div>
                    <?php endif; ?>
                    <?php if ($row['sent_to_operations_by']): ?>
                        <div class="info-row"><div class="info-label">Sent to Operations By:</div><div class="info-value"><?= htmlspecialchars($row['sent_to_operations_by']) ?></div></div>
                        <div class="info-row"><div class="info-label">Sent At:</div><div class="info-value"><?= $row['sent_to_operations_at'] ? date('M d, Y h:i A', strtotime($row['sent_to_operations_at'])) : 'N/A' ?></div></div>
                    <?php endif; ?>
                </div>
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