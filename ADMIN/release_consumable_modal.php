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

require_once 'includes/check_permissions.php';
adminRequirePermission('consumables.manage', 'can_update', 'consumables.php');

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

            $source_data     = $source_result->fetch_assoc();
            $source_quantity = intval($source_data['quantity']);
            $supply_office_id = intval($source_data['office_id']);

            if ($release_quantity > $source_quantity) {
                throw new Exception("Cannot release {$release_quantity} items. Only {$source_quantity} items available in stock.");
            }

            // ── STEP 1: Check for an outstanding borrow balance for the target office ──────────
            // consumable_balance rows are keyed: office_id=supplier, for_office_id=borrower
            // So we look for: for_office_id = target AND description matches.
            $outstanding_balance = 0;
            $balance_action      = "No balance record found";

            $bal_stmt = $conn->prepare(
                "SELECT id, consumable_id, current_balance
                 FROM consumable_balance
                 WHERE for_office_id = ? AND consumable_description = ?
                 LIMIT 1
                 FOR UPDATE"
            );
            $bal_stmt->bind_param("is", $target_office_id, $source_data['description']);
            $bal_stmt->execute();
            $bal_result = $bal_stmt->get_result();
            $bal_stmt->close();

            if ($bal_result->num_rows > 0) {
                $balance_data        = $bal_result->fetch_assoc();
                $outstanding_balance = intval($balance_data['current_balance']);
                error_log("DEBUG: Balance record id={$balance_data['id']}, outstanding={$outstanding_balance}");

                if ($release_type === 'with_deduction' && $outstanding_balance > 0) {

                    // ── DELETE the consumable_balance record ──────────────────────────────────
                    $del_bal = $conn->prepare("DELETE FROM consumable_balance WHERE id = ?");
                    $del_bal->bind_param("i", $balance_data['id']);
                    if (!$del_bal->execute()) {
                        throw new Exception("Failed to delete balance record: " . $del_bal->error);
                    }
                    $del_bal->close();
                    error_log("DEBUG: Balance record {$balance_data['id']} deleted");

                    // ── Clean up matching lend_consumables rows ───────────────────────────────
                    $del_lend = $conn->prepare("DELETE FROM lend_consumables WHERE consumable_id = ? AND to_office_id = ?");
                    $del_lend->bind_param("ii", $balance_data['consumable_id'], $target_office_id);
                    $del_lend->execute();
                    $del_lend->close();

                    // ── Return the outstanding qty to the Supply Office's OWN stock row ───────
                    // The "own stock" row is: office_id = supply AND for_office_id = supply (or NULL).
                    $ret_stmt = $conn->prepare(
                        "SELECT id, quantity FROM consumables
                         WHERE description = ? AND office_id = ? AND (for_office_id = ? OR for_office_id IS NULL)
                         ORDER BY (for_office_id IS NULL) ASC
                         LIMIT 1
                         FOR UPDATE"
                    );
                    $ret_stmt->bind_param("sii", $source_data['description'], $supply_office_id, $supply_office_id);
                    $ret_stmt->execute();
                    $ret_result = $ret_stmt->get_result();
                    $ret_stmt->close();

                    if ($ret_result->num_rows > 0) {
                        $ret_row        = $ret_result->fetch_assoc();
                        $new_ret_qty    = intval($ret_row['quantity']) + $outstanding_balance;
                        $upd_ret = $conn->prepare("UPDATE consumables SET quantity = ? WHERE id = ?");
                        $upd_ret->bind_param("ii", $new_ret_qty, $ret_row['id']);
                        $upd_ret->execute();
                        $upd_ret->close();
                        $balance_action = "Deleted balance record {$balance_data['id']}, returned {$outstanding_balance} to supply stock (consumable id={$ret_row['id']})";
                        error_log("DEBUG: Returned {$outstanding_balance} to supply consumable id={$ret_row['id']}, new qty={$new_ret_qty}");
                    } else {
                        // No own-stock row exists — create one
                        $ins_ret = $conn->prepare(
                            "INSERT INTO consumables (description, quantity, unit_cost, reorder_level, office_id, for_office_id)
                             VALUES (?, ?, ?, ?, ?, ?)"
                        );
                        $reorder = intval($source_data['reorder_level']);
                        $ins_ret->bind_param("siidii",
                            $source_data['description'],
                            $outstanding_balance,
                            $source_data['unit_cost'],
                            $reorder,
                            $supply_office_id,
                            $supply_office_id
                        );
                        $ins_ret->execute();
                        $ins_ret->close();
                        $balance_action = "Deleted balance record {$balance_data['id']}, inserted new supply stock row with {$outstanding_balance} returned items";
                        error_log("DEBUG: Inserted new supply stock row with qty={$outstanding_balance}");
                    }

                } else {
                    // without_deduction — leave balance intact, treat as a straight release
                    $outstanding_balance = 0;
                    $balance_action      = "Kept balance record {$balance_data['id']} intact (without_deduction)";
                }
            }

            // ── STEP 2: Compute actual quantity to physically release to the target office ─────
            // with_deduction:    actual = release_qty - outstanding_balance
            //   (the balance portion has already been "returned" to supply above)
            // without_deduction: actual = release_qty (full amount goes to target)
            $actual_release_quantity = $release_quantity - $outstanding_balance;

            if ($actual_release_quantity < 0) {
                throw new Exception(
                    "Outstanding balance ({$outstanding_balance}) exceeds release quantity ({$release_quantity}). " .
                    "Increase the release quantity to at least {$outstanding_balance}."
                );
            }

            // ── STEP 3: Add actual_release_quantity to the target office's stock ───────────────
            if ($actual_release_quantity > 0) {
                $tgt_stmt = $conn->prepare("SELECT id, quantity FROM consumables WHERE description = ? AND office_id = ? FOR UPDATE");
                $tgt_stmt->bind_param("si", $source_data['description'], $target_office_id);
                $tgt_stmt->execute();
                $tgt_result = $tgt_stmt->get_result();

                if ($tgt_result->num_rows > 0) {
                    $tgt_row      = $tgt_result->fetch_assoc();
                    $new_tgt_qty  = intval($tgt_row['quantity']) + $actual_release_quantity;
                    $upd_tgt = $conn->prepare("UPDATE consumables SET quantity = ? WHERE id = ?");
                    $upd_tgt->bind_param("ii", $new_tgt_qty, $tgt_row['id']);
                    $upd_tgt->execute();
                    $upd_tgt->close();
                } else {
                    $reorder = intval($source_data['reorder_level']);
                    $ins_tgt = $conn->prepare("INSERT INTO consumables (description, quantity, unit_cost, reorder_level, office_id) VALUES (?, ?, ?, ?, ?)");
                    $ins_tgt->bind_param("siidi",
                        $source_data['description'],
                        $actual_release_quantity,
                        $source_data['unit_cost'],
                        $reorder,
                        $target_office_id
                    );
                    $ins_tgt->execute();
                    $ins_tgt->close();
                }
                $tgt_stmt->close();
            }

            // ── STEP 4: Deduct the FULL release_quantity from the source ───────────────────
            // All 50 items leave the source (id=3).
            // They are split into two destinations:
            //   → actual_release_quantity (e.g. 20) goes to the target office (ADMIN)
            //   → outstanding_balance     (e.g. 30) was already returned to Supply own-stock
            // Total: 20 + 30 = 50 = release_quantity  → source becomes 0.
            $new_source_quantity = $source_quantity - $release_quantity;
            $upd_src = $conn->prepare("UPDATE consumables SET quantity = ? WHERE id = ?");
            $upd_src->bind_param("ii", $new_source_quantity, $source_consumable_id);
            $upd_src->execute();
            $upd_src->close();

            // Get target office name for logging
            $office_stmt = $conn->prepare("SELECT office_name FROM offices WHERE id = ?");
            $office_stmt->bind_param("i", $target_office_id);
            $office_stmt->execute();
            $office_data = $office_stmt->get_result()->fetch_assoc();
            $office_stmt->close();

            // Log release action
            $log_remarks = "Released {$release_quantity} '{$source_data['description']}' from source (id={$source_consumable_id}) "
                         . "→ {$actual_release_quantity} to {$office_data['office_name']}, "
                         . "{$outstanding_balance} returned to supply stock. "
                         . "(release_type={$release_type}). {$balance_action}. Source remaining: {$new_source_quantity}. "
                         . "Remarks: " . ($remarks ?: 'No remarks');
            logSystemAction($_SESSION['user_id'], 'consumable_released', 'consumable_management', $log_remarks);

            // Commit transaction
            $conn->commit();

            $message = "Successfully released {$release_quantity} '{$source_data['description']}' item(s) from source. "
                     . "→ {$actual_release_quantity} item(s) delivered to {$office_data['office_name']}, "
                     . "{$outstanding_balance} item(s) returned to supply stock. "
                     . "{$balance_action}. Source remaining: {$new_source_quantity}.";
            $message_type = "success";
            $_SESSION['success_message'] = $message;

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
                                            <input type="text"
                                                   inputmode="numeric"
                                                   pattern="[0-9]+"
                                                   class="form-control"
                                                   name="release_quantity"
                                                   id="releaseQuantityInput"
                                                   autocomplete="off"
                                                   data-max="<?php echo $consumable['quantity']; ?>"
                                                   placeholder="Enter quantity (max <?php echo $consumable['quantity']; ?>)"
                                                   required>
                                            <small class="text-muted">Maximum available: <strong><?php echo $consumable['quantity']; ?></strong> items</small>
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
                            <script>
                                document.querySelector('form').addEventListener('submit', function(e) {
                                    const input = document.getElementById('releaseQuantityInput');
                                    const raw   = input.value.trim();
                                    const max   = parseInt(input.dataset.max, 10);

                                    if (!/^\d+$/.test(raw)) {
                                        e.preventDefault();
                                        alert('Please enter a valid whole number.');
                                        input.focus();
                                        return;
                                    }

                                    const val = parseInt(raw, 10);

                                    if (val <= 0) {
                                        e.preventDefault();
                                        alert('Quantity must be greater than 0.');
                                        input.focus();
                                        return;
                                    }

                                    if (val > max) {
                                        e.preventDefault();
                                        alert('Cannot release ' + val + ' items.\nOnly ' + max + ' items are available in stock.\nPlease enter ' + max + ' or less.');
                                        input.value = '';
                                        input.focus();
                                        return;
                                    }

                                    // Set clean integer value
                                    input.value = val;
                                });

                                // Block non-numeric keystrokes
                                document.getElementById('releaseQuantityInput').addEventListener('keypress', function(e) {
                                    if (!/[0-9]/.test(e.key)) {
                                        e.preventDefault();
                                    }
                                });
                            </script>
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
