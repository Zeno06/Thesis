<?php
session_start();
include '../db_connect.php';
if ($_SESSION['role'] !== 'superadmin') { header('Location: ../loginPage/loginPage.php'); exit; }

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$searchQuery = $_GET['search'] ?? '';

// Build query with filters
$query = "SELECT * FROM vehicle_acquisition WHERE status = 'Sent to Operations'";

if (!empty($searchQuery)) {
    $query .= " AND (plate_number LIKE '%$searchQuery%' OR vehicle_model LIKE '%$searchQuery%')";
}

if (!empty($statusFilter)) {
    if ($statusFilter === 'released') {
        $query .= " AND is_released = 1";
    } elseif ($statusFilter === 'archived') {
        $query .= " AND is_released = 2";
    } elseif ($statusFilter === 'pending') {
        $query .= " AND is_released = 0";
    }
}

$query .= " ORDER BY sent_to_operations_at DESC";
$operations = $conn->query($query);

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_released = 0 THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN is_released = 1 THEN 1 ELSE 0 END) as released,
    SUM(CASE WHEN is_released = 2 THEN 1 ELSE 0 END) as archived,
    SUM(CASE WHEN selling_price > 0 THEN 1 ELSE 0 END) as priced
FROM vehicle_acquisition WHERE status = 'Sent to Operations'";
$stats = $conn->query($statsQuery)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Operations</title>
<link rel="stylesheet" href="../css/superadmin.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
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
</style>
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
    <a href="viewInventory.php" class="sidebar-item">
        <i class="fas fa-warehouse"></i><span>View Inventory</span>
    </a>
</div>

<main class="main-content">
  <!-- Statistics Dashboard -->
  <div class="stats-grid">
    <div class="stat-box">
      <div class="stat-number"><?= $stats['total'] ?></div>
      <div class="stat-label"><i class="fas fa-car"></i> Total Vehicles</div>
    </div>
    <div class="stat-box">
      <div class="stat-number" style="color: #ffc107;"><?= $stats['pending'] ?></div>
      <div class="stat-label"><i class="fas fa-clock"></i> Pending Release</div>
    </div>
    <div class="stat-box">
      <div class="stat-number" style="color: #28a745;"><?= $stats['released'] ?></div>
      <div class="stat-label"><i class="fas fa-globe"></i> Released</div>
    </div>
    <div class="stat-box">
      <div class="stat-number" style="color: #6c757d;"><?= $stats['archived'] ?></div>
      <div class="stat-label"><i class="fas fa-archive"></i> Archived</div>
    </div>
    <div class="stat-box">
      <div class="stat-number" style="color: #17a2b8;"><?= $stats['priced'] ?></div>
      <div class="stat-label"><i class="fas fa-tag"></i> Priced</div>
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
                <tr>
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

</body>
</html>