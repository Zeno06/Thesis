<?php
require_once '../session_helper.php';
startRoleSession('superadmin'); 

include '../db_connect.php';

// Check if logged in and has correct role
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ../LoginPage/loginPage.php');
    exit();
}

// Handle filters
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$dateFilter = $_GET['date'] ?? '';

$filteredResults = [];

// Get Activity Logs
$activityQuery = "
    SELECT 
        al.*, 
        u.firstname, 
        u.lastname, 
        u.role,
        al.remarks as display_remarks,
        'activity_log' as remark_source,
        NULL as plate_number
    FROM activity_logs al
    JOIN users u ON al.user_id = u.id
    WHERE 1=1
";

if (!empty($search)) {
    $activityQuery .= " AND (u.firstname LIKE '%$search%' OR u.lastname LIKE '%$search%' OR al.action LIKE '%$search%' OR al.page LIKE '%$search%' OR al.remarks LIKE '%$search%')";
}

if (!empty($roleFilter)) {
    $activityQuery .= " AND u.role = '$roleFilter'";
}

if (!empty($dateFilter)) {
    $activityQuery .= " AND DATE(al.timestamp) = '$dateFilter'";
}

$activityQuery .= " ORDER BY al.timestamp DESC";

$activityResult = $conn->query($activityQuery);
if ($activityResult && $activityResult->num_rows > 0) {
    while ($row = $activityResult->fetch_assoc()) {
        $filteredResults[] = $row;
    }
}

// Get Vehicle Acquisition Remarks
$vehicleQuery = "
    SELECT 
        NULL as log_id,
        va.created_by as user_id,
        CONCAT('Vehicle: ', va.plate_number, ' - ', va.status) as action,
        'Vehicle Acquisition' as page,
        va.created_at as timestamp,
        u.firstname,
        u.lastname,
        u.role,
        va.remarks as display_remarks,
        'vehicle_acquisition' as remark_source,
        va.plate_number,
        va.status
    FROM vehicle_acquisition va
    JOIN users u ON va.created_by = u.id
    WHERE va.remarks IS NOT NULL AND va.remarks != ''
";

if (!empty($search)) {
    $vehicleQuery .= " AND (u.firstname LIKE '%$search%' OR u.lastname LIKE '%$search%' OR va.plate_number LIKE '%$search%' OR va.remarks LIKE '%$search%')";
}

if (!empty($roleFilter)) {
    $vehicleQuery .= " AND u.role = '$roleFilter'";
}

if (!empty($dateFilter)) {
    $vehicleQuery .= " AND DATE(va.created_at) = '$dateFilter'";
}

$vehicleQuery .= " ORDER BY va.created_at DESC";

$vehicleResult = $conn->query($vehicleQuery);
if ($vehicleResult && $vehicleResult->num_rows > 0) {
    while ($row = $vehicleResult->fetch_assoc()) {
        $filteredResults[] = $row;
    }
}

// Get Quality Check Remarks
$qualityQuery = "
    SELECT 
        NULL as log_id,
        NULL as user_id,
        CONCAT('Quality Check: ', va.plate_number) as action,
        'Quality Check' as page,
        va.quality_checked_at as timestamp,
        va.quality_checked_by as firstname,
        '' as lastname,
        'acquisition' as role,
        va.remarks as display_remarks,
        'quality_check' as remark_source,
        va.plate_number,
        va.status
    FROM vehicle_acquisition va
    WHERE va.remarks IS NOT NULL AND va.remarks != ''
    AND va.quality_checked_by IS NOT NULL
    AND va.quality_checked_at IS NOT NULL
";

if (!empty($search)) {
    $qualityQuery .= " AND (va.quality_checked_by LIKE '%$search%' OR va.plate_number LIKE '%$search%' OR va.remarks LIKE '%$search%')";
}

if (!empty($dateFilter)) {
    $qualityQuery .= " AND DATE(va.quality_checked_at) = '$dateFilter'";
}

$qualityQuery .= " ORDER BY timestamp DESC";

$qualityResult = $conn->query($qualityQuery);
if ($qualityResult && $qualityResult->num_rows > 0) {
    while ($row = $qualityResult->fetch_assoc()) {
        $filteredResults[] = $row;
    }
}

