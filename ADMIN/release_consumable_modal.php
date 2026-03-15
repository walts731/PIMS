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

// Debug logging
error_log("DEBUG: Modal loaded with consumable_id: " . (isset($_GET['id']) ? intval($_GET['id']) : 0));
error_log("DEBUG: Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("DEBUG: GET parameters: " . print_r($_GET, true));
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("DEBUG: POST parameters: " . print_r($_POST, true));
}

// Get consumable ID from URL parameter
$consumable_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$selected_office_id = isset($_GET['office']) ? intval($_GET['office']) : 0;
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
            error_log("DEBUG: Consumable loaded successfully - ID: {$consumable['id']}, Description: {$consumable['description']}, Quantity: {$consumable['quantity']}, for_office_id: {$consumable['for_office_id']}");
        } else {
            error_log("DEBUG: Consumable not found in database - ID: {$consumable_id}");
            $consumable = null;
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("DEBUG: Error fetching consumable: " . $e->getMessage());
        $consumable = null;
    }
} else {
    error_log("DEBUG: Invalid consumable ID from URL: {$consumable_id}");
    $consumable = null;
}

if (!$consumable) {
    error_log("DEBUG: Consumable object is null, showing error message");
    echo "<div class='alert alert-danger'>Consumable not found.</div>";
    exit();
}

