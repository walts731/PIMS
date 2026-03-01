<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Get software ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id === 0) {
    echo '<div class="alert alert-danger">Invalid software ID</div>';
    exit();
}

// Get software data
$sql = "SELECT * FROM software WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="alert alert-danger">Software item not found</div>';
    exit();
}

$software = $result->fetch_assoc();
$stmt->close();

// Get files
$files = json_decode($software['files'] ?? '{}', true);
$license_doc = $files['license_doc'] ?? '';
$installation_files = $files['installation_files'] ?? [];
?>

<!-- Edit Software Modal -->
<div class="modal fade" id="editSoftwareModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil"></i> Edit Software
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="process_software.php" enctype="multipart/form-data" id="editSoftwareForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?php echo $software['id']; ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Basic Information</h6>
                            <div class="mb-3">
                                <label class="form-label">Software Name *</label>
                                <input type="text" name="software_name" class="form-control" value="<?php echo htmlspecialchars($software['software_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Category *</label>
                                <select name="category" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <option value="Operating System" <?php echo $software['category'] === 'Operating System' ? 'selected' : ''; ?>>Operating System</option>
                                    <option value="Office Suite" <?php echo $software['category'] === 'Office Suite' ? 'selected' : ''; ?>>Office Suite</option>
                                    <option value="Antivirus" <?php echo $software['category'] === 'Antivirus' ? 'selected' : ''; ?>>Antivirus</option>
                                    <option value="Database" <?php echo $software['category'] === 'Database' ? 'selected' : ''; ?>>Database</option>
                                    <option value="Development Tools" <?php echo $software['category'] === 'Development Tools' ? 'selected' : ''; ?>>Development Tools</option>
                                    <option value="Design Software" <?php echo $software['category'] === 'Design Software' ? 'selected' : ''; ?>>Design Software</option>
                                    <option value="Accounting" <?php echo $software['category'] === 'Accounting' ? 'selected' : ''; ?>>Accounting</option>
                                    <option value="Other" <?php echo $software['category'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($software['description'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Vendor *</label>
                                <input type="text" name="vendor" class="form-control" value="<?php echo htmlspecialchars($software['vendor']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Version</label>
                                <input type="text" name="version" class="form-control" value="<?php echo htmlspecialchars($software['version'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">License Information</h6>
                            <div class="mb-3">
                                <label class="form-label">License Type *</label>
                                <select name="license_type" class="form-select" required>
                                    <option value="">Select License Type</option>
                                    <option value="Perpetual" <?php echo $software['license_type'] === 'Perpetual' ? 'selected' : ''; ?>>Perpetual</option>
                                    <option value="Annual Subscription" <?php echo $software['license_type'] === 'Annual Subscription' ? 'selected' : ''; ?>>Annual Subscription</option>
                                    <option value="Monthly Subscription" <?php echo $software['license_type'] === 'Monthly Subscription' ? 'selected' : ''; ?>>Monthly Subscription</option>
                                    <option value="Open Source" <?php echo $software['license_type'] === 'Open Source' ? 'selected' : ''; ?>>Open Source</option>
                                    <option value="Freeware" <?php echo $software['license_type'] === 'Freeware' ? 'selected' : ''; ?>>Freeware</option>
                                    <option value="Trial" <?php echo $software['license_type'] === 'Trial' ? 'selected' : ''; ?>>Trial</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">License Key</label>
                                <input type="text" name="license_key" class="form-control" value="<?php echo htmlspecialchars($software['license_key'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Purchase Date *</label>
                                <input type="date" name="purchase_date" class="form-control" value="<?php echo $software['purchase_date']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Purchase Cost *</label>
                                <input type="number" name="purchase_cost" class="form-control" step="0.01" value="<?php echo $software['purchase_cost']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Renewal Date</label>
                                <input type="date" name="renewal_date" class="form-control" value="<?php echo $software['renewal_date'] ?? ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Renewal Cost</label>
                                <input type="number" name="renewal_cost" class="form-control" step="0.01" value="<?php echo $software['renewal_cost']; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="">Select Status</option>
                                <option value="active" <?php echo $software['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $software['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="expired" <?php echo $software['status'] === 'expired' ? 'selected' : ''; ?>>Expired</option>
                                <option value="pending" <?php echo $software['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Assigned To</label>
                            <input type="text" name="assigned_to" class="form-control" value="<?php echo htmlspecialchars($software['assigned_to'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Installation Date</label>
                            <input type="date" name="installation_date" class="form-control" value="<?php echo $software['installation_date'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($software['notes'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Documents & Files</h6>
                            <?php if (!empty($license_doc)): ?>
                                <div class="mb-3">
                                    <label class="form-label">Current License Document:</label>
                                    <div class="d-flex align-items-center">
                                        <a href="../uploads/software/licenses/<?php echo htmlspecialchars($license_doc); ?>" target="_blank" class="btn btn-sm btn-outline-primary me-2">
                                            <i class="bi bi-file-earmark-pdf"></i> View
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeLicenseDoc()">
                                            <i class="bi bi-trash"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($installation_files)): ?>
                                <div class="mb-3">
                                    <label class="form-label">Current Installation Files:</label>
                                    <?php foreach ($installation_files as $index => $file): ?>
                                        <div class="d-flex align-items-center mb-1">
                                            <a href="../uploads/software/installations/<?php echo htmlspecialchars($file); ?>" class="btn btn-sm btn-outline-secondary me-2">
                                                <i class="bi bi-download"></i> <?php echo htmlspecialchars($file); ?>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeInstallationFile(<?php echo $index; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <input type="hidden" name="existing_files" value="<?php echo htmlspecialchars(json_encode($files)); ?>">
                            
                            <div class="mb-2">
                                <label class="form-label">Replace License Document:</label>
                                <input type="file" name="license_document" class="form-control" accept=".pdf,.doc,.docx">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Add Installation Files:</label>
                                <input type="file" name="installation_files[]" class="form-control" multiple accept=".exe,.msi,.zip,.rar">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Remove license document
function removeLicenseDoc() {
    if (confirm('Are you sure you want to remove this license document?')) {
        const existingFilesInput = document.querySelector('input[name="existing_files"]');
        const files = JSON.parse(existingFilesInput.value);
        files.license_doc = '';
        existingFilesInput.value = JSON.stringify(files);
        
        // Reload the modal to reflect changes
        loadEditModal(<?php echo $software['id']; ?>);
    }
}

// Remove installation file
function removeInstallationFile(index) {
    if (confirm('Are you sure you want to remove this installation file?')) {
        const existingFilesInput = document.querySelector('input[name="existing_files"]');
        const files = JSON.parse(existingFilesInput.value);
        files.installation_files.splice(index, 1);
        existingFilesInput.value = JSON.stringify(files);
        
        // Reload the modal to reflect changes
        loadEditModal(<?php echo $software['id']; ?>);
    }
}

// Handle form submission
document.getElementById('editSoftwareForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<i class="bi bi-spinner fa-spin"></i> Saving...';
    submitBtn.disabled = true;
    
    fetch('process_software.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Software updated successfully!');
            $('#editSoftwareModal').modal('hide');
            location.reload();
        } else {
            alert('Error updating software: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});
</script>