// Sort all results by timestamp
usort($filteredResults, function($a, $b) {
    $timeA = !empty($a['timestamp']) ? strtotime($a['timestamp']) : 0;
    $timeB = !empty($b['timestamp']) ? strtotime($b['timestamp']) : 0;
    return $timeB - $timeA;
});
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
    <style>
        .remarks-cell {
            max-width: 250px;
            cursor: pointer;
            position: relative;
        }
        .remarks-preview {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }
        .remarks-cell:hover .remarks-preview {
            text-decoration: underline;
        }
        .modal-remarks {
            margin-top: 10px;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 500px;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        .remark-source-badge {
            font-size: 0.75rem;
            padding: 3px 8px;
            margin-left: 6px;
            border-radius: 12px;
            font-weight: 600;
        }
        .remarks-info-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .remarks-info-section .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        .remarks-info-section .info-item i {
            width: 20px;
            margin-right: 8px;
        }
        .remarks-info-section .info-item strong {
            margin-right: 8px;
            min-width: 100px;
        }
        .remarks-modal {
            margin: 0 auto !important;
            padding-top: 250px; 
            overflow: hidden;
        }

    </style>
</head>
<body>
<div class="header">
    <div class="header-left">
        <img src="../Pictures/Carmax_logo.jpg" class="logo" alt="CarMax Logo">
        <div class="header-title h5 mb-0">Activity Logs & Remarks</div>
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
        <i class="fas fa-car"></i><span>View Acquisition</span>
    </a>
    <a href="viewOperation.php" class="sidebar-item ">
        <i class="fas fa-cogs"></i><span>View Operations</span>
    </a>
</div>

<main class="main-content">
    <div class="sap-card">
        <div class="sap-card-header">
            <div class="header-title-section">
                <span><i class="fas fa-clock"></i> All Activity Logs & Remarks (<?= count($filteredResults) ?> records)</span>
            </div>
            <form method="GET" class="filter-form-horizontal">
                <input type="text" name="search" class="form-control" placeholder="Search user, action, or remarks..." value="<?= htmlspecialchars($search) ?>">
                
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
                            <th><i class="fas fa-comment"></i> Remarks</th>
                            <th><i class="fas fa-clock"></i> Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($filteredResults)): ?>
                            <?php 
                            $counter = 0;
                            foreach ($filteredResults as $log): 
                                $counter++;
                                // Format user display name
                                $userDisplay = '';
                                if ($log['remark_source'] === 'quality_check') {
                                    $userDisplay = $log['firstname'];
                                } else {
                                    $userDisplay = $log['firstname'] . ' ' . $log['lastname'];
                                }
                                
                                $sourceBadgeText = match($log['remark_source']) {
                                    'activity_log' => 'System Log',
                                    'vehicle_acquisition' => 'Vehicle',
                                    'quality_check' => 'Quality',
                                    default => 'Other'
                                };
                                
                                $sourceBadgeColor = match($log['remark_source']) {
                                    'activity_log' => 'bg-info',
                                    'vehicle_acquisition' => 'bg-primary',
                                    'quality_check' => 'bg-success',
                                    default => 'bg-secondary'
                                };
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($userDisplay); ?></td>
                                    <td>
                                        <span class="role-badge role-<?= strtolower($log['role']); ?>">
                                            <?= ucfirst($log['role']); ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($log['action']); ?></td>
                                    <td><?= htmlspecialchars($log['page']); ?></td>
                                    <td>
                                        <?php if (!empty($log['display_remarks'])): ?>
                                            <div class="remarks-cell" data-bs-toggle="modal" data-bs-target="#remarksModal<?= $counter ?>">
                                                <span class="remarks-preview">
                                                    <i class="fas fa-comment-dots text-primary"></i>
                                                    <?= htmlspecialchars(substr($log['display_remarks'], 0, 50)) ?>
                                                    <?= strlen($log['display_remarks']) > 50 ? '...' : '' ?>
                                                    
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($log['timestamp'])): ?>
                                            <?= date('M d, Y h:i A', strtotime($log['timestamp'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">
                                    <div class="no-data">
                                        <i class="fas fa-inbox fa-3x"></i>
                                        <p>No activity logs or remarks found.</p>
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

<!-- Remarks Modals -->
<?php 
if (!empty($filteredResults)):
    $counter = 0;
    foreach ($filteredResults as $log): 
        $counter++;
        if (!empty($log['display_remarks'])):
            // Format user display name
            $userDisplay = '';
            if ($log['remark_source'] === 'quality_check') {
                $userDisplay = $log['firstname'];
            } else {
                $userDisplay = $log['firstname'] . ' ' . $log['lastname'];
            }
            
            // Source badge
            $sourceBadgeText = match($log['remark_source']) {
                'activity_log' => 'System Log',
                'vehicle_acquisition' => 'Vehicle Acquisition',
                'quality_check' => 'Quality Check',
                default => 'Other'
            };
            
            $sourceBadgeColor = match($log['remark_source']) {
                'activity_log' => 'bg-info',
                'vehicle_acquisition' => 'bg-primary',
                'quality_check' => 'bg-success',
                default => 'bg-secondary'
            };
?>
<div class="modal fade remarks-modal" id="remarksModal<?= $counter ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white">
                <h5 class="modal-title">
                    <i class="fas fa-comment-alt"></i> Remarks Details
                    <span class="badge <?= $sourceBadgeColor ?> ms-2"><?= $sourceBadgeText ?></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="remarks-info-section">
                    <div class="info-item">
                        <i class="fas fa-user"></i>
                        <strong>User:</strong> <?= htmlspecialchars($userDisplay); ?>
                        <span class="badge bg-secondary ms-2"><?= ucfirst($log['role']); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-tasks"></i>
                        <strong>Action:</strong> <?= htmlspecialchars($log['action']); ?>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-file"></i>
                        <strong>Page:</strong> <?= htmlspecialchars($log['page']); ?>
                    </div>
                    <?php if (!empty($log['plate_number'])): ?>
                    <div class="info-item">
                        <i class="fas fa-id-card"></i>
                        <strong>Plate Number:</strong> <?= htmlspecialchars($log['plate_number']); ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($log['timestamp'])): ?>
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <strong>Timestamp:</strong> <?= date('M d, Y h:i A', strtotime($log['timestamp'])); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <h6 class="mb-3">
                    <i class="fas fa-comment-dots text-primary"></i> <strong>Remarks Content:</strong>
                </h6>
                <div class="modal-remarks">
                    <?= nl2br(htmlspecialchars($log['display_remarks'])); ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>
<?php 
        endif;
    endforeach; 
endif; 
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>