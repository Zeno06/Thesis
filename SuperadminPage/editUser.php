<?php
session_start();
include '../db_connect.php';
include '../log_activity.php'; 

if ($_SESSION['role'] !== 'superadmin') { header('Location: ../loginPage/loginPage.php'); exit; }

$id = $_GET['id'];
$user = $conn->query("SELECT * FROM users WHERE id=$id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role  = $_POST['role'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE users SET role=?, status=? WHERE id=?");
    $stmt->bind_param("ssi", $role, $status, $id);
    $stmt->execute();
    
    // Log the activity
    $action = "Updated user account: " . $user['firstname'] . " " . $user['lastname'] . " (Role: $role, Status: $status)";
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
<title>Edit Account</title>
<link rel="stylesheet" href="../css/superadmin.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body>
<div class="header">
  <div class="header-left">
    <img src="../Pictures/Carmax_logo.jpg" class="logo" alt="CarMax Logo">
    <div class="header-title h5 mb-0">Edit Account</div>
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
        <i class="fas fa-check-square"></i><span>View Acquisition</span>
    </a>
</div>

<main class="main-content">
  <div class="sap-card">
    <div class="sap-card-header">
      <i class="fas fa-user-edit"></i> Edit User Account
    </div>
    <div class="sap-card-body">
      <form method="POST">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-user"></i> First Name
            </label>
            <input type="text" value="<?= htmlspecialchars($user['firstname']); ?>" class="form-control" disabled style="background-color: #f5f5f5; cursor: not-allowed;">
            <small class="form-text text-muted">
              <i class="fas fa-lock"></i> Name cannot be edited
            </small>
          </div>

          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-user"></i> Last Name
            </label>
            <input type="text" value="<?= htmlspecialchars($user['lastname']); ?>" class="form-control" disabled style="background-color: #f5f5f5; cursor: not-allowed;">
            <small class="form-text text-muted">
              <i class="fas fa-lock"></i> Name cannot be edited
            </small>
          </div>

          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-user-tag"></i> Role
            </label>
            <select name="role" class="form-select" required>
              <option value="acquisition" <?= $user['role']=='acquisition'?'selected':''; ?>>Acquisition</option>
              <option value="operation" <?= $user['role']=='operation'?'selected':''; ?>>Operation</option>
              <option value="superadmin" <?= $user['role']=='superadmin'?'selected':''; ?>>Superadmin</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-toggle-on"></i> Status
            </label>
            <select name="status" class="form-select" required>
              <option value="active" <?= $user['status']=='active'?'selected':''; ?>>Active</option>
              <option value="inactive" <?= $user['status']=='inactive'?'selected':''; ?>>Inactive</option>
            </select>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-carmax-primary">
            <i class="fas fa-save"></i> Save Changes
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