<?php
session_start();
include '../db_connect.php';

// ✅ Check login session
if (!isset($_SESSION['id'])) {
    header('Location: ../LoginPage/loginPage.php');
    exit();
}

// ✅ Session variables
$id = $_SESSION['id'];
$userName = $_SESSION['user_name'];
$userRole = $_SESSION['role'];

// ✅ Allow edit only for acquisition & superadmin
$canEdit = ($userRole === 'acquisition' || $userRole === 'superadmin');

// ✅ Search functionality
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$searchQuery = '';
if (!empty($search)) {
    $searchQuery = " AND (plate_number LIKE '%$search%' 
                     OR vehicle_model LIKE '%$search%' 
                     OR color LIKE '%$search%')";
}

// ✅ Get all acquisitions including drafts & sent ones
$query = "SELECT * FROM vehicle_acquisition WHERE status IN ('Draft', 'Sent to Operations') $searchQuery ORDER BY created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recent Acquisitions - CarMax</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/acquiPage.css">

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
    .table_blue {
        background-color: white;
        color: #1E40AF;
    }
    </style>
</head>
<body>

<!--Header -->
<div class="header">
    <div class="header-left">
        <img src="../Pictures/Carmax_logo.jpg" alt="CarMax" class="logo">
        <div class="header-title">Recent Acquisitions</div>
    </div>

    <div class="user-info">
        <i class="fas fa-user-circle" style="font-size: 24px;"></i>
        <span>
            <?php 
                $role = $_SESSION['role'];
                $title = match($role) {
                    'acquisition' => 'Acquisition Admin',
                    'operation' => 'Operation Admin',
                    'superadmin' => 'Super Admin',
                    default => ucfirst($role)
                };
                echo htmlspecialchars($userName) . " ($title)";
            ?>
        </span>
        <a href="../logout.php" style="margin-left: 15px; color: white; text-decoration: none;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!--Sidebar -->
