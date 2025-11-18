<?php
session_start();
include '../db_connect.php';
if ($_SESSION['role'] !== 'superadmin') { header('Location: ../loginPage/loginPage.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $fname = $_POST['firstname'];
    $lname = $_POST['lastname'];
    $role  = $_POST['role'];
    $pass  = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (email, password, role, firstname, lastname, status) VALUES (?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("sssss", $email, $pass, $role, $fname, $lname);
    $stmt->execute();
    header("Location: manageUsers.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Account</title>
<link rel="stylesheet" href="../css/superadmin.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="header">
  <div class="header-left">
    <img src="../Pictures/Carmax_logo.jpg" class="logo" alt="CarMax Logo">
    <div class="header-title h5 mb-0">Add New Account</div>
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
      <i class="fas fa-user-plus"></i> Create New User Account
    </div>
    <div class="sap-card-body">
      <form method="POST">
        <div class="form-grid">
          <div class="form-group full-width">
            <label class="form-label">
              <i class="fas fa-envelope"></i> Email Address
            </label>
            <input type="email" name="email" class="form-control" placeholder="user@example.com" required>
          </div>

          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-user"></i> First Name
            </label>
            <input type="text" name="firstname" class="form-control" placeholder="John" required>
          </div>

          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-user"></i> Last Name
            </label>
            <input type="text" name="lastname" class="form-control" placeholder="Doe" required>
          </div>

          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-user-tag"></i> Role
            </label>
            <select name="role" class="form-select" required>
              <option value="">Select Role</option>
              <option value="acquisition">Acquisition</option>
              <option value="operation">Operation</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-lock"></i> Password
            </label>
            <input type="text" name="password" class="form-control" placeholder="Enter password" required>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-carmax-primary">
            <i class="fas fa-user-plus"></i> Create Account
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