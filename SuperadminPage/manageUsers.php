<?php
require_once '../session_helper.php';
startRoleSession('superadmin');
include '../db_connect.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ../LoginPage/loginPage.php');
    exit();
}


$users = $conn->query("SELECT * FROM users WHERE id != {$_SESSION['id']} ORDER BY role, lastname, firstname");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Accounts</title>
<link rel="stylesheet" href="../css/superadmin.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="header">
  <div class="header-left">
    <img src="../Pictures/Carmax_logo.jpg" class="logo" alt="CarMax Logo">
    <div class="header-title h5 mb-0">Manage Accounts</div>
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
    <a href="manageUsers.php" class="sidebar-item active">
        <i class="fas fa-users"></i><span>Manage Accounts</span>
    </a>
    <a href="viewAcquisition.php" class="sidebar-item">
        <i class="fas fa-car"></i><span>View Acquisition</span>
    </a>
    <a href="viewOperation.php" class="sidebar-item">
        <i class="fas fa-cogs"></i><span>View Operations</span>
    </a>
    <a href="viewInventory.php" class="sidebar-item">
        <i class="fas fa-warehouse"></i><span>View Inventory</span>
    </a>
</div>

<main class="main-content">
  <div class="sap-card">
    <div class="sap-card-header d-flex justify-content-between align-items-center">
      <span><i class="fas fa-users"></i> User Accounts</span>
      <a href="addUser.php" class="btn-carmax-primary">
        <i class="fas fa-plus"></i> Add Account
      </a>
    </div>
    <div class="sap-card-body">
      <div class="table-responsive">
        <table class="sap-table">
          <thead>
            <tr>
              <th><i class="fas fa-hashtag"></i> ID</th>
              <th><i class="fas fa-envelope"></i> Email</th>
              <th><i class="fas fa-user"></i> Name</th>
              <th><i class="fas fa-user-tag"></i> Role</th>
              <th><i class="fas fa-toggle-on"></i> Status</th>
              <th><i class="fas fa-cogs"></i> Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($u = $users->fetch_assoc()) { ?>
            <tr>
              <td><?= $u['id']; ?></td>
              <td><?= htmlspecialchars($u['email']); ?></td>
              <td><?= htmlspecialchars($u['firstname'] . ' ' . $u['lastname']); ?></td>
              <td>
                <span class="role-badge role-<?= strtolower($u['role']); ?>">
                  <?= ucfirst($u['role']); ?>
                </span>
              </td>
              <td>
                <span class="status-badge status-<?= strtolower($u['status']); ?>">
                  <?= ucfirst($u['status']); ?>
                </span>
              </td>
              <td>
                <a href="editUser.php?id=<?= $u['id']; ?>" class="btn-carmax-secondary btn-sm">
                  <i class="fas fa-edit"></i> Edit
                </a>
                <a href="changePassword.php?id=<?= $u['id']; ?>" class="btn-carmax-primary btn-sm">
                  <i class="fas fa-key"></i> Password
                </a>
              </td>
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