// Handle release form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'release') {
    $source_consumable_id = intval($_POST['source_consumable_id'] ?? 0);

    // Fallback: if source_consumable_id is not in POST, use the URL parameter
    if ($source_consumable_id <= 0 && isset($_GET['id'])) {
        $source_consumable_id = intval($_GET['id']);
        error_log("DEBUG: Using URL consumable_id as fallback: {$source_consumable_id}");
    }

    $release_quantity = intval($_POST['release_quantity'] ?? 0);
    $target_office_id = intval($_POST['target_office_id'] ?? 0);
    $received_by = trim($_POST['received_by'] ?? '');
    $release_type = trim($_POST['release_type'] ?? 'with_deduction');
    $remarks = trim($_POST['remarks'] ?? '');

    // Debug logging
    error_log("DEBUG: Release form submitted");
    error_log("DEBUG: source_consumable_id: {$source_consumable_id}");
    error_log("DEBUG: release_quantity: {$release_quantity}");
    error_log("DEBUG: target_office_id: {$target_office_id}");
    error_log("DEBUG: received_by: {$received_by}");
    error_log("DEBUG: URL consumable_id: {$consumable_id}");
    error_log("DEBUG: POST data: " . print_r($_POST, true));
    error_log("DEBUG: GET data: " . print_r($_GET, true));

    // Validation
    if ($source_consumable_id <= 0) {
        error_log("DEBUG: Invalid source consumable - source_consumable_id is {$source_consumable_id}");
        $message = "Invalid source consumable.";
        $message_type = "danger";
    } elseif ($release_quantity <= 0) {
        $message = "Release quantity must be greater than 0.";
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

            if ($release_quantity > $source_quantity) {
                throw new Exception("Cannot release {$release_quantity} items. Only {$source_quantity} items available in stock.");
            }

            // Step 1: Check Balance Record for target office
            // Look for balance records where:
            // - for_office_id matches the target office (the office receiving the release)
            // - consumable_description matches the consumable being released
            // This finds if the target office has any outstanding borrowed items that need to be returned first
            error_log("DEBUG: Checking balance for target_office_id: {$target_office_id}, consumable_description: '{$source_data['description']}' (escaped: '" . addslashes($source_data['description']) . "')");
            $balance_check_sql = "SELECT id, consumable_id, consumable_description, office_id, office_name, for_office_id, total_borrowed, total_deducted, current_balance, last_updated, created_at
                                  FROM consumable_balance
                                  WHERE for_office_id = {$target_office_id} AND consumable_description = '" . addslashes($source_data['description']) . "'
                                  FOR UPDATE";
            $balance_result = $conn->query($balance_check_sql);
            error_log("DEBUG: Balance check SQL: {$balance_check_sql}");
            error_log("DEBUG: Balance result num_rows: " . ($balance_result ? $balance_result->num_rows : 'null'));

            $current_balance_for_office = 0;
            $borrowed_deducted = 0;
            $balance_action = "No balance record found";

            if ($balance_result && $balance_result->num_rows > 0) {
                $balance_data = $balance_result->fetch_assoc();

                error_log("DEBUG: Found balance record - id: {$balance_data['id']}, office_id: {$balance_data['office_id']}, for_office_id: {$balance_data['for_office_id']}, consumable_description: '{$balance_data['consumable_description']}', current_balance: {$balance_data['current_balance']}, total_borrowed: {$balance_data['total_borrowed']}, total_deducted: {$balance_data['total_deducted']}");

                if ($release_type === 'with_deduction') {
                    // Process balance deduction - delete balance record and return items to supply office
                    $delete_stmt = $conn->prepare("DELETE FROM consumable_balance WHERE id = ?");
                    $delete_stmt->bind_param("i", $balance_data['id']);
                    if (!$delete_stmt->execute()) {
                        error_log("ERROR: Failed to delete balance record: " . $delete_stmt->error);
                        throw new Exception("Failed to delete balance record: " . $delete_stmt->error);
                    }
                    error_log("DEBUG: Balance record deleted successfully, affected rows: " . $delete_stmt->affected_rows);
                    $delete_stmt->close();
                    error_log("DEBUG: Balance record {$balance_data['id']} deleted");

                    // Delete corresponding lend_consumables records since items are being permanently released/transferred
                    $delete_lend_stmt = $conn->prepare("DELETE FROM lend_consumables WHERE consumable_id = ? AND to_office_id = ?");
                    $delete_lend_stmt->bind_param("ii", $balance_data['consumable_id'], $balance_data['for_office_id']);
                    if (!$delete_lend_stmt->execute()) {
                        error_log("ERROR: Failed to delete lend_consumables records: " . $delete_lend_stmt->error);
                        throw new Exception("Failed to update lending records: " . $delete_lend_stmt->error);
                    }
                    error_log("DEBUG: Deleted lend_consumables records for consumable_id: {$balance_data['consumable_id']}, to_office_id: {$balance_data['for_office_id']}");
                    $delete_lend_stmt->close();

                    $borrowed_deducted = $balance_data['current_balance'];
                    $balance_action = "Deleted balance record {$balance_data['id']}";
                } else {
                    // Release without deduction - keep balance record intact
                    $borrowed_deducted = 0;
                    $balance_action = "Kept balance record {$balance_data['id']} intact";
                }
            } else {
                $borrowed_deducted = 0;
                $balance_action = "No balance record found";
            }

            // Step 4: Release to Target Office (remaining quantity after balance deduction)
            $actual_release_quantity = $release_quantity - $borrowed_deducted;

            if ($actual_release_quantity > 0) {
                // Check if target office already has this consumable
                $target_stmt = $conn->prepare("SELECT id, quantity FROM consumables WHERE description = ? AND office_id = ? FOR UPDATE");
                $target_stmt->bind_param("si", $source_data['description'], $target_office_id);
                $target_stmt->execute();
                $target_result = $target_stmt->get_result();

                if ($target_result->num_rows > 0) {
                    // Update existing consumable in target office
                    $target_data = $target_result->fetch_assoc();
                    $new_target_quantity = $target_data['quantity'] + $actual_release_quantity;

                    $update_target_stmt = $conn->prepare("UPDATE consumables SET quantity = ? WHERE id = ?");
                    $update_target_stmt->bind_param("ii", $new_target_quantity, $target_data['id']);
                    $update_target_stmt->execute();
                    $update_target_stmt->close();
                } else {
                    // Insert new consumable in target office
                    $insert_target_stmt = $conn->prepare("INSERT INTO consumables (description, quantity, unit_cost, reorder_level, office_id) VALUES (?, ?, ?, ?, ?)");
                    $insert_target_stmt->bind_param("sidii",
                        $source_data['description'],
                        $actual_release_quantity,
                        $source_data['unit_cost'],
                        $source_data['reorder_level'],
                        $target_office_id
                    );
                    $insert_target_stmt->execute();
                    $insert_target_stmt->close();
                }
                $target_stmt->close();
            }

            // Step 5: Deduct from Source (ID 3 in the example)
            $new_source_quantity = $source_quantity - $release_quantity;
            $update_source_stmt = $conn->prepare("UPDATE consumables SET quantity = ? WHERE id = ?");
            $update_source_stmt->bind_param("ii", $new_source_quantity, $source_consumable_id);
            $update_source_stmt->execute();
            $update_source_stmt->close();

            // Get target office name for logging
            $office_stmt = $conn->prepare("SELECT office_name FROM offices WHERE id = ?");
            $office_stmt->bind_param("i", $target_office_id);
            $office_stmt->execute();
            $office_result = $office_stmt->get_result();
            $office_data = $office_result->fetch_assoc();
            $office_stmt->close();

            // Log release action
            $log_remarks = "Released {$release_quantity} '{$source_data['description']}' from office ID {$source_data['office_id']} to {$office_data['office_name']}. Release type: {$release_type}. {$balance_action}, returned {$borrowed_deducted} to supply office, actual release: {$actual_release_quantity}. Remarks: " . ($remarks ?: 'No remarks');
            logSystemAction($_SESSION['user_id'], 'consumable_released', 'consumable_management', $log_remarks);

            // Commit transaction
            $conn->commit();

            $message = "Successfully released {$release_quantity} '{$source_data['description']}' item(s) to {$office_data['office_name']} using {$release_type}. {$balance_action}, returned {$borrowed_deducted} to supply office. Source remaining: {$new_source_quantity}.";
            $message_type = "success";

            // Close modal on success and refresh parent consumables page with success message
            echo "<script>
                if (window.parent && window.parent !== window) {
                    // We're in an iframe, close modal and redirect parent with success message
                    window.parent.closeReleaseModal();
                    setTimeout(() => {
                        const currentUrl = new URL(window.parent.location.href);
                        currentUrl.searchParams.set('message', '" . urlencode($message) . "');
                        currentUrl.searchParams.set('type', 'success');
                        window.parent.location.href = currentUrl.toString();
                    }, 500);
                } else {
                    // We're not in an iframe, redirect to parent page with success message
                    window.location.href = 'consumables.php?message=" . urlencode($message) . "&type=success';
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

                        <?php
                        // Check if target office has outstanding balance for this consumable
                        $balance_info = null;
                        if (isset($consumable['for_office_id']) && isset($consumable['description'])) {
                            $balance_check = $conn->prepare("SELECT id, current_balance, office_name FROM consumable_balance WHERE for_office_id = ? AND consumable_description = ?");
                            $balance_check->bind_param("is", $consumable['for_office_id'], $consumable['description']);
                            $balance_check->execute();
                            $balance_result = $balance_check->get_result();
                            if ($balance_result->num_rows > 0) {
                                $balance_info = $balance_result->fetch_assoc();
                                error_log("DEBUG: Balance record found - ID: {$balance_info['id']}, office: {$consumable['for_office_id']}, description: '{$consumable['description']}', balance: {$balance_info['current_balance']}");
                            } else {
                                error_log("DEBUG: No balance found for office {$consumable['for_office_id']}, description '{$consumable['description']}'");
                            }
                            $balance_check->close();
                        } else {
                            error_log("DEBUG: Missing for_office_id or description - for_office_id: " . ($consumable['for_office_id'] ?? 'null') . ", description: " . ($consumable['description'] ?? 'null'));
                        }
                        ?>

                        <?php if ($consumable['quantity'] > 0): ?>
                            <form method="POST" action="?id=<?php echo $consumable_id; ?>">
                                <input type="hidden" name="action" value="release">
                                <input type="hidden" name="source_consumable_id" value="<?php echo $consumable['id']; ?>">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Release Quantity *</label>
                                            <input type="number" class="form-control" name="release_quantity"
                                                   min="1" max="<?php echo $consumable['quantity']; ?>" required>
                                            <small class="text-muted">Maximum available: <?php echo $consumable['quantity']; ?> items</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Target Office</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($consumable['for_office_name'] ?? 'Unknown'); ?>" readonly>
                                            <input type="hidden" name="target_office_id" value="<?php echo $consumable['for_office_id'] ?? 0; ?>">
                                            <small class="text-muted">This consumable is allocated to this office</small>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($balance_info && $balance_info['current_balance'] > 0): ?>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-info-triangle"></i>
                                        <strong>Outstanding Balance Detected:</strong> <?php echo htmlspecialchars($consumable['for_office_name'] ?? 'Target Office'); ?> has <?php echo $balance_info['current_balance']; ?> borrowed item(s) of this consumable.
                                        <br><small class="text-muted">Balance Record ID: <?php echo $balance_info['id']; ?> - You can choose to release with balance deduction (recommended) or release additional items while keeping the borrowing history intact.</small>
                                    </div>
                                <?php endif; ?>

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
                                              placeholder="Enter any remarks or notes for this release..."></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Release Type *</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="release_type" id="release_with_deduction" value="with_deduction" checked>
                                                <label class="form-check-label" for="release_with_deduction">
                                                    <strong>Release with Balance Deduction</strong>
                                                    <br><small class="text-muted">Delete balance record and return borrowed items to supply office</small>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="release_type" id="release_without_deduction" value="without_deduction">
                                                <label class="form-check-label" for="release_without_deduction">
                                                    <strong>Release without Balance Deduction</strong>
                                                    <br><small class="text-muted">Keep balance record intact and release additional items</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Note:</strong> This will release consumables permanently. If the target office has borrowed items (tracked in consumable_balance), choose "Release with Balance Deduction" to return those items to the supply office first, or "Release without Balance Deduction" to keep the borrowing history intact.
                                </div>

                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary" onclick="parent.closeReleaseModal()">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-arrow-down-left"></i> Release Consumable
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
    <script>
        // No JavaScript needed since target office is fixed
    </script>
</body>
</html>
