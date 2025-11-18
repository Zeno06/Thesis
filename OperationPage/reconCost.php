<?php
session_start();
include '../db_connect.php';

// ✅ Check login session and role
if (!isset($_SESSION['id']) || ($_SESSION['role'] !== 'operation' && $_SESSION['role'] !== 'superadmin')) {
    header('Location: ../LoginPage/loginPage.php');
    exit();
}

$userName = $_SESSION['user_name'];
$userRole = $_SESSION['role'];

// ✅ Fetch distinct vehicles that have recon data (issues or parts)
$query = "
    SELECT 
        va.acquisition_id,
        va.plate_number,
        va.vehicle_model,
        va.year_model,
        va.color,
        va.acquired_price,
        va.status,
        va.approved_by,
        va.approved_at,
        va.quality_checked_by,
        va.quality_checked_at
    FROM vehicle_acquisition va
    LEFT JOIN acquisition_issues ai ON va.acquisition_id = ai.acquisition_id
    LEFT JOIN acquisition_parts ap ON va.acquisition_id = ap.acquisition_id
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
    <title>Reconditioning Costs - CarMax</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/operationPage.css">
    <style>
        .info-row { display: flex; padding: 8px 0; border-bottom: 1px solid #eee; }
        .info-label { font-weight: 600; width: 200px; color: #555; }
        .info-value { flex: 1; color: #333; }
        .sap-table tr { cursor: pointer; }
        .sap-table tr:hover { background-color: #f6fef6; }
        .cost-breakdown-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .cost-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .cost-item.total {
            font-weight: bold;
            color: #0d6efd;
            border-top: 2px solid #0d6efd;
            margin-top: 10px;
            padding-top: 15px;
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-left">
        <img src="../Pictures/Carmax_logo.jpg" alt="CarMax" class="logo">
        <div class="header-title fs-4 fw-bold">Reconditioning Costs</div>
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
    <a href="reconCost.php" class="sidebar-item active">
        <i class="fas fa-wrench"></i><span>Recon Cost</span>
    </a>
    <a href="partsOrder.php" class="sidebar-item">
        <i class="fas fa-tools"></i><span>Parts Needed/Order</span>
    </a>
 
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="sap-card">
        <div class="sap-card-header">
            <i class="fas fa-wrench"></i> Vehicle Reconditioning Costs
        </div>
        <div class="sap-card-body">
            <table class="sap-table table table-hover">
                <thead class="table-success text-center">
                    <tr>
                        <th>Plate Number</th>
                        <th>Model</th>
                        <th>Year</th>
                        <th>Color</th>
                        <th>Acquired Price</th>
                        <th>Issues Cost</th>
                        <th>Parts Cost</th>
                        <th>Total Recon Cost</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): 
                            // Fetch totals
                            $issuesTotal = $conn->query("SELECT SUM(COALESCE(issue_price,0)) as total FROM acquisition_issues WHERE acquisition_id = {$row['acquisition_id']}")->fetch_assoc()['total'] ?? 0;
                            $partsTotal  = $conn->query("SELECT SUM(COALESCE(part_price,0)) as total FROM acquisition_parts WHERE acquisition_id = {$row['acquisition_id']}")->fetch_assoc()['total'] ?? 0;
                            $acquiredPrice = $row['acquired_price'] ?? 0;
                            $totalReconCost = $issuesTotal + $partsTotal;
                        ?>
                            <tr data-bs-toggle="modal" data-bs-target="#reconModal<?= $row['acquisition_id'] ?>" class="text-center">
                                <td><?= htmlspecialchars($row['plate_number']) ?></td>
                                <td><?= htmlspecialchars($row['vehicle_model']) ?></td>
                                <td><?= htmlspecialchars($row['year_model']) ?></td>
                                <td><?= htmlspecialchars($row['color']) ?></td>
                                <td>₱<?= number_format($acquiredPrice, 2) ?></td>
                                <td><?= $issuesTotal > 0 ? "₱" . number_format($issuesTotal, 2) : '<span class="text-muted">₱0.00</span>' ?></td>
                                <td><?= $partsTotal > 0 ? "₱" . number_format($partsTotal, 2) : '<span class="text-muted">₱0.00</span>' ?></td>
                                <td><strong class="text-primary">₱<?= number_format($totalReconCost, 2) ?></strong></td>
                                <td>
                                    <?php if ($row['status'] === 'Sent to Operations'): ?>
                                        <span class="badge bg-success">In Operations</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Approved</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center text-muted">No recon data found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modals per Vehicle -->
<?php 
if ($result && $result->num_rows > 0):
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()):
        $issuesResult = $conn->query("SELECT * FROM acquisition_issues WHERE acquisition_id = {$row['acquisition_id']} ORDER BY created_at DESC");
        $partsResult  = $conn->query("SELECT * FROM acquisition_parts WHERE acquisition_id = {$row['acquisition_id']} ORDER BY created_at DESC");
        $issuesTotal = $conn->query("SELECT SUM(COALESCE(issue_price,0)) as total FROM acquisition_issues WHERE acquisition_id = {$row['acquisition_id']}")->fetch_assoc()['total'] ?? 0;
        $partsTotal  = $conn->query("SELECT SUM(COALESCE(part_price,0)) as total FROM acquisition_parts WHERE acquisition_id = {$row['acquisition_id']}")->fetch_assoc()['total'] ?? 0;
        $acquiredPrice = $row['acquired_price'] ?? 0;
        $totalReconCost = $issuesTotal + $partsTotal;
?>
<div class="modal fade" id="reconModal<?= $row['acquisition_id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-wrench"></i> Recon Details - <?= htmlspecialchars($row['plate_number']) ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <!-- Vehicle Info -->
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-car"></i> Vehicle Information</h6>
                <div class="mb-4">
                    <div class="info-row"><div class="info-label">Plate Number:</div><div class="info-value"><?= htmlspecialchars($row['plate_number']) ?></div></div>
                    <div class="info-row"><div class="info-label">Model:</div><div class="info-value"><?= htmlspecialchars($row['vehicle_model']) ?></div></div>
                    <div class="info-row"><div class="info-label">Year:</div><div class="info-value"><?= htmlspecialchars($row['year_model']) ?></div></div>
                    <div class="info-row"><div class="info-label">Color:</div><div class="info-value"><?= htmlspecialchars($row['color']) ?></div></div>
                </div>

                <!-- Cost Summary -->
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-calculator"></i> Cost Breakdown</h6>
                <div class="cost-breakdown-box">
                    <div class="cost-item"><span>Issues Cost:</span><span>₱<?= number_format($issuesTotal, 2) ?></span></div>
                    <div class="cost-item"><span>Parts Cost:</span><span>₱<?= number_format($partsTotal, 2) ?></span></div>
                    <div class="cost-item total"><span>TOTAL RECON COST:</span><span>₱<?= number_format($totalReconCost, 2) ?></span></div>
                </div>

                <!-- Issues -->
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-exclamation-triangle"></i> Issues</h6>
                <table class="table table-bordered align-middle">
                    <thead class="table-warning text-center">
                        <tr><th>Issue Name</th><th>Cost</th><th>Remarks</th><th>Status</th><th>Repaired By</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($issuesResult->num_rows > 0): ?>
                            <?php while ($issue = $issuesResult->fetch_assoc()): ?>
                                <tr class="text-center">
                                    <td><?= htmlspecialchars($issue['issue_name']) ?></td>
                                    <td><?= $issue['issue_price'] ? "₱" . number_format($issue['issue_price'], 2) : '<span class="text-muted">Not Set</span>' ?></td>
                                    <td><?= htmlspecialchars($issue['issue_remarks'] ?? 'No remarks') ?></td>
                                    <td><?= $issue['is_repaired'] ? '<span class="badge bg-success">Repaired</span>' : '<span class="badge bg-secondary">Pending</span>' ?></td>
                                    <td><?= htmlspecialchars($issue['repaired_by'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted">No issues recorded.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Parts -->
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-tools"></i> Parts</h6>
                <table class="table table-bordered align-middle">
                    <thead class="table-info text-center">
                        <tr><th>Part Name</th><th>Cost</th><th>Remarks</th><th>Status</th><th>Ordered By</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($partsResult->num_rows > 0): ?>
                            <?php while ($part = $partsResult->fetch_assoc()): ?>
                                <tr class="text-center">
                                    <td><?= htmlspecialchars($part['part_name']) ?></td>
                                    <td><?= $part['part_price'] ? "₱" . number_format($part['part_price'], 2) : '<span class="text-muted">Not Set</span>' ?></td>
                                    <td><?= htmlspecialchars($part['part_remarks'] ?? 'No remarks') ?></td>
                                    <td><?= $part['is_ordered'] ? '<span class="badge bg-success">Ordered</span>' : '<span class="badge bg-secondary">Pending</span>' ?></td>
                                    <td><?= htmlspecialchars($part['ordered_by'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted">No parts ordered.</td></tr>
                        <?php endif; ?>
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
