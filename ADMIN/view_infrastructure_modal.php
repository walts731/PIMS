<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Get infrastructure ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id === 0) {
    echo '<div class="alert alert-danger">Invalid infrastructure ID</div>';
    exit();
}

// Get infrastructure data
$sql = "SELECT * FROM infrastructure WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="alert alert-danger">Infrastructure item not found</div>';
    exit();
}

$infrastructure = $result->fetch_assoc();
$stmt->close();

// Get images
$images = json_decode($infrastructure['additional_images'] ?? '[]', true);
?>

<!-- View Infrastructure Modal -->
<div class="modal fade" id="viewInfrastructureModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-building"></i> Infrastructure Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">Basic Information</h6>
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td width="30%"><strong>Classification/Type:</strong></td>
                                <td><?php echo htmlspecialchars($infrastructure['classification']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Item Description:</strong></td>
                                <td><?php echo htmlspecialchars($infrastructure['item_description']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Nature Occupancy:</strong></td>
                                <td><?php echo htmlspecialchars($infrastructure['nature_occupancy'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Location:</strong></td>
                                <td><?php echo htmlspecialchars($infrastructure['location']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Date Constructed:</strong></td>
                                <td><?php echo date('F j, Y', strtotime($infrastructure['date_constructed'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Property No.:</strong></td>
                                <td><?php echo htmlspecialchars($infrastructure['property_no'] ?? 'N/A'); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">Financial Information</h6>
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td width="30%"><strong>Acquisition Cost:</strong></td>
                                <td class="text-success">₱<?php echo number_format($infrastructure['acquisition_cost'], 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Market/Appraisal Value:</strong></td>
                                <td class="text-info">₱<?php echo number_format($infrastructure['market_value'], 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Date of Appraisal:</strong></td>
                                <td><?php echo $infrastructure['date_appraisal'] ? date('F j, Y', strtotime($infrastructure['date_appraisal'])) : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Created Date:</strong></td>
                                <td><?php echo date('F j, Y h:i A', strtotime($infrastructure['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Last Updated:</strong></td>
                                <td><?php echo $infrastructure['updated_at'] ? date('F j, Y h:i A', strtotime($infrastructure['updated_at'])) : 'Never'; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php if (!empty($infrastructure['remarks'])): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6 class="text-primary mb-3">Remarks</h6>
                        <div class="alert alert-light">
                            <?php echo nl2br(htmlspecialchars($infrastructure['remarks'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($images)): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6 class="text-primary mb-3">Additional Images</h6>
                        <div class="row">
                            <?php foreach ($images as $image): ?>
                                <div class="col-md-3 mb-3">
                                    <img src="../uploads/infrastructure/<?php echo htmlspecialchars($image); ?>" 
                                         class="img-fluid rounded shadow-sm" 
                                         alt="Infrastructure image"
                                         style="width: 100%; height: 200px; object-fit: cover; cursor: pointer;"
                                         onclick="window.open(this.src, '_blank')">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="editInfrastructureFromView(<?php echo $infrastructure['id']; ?>)">
                    <i class="bi bi-pencil"></i> Edit
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Function to open edit modal from view modal
function editInfrastructureFromView(id) {
    $('#viewInfrastructureModal').modal('hide');
    setTimeout(() => {
        loadEditModal(id);
    }, 300);
}
</script>
