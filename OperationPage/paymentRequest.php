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

// Handle form submission for new payment request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_request'])) {
    $acquisition_id = $_POST['acquisition_id'];
    $plate_number = $_POST['plate_number'];
    $vehicle_model = $_POST['vehicle_model'];
    $request_type = $_POST['request_type'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $requested_by = $userName;

    $insertQuery = "INSERT INTO payment_requests (acquisition_id, plate_number, vehicle_model, request_type, amount, description, requested_by, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("isssdss", $acquisition_id, $plate_number, $vehicle_model, $request_type, $amount, $description, $requested_by);

    if ($stmt->execute()) {
        $new_id = $stmt->insert_id;
        header("Location: paymentRequest.php?success=1&new_id=$new_id");
        exit();
    } else {
        $error_message = "Failed to create payment request.";
    }
}

// Fetch all payment requests
$query = "SELECT pr.*, va.vehicle_model, va.year_model, va.color
          FROM payment_requests pr
          INNER JOIN vehicle_acquisition va ON pr.acquisition_id = va.acquisition_id
          ORDER BY pr.requested_at DESC";
$result = $conn->query($query);

// Get vehicles for dropdown
$vehiclesQuery = "SELECT acquisition_id, plate_number, vehicle_model FROM vehicle_acquisition WHERE status = 'Sent to Operations'";
$vehiclesResult = $conn->query($vehiclesQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request for Payment - CarMax</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/operationPage.css">
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-left">
        <img src="../Pictures/Carmax_logo.jpg" alt="CarMax" class="logo">
        <div class="header-title fs-4 fw-bold">Request for Payment</div>
    </div>
    <div class="user-info d-flex align-items-center gap-2">
        <i class="fas fa-user-circle" style="font-size: 24px;"></i>
        <span><?= htmlspecialchars($userName) ?> (<?= $userRole === 'operation' ? 'Operation Admin' : 'Super Admin' ?>)</span>
        <a href="../logout.php" style="margin-left:15px;color:white;text-decoration:none;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- Sidebar -->
<div class="sidebar">
    <a href="operationPage.php" class="sidebar-item"><i class="fas fa-inbox"></i><span>Received Acquisitions</span></a>
    <a href="reconCost.php" class="sidebar-item"><i class="fas fa-wrench"></i><span>Recon Cost</span></a>
    <a href="paymentRequest.php" class="sidebar-item active"><i class="fas fa-money-bill-wave"></i><span>Request for Payment</span></a>
    <a href="partsOrder.php" class="sidebar-item"><i class="fas fa-tools"></i><span>Parts Needed/Order</span></a>
    <a href="salesPage.php" class="sidebar-item"><i class="fas fa-shopping-cart"></i><span>Sales</span></a>
    <a href="recentSales.php" class="sidebar-item"><i class="fas fa-history"></i><span>Recent Sales</span></a>
</div>

<!-- Main Content -->
<div class="main-content">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Payment request created successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Create Request Card -->
    <div class="sap-card mb-4">
        <div class="sap-card-header"><i class="fas fa-plus-circle"></i> Create Payment Request</div>
        <div class="sap-card-body">
            <form method="POST" action="">
                <input type="hidden" name="create_request" value="1">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Select Vehicle</label>
                        <select class="form-select" name="acquisition_id" id="vehicleSelect" required>
                            <option value="">Choose...</option>
                            <?php while ($vehicle = $vehiclesResult->fetch_assoc()): ?>
                                <option value="<?= $vehicle['acquisition_id'] ?>" 
                                    data-plate="<?= htmlspecialchars($vehicle['plate_number']) ?>"
                                    data-model="<?= htmlspecialchars($vehicle['vehicle_model']) ?>">
                                    <?= htmlspecialchars($vehicle['plate_number']) ?> - <?= htmlspecialchars($vehicle['vehicle_model']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Plate Number</label>
                        <input type="text" class="form-control" name="plate_number" id="plateNumber" readonly required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Vehicle Model</label>
                        <input type="text" class="form-control" name="vehicle_model" id="vehicleModel" readonly required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Request Type</label>
                        <select class="form-select" name="request_type" required>
                            <option value="">Choose...</option>
                            <option value="Repair">Repair</option>
                            <option value="Parts">Parts</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" class="form-control" name="amount" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="1"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-paper-plane"></i> Submit Request
                </button>
            </form>
        </div>
    </div>

    <!-- Table of Requests -->
    <div class="sap-card">
        <div class="sap-card-header"><i class="fas fa-list"></i> Payment Requests</div>
        <div class="sap-card-body">
            <table class="sap-table table table-hover">
                <thead class="table-success">
                    <tr>
                        <th>Request ID</th>
                        <th>Plate Number</th>
                        <th>Vehicle</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Requested By</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= str_pad($row['request_id'], 5, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars($row['plate_number']) ?></td>
                                <td><?= htmlspecialchars($row['vehicle_model']) ?> (<?= $row['year_model'] ?>)</td>
                                <td><span class="badge bg-info"><?= htmlspecialchars($row['request_type']) ?></span></td>
                                <td>₱<?= number_format($row['amount'], 2) ?></td>
                                <td><?= htmlspecialchars($row['requested_by']) ?></td>
                                <td><?= date('M d, Y', strtotime($row['requested_at'])) ?></td>
                                <td>
                                    <a href="downloadPaymentPDF.php?id=<?= $row['request_id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-file-pdf"></i> Print
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted">No payment requests yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (isset($_GET['new_id'])): ?>
<script>window.open("downloadPaymentPDF.php?id=<?= $_GET['new_id'] ?>", "_blank");</script>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('vehicleSelect').addEventListener('change', function() {
    const s = this.options[this.selectedIndex];
    document.getElementById('plateNumber').value = s.dataset.plate || '';
    document.getElementById('vehicleModel').value = s.dataset.model || '';
});
</script>
</body>
</html>
