<?php
session_start();
include '../db_connect.php';
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ../loginPage/loginPage.php');
    exit();
}

$users = $conn->query("SELECT * FROM users ORDER BY role");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
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
    <a href="viewLogs.php" class="sidebar-item">
        <i class ="fas fa-list"></i> View Logs
    </a>
    <a href="manageUsers.php" class="sidebar-item active">
        <i class="fas fa-list"></i><span>Manage Accounts</span>
    </a>
    <a href="viewAcquisition.php" class="sidebar-item">
        <i class="fas fa-check-square"></i><span>View Acquisition</span>
    </a>
    <a href="viewSales.php" class="sidebar-item">
       <i class="fas fa-warehouse"></i><span>Sales Reports</span>
    </a>
</div>

<main class="main-content">
  <div class="sap-card">
    <div class="sap-card-header d-flex justify-content-between align-items-center">
      <span>User Accounts</span>
      <a href="addUser.php" class="btn-carmax-primary">+ Add Account</a>
    </div>
    <div class="sap-card-body">
      <table class="sap-table">
        <thead>
          <tr>
            <th>ID</th><th>Email</th><th>Name</th><th>Role</th><th>Status</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($u = $users->fetch_assoc()) { ?>
          <tr>
            <td><?= $u['id']; ?></td>
            <td><?= htmlspecialchars($u['email']); ?></td>
            <td><?= htmlspecialchars($u['firstname'] . ' ' . $u['lastname']); ?></td>
            <td><?= ucfirst($u['role']); ?></td>
            <td><?= ucfirst($u['status']); ?></td>
            <td>
              <a href="editUser.php?id=<?= $u['id']; ?>" class="btn-carmax-secondary btn-sm">Edit</a>
              <a href="changePassword.php?id=<?= $u['id']; ?>" class="btn-carmax-primary btn-sm">Password</a>
            </td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</body>
</html>
