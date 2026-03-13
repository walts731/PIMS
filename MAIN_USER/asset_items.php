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

$asset_id = isset($_GET['asset_id']) ? (int)$_GET['asset_id'] : 0;
if ($asset_id <= 0) {
    header('Location: assets.php');
    exit();
}

logSystemAction($_SESSION['user_id'], 'access', 'main_user_asset_items', 'Main user accessed asset items list for asset ID: ' . $asset_id);

$asset = null;
$items = [];
$error = null;

$office_filter = isset($_GET['office_id']) ? (int)$_GET['office_id'] : 0;
$offices = [];

$status_filter = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
$allowed_statuses = ['serviceable', 'unserviceable', 'red_tagged', 'in_use', 'no_tag'];
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

        $asset_sql = "SELECT a.*, ac.category_name, ac.category_code
                      FROM assets a
                      LEFT JOIN asset_categories ac ON ac.id = a.asset_categories_id
                      WHERE a.id = ?";
        $asset_stmt = $conn->prepare($asset_sql);
        $asset_stmt->bind_param('i', $asset_id);
        $asset_stmt->execute();
        $asset_result = $asset_stmt->get_result();
        if ($asset_row = $asset_result->fetch_assoc()) {
            $asset = $asset_row;
        }
        $asset_stmt->close();

        if (!$asset) {
            header('Location: assets.php');
            exit();
        }

        $items_sql = "SELECT ai.*, o.office_name
                      FROM asset_items ai
                      LEFT JOIN offices o ON o.id = ai.office_id
                      WHERE ai.asset_id = ?";

        $params = [$asset_id];
        $types = 'i';
        $where_clauses = [];

        if ($status_filter !== '') {
            if ($status_filter === 'in_use') {
                // For in_use status, filter by asset_items.status = 'in_use'
                $where_clauses[] = "ai.status = 'in_use'";
            } else {
                $where_clauses[] = "ai.status = ?";
                $params[] = $status_filter;
                $types .= 's';
            }
        }

        if ($office_filter > 0) {
            $where_clauses[] = "ai.office_id = ?";
            $params[] = $office_filter;
            $types .= 'i';
        }

        if (!empty($where_clauses)) {
            $items_sql .= " AND " . implode(' AND ', $where_clauses);
        }

        $items_sql .= " ORDER BY ai.id";

        $items_stmt = $conn->prepare($items_sql);
        if (!$items_stmt) {
            $error = 'Failed to prepare items query.';
        } else {
            $items_stmt->bind_param($types, ...$params);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
            while ($item_row = $items_result->fetch_assoc()) {
                $items[] = $item_row;
            }
            $items_stmt->close();
        }
    } catch (Exception $e) {
        $error = 'Error loading asset items: ' . $e->getMessage();
        error_log('Main User Asset Items Error: ' . $e->getMessage());
    }
}

$total_items = count($items);
$serviceable_items = count(array_filter($items, fn($item) => ($item['status'] ?? '') === 'serviceable'));
$unserviceable_items = count(array_filter($items, fn($item) => ($item['status'] ?? '') === 'unserviceable'));
$redtagged_items = count(array_filter($items, fn($item) => ($item['status'] ?? '') === 'red_tagged'));
$borrowed_items = count(array_filter($items, fn($item) => ($item['status'] ?? '') === 'in_use'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Items - Main User | PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="../ADMIN/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php $page_title = 'Asset Items'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>

        <div class="main-content">
            <div class="dashboard-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-1" style="font-weight: 700; color: #191BA9;">
                            <i class="bi bi-collection me-2"></i>Asset Items
                        </h1>
                        <p class="text-muted mb-0">
                            Asset: <?php echo htmlspecialchars($asset['description'] ?? ''); ?> | Viewing all asset items across all offices.
                        </p>
                        <?php if ($error): ?>
                            <div class="alert alert-warning mt-2 mb-0 py-2" role="alert">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <small><?php echo htmlspecialchars($error); ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
                            <a class="btn btn-outline-primary btn-sm" href="asset_items.php">
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

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="bi bi-collection"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($total_items); ?></div>
                        <div class="stat-label">Total Items</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($serviceable_items); ?></div>
                        <div class="stat-label">Serviceable</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="bi bi-x-circle"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($unserviceable_items); ?></div>
                        <div class="stat-label">Unserviceable</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($redtagged_items); ?></div>
                        <div class="stat-label">Red-Tagged</div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="table-responsive">
                    <table class="table table-hover" id="assetItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Property No</th>
                                <th>Description</th>
                                <th>Office</th>
                                <th>Status</th>
                                <th class="text-end">Value</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$error && !empty($items)): ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(($item['property_no'] ?? '') !== '' ? (string)$item['property_no'] : (($item['property_number'] ?? '') !== '' ? (string)$item['property_number'] : 'N/A')); ?></td>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($item['description'] ?? ''); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($asset['description'] ?? ''); ?></div>
                                        </td>
                                        <td class="text-muted small"><?php echo htmlspecialchars($item['office_name'] ?? ''); ?></td>
                                        <td>
                                            <?php
                                            $status = $item['status'] ?? '';
                                            $status_class = '';
                                            $display_status = '';
                                            switch($status) {
                                                case 'serviceable':
                                                    $status_class = 'status-serviceable';
                                                    $display_status = 'Serviceable';
                                                    break;
                                                case 'unserviceable':
                                                    $status_class = 'status-unserviceable';
                                                    $display_status = 'Unserviceable';
                                                    break;
                                                case 'red_tagged':
                                                    $status_class = 'status-red-tagged';
                                                    $display_status = 'Red-Tagged';
                                                    break;
                                                case 'in_use':
                                                    $status_class = 'status-borrowed';
                                                    $display_status = 'Borrowed';
                                                    break;
                                                case 'no_tag':
                                                    $status_class = 'status-no-tag';
                                                    $display_status = 'No Tag';
                                                    break;
                                                default:
                                                    $status_class = 'status-unknown';
                                                    $display_status = ucfirst(str_replace('_', ' ', $status));
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo $display_status; ?>
                                            </span>
                                        </td>
                                        <td class="text-end"><?php echo number_format((float)($item['value'] ?? 0), 2); ?></td>
                                        <td class="text-muted small"><?php echo htmlspecialchars($item['last_updated'] ?? ''); ?></td>
                                        <td class="text-end">
                                            <a href="view_asset_item.php?id=<?php echo (int)($item['id'] ?? 0); ?>" class="btn btn-outline-info btn-sm">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No asset items found.</td>
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
