<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

// Get consumable ID from URL parameter
$consumable_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$consumable = null;

if ($consumable_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM consumables WHERE id = ?");
        $stmt->bind_param("i", $consumable_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $consumable = $result->fetch_assoc();
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching consumable: " . $e->getMessage());
    }
}

if (!$consumable) {
    echo "<div class='alert alert-danger'>Consumable not found.</div>";
    exit();
}

// Get offices for dropdown
$offices = [];
try {
    $result = $conn->query("SELECT id, office_name FROM offices WHERE status = 'active' ORDER BY office_name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $offices[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching offices: " . $e->getMessage());
}

// Handle release form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'release') {
    $source_consumable_id = intval($_POST['source_consumable_id'] ?? 0);
    $release_quantity = intval($_POST['release_quantity'] ?? 0);
    $target_office_id = intval($_POST['target_office_id'] ?? 0);
    $received_by = trim($_POST['received_by'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    
    // Validation
    if ($source_consumable_id <= 0) {
        $message = "Invalid source consumable.";
        $message_type = "danger";
    } elseif ($release_quantity <= 0) {
        $message = "Release quantity must be greater than 0.";
        $message_type = "danger";
    } elseif ($target_office_id <= 0) {
        $message = "Please select a target office.";
        $message_type = "danger";
    } elseif (empty($received_by)) {
        $message = "Please enter the name of the person receiving the consumables.";
        $message_type = "danger";
    } else {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Get source consumable data
            $source_stmt = $conn->prepare("SELECT * FROM consumables WHERE id = ? FOR UPDATE");
            $source_stmt->bind_param("i", $source_consumable_id);
            $source_stmt->execute();
            $source_result = $source_stmt->get_result();
            
            if ($source_result->num_rows === 0) {
                throw new Exception("Source consumable not found.");
            }
            
            $source_data = $source_result->fetch_assoc();
            $source_quantity = $source_data['quantity'];
            
            if ($release_quantity > $source_quantity) {
                throw new Exception("Cannot release {$release_quantity} items. Only {$source_quantity} items available in stock.");
            }
            
            // Check if target office already has this consumable
            $target_stmt = $conn->prepare("SELECT id, quantity FROM consumables WHERE description = ? AND office_id = ? FOR UPDATE");
            $target_stmt->bind_param("si", $source_data['description'], $target_office_id);
            $target_stmt->execute();
            $target_result = $target_stmt->get_result();
            
            if ($target_result->num_rows > 0) {
                // Update existing consumable in target office
                $target_data = $target_result->fetch_assoc();
                $new_target_quantity = $target_data['quantity'] + $release_quantity;
                
                $update_target_stmt = $conn->prepare("UPDATE consumables SET quantity = ? WHERE id = ?");
                $update_target_stmt->bind_param("ii", $new_target_quantity, $target_data['id']);
                $update_target_stmt->execute();
                $update_target_stmt->close();
                
                $target_action = "Updated existing consumable in target office";
            } else {
                // Insert new consumable in target office
                $insert_target_stmt = $conn->prepare("INSERT INTO consumables (description, quantity, unit_cost, reorder_level, office_id) VALUES (?, ?, ?, ?, ?)");
                $insert_target_stmt->bind_param("sidii", 
                    $source_data['description'], 
                    $release_quantity, 
                    $source_data['unit_cost'], 
                    $source_data['reorder_level'], 
                    $target_office_id
                );
                $insert_target_stmt->execute();
                $insert_target_stmt->close();
                
                $target_action = "Created new consumable in target office";
            }
            $target_stmt->close();
            
            // Update source consumable quantity
            $new_source_quantity = $source_quantity - $release_quantity;
            $update_source_stmt = $conn->prepare("UPDATE consumables SET quantity = ? WHERE id = ?");
            $update_source_stmt->bind_param("ii", $new_source_quantity, $source_consumable_id);
            $update_source_stmt->execute();
            $update_source_stmt->close();
            
            // Record release history
            $total_value = $release_quantity * $source_data['unit_cost'];
            $history_stmt = $conn->prepare("INSERT INTO consumable_release_history (consumable_id, description, quantity_released, unit_cost, total_value, from_office_id, to_office_id, released_by, received_by, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $history_stmt->bind_param("isddiiisss", 
                $source_consumable_id,
                $source_data['description'],
                $release_quantity,
                $source_data['unit_cost'],
                $total_value,
                $source_data['office_id'],
                $target_office_id,
                $_SESSION['user_id'],
                $received_by,
                $remarks
            );
            $history_stmt->execute();
            $history_stmt->close();
            
            // Get target office name for logging
            $office_stmt = $conn->prepare("SELECT office_name FROM offices WHERE id = ?");
            $office_stmt->bind_param("i", $target_office_id);
            $office_stmt->execute();
            $office_result = $office_stmt->get_result();
            $office_data = $office_result->fetch_assoc();
            $office_stmt->close();
            
            // Log the release action
            $log_remarks = "Released {$release_quantity} '{$source_data['description']}' from office ID {$source_data['office_id']} to {$office_data['office_name']}. {$target_action}. Remarks: " . ($remarks ?: 'No remarks');
            logSystemAction($_SESSION['user_id'], 'consumable_released', 'consumable_management', $log_remarks);
            
            // Commit transaction
            $conn->commit();
            
            $message = "Successfully released {$release_quantity} '{$source_data['description']}' item(s) to {$office_data['office_name']}. Source remaining: {$new_source_quantity}.";
            $message_type = "success";
            
            // Close modal on success and refresh parent consumables page
            echo "<script>
                if (window.parent && window.parent !== window) {
                    // We're in an iframe, refresh the parent window
                    window.parent.location.reload();
                } else {
                    // We're not in an iframe, reload current page
                    window.location.reload();
                }
            </script>";
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error releasing consumable: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Release Consumable - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f8f9fa;
            padding: 20px;
        }
        .modal-header {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
        }
        .form-label {
            font-weight: 600;
            color: #333;
        }
        .consumable-info {
            background: #e3f2fd;
            border-left: 4px solid #191BA9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .quantity-display {
            font-size: 1.2rem;
            font-weight: 700;
            color: #191BA9;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-box-arrow-right"></i> Release Consumable
                        </h5>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> m-3" role="alert">
                            <i class="bi bi-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <!-- Source Consumable Information -->
                        <div class="consumable-info">
                            <h6><i class="bi bi-info-circle"></i> Source Consumable Information</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Description:</strong> <?php echo htmlspecialchars($consumable['description']); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Available Quantity:</strong> 
                                    <span class="quantity-display"><?php echo $consumable['quantity']; ?></span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <strong>Unit Cost:</strong> <?php echo number_format($consumable['unit_cost'], 2); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Current Office:</strong> <?php echo htmlspecialchars($consumable['office_name'] ?? 'Unknown'); ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($consumable['quantity'] > 0): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="release">
                                <input type="hidden" name="source_consumable_id" value="<?php echo $consumable['id']; ?>">
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Release Quantity *</label>
                                            <input type="number" class="form-control" name="release_quantity" 
                                                   min="1" max="<?php echo $consumable['quantity']; ?>" required>
                                            <small class="text-muted">Maximum available: <?php echo $consumable['quantity']; ?> items</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Target Office *</label>
                                            <select class="form-select" name="target_office_id" required>
                                                <option value="">Select Office</option>
                                                <?php foreach ($offices as $office): ?>
                                                    <?php if ($office['id'] != $consumable['office_id']): ?>
                                                        <option value="<?php echo $office['id']; ?>">
                                                            <?php echo htmlspecialchars($office['office_name']); ?>
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted">Select office to receive consumables</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Received By</label>
                                            <input type="text" class="form-control" name="received_by" 
                                                   placeholder="Enter name of person receiving" required>
                                            <small class="text-muted">Name of person receiving the consumables</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Remarks</label>
                                    <textarea class="form-control" name="remarks" rows="3" 
                                              placeholder="Enter any remarks or notes for this release..."></textarea>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> 
                                    <strong>Note:</strong> If the target office already has this consumable, the quantity will be added to their existing stock. Otherwise, a new consumable record will be created.
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary" onclick="parent.closeReleaseModal()">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-box-arrow-right"></i> Release Consumable
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>No Stock Available:</strong> This consumable has 0 items available for release.
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-secondary" onclick="parent.closeReleaseModal()">
                                    <i class="bi bi-x-circle"></i> Close
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
