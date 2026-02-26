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
                    ai.id as item_id,
                    ai.description as item_description,
                    ai.status as item_status,
                    ai.value as item_value,
                    ai.acquisition_date,
                    ai.property_no,
                    a.id,
                    a.description,
                    a.unit,
                    a.quantity,
                    a.unit_cost,
                    a.updated_at,
                    ac.category_name,
                    ac.category_code,
                    o.office_name,
                    o.id as office_id
                FROM asset_items ai
                LEFT JOIN assets a ON ai.asset_id = a.id
                LEFT JOIN asset_categories ac ON ac.id = a.asset_categories_id
                LEFT JOIN offices o ON o.id = ai.office_id";

        $params = [];
        $types = '';
        $where_clauses = [];

        if ($status_filter !== '') {
            $where_clauses[] = "ai.status = ?";
            $params[] = $status_filter;
            $types .= 's';
        }

        if ($office_filter > 0) {
            $where_clauses[] = "ai.office_id = ?";
            $params[] = $office_filter;
            $types .= 'i';
        }

        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }

        $sql .= " ORDER BY ai.last_updated DESC";

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
    <style>
    .status-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        white-space: nowrap;
    }
    
    .status-serviceable {
        background: #d4edda;
        color: #155724;
    }
    
    .status-unserviceable {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-red-tagged {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-borrowed {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-no-tag {
        background: #e2e3e5;
        color: #383d41;
    }
    
    .status-unknown {
        background: #e2e3e5;
        color: #383d41;
    }
    </style>
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
                                    <option value="0" <?php echo $office_filter === 0 ? 'selected' : ''; ?>>Asset of Offices</option>
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
                                <th>Property No</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Office</th>
                                <th>Status</th>
                                <th class="text-end">Value</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$error && !empty($assets)): ?>
                                <?php foreach ($assets as $row): ?>
                                    <tr>
                                        <td class="ps-3"><?php echo htmlspecialchars(($row['property_no'] ?? '') !== '' ? (string)$row['property_no'] : (($row['property_number'] ?? '') !== '' ? (string)$row['property_number'] : 'N/A')); ?></td>
                                        <td class="ps-3">
                                            <div class="fw-semibold"><?php echo htmlspecialchars($row['item_description'] ?? ''); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($row['description'] ?? ''); ?></div>
                                        </td>
                                        <td class="ps-3">
                                            <div class="text-muted small"><?php echo htmlspecialchars($row['category_name'] ?? ''); ?></div>
                                        </td>
                                        <td class="ps-3"><?php echo htmlspecialchars($row['office_name'] ?? ''); ?></td>
                                        <td class="ps-3">
                                            <?php
                                            $status = $row['item_status'] ?? '';
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
                                                case 'borrowed':
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
                                        <td class="text-end ps-3"><?php echo number_format((float)($row['item_value'] ?? 0), 2); ?></td>
                                        <td class="text-muted small ps-3"><?php echo htmlspecialchars($row['updated_at'] ?? ''); ?></td>
                                        <td class="ps-3">
                                            <a href="view_asset_item.php?id=<?php echo (int)$row['item_id']; ?>" class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No assets found.</td>
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
