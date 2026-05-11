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

// Check if user has correct role
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

// Optional filter
$office_id = isset($_GET['office_id']) ? intval($_GET['office_id']) : 0;

// Fetch offices for dropdown (only offices that have allocatable items)
$offices = [];
$office_query = $conn->query("SELECT DISTINCT o.id, o.office_name FROM offices o JOIN consumables c ON c.for_office_id = o.id WHERE c.quantity > 0 AND c.for_office_id != 163 AND c.for_office_id IS NOT NULL AND o.office_code NOT LIKE 'L%' AND o.office_code NOT LIKE 'B%' ORDER BY o.office_name");
if ($office_query) {
    while ($row = $office_query->fetch_assoc()) {
        $offices[] = $row;
    }
}

$consumables = [];
if ($office_id > 0) {
    $stmt = $conn->prepare("SELECT id, description, quantity, units, unit_cost, for_office_id FROM consumables WHERE for_office_id = ? AND quantity > 0 ORDER BY description");
    $stmt->bind_param("i", $office_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $consumables[] = $row;
    }
    $stmt->close();
}

$message = isset($_GET['message']) ? urldecode($_GET['message']) : '';
$message_type = isset($_GET['type']) ? $_GET['type'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Release - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/admin-unified.css" rel="stylesheet">
<?php require_once 'includes/dark-mode-init.php'; ?>
</head>
<body>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
        
        <div class="main-content">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2"><i class="bi bi-box-arrow-right"></i> Bulk Release Consumables</h1>
                        <p class="text-muted mb-0">Release multiple consumables at once to a specific office</p>
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> mt-3" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="consumables.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Consumables
                        </a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row gx-3 gy-2 align-items-center">
                        <div class="col-sm-5">
                            <label class="form-label mb-0" for="office_id">Select Target Office:</label>
                        </div>
                        <div class="col-sm-5">
                            <select class="form-select" id="office_id" name="office_id" onchange="this.form.submit()">
                                <option value="0">-- Select Office --</option>
                                <?php foreach ($offices as $office): ?>
                                    <option value="<?php echo $office['id']; ?>" <?php echo $office_id == $office['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($office['office_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($office_id > 0): ?>
                <?php if (empty($consumables)): ?>
                    <div class="alert alert-warning">No releasable consumables found for this office.</div>
                <?php else: ?>
                    <form action="process_bulk_release.php" method="POST">
                        <input type="hidden" name="target_office_id" value="<?php echo $office_id; ?>">
                        
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Items Available for Release</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 5%">
                                                <input type="checkbox" class="form-check-input" id="checkAll">
                                            </th>
                                            <th>Description</th>
                                            <th>Available Qty (Units)</th>
                                            <th style="width: 20%">Release Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($consumables as $index => $item): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" class="form-check-input item-check" name="selected_items[]" value="<?php echo $item['id']; ?>">
                                                </td>
                                                <td><?php echo htmlspecialchars($item['description']); ?></td>
                                                <td><?php echo $item['quantity'] . ' ' . htmlspecialchars($item['units']); ?></td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" class="form-control qty-input" name="release_quantities[<?php echo $item['id']; ?>]" min="1" max="<?php echo $item['quantity']; ?>" disabled>
                                                        <span class="input-group-text"><?php echo htmlspecialchars($item['units']); ?></span>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Release Details</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Received By *</label>
                                        <input type="text" class="form-control" name="received_by" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Remarks</label>
                                        <input type="text" class="form-control" name="remarks">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Release Type *</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="release_type" id="release_with_deduction" value="with_deduction" checked>
                                                <label class="form-check-label" for="release_with_deduction">
                                                    <strong>With Balance Deduction</strong>
                                                    <br><small class="text-muted">Return borrowed items to supply office</small>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="release_type" id="release_without_deduction" value="without_deduction">
                                                <label class="form-check-label" for="release_without_deduction">
                                                    <strong>Without Balance Deduction</strong>
                                                    <br><small class="text-muted">Keep balance record intact</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-white text-end">
                                <button type="submit" class="btn btn-primary" id="btnRelease" disabled>Execute Bulk Release</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkAll = document.getElementById('checkAll');
            const itemChecks = document.querySelectorAll('.item-check');
            const btnRelease = document.getElementById('btnRelease');

            function updateState() {
                let anyChecked = false;
                itemChecks.forEach(check => {
                    const qtyInput = check.closest('tr').querySelector('.qty-input');
                    if (check.checked) {
                        anyChecked = true;
                        qtyInput.disabled = false;
                        if (!qtyInput.value) qtyInput.value = qtyInput.max; // auto-fill max
                    } else {
                        qtyInput.disabled = true;
                        qtyInput.value = '';
                    }
                });
                btnRelease.disabled = !anyChecked;
            }

            if (checkAll) {
                checkAll.addEventListener('change', function() {
                    itemChecks.forEach(check => check.checked = this.checked);
                    updateState();
                });
            }

            itemChecks.forEach(check => {
                check.addEventListener('change', updateState);
            });
        });
    </script>
</body>
</html>
