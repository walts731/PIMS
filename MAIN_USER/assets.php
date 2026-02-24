<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

checkSessionTimeout();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['system_admin', 'admin', 'main_user'], true)) {
    header('Location: ../index.php');
    exit();
}

logSystemAction($_SESSION['user_id'], 'access', 'main_user_assets', 'Main user accessed assets list');

$assets = [];
$error = null;

$office_filter = isset($_GET['office_id']) ? (int)$_GET['office_id'] : 0;
$offices = [];

$status_filter = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
$allowed_statuses = ['serviceable', 'unserviceable', 'red_tagged', 'borrowed', 'no_tag'];
if ($status_filter !== '' && !in_array($status_filter, $allowed_statuses, true)) {
    $status_filter = '';
}

if (!$conn || $conn->connect_error) {
    $error = 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error');
} else {
    try {
        $res = $conn->query("SELECT id, office_name FROM offices ORDER BY office_name ASC");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $offices[] = $row;
            }
        }

        $sql = "SELECT 
                    a.id,
                    a.description,
                    a.unit,
                    a.quantity,
                    a.unit_cost,
                    a.updated_at,
                    ac.category_name,
                    ac.category_code,
                    o.office_name,
                    COUNT(ai.id) AS item_count,
                    COALESCE(SUM(ai.value), 0) AS items_total_value
                FROM assets a
                LEFT JOIN asset_categories ac ON ac.id = a.asset_categories_id
                LEFT JOIN offices o ON o.id = a.office_id
                LEFT JOIN asset_items ai ON ai.asset_id = a.id AND ai.office_id = a.office_id";

        $params = [];
        $types = '';
        $where_clauses = [];

        if ($status_filter !== '') {
            $where_clauses[] = "ai.status = ?";
            $params[] = $status_filter;
            $types .= 's';
        }

        if ($office_filter > 0) {
            $where_clauses[] = "a.office_id = ?";
            $params[] = $office_filter;
            $types .= 'i';
        }

        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }

        $sql .= " GROUP BY a.id";

        if ($status_filter !== '') {
            $sql .= " HAVING COUNT(ai.id) > 0";
        }

        $sql .= " ORDER BY a.updated_at DESC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error = 'Failed to prepare query.';
        } else {
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $assets[] = $row;
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        $error = 'Error loading assets: ' . $e->getMessage();
        error_log('Main User Assets Error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assets - Main User | PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="../ADMIN/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php $page_title = 'Assets'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>

        <div class="main-content">
            <div class="dashboard-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-1" style="font-weight: 700; color: #191BA9;">
                            <i class="bi bi-box-seam me-2"></i>Assets
                        </h1>
                        <p class="text-muted mb-0">Viewing assets across all offices.</p>
                        <?php if ($error): ?>
                            <div class="alert alert-warning mt-2 mb-0 py-2" role="alert">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <small><?php echo htmlspecialchars($error); ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
                            <a class="btn btn-outline-primary btn-sm" href="assets.php">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </a>
                            <div class="d-inline-block" style="min-width: 200px;">
                                <select class="form-select form-select-sm" id="officeFilter">
                                    <option value="0" <?php echo $office_filter === 0 ? 'selected' : ''; ?>>All Offices</option>
                                    <?php foreach ($offices as $office): ?>
                                        <option value="<?php echo (int)$office['id']; ?>" <?php echo $office_filter === (int)$office['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($office['office_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="d-inline-block" style="min-width: 180px;">
                                <select class="form-select form-select-sm" id="statusFilter">
                                    <option value="" <?php echo $status_filter === '' ? 'selected' : ''; ?>>All Statuses</option>
                                    <option value="serviceable" <?php echo $status_filter === 'serviceable' ? 'selected' : ''; ?>>Serviceable</option>
                                    <option value="unserviceable" <?php echo $status_filter === 'unserviceable' ? 'selected' : ''; ?>>Unserviceable</option>
                                    <option value="red_tagged" <?php echo $status_filter === 'red_tagged' ? 'selected' : ''; ?>>Red-Tagged</option>
                                    <option value="borrowed" <?php echo $status_filter === 'borrowed' ? 'selected' : ''; ?>>Borrowed</option>
                                    <option value="no_tag" <?php echo $status_filter === 'no_tag' ? 'selected' : ''; ?>>No Tag</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th class="text-center">Office</th>
                                <th>Updated</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Unit Cost</th>
                                <th class="text-end">Items</th>
                                <th class="text-end">Items Total Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$error && !empty($assets)): ?>
                                <?php foreach ($assets as $row): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-semibold"><?php echo htmlspecialchars($row['description'] ?? ''); ?></div>
                                            <div class="text-muted small">
                                                <?php echo htmlspecialchars(($row['category_code'] ?? '') !== '' ? ($row['category_code'] . ' - ') : ''); ?><?php echo htmlspecialchars($row['category_name'] ?? ''); ?>
                                            </div>
                                        </td>
                                        <td class="text-muted small text-center ps-3"><?php echo htmlspecialchars($row['office_name'] ?? ''); ?></td>
                                        <td class="text-muted small ps-3"><?php echo htmlspecialchars($row['updated_at'] ?? ''); ?></td>
                                        <td class="text-end ps-3"><?php echo number_format((float)($row['quantity'] ?? 0), 0); ?></td>
                                        <td class="text-end ps-3"><?php echo number_format((float)($row['unit_cost'] ?? 0), 2); ?></td>
                                        <td class="text-end ps-3"><?php echo number_format((int)($row['item_count'] ?? 0)); ?></td>
                                        <td class="text-end ps-3"><?php echo number_format((float)($row['items_total_value'] ?? 0), 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No assets found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const officeFilter = document.getElementById('officeFilter');
            const statusFilter = document.getElementById('statusFilter');

            function applyFilters() {
                const currentUrl = new URL(window.location.href);

                const officeValue = parseInt(officeFilter.value || '0', 10);
                if (officeValue > 0) {
                    currentUrl.searchParams.set('office_id', String(officeValue));
                } else {
                    currentUrl.searchParams.delete('office_id');
                }

                const statusValue = statusFilter.value || '';
                if (statusValue) {
                    currentUrl.searchParams.set('status', statusValue);
                } else {
                    currentUrl.searchParams.delete('status');
                }

                window.location.href = currentUrl.toString();
            }

            if (officeFilter) {
                officeFilter.addEventListener('change', applyFilters);
            }
            if (statusFilter) {
                statusFilter.addEventListener('change', applyFilters);
            }
        });
    </script>
</body>
</html>
