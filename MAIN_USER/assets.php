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
$office_name_filter = isset($_GET['office']) ? trim((string)$_GET['office']) : '';
$offices = [];

$status_filter = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
$allowed_statuses = ['serviceable', 'unserviceable', 'red_tagged', 'borrowed', 'no_tag'];
if ($status_filter !== '' && !in_array($status_filter, $allowed_statuses, true)) {
    $status_filter = '';
}

$category_filter = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$categories = [];

$search_filter = isset($_GET['search']) ? trim((string)$_GET['search']) : '';

if (!$conn || $conn->connect_error) {
    $error = 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error');
} else {
    try {
        // Auto-fix borrowed status for approved borrow requests
        $fix_sql = "
            UPDATE asset_items ai 
            JOIN borrow_requests br ON br.asset_id = ai.id 
            SET ai.status = 'borrowed' 
            WHERE br.status = 'approved' AND ai.status != 'borrowed'
        ";
        $conn->query($fix_sql);
        
        $res = $conn->query("SELECT id, office_name FROM offices ORDER BY office_name ASC");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $offices[] = $row;
            }
        }
        
        // Get all categories
        $category_result = $conn->query("SELECT id, category_name FROM asset_categories ORDER BY category_name ASC");
        if ($category_result) {
            while ($row = $category_result->fetch_assoc()) {
                $categories[] = $row;
            }
        }

        $sql = "SELECT 
                    ai.id as item_id,
                    ai.property_no,
                    ai.description as item_description,
                    ai.status as item_status,
                    ai.value as item_value,
                    ai.last_updated,
                    a.id,
                    a.description,
                    a.unit,
                    a.quantity,
                    a.unit_cost,
                    a.updated_at,
                    ac.category_name,
                    ac.category_code,
                    o.office_name,
                    o.id as office_id,
                    borrower_office.office_name as borrower_office_name,
                    borrower_office.id as borrower_office_id,
                    br.id as borrow_request_id,
                    br.start_date as borrowed_date,
                    br.end_date as expected_return_date,
                    br.returned_at as actual_return_date,
                    br.purpose,
                    br.status as borrow_status,
                    br.approved_at,
                    br.quantity_requested,
                    br.requested_by_office as borrower_office_id_ref,
                    u.first_name as borrower_firstname,
                    u.last_name as borrower_lastname
                FROM asset_items ai
                LEFT JOIN assets a ON ai.asset_id = a.id
                LEFT JOIN asset_categories ac ON ac.id = a.asset_categories_id
                LEFT JOIN offices o ON o.id = ai.office_id
                LEFT JOIN borrow_requests br ON br.asset_id = ai.id AND br.status = 'approved'
                LEFT JOIN offices borrower_office ON borrower_office.id = br.requested_by_office
                LEFT JOIN users u ON u.id = br.requested_by";

        $params = [];
        $types = '';
        $where_clauses = [];

        if ($status_filter !== '') {
            if ($status_filter === 'borrowed') {
                // For borrowed status, filter by asset_items.status = 'borrowed'
                $where_clauses[] = "ai.status = 'borrowed'";
            } else {
                $where_clauses[] = "ai.status = ?";
                $params[] = $status_filter;
                $types .= 's';
            }
        }

        if ($office_filter > 0) {
            if ($status_filter === 'borrowed') {
                // For borrowed assets, filter by borrower office
                $where_clauses[] = "borrower_office.id = ?";
            } else {
                // For other statuses, filter by owning office
                $where_clauses[] = "ai.office_id = ?";
            }
            $params[] = $office_filter;
            $types .= 'i';
        } elseif ($office_name_filter !== '') {
            if ($status_filter === 'borrowed') {
                // For borrowed assets, filter by borrower office name
                $where_clauses[] = "borrower_office.office_name = ?";
            } else {
                // For other statuses, filter by owning office name
                $where_clauses[] = "o.office_name = ?";
            }
            $params[] = $office_name_filter;
            $types .= 's';
        }
        
        if ($category_filter > 0) {
            $where_clauses[] = "ac.id = ?";
            $params[] = $category_filter;
            $types .= 'i';
        }
        
        if ($search_filter !== '') {
            $where_clauses[] = "(ai.property_no LIKE ? OR ai.description LIKE ?)";
            $search_param = '%' . $search_filter . '%';
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= 'ss';
        }

        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }

        // Group by office for per-office display
        if ($status_filter === 'borrowed') {
            // For borrowed assets, group and sort by borrower office
            $sql .= " GROUP BY borrower_office.id, ai.id ORDER BY borrower_office.office_name ASC, ai.last_updated DESC";
        } else {
            // For other statuses, group and sort by owning office
            $sql .= " GROUP BY o.id, ai.id ORDER BY o.office_name ASC, ai.last_updated DESC";
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error = 'Failed to prepare query: ' . $conn->error;
        } else {
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Get all assets (no office grouping)
            $assets = [];
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
    /* Mobile UI Fixes */
    @media (max-width: 992px) {
        .dashboard-header .row {
            flex-direction: column;
            gap: 1rem;
        }
        
        .dashboard-header .col-md-4 {
            text-align: left !important;
        }
        
        .dashboard-header h1 {
            font-size: 1.75rem;
        }
    }
    
    @media (max-width: 768px) {
        .dashboard-header h1 {
            font-size: 1.5rem;
        }
        
        .dashboard-header p {
            font-size: 0.9rem;
        }
        
        .dashboard-header .d-flex {
            flex-direction: column;
            align-items: stretch !important;
            gap: 0.5rem;
        }
        
        .dashboard-header .d-inline-block {
            width: 100% !important;
            min-width: auto !important;
        }
        
        .form-select-sm {
            font-size: 0.85rem;
        }
        
        .btn-sm {
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
        }
        
        .section-card {
            margin-bottom: 1rem;
        }
        
        .table-responsive {
            font-size: 0.85rem;
        }
        
        .table th {
            font-size: 0.8rem;
            padding: 0.5rem;
        }
        
        .table td {
            font-size: 0.8rem;
            padding: 0.5rem;
        }
        
        .status-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
    }
    
    @media (max-width: 576px) {
        .dashboard-header h1 {
            font-size: 1.25rem;
        }
        
        .dashboard-header p {
            font-size: 0.85rem;
        }
        
        .btn-sm {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }
        
        .form-select-sm {
            font-size: 0.8rem;
        }
        
        .table-responsive {
            font-size: 0.75rem;
        }
        
        .table th {
            font-size: 0.75rem;
            padding: 0.4rem;
        }
        
        .table td {
            font-size: 0.75rem;
            padding: 0.4rem;
        }
        
        .status-badge {
            font-size: 0.65rem;
            padding: 0.2rem 0.4rem;
        }
        
        .btn-sm {
            padding: 0.3rem 0.6rem;
            font-size: 0.75rem;
        }
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
                        <p class="text-muted mb-0">
                            <?php
                                if (($office_filter > 0 || $office_name_filter !== '') && $status_filter !== '' && $category_filter > 0) {
                                    $office_display = '';
                                    if ($office_filter > 0) {
                                        $office_display = isset($offices[array_search($office_filter, array_column($offices, 'id'))]['office_name']) ? htmlspecialchars($offices[array_search($office_filter, array_column($offices, 'id'))]['office_name']) : "Office " . $office_filter;
                                    } elseif ($office_name_filter !== '') {
                                        $office_display = htmlspecialchars($office_name_filter);
                                    }
                                    $category_display = isset($categories[array_search($category_filter, array_column($categories, 'id'))]['category_name']) ? htmlspecialchars($categories[array_search($category_filter, array_column($categories, 'id'))]['category_name']) : "Category " . $category_filter;
                                    $office_text = ($status_filter === 'borrowed') ? "borrowed by" : "of";
                                    echo "Viewing " . htmlspecialchars(ucfirst($status_filter)) . " " . $category_display . " assets " . $office_text . " " . $office_display . ".";
                                } elseif (($office_filter > 0 || $office_name_filter !== '') && $status_filter !== '') {
                                    $office_display = '';
                                    if ($office_filter > 0) {
                                        $office_display = isset($offices[array_search($office_filter, array_column($offices, 'id'))]['office_name']) ? htmlspecialchars($offices[array_search($office_filter, array_column($offices, 'id'))]['office_name']) : "Office " . $office_filter;
                                    } elseif ($office_name_filter !== '') {
                                        $office_display = htmlspecialchars($office_name_filter);
                                    }
                                    $office_text = ($status_filter === 'borrowed') ? "borrowed by" : "of";
                                    echo "Viewing " . htmlspecialchars(ucfirst($status_filter)) . " assets " . $office_text . " " . $office_display . ".";
                                } elseif (($office_filter > 0 || $office_name_filter !== '') && $category_filter > 0) {
                                    $office_display = '';
                                    if ($office_filter > 0) {
                                        $office_display = isset($offices[array_search($office_filter, array_column($offices, 'id'))]['office_name']) ? htmlspecialchars($offices[array_search($office_filter, array_column($offices, 'id'))]['office_name']) : "Office " . $office_filter;
                                    } elseif ($office_name_filter !== '') {
                                        $office_display = htmlspecialchars($office_name_filter);
                                    }
                                    $category_display = isset($categories[array_search($category_filter, array_column($categories, 'id'))]['category_name']) ? htmlspecialchars($categories[array_search($category_filter, array_column($categories, 'id'))]['category_name']) : "Category " . $category_filter;
                                    echo "Viewing " . $category_display . " assets of " . $office_display . ".";
                                } elseif ($office_filter > 0 || $office_name_filter !== '') {
                                    $office_display = '';
                                    if ($office_filter > 0) {
                                        $office_display = isset($offices[array_search($office_filter, array_column($offices, 'id'))]['office_name']) ? htmlspecialchars($offices[array_search($office_filter, array_column($offices, 'id'))]['office_name']) : "Office " . $office_filter;
                                    } elseif ($office_name_filter !== '') {
                                        $office_display = htmlspecialchars($office_name_filter);
                                    }
                                    echo "Viewing assets of " . $office_display . ".";
                                } elseif ($status_filter !== '' && $category_filter > 0) {
                                    $category_display = isset($categories[array_search($category_filter, array_column($categories, 'id'))]['category_name']) ? htmlspecialchars($categories[array_search($category_filter, array_column($categories, 'id'))]['category_name']) : "Category " . $category_filter;
                                    echo "Viewing " . $category_display . " assets across all offices.";
                                } elseif ($status_filter !== '') {
                                    echo "Viewing " . htmlspecialchars(ucfirst($status_filter)) . " assets across all offices.";
                                } else {
                                    echo "Viewing all assets in the system.";
                                }
                            ?>
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
                            <div class="d-inline-block" style="min-width: 180px;">
                                <select class="form-select form-select-sm" id="categoryFilter" <?php echo $category_filter > 0 ? 'style="background-color: #007bff; color: white; border-color: #0056b3; font-weight: bold;"' : ''; ?>>
                                    <option value="0" <?php echo $category_filter === 0 ? 'selected' : ''; ?>>All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo (int)$category['id']; ?>" <?php echo $category_filter === (int)$category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <a class="btn btn-outline-primary btn-sm" href="assets.php">
                                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0" id="assetsTable">
                        <thead>
                            <tr>
                                <th>Property No</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Office</th>
                                <th>Status</th>
                                <th>Borrower</th>
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
                                        <td class="ps-3">
                                            <?php 
                                            // Show borrower office for borrowed assets, otherwise show owning office
                                            if (($row['item_status'] ?? '') === 'borrowed' && !empty($row['borrower_office_name'])) {
                                                echo htmlspecialchars($row['borrower_office_name']);
                                                echo ' <small class="text-muted">(from ' . htmlspecialchars($row['office_name'] ?? '') . ')</small>';
                                            } else {
                                                echo htmlspecialchars($row['office_name'] ?? '');
                                            }
                                            ?>
                                        </td>
                                        <td class="ps-3">
                                            <?php
                                            // Show actual asset status
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
                                        <td class="ps-3">
                                            <?php if (!empty($row['borrow_request_id'])): ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars(($row['borrower_firstname'] ?? '') . ' ' . ($row['borrower_lastname'] ?? '')); ?></strong>
                                                    <?php if (!empty($row['borrowed_date'])): ?>
                                                        <div class="small text-info">Since: <?php echo date('M j, Y', strtotime($row['borrowed_date'])); ?></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($row['expected_return_date'])): ?>
                                                        <div class="small text-warning">Due: <?php echo date('M j, Y', strtotime($row['expected_return_date'])); ?></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($row['quantity_requested']) && $row['quantity_requested'] > 1): ?>
                                                        <div class="small text-primary">Qty: <?php echo (int)$row['quantity_requested']; ?></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($row['purpose'])): ?>
                                                        <div class="small text-muted" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($row['purpose']); ?>">
                                                            <?php echo htmlspecialchars(substr($row['purpose'], 0, 30)) . (strlen($row['purpose']) > 30 ? '...' : ''); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end ps-3"><?php echo number_format((float)($row['item_value'] ?? 0), 2); ?></td>
                                        <td class="text-muted small ps-3"><?php echo htmlspecialchars($row['updated_at'] ?? ''); ?></td>
                                        <td class="ps-3">
                                            <a href="view_asset_item.php?id=<?php echo (int)$row['item_id']; ?>" class="btn btn-sm btn-outline-info me-1">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">No assets found.</td>
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
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        function transferAssetToOMBO() {
            if (confirm('Transfer asset 2026-07-05-030-0307-01 from OMM to OMBO office?')) {
                fetch('transfer_asset_office.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'transfer_asset',
                        property_no: '2026-07-05-030-0307-01',
                        from_office: 'OMM',
                        to_office: 'OMBO'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ ' + data.message);
                        // Redirect to OMBO borrowed assets page
                        window.location.href = 'assets.php?office=OMBO&status=borrowed';
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while transferring the asset.');
                });
            }
        }
        
        function transferAssetToOMM() {
            if (confirm('Transfer asset 2026-07-05-030-0307-01 back to OMM office?')) {
                fetch('transfer_asset_office.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'transfer_asset',
                        property_no: '2026-07-05-030-0307-01',
                        from_office: 'OMBO',
                        to_office: 'OMM'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ ' + data.message);
                        // Redirect to OMM borrowed assets page
                        window.location.href = 'assets.php?office=OMM&status=borrowed';
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while transferring the asset.');
                });
            }
        }
        
        function fixBorrowedStatus() {
            if (confirm('Update asset status to "borrowed" for all approved borrow requests?')) {
                fetch('fix_borrowed_status_ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({action: 'fix_borrowed_status'})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ ' + data.message + ' assets updated to "borrowed" status.');
                        location.reload();
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while fixing borrowed status.');
                });
            }
        }
        
        let assetsTable;
        
        document.addEventListener('DOMContentLoaded', function() {
            const categoryFilter = document.getElementById('categoryFilter');
            
            // Initialize DataTable
            assetsTable = $('#assetsTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[6, 'desc']], // Sort by Last Updated column (index 6) by default
                columnDefs: [
                    {
                        targets: -1, // Actions column (last column)
                        orderable: false,
                        searchable: false
                    }
                ],
                dom: '<"row"<"col-md-6"l><"col-md-6 text-end"f>>rtip',
                language: {
                    search: "Search assets:",
                    lengthMenu: "Show _MENU_ assets per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ assets",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    },
                    emptyTable: "No assets available",
                    zeroRecords: "No matching assets found"
                }
            });
            
            // Add category filter event listener
            if (categoryFilter) {
                categoryFilter.addEventListener('change', function() {
                    const currentUrl = new URL(window.location.href);
                    const categoryValue = parseInt(categoryFilter.value || '0', 10);
                    
                    if (categoryValue > 0) {
                        currentUrl.searchParams.set('category_id', String(categoryValue));
                    } else {
                        currentUrl.searchParams.delete('category_id');
                    }
                    
                    window.location.href = currentUrl.toString();
                });
            }
        });

        // Make functions global for onclick handlers
        window.borrowItem = function(itemId) {
            if (confirm('Are you sure you want to borrow this item?')) {
                // Show loading state
                const button = event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
                button.disabled = true;

                // Create form data
                const formData = new FormData();
                formData.append('action', 'borrow');
                formData.append('item_id', itemId);
                formData.append('user_id', <?php echo (int)($_SESSION['user_id'] ?? 0); ?>);

                // Send request
                fetch('process_borrow.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Item borrowed successfully!');
                        // Reload page to show updated status
                        window.location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to borrow item'));
                        // Restore button
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while borrowing the item.');
                    // Restore button
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
            }
        }

        window.returnItem = function(itemId) {
            if (confirm('Are you sure you want to return this item?')) {
                // Show loading state
                const button = event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
                button.disabled = true;

                // Create form data
                const formData = new FormData();
                formData.append('action', 'return');
                formData.append('item_id', itemId);
                formData.append('user_id', <?php echo (int)($_SESSION['user_id'] ?? 0); ?>);

                // Send request
                fetch('process_borrow.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Item returned successfully!');
                        // Reload page to show updated status
                        window.location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to return item'));
                        // Restore button
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while returning the item.');
                    // Restore button
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
            }
        }
    </script>
</body>
</html>
