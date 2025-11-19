<?php
session_start();
include '../db_connect.php';
if ($_SESSION['role'] !== 'superadmin') { header('Location: ../loginPage/loginPage.php'); exit; }

// Get filter parameters
$searchQuery = $_GET['search'] ?? '';
$makeFilter = $_GET['make'] ?? '';

// Build query with filters
$query = "SELECT * FROM vehicle_inventory WHERE 1=1";

if (!empty($searchQuery)) {
    // basic escaping to reduce SQL injection risk (keeps style consistent with your original code)
    $escSearch = $conn->real_escape_string($searchQuery);
    $query .= " AND (plate_number LIKE '%$escSearch%' OR model LIKE '%$escSearch%' OR make LIKE '%$escSearch%')";
}

if (!empty($makeFilter)) {
    $escMake = $conn->real_escape_string($makeFilter);
    $query .= " AND make = '$escMake'";
}

$query .= " ORDER BY created_at DESC";
$inventory = $conn->query($query);

// Get statistics (removed total_actual and avg_cost)
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(projected_repair_cost) as total_projected
FROM vehicle_inventory";
$stats = $conn->query($statsQuery)->fetch_assoc();

// Get unique makes for filter
$makesQuery = "SELECT DISTINCT make FROM vehicle_inventory ORDER BY make";
$makes = $conn->query($makesQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Inventory</title>
<link rel="stylesheet" href="../css/superadmin.css">
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
        display:flex;
        gap:10px;
        align-items:center;
        margin:0;
    }
    .filter-form-horizontal .form-control,
    .filter-form-horizontal .form-select {
        width: 220px;
    }
</style>
</head>

<body>
<div class="header">
  <div class="header-left">
    <img src="../Pictures/Carmax_logo.jpg" class="logo" alt="CarMax Logo">
    <div class="header-title h5 mb-0">View Inventory</div>
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
    <a href="viewOperation.php" class="sidebar-item">
        <i class="fas fa-cogs"></i><span>View Operations</span>
    </a>
    <a href="viewInventory.php" class="sidebar-item active">
        <i class="fas fa-warehouse"></i><span>View Inventory</span>
    </a>
</div>

<main class="main-content">
  <!-- Statistics Dashboard (only total inventory & total projected cost) -->
  <div class="stats-grid">
    <div class="stat-box">
      <div class="stat-number"><?= number_format($stats['total'] ?? 0) ?></div>
      <div class="stat-label"><i class="fas fa-warehouse"></i> Total Inventory</div>
    </div>
    <div class="stat-box">
      <div class="stat-number" style="color: #ffc107;">₱<?= number_format($stats['total_projected'] ?? 0, 2) ?></div>
      <div class="stat-label"><i class="fas fa-calculator"></i> Total Projected Cost</div>
    </div>
  </div>

  <div class="sap-card">
    <div class="sap-card-header">
      <span><i class="fas fa-warehouse"></i> Inventory Management</span>
      <form method="GET" class="filter-form-horizontal">
        <input type="text" name="search" class="form-control" placeholder="Search plate, make or model..." value="<?= htmlspecialchars($searchQuery) ?>">
        
        <select name="make" class="form-select">
          <option value="">All Makes</option>
          <?php 
            // reset pointer if already iterated
            if ($makes) {
                $makes->data_seek(0);
                while($make = $makes->fetch_assoc()):
          ?>
            <option value="<?= htmlspecialchars($make['make']) ?>" <?= $makeFilter === $make['make'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($make['make']) ?>
            </option>
          <?php 
                endwhile;
            }
          ?>
        </select>

        <button type="submit" class="btn-carmax-secondary"><i class="fas fa-filter"></i> Filter</button>
        <?php if (!empty($searchQuery) || !empty($makeFilter)): ?>
          <a href="viewInventory.php" class="btn-carmax-primary"><i class="fas fa-times"></i> Clear</a>
        <?php endif; ?>
      </form>
    </div>
    <div class="sap-card-body">
      <div class="table-responsive">
        <table class="sap-table">
          <thead>
            <tr>
              <th><i class="fas fa-hashtag"></i> ID</th>
              <th><i class="fas fa-id-card"></i> Plate</th>
              <th><i class="fas fa-car"></i> Make/Model</th>
              <th><i class="fas fa-calendar"></i> Year</th>
              <th><i class="fas fa-palette"></i> Color</th>
              <th><i class="fas fa-store"></i> Supplier</th>
              <th><i class="fas fa-calculator"></i> Projected Cost</th>
              <th><i class="fas fa-user"></i> Checked By</th>
              <th><i class="fas fa-clock"></i> Date Added</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($inventory && $inventory->num_rows > 0): ?>
              <?php while($row = $inventory->fetch_assoc()): ?>
                <tr>
                  <td><?= $row['inventory_id']; ?></td>
                  <td><?= htmlspecialchars($row['plate_number']); ?></td>
                  <td><?= htmlspecialchars(($row['make'] ?? '') . ' ' . ($row['model'] ?? '')); ?></td>
                  <td><?= htmlspecialchars($row['year_model']); ?></td>
                  <td><?= htmlspecialchars($row['color']); ?></td>
                  <td><?= htmlspecialchars($row['supplier']); ?></td>
                  <td>
                    <span class="badge bg-warning text-dark">₱<?= number_format($row['projected_repair_cost'] ?? 0, 2); ?></span>
                  </td>
                  <td><?= htmlspecialchars($row['approved_checked_by'] ?? 'N/A'); ?></td>
                  <td><?= !empty($row['created_at']) ? date('M d, Y', strtotime($row['created_at'])) : 'N/A'; ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="text-center">
                  <div class="no-data">
                    <i class="fas fa-inbox fa-3x"></i>
                    <p>No inventory records found.</p>
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
