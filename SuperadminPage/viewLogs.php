<?php
session_start();
include '../db_connect.php';

// Check if logged in and has correct role
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: /loginPage/loginPage.php');
    exit();
}

// Handle filters
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$dateFilter = $_GET['date'] ?? '';

$query = "
    SELECT activity_logs.*, users.firstname, users.lastname, users.role 
    FROM activity_logs 
    JOIN users ON activity_logs.user_id = users.id
    WHERE 1
";

if (!empty($search)) {
    $searchEscaped = $conn->real_escape_string($search);
    $query .= " AND (users.firstname LIKE '%$searchEscaped%' 
                     OR users.lastname LIKE '%$searchEscaped%'
                     OR activity_logs.action LIKE '%$searchEscaped%'
                     OR activity_logs.page LIKE '%$searchEscaped%')";
}

if (!empty($roleFilter)) {
    $roleEscaped = $conn->real_escape_string($roleFilter);
    $query .= " AND users.role = '$roleEscaped'";
}

if (!empty($dateFilter)) {
    $dateEscaped = $conn->real_escape_string($dateFilter);
    $query .= " AND DATE(activity_logs.timestamp) = '$dateEscaped'";
}

$query .= " ORDER BY activity_logs.timestamp DESC";

$logs = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Super Admin</title>
    <link rel="stylesheet" href="../css/superadmin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="header">
    <div class="header-left">
        <img src="../Pictures/Carmax_logo.jpg" class="logo" alt="CarMax Logo">
        <div class="header-title h5 mb-0">Activity Logs</div>
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

<main class="main-content">
    <div class="sap-card">
        <div class="sap-card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-clock"></i> All Activity Logs</span>
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control" placeholder="Search user or action..." value="<?= htmlspecialchars($search) ?>">
                
                <select name="role" class="form-select">
                    <option value="">All Roles</option>
                    <option value="superadmin" <?= $roleFilter === 'superadmin' ? 'selected' : '' ?>>Super Admin</option>
                    <option value="acquisition" <?= $roleFilter === 'acquisition' ? 'selected' : '' ?>>Acquisition</option>
                    <option value="operation" <?= $roleFilter === 'operation' ? 'selected' : '' ?>>Operation</option>
                </select>

                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($dateFilter) ?>">

                <button type="submit" class="btn-carmax-secondary">Filter</button>
            </form>
        </div>

        <div class="sap-card-body">
            <table class="sap-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Page</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs && $logs->num_rows > 0): ?>
                        <?php while ($log = $logs->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['firstname'] . ' ' . $log['lastname']); ?></td>
                                <td><?= ucfirst($log['role']); ?></td>
                                <td><?= htmlspecialchars($log['action']); ?></td>
                                <td><?= htmlspecialchars($log['page']); ?></td>
                                <td><?= date('M d, Y h:i A', strtotime($log['timestamp'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">No activity logs found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

</body>
</html>
