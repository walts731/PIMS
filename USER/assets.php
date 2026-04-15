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

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: ../index.php');
    exit();
}

logSystemAction($_SESSION['user_id'], 'access', 'user_assets', 'User accessed assets list');

// Get filter parameters
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_filter = isset($_GET['date_filter']) ? trim($_GET['date_filter']) : 'all';

$user_office_id = null;
$user_office_name = null;
$assets = [];
$categories = [];
$error = null;

if (!$conn || $conn->connect_error) {
    $error = 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error');
} else {
    try {
        $user_office_value = null;

        $stmt = $conn->prepare("SELECT office FROM users WHERE id = ?");
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $user_office_value = $row['office'] ?? null;
        }
        $stmt->close();

        if ($user_office_value !== null && $user_office_value !== '') {
            if (ctype_digit((string)$user_office_value)) {
                $user_office_id = (int)$user_office_value;
                $stmt = $conn->prepare("SELECT office_name FROM offices WHERE id = ?");
                $stmt->bind_param('i', $user_office_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    $user_office_name = $result->fetch_assoc()['office_name'];
                }
                $stmt->close();
            } else {
                $stmt = $conn->prepare("SELECT id, office_name FROM offices WHERE office_name = ? OR office_code = ? LIMIT 1");
                $stmt->bind_param('ss', $user_office_value, $user_office_value);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $user_office_id = (int)$row['id'];
                    $user_office_name = $row['office_name'];
                }
                $stmt->close();
            }
        }

        if (!$user_office_id) {
            $error = 'Office not assigned to your account. Please contact administrator.';
        } else {
            // Get categories for filter dropdown
            $categories_query = "SELECT DISTINCT ac.id, ac.category_name, ac.category_code
                                FROM asset_categories ac
                                INNER JOIN assets a ON a.asset_categories_id = ac.id
                                WHERE a.office_id = ?
                                ORDER BY ac.category_name";
            $stmt = $conn->prepare($categories_query);
            $stmt->bind_param('i', $user_office_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
            $stmt->close();

            // Build the main query with filters - focusing on asset_items table
            $sql = "SELECT 
                        ai.id,
                        ai.description,
                        ai.model,
                        ai.serial_number,
                        ai.unit,
                        ai.property_no,
                        ai.ics_par_no,
                        ai.inventory_tag,
                        ai.status,
                        ai.value,
                        ai.acquisition_date,
                        ai.office_id,
                        ai.office_name,
                        ai.last_updated,
                        ac.category_name,
                        ac.category_code,
                        a.description as asset_description
                    FROM asset_items ai
                    LEFT JOIN asset_categories ac ON ac.id = ai.asset_category_id
                    LEFT JOIN assets a ON a.id = ai.asset_id
                    WHERE ai.office_id = ?";

            $params = [$user_office_id];
            $types = 'i';

            // Add search filter
            if (!empty($search_filter)) {
                $sql .= " AND (ai.description LIKE ? OR ai.property_no LIKE ? OR ai.serial_number LIKE ? OR ac.category_name LIKE ? OR ai.model LIKE ?)";
                $search_term = '%' . $search_filter . '%';
                $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term, $search_term]);
                $types .= 'sssss';
            }

            // Add category filter
            if ($category_filter > 0) {
                $sql .= " AND ai.asset_category_id = ?";
                $params[] = $category_filter;
                $types .= 'i';
            }

            // Add status filter
            if (!empty($status_filter)) {
                $sql .= " AND ai.status = ?";
                $params[] = $status_filter;
                $types .= 's';
            }

            // Add date filter
            switch ($date_filter) {
                case 'today':
                    $sql .= " AND DATE(ai.last_updated) = CURDATE()";
                    break;
                case 'week':
                    $sql .= " AND ai.last_updated >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $sql .= " AND ai.last_updated >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                case 'quarter':
                    $sql .= " AND ai.last_updated >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
                    break;
                case 'year':
                    $sql .= " AND ai.last_updated >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
                    break;
            }

            $sql .= " ORDER BY ai.last_updated DESC";

            // Debug: Log the SQL and parameters
            error_log("SQL Query: " . $sql);
            error_log("Parameters: " . print_r($params, true));
            error_log("Types: " . $types);

            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("SQL preparation failed: " . $conn->error . " Query: " . $sql);
            }
            
            if (!empty($params)) {
                // Use individual parameter binding to avoid issues with spread operator
                $bind_params = array_merge([$types], $params);
                $ref_params = [];
                foreach ($bind_params as $key => $value) {
                    $ref_params[$key] = &$bind_params[$key];
                }
                call_user_func_array([$stmt, 'bind_param'], $ref_params);
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
        error_log('User Assets Error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assets - PIMS</title>
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
                        <p class="text-muted mb-0">
                            Showing asset items for your office<?php echo $user_office_name ? ': ' . htmlspecialchars($user_office_name) : ''; ?>.
                        </p>
                        <?php if ($error): ?>
                            <div class="alert alert-warning mt-2 mb-0 py-2" role="alert">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <small><?php echo htmlspecialchars($error); ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a class="btn btn-outline-primary btn-sm" href="assets.php">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="section-card mb-3">
                <div class="card-header bg-light py-3">
                    <h6 class="mb-0">
                        <i class="bi bi-funnel me-2"></i>Filters
                        <?php if (!empty($search_filter) || $category_filter > 0 || !empty($status_filter) || $date_filter !== 'all'): ?>
                            <a href="assets.php" class="btn btn-sm btn-outline-secondary ms-2">
                                <i class="bi bi-x-circle"></i> Clear All
                            </a>
                        <?php endif; ?>
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="assets.php" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">
                                <i class="bi bi-search me-1"></i>Search
                            </label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search_filter); ?>" 
                                   placeholder="Search description, property no...">
                        </div>
                        <div class="col-md-2">
                            <label for="category" class="form-label">
                                <i class="bi bi-tags me-1"></i>Category
                            </label>
                            <select class="form-select" id="category" name="category">
                                <option value="0">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo $category_filter === (int)$cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">
                                <i class="bi bi-info-circle me-1"></i>Status
                            </label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="serviceable" <?php echo $status_filter === 'serviceable' ? 'selected' : ''; ?>>
                                    Serviceable
                                </option>
                                <option value="unserviceable" <?php echo $status_filter === 'unserviceable' ? 'selected' : ''; ?>>
                                    Unserviceable
                                </option>
                                <option value="red_tagged" <?php echo $status_filter === 'red_tagged' ? 'selected' : ''; ?>>
                                    Red-Tagged
                                </option>
                                <option value="borrowed" <?php echo $status_filter === 'borrowed' ? 'selected' : ''; ?>>
                                    Borrowed
                                </option>
                                <option value="no_tag" <?php echo $status_filter === 'no_tag' ? 'selected' : ''; ?>>
                                    No Tag
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_filter" class="form-label">
                                <i class="bi bi-calendar me-1"></i>Date Range
                            </label>
                            <select class="form-select" id="date_filter" name="date_filter">
                                <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>
                                    All Time
                                </option>
                                <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>
                                    Today
                                </option>
                                <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>
                                    Last 7 Days
                                </option>
                                <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>
                                    Last 30 Days
                                </option>
                                <option value="quarter" <?php echo $date_filter === 'quarter' ? 'selected' : ''; ?>>
                                    Last 90 Days
                                </option>
                                <option value="year" <?php echo $date_filter === 'year' ? 'selected' : ''; ?>>
                                    Last 365 Days
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label d-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Apply Filters
                            </button>
                        </div>
                    </form>
                    
                    <?php if (!empty($search_filter) || $category_filter > 0 || !empty($status_filter) || $date_filter !== 'all'): ?>
                        <div class="alert alert-info mt-3 mb-0 py-2">
                            <small class="mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                <strong>Active Filters:</strong>
                                <?php if (!empty($search_filter)): ?>
                                    <span class="badge bg-primary me-1">Search: <?php echo htmlspecialchars($search_filter); ?></span>
                                <?php endif; ?>
                                <?php if ($category_filter > 0): ?>
                                    <?php $selected_cat = array_filter($categories, fn($c) => $c['id'] == $category_filter); ?>
                                    <?php if ($selected_cat): ?>
                                        <?php $cat = reset($selected_cat); ?>
                                        <span class="badge bg-success me-1">Category: <?php echo htmlspecialchars($cat['category_name']); ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if (!empty($status_filter)): ?>
                                    <span class="badge bg-warning me-1">Status: <?php echo ucfirst(str_replace('_', ' ', $status_filter)); ?></span>
                                <?php endif; ?>
                                <?php if ($date_filter !== 'all'): ?>
                                    <span class="badge bg-info me-1">
                                        Date: <?php 
                                        $date_labels = [
                                            'today' => 'Today',
                                            'week' => 'Last 7 Days', 
                                            'month' => 'Last 30 Days',
                                            'quarter' => 'Last 90 Days',
                                            'year' => 'Last 365 Days'
                                        ];
                                        echo $date_labels[$date_filter] ?? $date_filter; 
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section-card">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Property No</th>
                                <th>Description</th>
                                <th>Model/Serial</th>
                                <th>Category</th>
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
                                        <td class="ps-3">
                                            <?php echo htmlspecialchars($row['property_no'] ?? 'N/A'); ?>
                                            <?php if (!empty($row['ics_par_no'])): ?>
                                                <br><small class="text-muted">ICS/PAR: <?php echo htmlspecialchars($row['ics_par_no']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="ps-3">
                                            <div class="fw-semibold"><?php echo htmlspecialchars($row['description'] ?? ''); ?></div>
                                            <?php if (!empty($row['inventory_tag'])): ?>
                                                <div class="text-muted small">Tag: <?php echo htmlspecialchars($row['inventory_tag']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="ps-3">
                                            <?php if (!empty($row['model'])): ?>
                                                <div><?php echo htmlspecialchars($row['model']); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($row['serial_number'])): ?>
                                                <div class="text-muted small">S/N: <?php echo htmlspecialchars($row['serial_number']); ?></div>
                                            <?php endif; ?>
                                            <?php if (empty($row['model']) && empty($row['serial_number'])): ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="ps-3">
                                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($row['category_name'] ?? 'N/A'); ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $status = $row['status'] ?? '';
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
                                                    $status_class = 'status-in-use';
                                                    $display_status = 'In Use';
                                                    break;
                                                case 'maintenance':
                                                    $status_class = 'status-maintenance';
                                                    $display_status = 'Maintenance';
                                                    break;
                                                case 'disposed':
                                                    $status_class = 'status-disposed';
                                                    $display_status = 'Disposed';
                                                    break;
                                                case 'available':
                                                    $status_class = 'status-available';
                                                    $display_status = 'Available';
                                                    break;
                                                case 'no_tag':
                                                    $status_class = 'status-no-tag';
                                                    $display_status = 'No Tag';
                                                    break;
                                                case 'pending_tag':
                                                    $status_class = 'status-pending-tag';
                                                    $display_status = 'Pending Tag';
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
                                        <td class="text-end ps-3">
                                            <?php echo number_format($row['value'] ?? 0, 2); ?>
                                        </td>
                                        <td class="text-muted small ps-3"><?php echo htmlspecialchars($row['last_updated'] ?? ''); ?></td>
                                        <td class="ps-3">
                                            <a href="view_asset_item.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <?php if ($error): ?>
                                            <?php echo htmlspecialchars($error); ?>
                                        <?php else: ?>
                                            No assets found matching your filters.
                                            <?php if (!empty($search_filter) || $category_filter > 0 || !empty($status_filter) || $date_filter !== 'all'): ?>
                                                <br><small>Try adjusting your filters or <a href="assets.php">clear all filters</a>.</small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (!$error && !empty($assets)): ?>
                    <div class="card-footer bg-light py-2">
                        <small class="text-muted">
                            Showing <strong><?php echo count($assets); ?></strong> asset(s)
                            <?php if (!empty($search_filter) || $category_filter > 0 || !empty($status_filter) || $date_filter !== 'all'): ?>
                                matching your filters
                            <?php endif; ?>
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
</body>
</html>
