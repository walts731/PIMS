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

<!-- Edit Infrastructure Modal -->
<div class="modal fade" id="editInfrastructureModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil"></i> Edit Infrastructure Item
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="process_infrastructure.php" enctype="multipart/form-data" id="editInfrastructureForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?php echo $infrastructure['id']; ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Basic Information</h6>
                            <div class="mb-3">
                                <label class="form-label">Classification/Type *</label>
                                <input type="text" name="classification" class="form-control" value="<?php echo htmlspecialchars($infrastructure['classification']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nature Occupancy</label>
                                <input type="text" name="nature_occupancy" class="form-control" value="<?php echo htmlspecialchars($infrastructure['nature_occupancy'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Item Description *</label>
                                <textarea name="item_description" class="form-control" rows="3" required><?php echo htmlspecialchars($infrastructure['item_description']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Location *</label>
                                <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($infrastructure['location']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date Constructed *</label>
                                <input type="date" name="date_constructed" class="form-control" value="<?php echo $infrastructure['date_constructed']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Property No./Other Reference</label>
                                <input type="text" name="property_no" class="form-control" value="<?php echo htmlspecialchars($infrastructure['property_no'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Financial Information</h6>
                            <div class="mb-3">
                                <label class="form-label">Acquisition Cost *</label>
                                <input type="number" name="acquisition_cost" class="form-control" step="0.01" value="<?php echo $infrastructure['acquisition_cost']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Market/Appraisal Value</label>
                                <input type="number" name="market_value" class="form-control" step="0.01" value="<?php echo $infrastructure['market_value']; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date of Appraisal</label>
                                <input type="date" name="date_appraisal" class="form-control" value="<?php echo $infrastructure['date_appraisal'] ?? ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" class="form-control" rows="3"><?php echo htmlspecialchars($infrastructure['remarks'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">Additional Images</h6>
                            <?php if (!empty($images)): ?>
                                <div class="mb-3">
                                    <label class="form-label">Current Images:</label>
                                    <div class="row">
                                        <?php foreach ($images as $index => $image): ?>
                                            <div class="col-md-3 mb-2">
                                                <div class="position-relative">
                                                    <img src="../uploads/infrastructure/<?php echo htmlspecialchars($image); ?>" 
                                                         class="img-fluid rounded shadow-sm" 
                                                         alt="Infrastructure image"
                                                         style="width: 100%; height: 150px; object-fit: cover;">
                                                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1" 
                                                            onclick="removeExistingImage(<?php echo $index; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="existing_images" value="<?php echo htmlspecialchars(json_encode($images)); ?>">
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label class="form-label">Add New Images (max 4 total):</label>
                                <input type="file" name="additional_images[]" class="form-control" multiple accept="image/*">
                                <small class="text-muted">You can upload up to 4 images total</small>
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
// Remove existing image
function removeExistingImage(index) {
    if (confirm('Are you sure you want to remove this image?')) {
        const existingImagesInput = document.querySelector('input[name="existing_images"]');
        const images = JSON.parse(existingImagesInput.value);
        images.splice(index, 1);
        existingImagesInput.value = JSON.stringify(images);
        
        // Reload the modal to reflect changes
        loadEditModal(<?php echo $infrastructure['id']; ?>);
    }
}

// Handle form submission
document.getElementById('editInfrastructureForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<i class="bi bi-spinner fa-spin"></i> Saving...';
    submitBtn.disabled = true;
    
    fetch('process_infrastructure.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Infrastructure item updated successfully!');
            $('#editInfrastructureModal').modal('hide');
            location.reload();
        } else {
            alert('Error updating infrastructure item: ' + data.message);
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
