<?php
require_once '../session_helper.php';
startRoleSession('superadmin');  

include '../db_connect.php';
include '../log_activity.php'; 

// Check if user is logged in and has correct role
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') { 
  header('Location: ../LoginPage/loginPage.php'); 
  exit(); 
}

// Initialize variables with proper null checks
$userName = $_SESSION['user_name'] ?? '';
$userRole = $_SESSION['role'] ?? '';
$user_id = $_SESSION['id'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $fname = trim($_POST['firstname'] ?? '');
    $lname = trim($_POST['lastname'] ?? '');
    $role  = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate required fields
    if (empty($email) || empty($fname) || empty($lname) || empty($role) || empty($password)) {
        $error = "All fields are required.";
    }
    // Validate email domain
    elseif (!preg_match('/@carmax\.com$/i', $email)) {
        $error = "Email must be from @carmax.com domain.";
    }
    // Validate first name (letters, spaces, periods, Roman numerals)
    elseif (!preg_match('/^[A-Za-z\s\.IIIVXLCDM]+$/u', $fname)) {
        $error = "First name can only contain letters, spaces, periods, and Roman numerals.";
    }
    // Validate last name (letters, spaces, periods, Roman numerals)
    elseif (!preg_match('/^[A-Za-z\s\.IIIVXLCDM]+$/u', $lname)) {
        $error = "Last name can only contain letters, spaces, periods, and Roman numerals.";
    }
    // Check if email already exists
    else {
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
            
            if ($stmt->execute()) {
                // Log the activity
                $action = "Created new user account: $fname $lname ($email) with role: $role";
                logActivity($conn, $_SESSION['id'], $action, 'Manage Users');
                
                header("Location: manageUsers.php");
                exit;
            } else {
                $error = "Error creating user: " . $conn->error;
            }
        }
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
<style>
/* Additional CSS for password toggle */
.password-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.password-wrapper .form-control {
    padding-right: 40px; /* Make space for the icon */
    width: 100%;
}

.password-wrapper #togglePassword {
    position: absolute;
    right: 12px;
    cursor: pointer;
    color: var(--text-gray);
    transition: color 0.3s;
    z-index: 10;
}

.password-wrapper #togglePassword:hover {
    color: var(--carmax-blue);
}
</style>
</head>
<body>
<div class="header">
  <div class="header-left">
    <img src="../Pictures/Carmax_logo.jpg" class="logo" alt="CarMax Logo">
    <div class="header-title h5 mb-0">Add New Account</div>
  </div>
  <div class="user-info">
    <i class="fas fa-user-circle" style="font-size: 24px;"></i>
    <span><?php echo htmlspecialchars($userName); ?> (Super Admin)</span>
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
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <form method="POST" onsubmit="return validateForm()">
        <div class="form-grid">
          <div class="form-group full-width">
            <label class="form-label">
              <i class="fas fa-envelope"></i> Email Address
            </label>
            <input type="email" name="email" class="form-control" placeholder="user@carmax.com" 
                   pattern="[a-zA-Z0-9._%+-]+@carmax\.com$" 
                   title="Email must end with @carmax.com" required
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <small class="form-text text-muted">Must be a @carmax.com email address</small>
          </div>

          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-user"></i> First Name
            </label>
            <input type="text" name="firstname" class="form-control" placeholder="John" 
                   pattern="[A-Za-z\s\.IIIVXLCDM]+" 
                   title="Only letters, spaces, periods, and Roman numerals allowed" required
                   value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>">
          </div>

          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-user"></i> Last Name
            </label>
            <input type="text" name="lastname" class="form-control" placeholder="Doe" 
                   pattern="[A-Za-z\s\.IIIVXLCDM]+" 
                   title="Only letters, spaces, periods, and Roman numerals allowed" required
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
    togglePassword.classList.toggle('fa-eye');
    togglePassword.classList.toggle('fa-eye-slash');
});

function validateForm() {
    const email = document.querySelector('input[name="email"]');
    const fname = document.querySelector('input[name="firstname"]');
    const lname = document.querySelector('input[name="lastname"]');
    
    if (!email.value.endsWith('@carmax.com')) {
        alert('Email must be from @carmax.com domain.');
        return false;
    }
    
    const nameRegex = /^[A-Za-z\s\.IIIVXLCDM]+$/;
    if (!nameRegex.test(fname.value)) {
        alert('First name contains invalid characters.');
        return false;
    }
    
    if (!nameRegex.test(lname.value)) {
        alert('Last name contains invalid characters.');
        return false;
    }
    
    return true;
}
</script>

</body>
</html>