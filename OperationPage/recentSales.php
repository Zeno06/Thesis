<?php
session_start();
include '../db_connect.php';

// Check login session and role
if (!isset($_SESSION['id']) || ($_SESSION['role'] !== 'operation' && $_SESSION['role'] !== 'superadmin')) {
    header('Location: ../LoginPage/loginPage.php');
    exit();
}

$userName = $_SESSION['user_name'];
$userRole = $_SESSION['role'];

// Fetch all sold vehicles
$query = "SELECT * FROM vehicle_sales ORDER BY date_sold DESC, created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recent Sales - CarMax</title>
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
            width: 220px;
            color: #555;
        }
        .info-value {
            flex: 1;
            color: #333;
        }
        .profit-positive {
            color: #198754;
            font-weight: bold;
        }
        .profit-negative {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-left">
        <img src="../Pictures/Carmax_logo.jpg" alt="CarMax" class="logo">
        <div class="header-title fs-4 fw-bold">Recent Sales</div>
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
    <a href="paymentRequest.php" class="sidebar-item">
        <i class="fas fa-money-bill-wave"></i><span>Request for Payment</span>
    </a>
    <a href="partsOrder.php" class="sidebar-item">
        <i class="fas fa-tools"></i><span>Parts Needed/Order</span>
    </a>
    <a href="salesPage.php" class="sidebar-item">
        <i class="fas fa-shopping-cart"></i><span>Sales</span>
    </a>
    <a href="recentSales.php" class="sidebar-item active">
        <i class="fas fa-history"></i><span>Recent Sales</span>
    </a>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="sap-card">
        <div class="sap-card-header">
            <i class="fas fa-chart-line"></i> Sold Cars
        </div>
        <div class="sap-card-body">
            <table class="sap-table table table-hover">
                <thead class="table-success">
                    <tr>
                        <th>Date Sold</th>
                        <th>Date Released</th>
                        <th>Customer Name</th>
                        <th>Make</th>
                        <th>Model</th>
                        <th>Year</th>
                        <th>Plate</th>
                        <th>Gross Profit</th>
                        <th>Payment Terms</th>
                        <th>Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr data-bs-toggle="modal" data-bs-target="#saleModal<?= $row['sale_id'] ?>" style="cursor:pointer;">
                                <td><?= date('M d, Y', strtotime($row['date_sold'])) ?></td>
                                <td><?= $row['date_released'] ? date('M d, Y', strtotime($row['date_released'])) : 'N/A' ?></td>
                                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                <td><?= htmlspecialchars($row['vehicle_make']) ?></td>
                                <td><?= htmlspecialchars($row['vehicle_model']) ?></td>
                                <td><?= htmlspecialchars($row['year_model']) ?></td>
                                <td><?= htmlspecialchars($row['plate_number']) ?></td>
                                <td>
                                    <span class="<?= $row['gross_profit'] >= 0 ? 'profit-positive' : 'profit-negative' ?>">
                                        ₱<?= number_format($row['gross_profit'], 2) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['payment_terms'] === 'Cash'): ?>
                                        <span class="badge bg-success">Cash</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Financing</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['agent_name']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="10" class="text-center">No sales recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Sales Detail Modals -->
<?php 
if ($result && $result->num_rows > 0):
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()):
?>
<div class="modal fade" id="saleModal<?= $row['sale_id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header  text-white">
                <h5 class="modal-title">
                    <i class="fas fa-receipt"></i> Sale Details - <?= htmlspecialchars($row['vehicle_make']) ?> <?= htmlspecialchars($row['vehicle_model']) ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <!-- Sale Information -->
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-calendar-alt"></i> Sale Information</h6>
                <div class="mb-4">
                    <div class="info-row">
                        <div class="info-label">Date Sold:</div>
                        <div class="info-value"><?= date('F d, Y', strtotime($row['date_sold'])) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Date Released:</div>
                        <div class="info-value">
                            <?= $row['date_released'] ? date('F d, Y', strtotime($row['date_released'])) : 'Not Released Yet' ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Agent Name:</div>
                        <div class="info-value"><?= htmlspecialchars($row['agent_name']) ?></div>
                    </div>
                </div>

                <!-- Customer Information -->
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-user"></i> Customer Information</h6>
                <div class="mb-4">
                    <div class="info-row">
                        <div class="info-label">Customer Name:</div>
                        <div class="info-value"><?= htmlspecialchars($row['customer_name']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Payment Terms:</div>
                        <div class="info-value">
                            <?php if ($row['payment_terms'] === 'Cash'): ?>
                                <span class="badge bg-success">Cash</span>
                            <?php else: ?>
                                <span class="badge bg-info">Financing</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($row['payment_terms'] === 'Financing' && $row['financing_company']): ?>
                    <div class="info-row">
                        <div class="info-label">Financing Company:</div>
                        <div class="info-value"><?= htmlspecialchars($row['financing_company']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Vehicle Information -->
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-car"></i> Vehicle Information</h6>
                <div class="mb-4">
                    <div class="info-row">
                        <div class="info-label">Make:</div>
                        <div class="info-value"><?= htmlspecialchars($row['vehicle_make']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Model:</div>
                        <div class="info-value"><?= htmlspecialchars($row['vehicle_model']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Year:</div>
                        <div class="info-value"><?= htmlspecialchars($row['year_model']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Plate Number:</div>
                        <div class="info-value"><?= htmlspecialchars($row['plate_number']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Vehicle Released Pass ID:</div>
                        <div class="info-value"><?= htmlspecialchars($row['vehicle_released_pass_id'] ?? 'N/A') ?></div>
                    </div>
                </div>

                <!-- Financial Information -->
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-dollar-sign"></i> Financial Information</h6>
                <div class="mb-4" style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                    <div class="info-row">
                        <div class="info-label">Selling Price:</div>
                        <div class="info-value" style="font-weight: bold; color: #0d6efd;">
                            ₱<?= number_format($row['selling_price'], 2) ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Total Cost:</div>
                        <div class="info-value">₱<?= number_format($row['total_cost'], 2) ?></div>
                    </div>
                    <div class="info-row" style="border-bottom: 2px solid #198754; padding-bottom: 15px; margin-bottom: 15px;">
                        <div class="info-label" style="font-size: 1.1em;">Gross Profit:</div>
                        <div class="info-value">
                            <span style="font-size: 1.3em;" class="<?= $row['gross_profit'] >= 0 ? 'profit-positive' : 'profit-negative' ?>">
                                ₱<?= number_format($row['gross_profit'], 2) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Remarks -->
                <?php if ($row['remarks']): ?>
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-comment"></i> Remarks</h6>
                <div class="alert alert-info"><?= nl2br(htmlspecialchars($row['remarks'])) ?></div>
                <?php endif; ?>

                <!-- Record Information -->
                <div class="mt-4 pt-3 border-top">
                    <small class="text-muted">
                        <i class="fas fa-clock"></i> Recorded on: <?= date('F d, Y h:i A', strtotime($row['created_at'])) ?>
                    </small>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endwhile; endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>