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
    WHERE 1=1
";

$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND (users.firstname LIKE ? 
                     OR users.lastname LIKE ?
                     OR activity_logs.action LIKE ?
                     OR activity_logs.page LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ssss';
}

if (!empty($roleFilter)) {
    $query .= " AND users.role = ?";
    $params[] = $roleFilter;
    $types .= 's';
}

if (!empty($dateFilter)) {
    $query .= " AND DATE(activity_logs.timestamp) = ?";
    $params[] = $dateFilter;
    $types .= 's';
}

$query .= " ORDER BY activity_logs.timestamp DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs</title>
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
    <a href="superadminPage.php" class="sidebar-item active">
        <i class="fas fa-list"></i> View Logs
    </a>
    <a href="manageUsers.php" class="sidebar-item">
        <i class="fas fa-users"></i><span>Manage Accounts</span>
    </a>
    <a href="viewAcquisition.php" class="sidebar-item">
        <i class="fas fa-check-square"></i><span>View Acquisition</span>
    </a>
</div>

<main class="main-content">
    <div class="sap-card">
        <div class="sap-card-header">
            <div class="header-title-section">
                <span><i class="fas fa-clock"></i> All Activity Logs</span>
            </div>
            <form method="GET" class="filter-form-horizontal">
                <input type="text" name="search" class="form-control" placeholder="Search user or action..." value="<?= htmlspecialchars($search) ?>">
                
                <select name="role" class="form-select">
                    <option value="">All Roles</option>
                    <option value="superadmin" <?= $roleFilter === 'superadmin' ? 'selected' : '' ?>>Super Admin</option>
                    <option value="acquisition" <?= $roleFilter === 'acquisition' ? 'selected' : '' ?>>Acquisition</option>
                    <option value="operation" <?= $roleFilter === 'operation' ? 'selected' : '' ?>>Operation</option>
                </select>

                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($dateFilter) ?>">

                <button type="submit" class="btn-carmax-secondary"><i class="fas fa-filter"></i> Filter</button>
                <?php if (!empty($search) || !empty($roleFilter) || !empty($dateFilter)): ?>
                    <a href="superadminPage.php" class="btn-carmax-primary"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="sap-card-body">
            <div class="table-responsive">
                <table class="sap-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user"></i> User</th>
                            <th><i class="fas fa-user-tag"></i> Role</th>
                            <th><i class="fas fa-tasks"></i> Action</th>
                            <th><i class="fas fa-file"></i> Page</th>
                            <th><i class="fas fa-clock"></i> Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($logs && $logs->num_rows > 0): ?>
                            <?php while ($log = $logs->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['firstname'] . ' ' . $log['lastname']); ?></td>
                                    <td>
                                        <span class="role-badge role-<?= strtolower($log['role']); ?>">
                                            <?= ucfirst($log['role']); ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($log['action']); ?></td>
                                    <td><?= htmlspecialchars($log['page']); ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($log['timestamp'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">
                                    <div class="no-data">
                                        <i class="fas fa-inbox fa-3x"></i>
                                        <p>No activity logs found.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

</body>
</html>