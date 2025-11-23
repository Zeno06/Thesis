<?php
require_once '../session_helper.php';
startRoleSession('superadmin');  

include '../db_connect.php';
include '../log_activity.php'; 

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') { 
  header('Location: ../LoginPage/loginPage.php'); 
  exit(); 
}

$userName = $_SESSION['user_name'];
$userRole = $_SESSION['role'];
$user_id = $_SESSION['id'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $fname = trim($_POST['firstname']);
    $lname = trim($_POST['lastname']);
    $role  = $_POST['role'];
    $password = $_POST['password'];

    // Check if email already exists
    $stmtCheck = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmtCheck->bind_param("s", $email);
    $stmtCheck->execute();
    $stmtCheck->store_result();
    
    if ($stmtCheck->num_rows > 0) {
        $error = "Email already exists. Please use a different email.";
    } else {
        $passHash  = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO users (email, password, role, firstname, lastname, status) VALUES (?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("sssss", $email, $passHash, $role, $fname, $lname);
        $stmt->execute();
        
        // Log the activity
        $action = "Created new user account: $fname $lname ($email) with role: $role";
        logActivity($conn, $_SESSION['id'], $action, 'Manage Users');
        
        header("Location: manageUsers.php");
        exit;
    }
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
        <i class="fas fa-car"></i><span>View Acquisition</span>
    </a>
    <a href="viewOperation.php" class="sidebar-item">
        <i class="fas fa-cogs"></i><span>View Operations</span>
    </a>
</div>

<main class="main-content">
  <div class="sap-card">
    <div class="sap-card-header">
      <i class="fas fa-user-plus"></i> Create New User Account
    </div>
    <div class="sap-card-body">
      <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <form method="POST">
        <div class="form-grid">
          <div class="form-group full-width">
            <label class="form-label">
              <i class="fas fa-envelope"></i> Email Address
            </label>
            <input type="email" name="email" class="form-control" placeholder="user@example.com" required
              value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
          </div>

          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-user"></i> First Name
            </label>
            <input type="text" name="firstname" class="form-control" placeholder="John" required
              value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>">
          </div>

          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-user"></i> Last Name
            </label>
            <input type="text" name="lastname" class="form-control" placeholder="Doe" required
              value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>">
          </div>

          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-user-tag"></i> Role
            </label>
            <select name="role" class="form-select" required>
              <option value="">Select Role</option>
              <option value="acquisition" <?php echo (isset($_POST['role']) && $_POST['role']=='acquisition')?'selected':''; ?>>Acquisition</option>
              <option value="operation" <?php echo (isset($_POST['role']) && $_POST['role']=='operation')?'selected':''; ?>>Operation</option>
            </select>
          </div>

        <div class="form-group">
          <label class="form-label">
            <i class="fas fa-lock"></i> Password
          </label>
          <div class="password-wrapper">
            <input type="password" name="password" class="form-control" placeholder="Enter password" id="passwordField" required>
            <i class="fas fa-eye" id="togglePassword"></i>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
const togglePassword = document.getElementById('togglePassword');
const passwordField = document.getElementById('passwordField');

togglePassword.addEventListener('click', () => {
    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordField.setAttribute('type', type);
    togglePassword.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
});
</script>

</body>
</html>

