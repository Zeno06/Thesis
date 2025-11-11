<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['id']) || !isset($_GET['id'])) {
    exit('Unauthorized');
}

$request_id = $_GET['id'];

// Fetch payment request details
$query = "SELECT pr.*, va.vehicle_model, va.year_model, va.color
          FROM payment_requests pr
          INNER JOIN vehicle_acquisition va ON pr.acquisition_id = va.acquisition_id
          WHERE pr.request_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    exit('Request not found');
}

// ✅ Correct header for printable HTML
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payment Request <?= $request_id ?></title>
    <style>
        @page { margin: 20mm; }
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 3px solid black; padding-bottom: 15px; }
        .header h1 { color: black; margin: 0; }
        .header p { margin: 5px 0; color: #666; }
        .info-section { margin: 20px 0; }
        .info-row { display: flex; padding: 10px 0; border-bottom: 1px solid #eee; }
        .info-label { font-weight: bold; width: 200px; color: #555; }
        .info-value { flex: 1; }
        .amount-box { background: #f8f9fa; border: 2px solid black; border-radius: 10px; padding: 20px; text-align: center; margin: 30px 0; }
        .amount-box .label { font-size: 12px; color: #666; margin-bottom: 10px; }
        .amount-box .amount { font-size: 28px; font-weight: bold; color: black; }
        .footer { margin-top: 40px; border-top: 1px solid #ddd; padding-top: 15px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>CARMAX CARMONA</h1>
        <p>Payment Request Form</p>
        <p>Request ID: #<?= str_pad($request_id, 5, '0', STR_PAD_LEFT) ?></p>
    </div>

    <div class="info-section">
        <h3 style="color: black; border-bottom: 2px solid black; padding-bottom: 10px;">Vehicle Information</h3>
        <div class="info-row"><div class="info-label">Plate Number:</div><div class="info-value"><?= htmlspecialchars($data['plate_number']) ?></div></div>
        <div class="info-row"><div class="info-label">Vehicle Model:</div><div class="info-value"><?= htmlspecialchars($data['vehicle_model']) ?></div></div>
        <div class="info-row"><div class="info-label">Year Model:</div><div class="info-value"><?= htmlspecialchars($data['year_model']) ?></div></div>
        <div class="info-row"><div class="info-label">Color:</div><div class="info-value"><?= htmlspecialchars($data['color']) ?></div></div>
    </div>

    <div class="info-section">
        <h3 style="color: black; border-bottom: 2px solid black; padding-bottom: 10px;">Request Details</h3>
        <div class="info-row"><div class="info-label">Request Type:</div><div class="info-value"><?= htmlspecialchars($data['request_type']) ?></div></div>
        <div class="info-row"><div class="info-label">Description:</div><div class="info-value"><?= htmlspecialchars($data['description'] ?? 'N/A') ?></div></div>
        <div class="info-row"><div class="info-label">Requested By:</div><div class="info-value"><?= htmlspecialchars($data['requested_by']) ?></div></div>
        <div class="info-row"><div class="info-label">Request Date:</div><div class="info-value"><?= date('F d, Y h:i A', strtotime($data['requested_at'])) ?></div></div>
        <?php if ($data['approved_by']): ?>
        <div class="info-row"><div class="info-label">Approved By:</div><div class="info-value"><?= htmlspecialchars($data['approved_by']) ?></div></div>
        <div class="info-row"><div class="info-label">Approved Date:</div><div class="info-value"><?= date('F d, Y h:i A', strtotime($data['approved_at'])) ?></div></div>
        <?php endif; ?>
    </div>

    <div class="amount-box">
        <div class="label">REQUESTED AMOUNT</div>
        <div class="amount">₱<?= number_format($data['amount'], 2) ?></div>
    </div>

    <div class="footer">
        <p>Generated on <?= date('F d, Y h:i A') ?></p>
        <p>&copy; <?= date('Y') ?> CarMax Carmona. All rights reserved.</p>
    </div>

    <script>
        window.onload = function() { window.print(); }
    </script>
</body>
</html>
