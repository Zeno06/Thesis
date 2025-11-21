<?php
require_once '../session_helper.php';
startRoleSession('operation');  

include '../db_connect.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'operation') {
    header('Location: ../LoginPage/loginPage.php');
    exit();
}

$userName = $_SESSION['user_name'];
$userRole = $_SESSION['role'];
$user_id = $_SESSION['id'];

// Fetch distinct vehicles that have ordered parts
$query = "
    SELECT 
        va.acquisition_id,
        va.plate_number,
        va.vehicle_model,
        va.year_model,
        va.color
    FROM vehicle_acquisition va
    INNER JOIN acquisition_parts ap ON va.acquisition_id = ap.acquisition_id
    WHERE va.status IN ('Approved', 'Sent to Operations')
    GROUP BY va.acquisition_id
    ORDER BY va.approved_at DESC
";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parts Needed/Order - CarMax</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/operationPage.css">
    <style>
        .info-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            font-weight: 600;
            width: 200px;
            color: #555;
        }
        .info-value {
            flex: 1;
            color: #333;
        }
        .sap-table tr {
            cursor: pointer;
        }
        .sap-table tr:hover {
            background-color: #f6fef6;
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-left">
        <img src="../Pictures/Carmax_logo.jpg" alt="CarMax" class="logo">
        <div class="header-title fs-4 fw-bold">Parts Needed/Order</div>
    </div>
    <div class="user-info d-flex align-items-center gap-2">
        <i class="fas fa-user-circle" style="font-size: 24px;"></i>
        <span>
            <?php 
                $title = $userRole === 'operation' ? 'Operation Admin' : 'Super Admin';
                echo htmlspecialchars($userName) . " ($title)";
            ?>
        </span>
        <a href="../logout.php" style="margin-left:15px;color:white;text-decoration:none;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- Sidebar -->
<div class="sidebar">
    <a href="operationPage.php" class="sidebar-item">
        <i class="fas fa-inbox"></i><span>Received Acquisitions</span>
    </a>
    <a href="reconCost.php" class="sidebar-item">
        <i class="fas fa-wrench"></i><span>Recon Cost</span>
    </a>
    <a href="partsOrder.php" class="sidebar-item active">
        <i class="fas fa-tools"></i><span>Parts Needed/Order</span>
    </a>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="sap-card">
        <div class="sap-card-header">
            <i class="fas fa-tools"></i> Parts Needed/Order
        </div>
        <div class="sap-card-body">
            <table class="sap-table table table-hover">
                <thead class="table-success text-center">
                    <tr>
                        <th>Plate Number</th>
                        <th>Model</th>
                        <th>Year</th>
                        <th>Color</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr data-bs-toggle="modal" data-bs-target="#partsModal<?= $row['acquisition_id'] ?>" class="text-center">
                                <td><?= htmlspecialchars($row['plate_number']) ?></td>
                                <td><?= htmlspecialchars($row['vehicle_model']) ?></td>
                                <td><?= htmlspecialchars($row['year_model']) ?></td>
                                <td><?= htmlspecialchars($row['color']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">No ordered parts found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Modals for each vehicle -->
            <?php 
            if ($result && $result->num_rows > 0):
                $result->data_seek(0);
                while ($row = $result->fetch_assoc()):
            ?>
            <div class="modal fade" id="partsModal<?= $row['acquisition_id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title"><i class="fas fa-tools"></i> Ordered Parts - <?= htmlspecialchars($row['plate_number']) ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <h6 class="text-primary fw-bold mb-3"><i class="fas fa-car"></i> Vehicle Information</h6>
                            <div class="mb-4">
                                <div class="info-row"><div class="info-label">Plate Number:</div><div class="info-value"><?= htmlspecialchars($row['plate_number']) ?></div></div>
                                <div class="info-row"><div class="info-label">Model:</div><div class="info-value"><?= htmlspecialchars($row['vehicle_model']) ?></div></div>
                                <div class="info-row"><div class="info-label">Year:</div><div class="info-value"><?= htmlspecialchars($row['year_model']) ?></div></div>
                                <div class="info-row"><div class="info-label">Color:</div><div class="info-value"><?= htmlspecialchars($row['color']) ?></div></div>
                            </div>

                            <h6 class="text-primary fw-bold mb-3"><i class="fas fa-cogs"></i> Ordered Parts List</h6>
                            <table class="table table-bordered table-striped align-middle">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>Part Name</th>
                                        <th>Price</th>
                                        <th>Remarks</th>
                                        <th>Status</th>
                                        <th>Ordered By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $partsQuery = $conn->prepare("
                                        SELECT part_name, part_price, part_remarks, ordered_by
                                        FROM acquisition_parts
                                        WHERE acquisition_id = ?
                                        ORDER BY created_at DESC
                                    ");
                                    $partsQuery->bind_param("i", $row['acquisition_id']);
                                    $partsQuery->execute();
                                    $partsResult = $partsQuery->get_result();

                                    if ($partsResult->num_rows > 0):
                                        while ($part = $partsResult->fetch_assoc()):
                                    ?>
                                            <tr class="text-center">
                                                <td><?= htmlspecialchars($part['part_name']) ?></td>
                                                <td><?= $part['part_price'] ? "₱" . number_format($part['part_price'], 2) : '<span class="text-muted">Not Set</span>' ?></td>
                                                <td><?= htmlspecialchars($part['part_remarks'] ?? 'No remarks') ?></td>
                                                <td>
                                                    <span class="badge bg-success"><i class="fas fa-check"></i> Ordered</span>
                                                </td>
                                                <td><?= htmlspecialchars($part['ordered_by'] ?? 'N/A') ?></td>
                                            </tr>
                                    <?php
                                        endwhile;
                                    else:
                                        echo '<tr><td colspan="5" class="text-center text-muted">No parts found for this vehicle.</td></tr>';
                                    endif;
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; endif; ?>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