<div class="sidebar">
    <a href="../AcquisitionPage/acquiPage.php" class="sidebar-item">
        <i class="fas fa-car"></i><span>Acquisition</span>
    </a>
    <a href="../InventoryPage/inventoryPage.php" class="sidebar-item">
        <i class="fas fa-warehouse"></i><span>Inventory</span>
    </a>
    <a href="../AcquisitionPage/recentAcquisition.php" class="sidebar-item active">
        <i class="fas fa-history"></i><span>Recent Acquisition</span>
    </a>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="sap-card">
        <div class="sap-card-header d-flex justify-content-between align-items-center">
            <div><i class="fas fa-table"></i> Recent Acquisitions</div>
            <div>
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control" placeholder="Search..." 
                           value="<?php echo htmlspecialchars($search); ?>" style="width: 300px;">
                    <button type="submit" class="btn-carmax-secondary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="recentAcquisition.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="sap-card-body">
            <table class="sap-table table table-hover">
                <thead class="table-blue">
                    <tr>
                        <th>Plate Number</th>
                        <th>Model</th>
                        <th>Year</th>
                        <th>Color</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Checked By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['acquisition_id'] ?>">
                                <td><?= htmlspecialchars($row['plate_number']) ?></td>
                                <td><?= htmlspecialchars($row['vehicle_model']) ?></td>
                                <td><?= htmlspecialchars($row['year_model']) ?></td>
                                <td><?= htmlspecialchars($row['color']) ?></td>
                                <td>₱<?= number_format($row['projected_recon_price'], 2) ?></td>
                                <td>
                                    <span class="badge bg-<?= $row['status'] == 'Draft' ? 'secondary' : 'warning' ?>">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['approved_checked_by']) ?></td>
                            </tr>

        <!--Modal -->
        <div class="modal fade" id="viewModal<?= $row['acquisition_id'] ?>" tabindex="-1">
          <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content" data-id="<?= $row['acquisition_id'] ?>">
              <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-car"></i> Vehicle Acquisition Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
              </div>

        <form method="POST" action="saveAcquisitionUpdate.php" enctype="multipart/form-data">
          <input type="hidden" name="acquisition_id" value="<?= $row['acquisition_id'] ?>">
          <div class="modal-body">

            <!--Basic Info -->
            <h6 class="text-primary fw-bold mb-3"><i class="fas fa-info-circle"></i> Basic Information</h6>
            <div class="row g-3 mb-4">
              <div class="col-md-3"><label>Plate Number</label><input type="text" class="form-control" name="plate_number" value="<?= htmlspecialchars($row['plate_number']) ?>" disabled></div>
              <div class="col-md-3"><label>Vehicle Model</label><input type="text" class="form-control" name="vehicle_model" value="<?= htmlspecialchars($row['vehicle_model']) ?>" disabled></div>
              <div class="col-md-3"><label>Year Model</label><input type="number" class="form-control" name="year_model" value="<?= htmlspecialchars($row['year_model']) ?>" disabled></div>
              <div class="col-md-3"><label>Color</label><input type="text" class="form-control" name="color" value="<?= htmlspecialchars($row['color']) ?>" disabled></div>
            </div>

            <!--Vehicle Photos -->
            <h6 class="text-primary fw-bold mb-3"><i class="fas fa-images"></i> Vehicle Photos</h6>
            <div class="photo-grid mb-4">
              <?php 
              $photos = ['wholecar' => 'Whole Car','dashboard' => 'Dashboard','hood' => 'Hood','interior' => 'Interior','exterior' => 'Exterior','trunk' => 'Trunk'];
              foreach ($photos as $key => $label):
                $photoField = $key . '_photo';
                $photoPath = htmlspecialchars($row[$photoField] ?? '');
              ?>
              <div class="photo-box">
                <label><?= $label ?></label>
                <?php if (!empty($photoPath)): ?>
                  <img src="../uploads/<?= $photoPath ?>" alt="<?= $label ?>">
                <?php else: ?>
                  <div class="text-muted">No image uploaded</div>
                <?php endif; ?>
                <input type="file" name="<?= $photoField ?>" class="form-control mt-2 d-none" accept="image/*">
              </div>
              <?php endforeach; ?>
            </div>

            <!--Issue Photos -->
            <?php 
            if (!empty($row['issue_photos'])):
                $issuePhotos = json_decode($row['issue_photos'], true);
                if (!is_array($issuePhotos)) $issuePhotos = explode(',', $row['issue_photos']);
            ?>
            <h6 class="text-primary fw-bold mb-3"><i class="fas fa-exclamation-triangle"></i> Issue Photos</h6>
            <div class="photo-grid mb-4">
              <?php foreach ($issuePhotos as $photo): $photo = trim($photo); if (!empty($photo)): ?>
              <div class="photo-box">
                <img src="../uploads/<?= htmlspecialchars($photo) ?>" alt="Issue Photo">
              </div>
              <?php endif; endforeach; ?>
              <input type="file" name="issue_photos[]" class="form-control mt-2 d-none" accept="image/*" multiple>
            </div>
            <?php endif; ?>

            <!--Document Photos -->
            <?php 
            if (!empty($row['document_photos'])):
                $docPhotos = json_decode($row['document_photos'], true);
                if (!is_array($docPhotos)) $docPhotos = explode(',', $row['document_photos']);
            ?>
            <h6 class="text-primary fw-bold mb-3"><i class="fas fa-file-contract"></i> Document Photos</h6>
            <div class="photo-grid mb-4">
              <?php foreach ($docPhotos as $photo): $photo = trim($photo); if (!empty($photo)): ?>
              <div class="photo-box">
                <img src="../uploads/<?= htmlspecialchars($photo) ?>" alt="Document Photo">
              </div>
              <?php endif; endforeach; ?>
              <input type="file" name="document_photos[]" class="form-control mt-2 d-none" accept="image/*" multiple>
            </div>
            <?php endif; ?>

            <!--Vehicle Condition -->
            <h6 class="text-primary fw-bold mb-3"><i class="fas fa-clipboard-check"></i> Vehicle Condition</h6>
            <div class="row g-3 mb-4">
              <?php 
              $conditionFields = ['spare_tires'=>'Spare Tires','complete_tools'=>'Complete Tools','original_plate'=>'Original Plate','complete_documents'=>'Complete Documents'];
              foreach ($conditionFields as $field=>$label): ?>
              <div class="col-md-3">
                <label><?= $label ?></label>
                <select class="form-select" name="<?= $field ?>" disabled>
                  <option value="Yes" <?= $row[$field]=='Yes'?'selected':'' ?>>Yes</option>
                  <option value="No" <?= $row[$field]=='No'?'selected':'' ?>>No</option>
                </select>
              </div>
              <?php endforeach; ?>
            </div>

            <!-- Remarks -->
            <h6 class="text-primary fw-bold mb-3"><i class="fas fa-comment"></i> General Remarks</h6>
            <textarea class="form-control mb-4" name="remarks" rows="3" disabled><?= htmlspecialchars($row['remarks'] ?? '') ?></textarea>

            <!-- Financial Details -->
            <h6 class="text-primary fw-bold mb-3"><i class="fas fa-peso-sign"></i> Financial Details</h6>
            <div class="row g-3 mb-4">
              <div class="col-md-4"><label>Projected Recon Price</label><input type="number" class="form-control" name="projected_recon_price" value="<?= htmlspecialchars($row['projected_recon_price']) ?>" disabled></div>
              <div class="col-md-4"><label>Checked By</label><input type="text" class="form-control" name="approved_checked_by" value="<?= htmlspecialchars($row['approved_checked_by']) ?>" disabled></div>
              <div class="col-md-4">
                <label>Status</label>
                <select class="form-select" name="status" disabled>
                  <option <?= $row['status']=='Draft'?'selected':'' ?>>Draft</option>
                  <option <?= $row['status']=='Sent to Operations'?'selected':'' ?>>Sent to Operations</option>
                </select>
              </div>
            </div>

            <?php if (!empty($row['last_updated_by'])): ?>
              <div class="text-muted mt-3 small">
                <strong>Last updated by:</strong> <?= htmlspecialchars($row['last_updated_by']) ?> <br>
                <strong>On:</strong> <?= htmlspecialchars($row['last_updated_at']) ?>
              </div>
            <?php endif; ?>

          </div>

  <div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    <?php if ($canEdit): ?>
      <button type="button" class="btn btn-warning" onclick="enableEdit(this)">Edit</button>
    <?php endif; ?>
  </div>
</form>
</div></div></div>

<?php endwhile; else: ?>
<tr><td colspan="7" class="text-center">No acquisitions found.</td></tr>
<?php endif; ?>
</tbody></table></div></div></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
function enableEdit(btn) {
  const modal = btn.closest('.modal-content');
  const inputs = modal.querySelectorAll('input:not([type="hidden"]), select, textarea');
  const fileInputs = modal.querySelectorAll('input[type="file"]');
  const form = modal.querySelector('form');
  const isEditing = btn.dataset.editing === "true";

  if (!isEditing) {
    inputs.forEach(el => el.disabled = false);
    fileInputs.forEach(f => f.classList.remove('d-none'));
    btn.textContent = 'Save Changes';
    btn.classList.replace('btn-warning', 'btn-success');
    btn.dataset.editing = "true";
  } else {
    form.submit();
  }
}
</script>

</body>
</html>
