<?php
session_start();

// Check if logged in and has correct role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'acquisition') {
    header('Location: /LoginPage/loginPage.php');
    exit();
}

// Get user information
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Acquisition</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/acquiPage.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <img src="../Pictures/Carmax_logo.jpg" alt="CarMax" class="logo">
            <div class="header-title">Vehicle Acquisition Management</div>
        </div>
        <div class="user-info">
            <i class="fas fa-user-circle" style="font-size: 24px;"></i>
            <span><?php echo htmlspecialchars($userName); ?></span>
            <a href="../logout.php" style="margin-left: 15px; color: white; text-decoration: none;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-item active">
            <i class="fas fa-car"></i>
            <span>Acquisition</span>
        </div>
        <div class="sidebar-item">
            <i class="fas fa-clipboard-check"></i>
            <span>Inspection Reports</span>
        </div>
        <div class="sidebar-item">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Vehicle Information Card -->
        <div class="sap-card">
            <div class="sap-card-header">
                <div>
                    <i class="fas fa-car"></i> Vehicle Information
                </div>
                <button class="btn-carmax-secondary" onclick="openInspectionReport()">
                    <i class="fas fa-clipboard-list"></i> Inspection Report
                </button>
            </div>
            <div class="sap-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Vehicle Model</label>
                        <input type="text" class="form-control" id="vehicleModel" placeholder="e.g., HONDA BRV">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Plate Number</label>
                        <input type="text" class="form-control" id="plateNumber" placeholder="e.g., NEM1034">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Year</label>
                        <input type="number" class="form-control" id="year" placeholder="e.g., 2020">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Color</label>
                        <input type="text" class="form-control" id="color" placeholder="e.g., White">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="status">
                            <option value="draft">Draft</option>
                            <option value="saved">Saved</option>
                            <option value="sent">Sent to Operations</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Acquisitions Table -->
        <div class="sap-card">
            <div class="sap-card-header">
                <div><i class="fas fa-table"></i> Recent Acquisitions</div>
                <span class="status-badge status-draft">3 Draft</span>
            </div>
            <div class="sap-card-body">
                <table class="sap-table">
                    <thead>
                        <tr>
                            <th>Plate Number</th>
                            <th>Model</th>
                            <th>Year</th>
                            <th>Projected Price</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="acquisitionsTable">
                        <tr>
                            <td>NEM1034</td>
                            <td>HONDA BRV</td>
                            <td>2018</td>
                            <td>₱25,000</td>
                            <td><span class="status-badge status-draft">Draft</span></td>
                            <td>
                                <button class="btn btn-sm btn-carmax-secondary" onclick="openInspectionReport()">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Inspection Report Modal -->
    <div class="modal-overlay" id="inspectionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h4><i class="fas fa-clipboard-list"></i> Inspection Report</h4>
                <button class="modal-close" onclick="closeInspectionReport()">×</button>
            </div>
            <div class="modal-body">
                <!-- Vehicle Details -->
                <div class="sap-card">
                    <div class="sap-card-header">Vehicle Details</div>
                    <div class="sap-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Plate Number</label>
                                <input type="text" class="form-control" id="inspPlateNumber" value="NEM1034">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vehicle Model</label>
                                <input type="text" class="form-control" id="inspModel" value="HONDA BRV">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pictures of Car -->
                <h5 class="section-title">📸 Pictures of Car</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Dashboard (Including Mileage)</label>
                        <input type="file" class="form-control" accept="image/*" onchange="previewImage(this, 'dashboardPreview')">
                        <div class="image-preview-container" id="dashboardPreview"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Hood</label>
                        <input type="file" class="form-control" accept="image/*" onchange="previewImage(this, 'hoodPreview')">
                        <div class="image-preview-container" id="hoodPreview"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Interior</label>
                        <input type="file" class="form-control" accept="image/*" onchange="previewImage(this, 'interiorPreview')">
                        <div class="image-preview-container" id="interiorPreview"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Exterior</label>
                        <input type="file" class="form-control" accept="image/*" onchange="previewImage(this, 'exteriorPreview')">
                        <div class="image-preview-container" id="exteriorPreview"></div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Car Trunk (Including tools compartment, Spare tires)</label>
                        <input type="file" class="form-control" accept="image/*" onchange="previewImage(this, 'trunkPreview')">
                        <div class="image-preview-container" id="trunkPreview"></div>
                    </div>
                </div>

                <!-- Issues Photos -->
                <h5 class="section-title">⚠️ Issues - Photos</h5>
                <div class="mb-3">
                    <label class="form-label">Upload Issue Photos</label>
                    <input type="file" class="form-control" accept="image/*" multiple onchange="previewImage(this, 'issuesPreview')">
                    <div class="image-preview-container" id="issuesPreview"></div>
                </div>

                <!-- Inspection Report Table -->
                <h5 class="section-title">🔧 Parts Needed</h5>
                <table class="sap-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Part Name</th>
                            <th style="width: 20%;">Status</th>
                            <th style="width: 20%;">Priority</th>
                            <th style="width: 20%;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="partsTable">
                        <tr>
                            <td><input type="text" class="form-control" value="PMS" readonly></td>
                            <td>
                                <select class="form-select form-select-sm">
                                    <option>Needed</option>
                                    <option>Optional</option>
                                    <option>Completed</option>
                                </select>
                            </td>
                            <td>
                                <select class="form-select form-select-sm">
                                    <option>High</option>
                                    <option>Medium</option>
                                    <option>Low</option>
                                </select>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-danger" onclick="removeRow(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="text" class="form-control" value="STAB LINK SET" readonly></td>
                            <td>
                                <select class="form-select form-select-sm">
                                    <option>Needed</option>
                                    <option>Optional</option>
                                    <option>Completed</option>
                                </select>
                            </td>
                            <td>
                                <select class="form-select form-select-sm">
                                    <option>High</option>
                                    <option>Medium</option>
                                    <option>Low</option>
                                </select>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-danger" onclick="removeRow(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="text" class="form-control" value="CV BOOT INNER 2PCS" readonly></td>
                            <td>
                                <select class="form-select form-select-sm">
                                    <option>Needed</option>
                                    <option>Optional</option>
                                    <option>Completed</option>
                                </select>
                            </td>
                            <td>
                                <select class="form-select form-select-sm">
                                    <option>High</option>
                                    <option>Medium</option>
                                    <option>Low</option>
                                </select>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-danger" onclick="removeRow(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="text" class="form-control" value="TAIL LIGHT CRACK (small only)" readonly></td>
                            <td>
                                <select class="form-select form-select-sm">
                                    <option>Needed</option>
                                    <option>Optional</option>
                                    <option>Completed</option>
                                </select>
                            </td>
                            <td>
                                <select class="form-select form-select-sm">
                                    <option>High</option>
                                    <option selected>Medium</option>
                                    <option>Low</option>
                                </select>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-danger" onclick="removeRow(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="text" class="form-control" value="Window switch (initial)" readonly></td>
                            <td>
                                <select class="form-select form-select-sm">
                                    <option>Needed</option>
                                    <option>Optional</option>
                                    <option>Completed</option>
                                </select>
                            </td>
                            <td>
                                <select class="form-select form-select-sm">
                                    <option>High</option>
                                    <option>Medium</option>
                                    <option selected>Low</option>
                                </select>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-danger" onclick="removeRow(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <button class="btn btn-sm btn-carmax-secondary mt-2" onclick="addPartRow()">
                    <i class="fas fa-plus"></i> Add Part
                </button>

                <!-- Vehicle Condition -->
                <h5 class="section-title">✅ Vehicle Condition</h5>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Spare Tires <span class="thumbs-up">👍</span></label>
                        <select class="form-select">
                            <option value="yes" selected>Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Complete Tools <span class="thumbs-up">👍</span></label>
                        <select class="form-select">
                            <option value="yes" selected>Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Original Plate <span class="thumbs-up">👍</span></label>
                        <select class="form-select">
                            <option value="yes" selected>Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Complete Documents <span class="thumbs-up">👍</span></label>
                        <select class="form-select">
                            <option value="yes" selected>Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label">Include Document Photos</label>
                    <input type="file" class="form-control" accept="image/*,application/pdf" multiple onchange="previewImage(this, 'documentsPreview')">
                    <div class="image-preview-container" id="documentsPreview"></div>
                </div>

                <!-- Remarks -->
                <h5 class="section-title">📝 Remarks</h5>
                <div class="mb-3">
                    <label class="form-label">Remarks (OKAY TO BUY) <span class="thumbs-up">👍</span></label>
                    <textarea class="form-control" rows="4" placeholder="Enter remarks here...">Vehicle is in good condition. OKAY TO BUY.</textarea>
                </div>

                <!-- Projected Price -->
                <h5 class="section-title">💰 Projected Recon Price</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Projected Recon Price</label>
                        <input type="text" class="form-control" value="₱25,000" placeholder="e.g., ₱25,000">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Estimated Completion Time</label>
                        <input type="text" class="form-control" placeholder="e.g., 2-3 weeks">
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-4 d-flex gap-2 justify-content-end">
                    <button class="btn btn-secondary" onclick="closeInspectionReport()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button class="btn btn-carmax-primary" onclick="saveReport()">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button class="btn btn-carmax-secondary" onclick="sendReport()">
                        <i class="fas fa-paper-plane"></i> Send to Operations
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Open Inspection Report Modal
        function openInspectionReport() {
            document.getElementById('inspectionModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        // Close Inspection Report Modal
        function closeInspectionReport() {
            document.getElementById('inspectionModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Preview uploaded images
        function previewImage(input, previewId) {
            const previewContainer = document.getElementById(previewId);
            
            if (input.files && input.files.length > 0) {
                Array.from(input.files).forEach((file, index) => {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const previewDiv = document.createElement('div');
                        previewDiv.className = 'image-preview';
                        previewDiv.innerHTML = `
                            <img src="${e.target.result}" alt="Preview">
                            <button class="remove-image" onclick="removePreview(this)">×</button>
                        `;
                        previewContainer.appendChild(previewDiv);
                    };
                    
                    reader.readAsDataURL(file);
                });
            }
        }

        // Remove image preview
        function removePreview(button) {
            button.parentElement.remove();
        }

        // Add new part row
        function addPartRow() {
            const table = document.getElementById('partsTable');
            const newRow = table.insertRow();
            newRow.innerHTML = `
                <td><input type="text" class="form-control" placeholder="Enter part name"></td>
                <td>
                    <select class="form-select form-select-sm">
                        <option>Needed</option>
                        <option>Optional</option>
                        <option>Completed</option>
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm">
                        <option>High</option>
                        <option>Medium</option>
                        <option>Low</option>
                    </select>
                </td>
                <td>
                    <button class="btn btn-sm btn-danger" onclick="removeRow(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
        }

        // Remove row from table
        function removeRow(button) {
            if (confirm('Are you sure you want to remove this part?')) {
                button.closest('tr').remove();
            }
        }

        // Save Report
        function saveReport() {
            // Show loading
            const saveBtn = event.target;
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            saveBtn.disabled = true;

            // Simulate saving to database
            setTimeout(() => {
                alert('✅ Report saved successfully!\n\nThe inspection report has been saved to the database.');
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
                
                // Update status in main table
                updateTableStatus('NEM1034', 'saved');
            }, 1500);
        }

        // Send Report to Operations
        function sendReport() {
            if (confirm('Are you sure you want to send this report to Operations Management?\n\nOnce sent, they will be able to view and process this inspection report.')) {
                // Show loading
                const sendBtn = event.target;
                const originalText = sendBtn.innerHTML;
                sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                sendBtn.disabled = true;

                // Simulate sending
                setTimeout(() => {
                    alert('📤 Report sent successfully!\n\nThe inspection report has been sent to Operations Management for review.');
                    sendBtn.innerHTML = originalText;
                    sendBtn.disabled = false;
                    
                    // Update status in main table
                    updateTableStatus('NEM1034', 'sent');
                    
                    // Close modal
                    closeInspectionReport();
                }, 2000);
            }
        }

        // Update status in main table
        function updateTableStatus(plateNumber, status) {
            const table = document.getElementById('acquisitionsTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let row of rows) {
                const cells = row.getElementsByTagName('td');
                if (cells.length > 0 && cells[0].textContent === plateNumber) {
                    let statusBadge = cells[4].querySelector('.status-badge');
                    if (status === 'saved') {
                        statusBadge.className = 'status-badge status-saved';
                        statusBadge.textContent = 'Saved';
                    } else if (status === 'sent') {
                        statusBadge.className = 'status-badge status-sent';
                        statusBadge.textContent = 'Sent to Operations';
                    }
                    break;
                }
            }
        }

        // Close modal on outside click
        document.getElementById('inspectionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeInspectionReport();
            }
        });

        // Prevent modal content click from closing
        document.querySelector('.modal-content').addEventListener('click', function(e) {
            e.stopPropagation();
        });
    </script>
</body>
</html>