<?php
require_once '../session_helper.php';
startRoleSession('superadmin');
include '../db_connect.php';
include '../log_activity.php'; 

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ../LoginPage/loginPage.php');
    exit();
}


$id = $_GET['id'];
$user = $conn->query("SELECT firstname, lastname, email FROM users WHERE id=$id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $conn->query("UPDATE users SET password='$new' WHERE id=$id");
    
    // Log the activity
    $action = "Changed password for user: " . $user['firstname'] . " " . $user['lastname'];
    logActivity($conn, $_SESSION['id'], $action, 'Manage Users');
    
    header("Location: manageUsers.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Change Password</title>
<link rel="stylesheet" href="../css/superadmin.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="header">
  <div class="header-left">
    <img src="../Pictures/Carmax_logo.jpg" class="logo" alt="CarMax Logo">
    <div class="header-title h5 mb-0">Change Password</div>
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
</div>

<main class="main-content">
  <div class="sap-card">
    <div class="sap-card-header">
      <i class="fas fa-key"></i> Change User Password
    </div>
    <div class="sap-card-body">
      <div class="user-info-box">
        <h5><i class="fas fa-user"></i> User Information</h5>
        <p><strong>Name:</strong> <?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']); ?></p>
      </div>

      <form method="POST">
        <div class="form-group">
          <label class="form-label">
            <i class="fas fa-lock"></i> New Password
          </label>
          <input type="password" name="password" class="form-control" placeholder="Enter new password" required>
          <small class="form-text text-muted">
            <i class="fas fa-info-circle"></i> The user will need to use this password on their next login
          </small>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-carmax-primary">
            <i class="fas fa-check"></i> Update Password
          </button>
          <a href="manageUsers.php" class="btn-carmax-secondary">
            <i class="fas fa-times"></i> Cancel
          </a>
        </div>
      </form>
    </div>
  </div>
</main>
</body>
</html>