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

// Fetch all released vehicles
$vehicles = $conn->query("
    SELECT acquisition_id, plate_number, vehicle_model, year_model, color, selling_price, acquired_price, total_recon_cost
    FROM vehicle_acquisition 
    WHERE is_released = 1
    ORDER BY approved_at DESC
");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acquisition_id = $_POST['acquisition_id'];
    $date_sold = $_POST['date_sold'];
    $date_released = $_POST['date_released'];
    $customer_name = $_POST['customer_name'];
    $plate_number = $_POST['plate_number'];
    $vehicle_make = $_POST['vehicle_make'];
    $vehicle_model = $_POST['vehicle_model'];
    $year_model = $_POST['year_model'];
    $color = $_POST['color'];
    $selling_price = $_POST['selling_price'];
    $payment_terms = $_POST['payment_terms'];
    $financing_company = $_POST['financing_company'] ?? null;
    $agent_name = $_POST['agent_name'];
    $vehicle_released_pass_id = $_POST['vehicle_released_pass_id'];
    $remarks = $_POST['remarks'];

    // Fetch acquisition total cost (acquired + total_recon_cost)
    $getCost = $conn->query("SELECT acquired_price, total_recon_cost FROM vehicle_acquisition WHERE acquisition_id = '$acquisition_id'");
    $costRow = $getCost->fetch_assoc();
    $total_cost = $costRow['acquired_price'] + $costRow['total_recon_cost'];

    // Compute gross profit
    $gross_profit = $selling_price - $total_cost;

    // Insert into vehicle_sales
    $stmt = $conn->prepare("
        INSERT INTO vehicle_sales 
        (acquisition_id, date_sold, date_released, customer_name, plate_number, vehicle_make, vehicle_model, year_model, selling_price, total_cost, gross_profit, payment_terms, financing_company, agent_name, vehicle_released_pass_id, remarks)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
    "issssssiddssssss",
        $acquisition_id, $date_sold, $date_released, $customer_name, $plate_number,
        $vehicle_make, $vehicle_model, $year_model, $selling_price, $total_cost,
        $gross_profit, $payment_terms, $financing_company, $agent_name,
        $vehicle_released_pass_id, $remarks
    );

    if ($stmt->execute()) {
        echo "<script>alert('✅ Sale successfully recorded!'); window.location='recentSales.php';</script>";
        exit();
    } else {
        echo "<script>alert('❌ Error: " . addslashes($stmt->error) . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales - CarMax</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/operationPage.css">
    <style>
        .info-row { display: flex; padding: 8px 0; border-bottom: 1px solid #eee; }
        .info-label { font-weight: 600; width: 200px; color: #555; }
        .info-value { flex: 1; color: #333; }
        .sap-card-body input, select, textarea { font-size: 15px; }
        .form-section { margin-bottom: 25px; }
        .form-section h6 { color: #0d6efd; font-weight: bold; margin-bottom: 15px; }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-left">
        <img src="../Pictures/Carmax_logo.jpg" alt="CarMax" class="logo">
        <div class="header-title fs-4 fw-bold">Record Vehicle Sales</div>
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
    <a href="salesPage.php" class="sidebar-item active">
        <i class="fas fa-shopping-cart"></i><span>Sales</span>
    </a>
    <a href="recentSales.php" class="sidebar-item">
        <i class="fas fa-history"></i><span>Recent Sales</span>
    </a>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="sap-card">
        <div class="sap-card-header"><i class="fas fa-shopping-cart"></i> Record a Sale</div>
        <div class="sap-card-body">
            <form method="POST" id="salesForm">

                <!-- Vehicle Info -->
                <div class="form-section">
                    <h6><i class="fas fa-car"></i> Vehicle Information</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>Select Vehicle</label>
                            <select name="acquisition_id" id="vehicleSelect" class="form-select" required>
                                <option value="" disabled selected>-- Select a Vehicle --</option>
                                <?php while ($v = $vehicles->fetch_assoc()): ?>
                                    <option value="<?= $v['acquisition_id'] ?>"
                                        data-model="<?= htmlspecialchars($v['vehicle_model']) ?>"
                                        data-year="<?= htmlspecialchars($v['year_model']) ?>"
                                        data-plate="<?= htmlspecialchars($v['plate_number']) ?>"
                                        data-color="<?= htmlspecialchars($v['color']) ?>"
                                        data-selling="<?= htmlspecialchars($v['selling_price']) ?>"
                                        data-total="<?= htmlspecialchars($v['acquired_price'] + $v['total_recon_cost']) ?>">
                                        <?= htmlspecialchars($v['plate_number']) ?> - <?= htmlspecialchars($v['vehicle_model']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Customer Name</label>
                            <input type="text" name="customer_name" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label>Make</label>
                            <input type="text" name="vehicle_make" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label>Model</label>
                            <input type="text" name="vehicle_model" id="vehicle_model" class="form-control" readonly>
                        </div>
                        <div class="col-md-3">
                            <label>Year</label>
                            <input type="text" name="year_model" id="year_model" class="form-control" readonly>
                        </div>
                        <div class="col-md-3">
                            <label>Color</label>
                            <input type="text" name="color" id="color" class="form-control" readonly>
                        </div>
                        <div class="col-md-3">
                            <label>Plate Number</label>
                            <input type="text" name="plate_number" id="plate_number" class="form-control" readonly>
                        </div>
                    </div>
                </div>

                <!-- Sales Details -->
                <div class="form-section">
                    <h6><i class="fas fa-calendar-alt"></i> Sales Details</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label>Date Sold</label>
                            <input type="date" name="date_sold" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label>Date Released</label>
                            <input type="date" name="date_released" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label>Payment Terms</label>
                            <select name="payment_terms" id="payment_terms" class="form-select" required>
                                <option value="Cash">Cash</option>
                                <option value="Financing">Financing</option>
                            </select>
                        </div>
                        <div class="col-md-3" id="financing_field" style="display:none;">
                            <label>Financing Company</label>
                            <input type="text" name="financing_company" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label>Agent</label>
                            <input type="text" name="agent_name" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label>Vehicle Released Pass ID</label>
                            <input type="text" name="vehicle_released_pass_id" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Financial Details -->
                <div class="form-section">
                    <h6><i class="fas fa-coins"></i> Financial Summary</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label>Selling Price</label>
                            <input type="number" name="selling_price" id="selling_price" class="form-control" readonly>
                        </div>
                        <div class="col-md-4">
                            <label>Total Cost</label>
                            <input type="number" name="total_cost" id="total_cost" class="form-control" readonly>
                        </div>
                        <div class="col-md-4">
                            <label>Gross Profit</label>
                            <input type="number" name="gross_profit" id="gross_profit" class="form-control" readonly>
                        </div>
                    </div>
                </div>

                <!-- Remarks -->
                <div class="form-section">
                    <h6><i class="fas fa-comment"></i> Remarks</h6>
                    <textarea name="remarks" class="form-control" rows="3"></textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Record Sale</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('vehicleSelect').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    document.getElementById('vehicle_model').value = selected.dataset.model;
    document.getElementById('year_model').value = selected.dataset.year;
    document.getElementById('color').value = selected.dataset.color;
    document.getElementById('plate_number').value = selected.dataset.plate;
    document.getElementById('selling_price').value = selected.dataset.selling;
    document.getElementById('total_cost').value = selected.dataset.total;
    document.getElementById('gross_profit').value = (selected.dataset.selling - selected.dataset.total).toFixed(2);
});

document.getElementById('payment_terms').addEventListener('change', function() {
    document.getElementById('financing_field').style.display = this.value === 'Financing' ? 'block' : 'none';
});
</script>
</body>
</html>
