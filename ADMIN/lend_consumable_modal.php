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

// Create lend_consumables table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS lend_consumables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consumable_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity_lent INT NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,
    total_value DECIMAL(10,2) NOT NULL,
    from_office_id INT NOT NULL,
    to_office_id INT NOT NULL,
    lent_by INT NOT NULL,
    received_by VARCHAR(255) NOT NULL,
    date_lent DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expected_return_date DATE NULL,
    actual_return_date DATETIME NULL,
    status ENUM('lent', 'returned', 'overdue') NOT NULL DEFAULT 'lent',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (consumable_id) REFERENCES consumables(id),
    FOREIGN KEY (from_office_id) REFERENCES offices(id),
    FOREIGN KEY (to_office_id) REFERENCES offices(id),
    FOREIGN KEY (lent_by) REFERENCES users(id),
    INDEX idx_consumable_id (consumable_id),
    INDEX idx_status (status),
    INDEX idx_date_lent (date_lent)
)";
$conn->query($create_table_sql);

// Get consumable ID from URL parameter
$consumable_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$consumable = null;

if ($consumable_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT c.*, o.office_name, fo.office_name as for_office_name 
                            FROM consumables c 
                            LEFT JOIN offices o ON c.office_id = o.id 
                            LEFT JOIN offices fo ON c.for_office_id = fo.id 
                            WHERE c.id = ?");
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

// Handle lend form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'lend') {
    $source_consumable_id = intval($_POST['source_consumable_id'] ?? 0);
    $lend_quantity = intval($_POST['lend_quantity'] ?? 0);
    $target_office_id = intval($_POST['target_office_id'] ?? 0);
    $received_by = trim($_POST['received_by'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    
    // Validation
    if ($source_consumable_id <= 0) {
        $message = "Invalid source consumable.";
        $message_type = "danger";
    } elseif ($lend_quantity <= 0) {
        $message = "Lend quantity must be greater than 0.";
        $message_type = "danger";
    } elseif ($target_office_id <= 0) {
        $message = "Please select a target office.";
        $message_type = "danger";
    } elseif (empty($received_by)) {
        $message = "Please enter name of person receiving consumables.";
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
            
            if ($lend_quantity > $source_quantity) {
                throw new Exception("Cannot lend {$lend_quantity} items. Only {$source_quantity} items available in stock.");
            }
            
            // Check if target office already has this consumable
            $target_stmt = $conn->prepare("SELECT id, quantity FROM consumables WHERE description = ? AND office_id = ? FOR UPDATE");
            $target_stmt->bind_param("si", $source_data['description'], $target_office_id);
            $target_stmt->execute();
            $target_result = $target_stmt->get_result();
            
            if ($target_result->num_rows > 0) {
                // Update existing consumable in target office
                $target_data = $target_result->fetch_assoc();
                $new_target_quantity = $target_data['quantity'] + $lend_quantity;
                
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
                    $lend_quantity, 
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
            $new_source_quantity = $source_quantity - $lend_quantity;
            $update_source_stmt = $conn->prepare("UPDATE consumables SET quantity = ? WHERE id = ?");
            $update_source_stmt->bind_param("ii", $new_source_quantity, $source_consumable_id);
            $update_source_stmt->execute();
            $update_source_stmt->close();
            
            // Insert into lend_consumables table
            $total_value = $lend_quantity * $source_data['unit_cost'];
            $lend_stmt = $conn->prepare("INSERT INTO lend_consumables (consumable_id, description, quantity_lent, unit_cost, total_value, from_office_id, to_office_id, lent_by, received_by, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $lend_stmt->bind_param("isddiissss", 
                $source_consumable_id,          // i
                $source_data['description'],      // s
                $lend_quantity,                 // i
                $source_data['unit_cost'],       // d
                $total_value,                   // d
                $source_data['office_id'],        // i
                $target_office_id,               // i
                $_SESSION['user_id'],           // i
                $received_by,                   // s
                $remarks                        // s
            );
            $lend_stmt->execute();
            $lend_stmt->close();
            
            // Get target office name for logging
            $office_stmt = $conn->prepare("SELECT office_name FROM offices WHERE id = ?");
            $office_stmt->bind_param("i", $target_office_id);
            $office_stmt->execute();
            $office_result = $office_stmt->get_result();
            $office_data = $office_result->fetch_assoc();
            $office_stmt->close();
            
            // Log lend action
            $log_remarks = "Lent {$lend_quantity} '{$source_data['description']}' from office ID {$source_data['office_id']} to {$office_data['office_name']}. {$target_action}. Remarks: " . ($remarks ?: 'No remarks');
            logSystemAction($_SESSION['user_id'], 'consumable_lent', 'consumable_management', $log_remarks);
            
            // Commit transaction
            $conn->commit();
            
            $message = "Successfully lent {$lend_quantity} '{$source_data['description']}' item(s) to {$office_data['office_name']}. Source remaining: {$new_source_quantity}.";
            $message_type = "success";
            
            // Close modal on success and refresh parent consumables page
            echo "<script>
                if (window.parent && window.parent !== window) {
                    // We're in an iframe, call parent success function
                    window.parent.showLendSuccess('{$message}');
                    window.parent.closeLendModal();
                    setTimeout(() => {
                        window.parent.location.reload();
                    }, 1000);
                } else {
                    // We're not in an iframe, reload current page
                    window.location.reload();
                }
            </script>";
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error lending consumable: " . $e->getMessage();
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
    <title>Lend Consumable - PIMS</title>
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
            background: var(--primary-gradient);
            color: white;
        }
        .form-label {
            font-weight: 600;
            color: #333;
        }
        .consumable-info {
            background: #e3f2fd;
            border-left: 4px solid var(--primary-color);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .quantity-display {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    
                    
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
                                <input type="hidden" name="action" value="lend">
                                <input type="hidden" name="source_consumable_id" value="<?php echo $consumable['id']; ?>">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Lend Quantity *</label>
                                            <input type="number" class="form-control" name="lend_quantity" 
                                                   min="1" max="<?php echo $consumable['quantity']; ?>" required>
                                            <small class="text-muted">Maximum available: <?php echo $consumable['quantity']; ?> items</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Target Office *</label>
                                            <select class="form-select" name="target_office_id" required>
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
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Received By *</label>
                                            <input type="text" class="form-control" name="received_by" 
                                                   placeholder="Enter name of person receiving" required>
                                            <small class="text-muted">Name of person receiving consumables</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Remarks</label>
                                    <textarea class="form-control" name="remarks" rows="3" 
                                              placeholder="Enter any remarks or notes for this lending..."></textarea>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> 
                                    <strong>Note:</strong> This will lend consumables temporarily. Items will be tracked in the lend system and expected to be returned. If target office already has this consumable, quantity will be added to their existing stock.
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary" onclick="parent.closeLendModal()">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-arrow-up-right"></i> Lend Consumable
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>No Stock Available:</strong> This consumable has 0 items available for lending.
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-secondary" onclick="parent.closeLendModal()">
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
