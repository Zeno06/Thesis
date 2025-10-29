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

// Fetch all acquisitions sent to operations
$query = "SELECT * FROM vehicle_acquisition WHERE status = 'Sent to Operations' ORDER BY sent_to_operations_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operations Dashboard - CarMax</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/operationPage.css">
    <style>
        .photo-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: flex-start;
        }
        .photo-box {
            flex: 1 1 calc(33.333% - 15px);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .photo-box img {
            width: 100%;
            max-height: 200px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
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
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-left">
        <img src="../Pictures/Carmax_logo.jpg" alt="CarMax" class="logo">
        <div class="header-title fs-4 fw-bold">Operations Dashboard</div>
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
    <a href="operationPage.php" class="sidebar-item active">
        <i class="fas fa-inbox"></i><span>Received Acquisitions</span>
    </a>
    <a href="#" class="sidebar-item">
        <i class="fas fa-money-bill-wave"></i><span>Request for Payment</span>
    </a>
    <a href="#" class="sidebar-item">
        <i class="fas fa-tools"></i><span>Parts Needed/Order</span>
    </a>
    <a href="#" class="sidebar-item">
        <i class="fas fa-calculator"></i><span>Recon Cost</span>
    </a>
    <a href="#" class="sidebar-item">
        <i class="fas fa-shopping-cart"></i><span>Sales</span>
    </a>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="sap-card">
        <div class="sap-card-header">
            <i class="fas fa-clipboard-list"></i> Received Acquisitions 
        </div>
        <div class="sap-card-body">
            <table class="sap-table table table-hover">
                <thead class="table-success">
                    <tr>
                        <th>Plate Number</th>
                        <th>Model</th>
                        <th>Year</th>
                        <th>Color</th>
                        <th>Price</th>
                        <th>Sent By</th>
                        <th>Received Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['acquisition_id'] ?>" style="cursor:pointer;">
                                <td><?= htmlspecialchars($row['plate_number']) ?></td>
                                <td><?= htmlspecialchars($row['vehicle_model']) ?></td>
                                <td><?= htmlspecialchars($row['year_model']) ?></td>
                                <td><?= htmlspecialchars($row['color']) ?></td>
                                <td>₱<?= number_format($row['projected_recon_price'], 2) ?></td>
                                <td><?= htmlspecialchars($row['sent_to_operations_by'] ?? 'N/A') ?></td>
                                <td><?= $row['sent_to_operations_at'] ? date('M d, Y h:i A', strtotime($row['sent_to_operations_at'])) : 'N/A' ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">No vehicles received from acquisition team yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- ✅ Modals should be rendered AFTER the table -->
            <?php
            // Re-run query or use the same result if not exhausted
            $result->data_seek(0); 
            while ($row = $result->fetch_assoc()): 
            ?>
            <div class="modal fade" id="viewModal<?= $row['acquisition_id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-car"></i> Vehicle Details - <?= htmlspecialchars($row['vehicle_model']) ?>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <!-- Basic Info -->
                            <h6 class="text-primary fw-bold mb-3"><i class="fas fa-info-circle"></i> Basic Information</h6>
                            <div class="mb-4">
                                <div class="info-row"><div class="info-label">Plate Number:</div><div class="info-value"><?= htmlspecialchars($row['plate_number']) ?></div></div>
                                <div class="info-row"><div class="info-label">Vehicle Model:</div><div class="info-value"><?= htmlspecialchars($row['vehicle_model']) ?></div></div>
                                <div class="info-row"><div class="info-label">Year Model:</div><div class="info-value"><?= htmlspecialchars($row['year_model']) ?></div></div>
                                <div class="info-row"><div class="info-label">Color:</div><div class="info-value"><?= htmlspecialchars($row['color']) ?></div></div>
                                <div class="info-row"><div class="info-label">Projected Recon Price:</div><div class="info-value">₱<?= number_format($row['projected_recon_price'], 2) ?></div></div>
                            </div>

                            <!-- Vehicle Photos -->
                            <h6 class="text-primary fw-bold mb-3"><i class="fas fa-images"></i> Vehicle Photos</h6>
                            <div class="photo-grid mb-4">
                                <?php 
                                $photos = ['wholecar'=>'Whole Car','dashboard'=>'Dashboard','hood'=>'Hood','interior'=>'Interior','exterior'=>'Exterior','trunk'=>'Trunk'];
                                foreach ($photos as $key => $label):
                                    $photoField = $key.'_photo';
                                    $photoPath = htmlspecialchars($row[$photoField] ?? '');
                                ?>
                                <div class="photo-box">
                                    <label><?= $label ?></label>
                                    <?php if (!empty($photoPath)): ?>
                                        <img src="../uploads/<?= $photoPath ?>" alt="<?= $label ?>">
                                    <?php else: ?>
                                        <div class="text-muted">No image</div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Issues Section -->
                            <?php 
                            $issuesQuery = $conn->query("SELECT * FROM acquisition_issues WHERE acquisition_id = {$row['acquisition_id']}");
                            if ($issuesQuery && $issuesQuery->num_rows > 0): 
                            ?>
                            <h6 class="text-primary fw-bold mb-3"><i class="fas fa-exclamation-triangle"></i> Issues (Resolved)</h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-bordered">
                                    <thead><tr><th>Issue Name</th><th>Photo</th><th>Status</th><th>Repaired By</th></tr></thead>
                                    <tbody>
                                        <?php while ($issue = $issuesQuery->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($issue['issue_name']) ?></td>
                                            <td>
                                                <?php if (!empty($issue['issue_photo'])): ?>
                                                    <img src="../uploads/<?= htmlspecialchars($issue['issue_photo']) ?>" style="max-width:100px; max-height:100px; border-radius:5px;">
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge bg-success"><i class="fas fa-check"></i> Repaired</span></td>
                                            <td><?= htmlspecialchars($issue['repaired_by'] ?? 'N/A') ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>

                            <!-- Parts Section -->
                            <?php 
                            $partsQuery = $conn->query("SELECT * FROM acquisition_parts WHERE acquisition_id = {$row['acquisition_id']}");
                            if ($partsQuery && $partsQuery->num_rows > 0): 
                            ?>
                            <h6 class="text-primary fw-bold mb-3"><i class="fas fa-tools"></i> Parts (Ordered)</h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-bordered">
                                    <thead><tr><th>Part Name</th><th>Status</th><th>Ordered By</th></tr></thead>
                                    <tbody>
                                        <?php while ($part = $partsQuery->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($part['part_name']) ?></td>
                                            <td><span class="badge bg-success"><i class="fas fa-check"></i> Ordered</span></td>
                                            <td><?= htmlspecialchars($part['ordered_by'] ?? 'N/A') ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>

                            <!-- Vehicle Condition -->
                            <h6 class="text-primary fw-bold mb-3"><i class="fas fa-clipboard-check"></i> Vehicle Condition</h6>
                            <div class="mb-4">
                                <div class="info-row"><div class="info-label">Spare Tires:</div><div class="info-value"><?= htmlspecialchars($row['spare_tires']) ?></div></div>
                                <div class="info-row"><div class="info-label">Complete Tools:</div><div class="info-value"><?= htmlspecialchars($row['complete_tools']) ?></div></div>
                                <div class="info-row"><div class="info-label">Original Plate:</div><div class="info-value"><?= htmlspecialchars($row['original_plate']) ?></div></div>
                                <div class="info-row"><div class="info-label">Complete Documents:</div><div class="info-value"><?= htmlspecialchars($row['complete_documents']) ?></div></div>
                            </div>

                            <!-- Document Photos -->
                            <?php 
                            if (!empty($row['document_photos'])):
                                $docPhotos = json_decode($row['document_photos'], true);
                                if (!is_array($docPhotos)) $docPhotos = explode(',', $row['document_photos']);
                            ?>
                            <h6 class="text-primary fw-bold mb-3"><i class="fas fa-file-contract"></i> Document Photos</h6>
                            <div class="photo-grid mb-4">
                                <?php foreach ($docPhotos as $photo): $photo = trim($photo); if (!empty($photo)): ?>
                                <div class="photo-box"><img src="../uploads/<?= htmlspecialchars($photo) ?>" alt="Document"></div>
                                <?php endif; endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Remarks -->
                           <h6 class="text-primary fw-bold mb-3"><i class="fas fa-comment"></i> Remarks</h6>
                           <div class="alert alert-info"><?= nl2br(htmlspecialchars($row['remarks'] ?? 'No remarks')) ?></div>

                            <!-- Timeline -->
                            <h6 class="text-primary fw-bold mb-3"><i class="fas fa-history"></i> Process Timeline</h6>
                            <div class="mb-4">
                                <div class="info-row"><div class="info-label">Quality Checked By:</div><div class="info-value"><?= htmlspecialchars($row['quality_checked_by'] ?? 'N/A') ?></div></div>
                                <div class="info-row"><div class="info-label">Quality Checked At:</div><div class="info-value"><?= $row['quality_checked_at'] ? date('M d, Y h:i A', strtotime($row['quality_checked_at'])) : 'N/A' ?></div></div>
                                <div class="info-row"><div class="info-label">Approved By:</div><div class="info-value"><?= htmlspecialchars($row['approved_by'] ?? 'N/A') ?></div></div>
                                <div class="info-row"><div class="info-label">Approved At:</div><div class="info-value"><?= $row['approved_at'] ? date('M d, Y h:i A', strtotime($row['approved_at'])) : 'N/A' ?></div></div>
                                <div class="info-row"><div class="info-label">Sent to Operations By:</div><div class="info-value"><?= htmlspecialchars($row['sent_to_operations_by'] ?? 'N/A') ?></div></div>
                                <div class="info-row"><div class="info-label">Sent to Operations At:</div><div class="info-value"><?= $row['sent_to_operations_at'] ? date('M d, Y h:i A', strtotime($row['sent_to_operations_at'])) : 'N/A' ?></div></div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>


</body>
</html>