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

<!-- View Software Modal -->
<div class="modal fade" id="viewSoftwareModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-code-slash"></i> Software Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">Basic Information</h6>
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td width="30%"><strong>Software Name:</strong></td>
                                <td><?php echo htmlspecialchars($software['software_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Category:</strong></td>
                                <td><?php echo htmlspecialchars($software['category']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Description:</strong></td>
                                <td><?php echo htmlspecialchars($software['description'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Vendor:</strong></td>
                                <td><?php echo htmlspecialchars($software['vendor']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Version:</strong></td>
                                <td><?php echo htmlspecialchars($software['version'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo $software['status']; ?>">
                                        <?php echo ucfirst($software['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">License Information</h6>
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td width="30%"><strong>License Type:</strong></td>
                                <td><?php echo htmlspecialchars($software['license_type']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>License Key:</strong></td>
                                <td>
                                    <?php if (!empty($software['license_key'])): ?>
                                        <code><?php echo htmlspecialchars($software['license_key']); ?></code>
                                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('<?php echo htmlspecialchars($software['license_key']); ?>')">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Purchase Date:</strong></td>
                                <td><?php echo date('F j, Y', strtotime($software['purchase_date'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Purchase Cost:</strong></td>
                                <td class="text-success">₱<?php echo number_format($software['purchase_cost'], 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Renewal Date:</strong></td>
                                <td><?php echo $software['renewal_date'] ? date('F j, Y', strtotime($software['renewal_date'])) : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Renewal Cost:</strong></td>
                                <td class="text-info">₱<?php echo number_format($software['renewal_cost'], 2); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">Assignment Information</h6>
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td width="30%"><strong>Assigned To:</strong></td>
                                <td><?php echo htmlspecialchars($software['assigned_to'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Installation Date:</strong></td>
                                <td><?php echo $software['installation_date'] ? date('F j, Y', strtotime($software['installation_date'])) : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Created Date:</strong></td>
                                <td><?php echo date('F j, Y h:i A', strtotime($software['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Last Updated:</strong></td>
                                <td><?php echo $software['updated_at'] ? date('F j, Y h:i A', strtotime($software['updated_at'])) : 'Never'; ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">Documents & Files</h6>
                        <?php if (!empty($license_doc)): ?>
                            <div class="mb-2">
                                <strong>License Document:</strong><br>
                                <a href="../uploads/software/licenses/<?php echo htmlspecialchars($license_doc); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-file-earmark-pdf"></i> View License Document
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($installation_files)): ?>
                            <div class="mb-2">
                                <strong>Installation Files:</strong><br>
                                <?php foreach ($installation_files as $index => $file): ?>
                                    <div class="mb-1">
                                        <a href="../uploads/software/installations/<?php echo htmlspecialchars($file); ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-download"></i> <?php echo htmlspecialchars($file); ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($license_doc) && empty($installation_files)): ?>
                            <p class="text-muted">No documents or files available</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($software['notes'])): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6 class="text-primary mb-3">Notes</h6>
                        <div class="alert alert-light">
                            <?php echo nl2br(htmlspecialchars($software['notes'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="editSoftwareFromView(<?php echo $software['id']; ?>)">
                    <i class="bi bi-pencil"></i> Edit
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Copy license key to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('License key copied to clipboard!');
    });
}

// Function to open edit modal from view modal
function editSoftwareFromView(id) {
    $('#viewSoftwareModal').modal('hide');
    setTimeout(() => {
        loadEditModal(id);
    }, 300);
}
</script>
