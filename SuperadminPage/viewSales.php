<?php
session_start();
include '../db_connect.php';
if ($_SESSION['role'] !== 'superadmin') { header('Location: ../loginPage/loginPage.php'); exit; }
$sales = $conn->query("SELECT * FROM vehicle_sales ORDER BY date_sold DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sales Reports</title>
<link rel="stylesheet" href="../css/superadmin.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body>
<div class="header">
  <div class="header-left">
    <img src="../Pictures/Carmax_logo.jpg" class="logo" alt="CarMax Logo">
    <div class="header-title h5 mb-0">View Sales</div>
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
    <a href="superadminPage.php" class="sidebar-item ">
        <i class ="fas fa-list"></i> View Logs
    </a>
    <a href="manageUsers.php" class="sidebar-item">
        <i class="fas fa-users"></i><span>Manage Accounts</span>
    </a>
    <a href="viewAcquisition.php" class="sidebar-item">
        <i class="fas fa-check-square"></i><span>View Acquisition</span>
    </a>
    <a href="viewSales.php" class="sidebar-item active">
       <i class="fas fa-warehouse"></i><span>Sales Reports</span>
    </a>
</div>

<main class="main-content">
  <div class="sap-card">
    <div class="sap-card-header d-flex justify-content-between align-items-center">
      <span><i class="fas fa-warehouse"></i> Sales Reports</span>
    </div>
    <div class="sap-card-body">
      <div class="table-responsive">
        <table class="sap-table">
          <thead>
            <tr>
              <th><i class="fas fa-hashtag"></i> ID</th>
              <th><i class="fas fa-id-card"></i> Plate</th>
              <th><i class="fas fa-user"></i> Customer</th>
              <th><i class="fas fa-tag"></i> Price</th>
              <th><i class="fas fa-coins"></i> Profit</th>
              <th><i class="fas fa-user-tie"></i> Agent</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = $sales->fetch_assoc()) { ?>
              <tr>
                <td><?= htmlspecialchars($row['sale_id'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['plate_number'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['customer_name'] ?? '') ?></td>
                <td>₱<?= number_format($row['selling_price'] ?? 0, 2) ?></td>
                <td>₱<?= number_format($row['gross_profit'] ?? 0, 2) ?></td>
                <td><?= htmlspecialchars($row['agent_name'] ?? '') ?></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>
</body>
</html>
