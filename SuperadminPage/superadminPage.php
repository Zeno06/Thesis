<?php
session_start();

// Check if logged in and has correct role
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: /loginPage/loginPage.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    <link rel="stylesheet" href="../css/superadmin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="header">
    <div class="header-left">
        <img src="../Pictures/Carmax_logo.jpg" class="logo" alt="CarMax Logo">
        <div class="header-title h5 mb-0">Super Admin Dashboard</div>
    </div>
    
    <div class="user-info">
        <i class="fas fa-user-circle" style="font-size: 24px;"></i>
        <span>
            <?php 
                $role = $_SESSION['role'];
                $title = match($role) {
                    'acquisition' => 'Acquisition Admin',
                    'operation' => 'Operation Admin',
                    'superadmin' => 'Super Admin',
                    default => ucfirst($role)
                };
                echo htmlspecialchars($_SESSION['user_name']) . " ($title)";
            ?>
        </span>
        <a href="../logout.php" style="margin-left: 15px; color: white; text-decoration: none;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>


<div class="sidebar">
    <a href="viewLogs.php" class="sidebar-item active">
        <i class ="fas fa-list"></i> View Logs
    </a>
    <a href="manageUsers.php" class="sidebar-item">
        <i class="fas fa-list"></i><span>Manage Accounts</span>
    </a>
    <a href="viewAcquisition.php" class="sidebar-item">
        <i class="fas fa-check-square"></i><span>View Acquisition</span>
    </a>
    <a href="viewSales.php" class="sidebar-item">
       <i class="fas fa-warehouse"></i><span>Sales Reports</span>
    </a>
</div>


</body>
</html>
