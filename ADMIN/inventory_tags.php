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

// Log inventory tags page access
logSystemAction($_SESSION['user_id'], 'access', 'inventory_tags', 'Admin accessed inventory tags page');

// Handle search and filter
$search = trim($_GET['search'] ?? '');
$office_filter = intval($_GET['office'] ?? 0);
$status_filter = $_GET['status'] ?? '';

// Build WHERE clause
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(ai.inventory_tag LIKE ? OR ai.property_no LIKE ? OR a.description LIKE ? OR e.lastname LIKE ? OR e.firstname LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    $types .= 'sssss';
}

if ($office_filter > 0) {
    $where_conditions[] = "ai.office_id = ?";
    $params[] = $office_filter;
    $types .= 'i';
}

if (!empty($status_filter)) {
    $where_conditions[] = "ai.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get inventory tags
$tags = [];
try {
    $sql = "SELECT ai.id, ai.inventory_tag, ai.property_no, ai.description, ai.status, ai.date_counted, ai.image, ai.qr_code,
                   a.description as asset_description, a.unit_cost,
                   ac.category_name, ac.category_code,
                   o.office_name,
                   e.employee_no, e.firstname, e.lastname,
                   ai.created_at
            FROM asset_items ai 
            LEFT JOIN assets a ON ai.asset_id = a.id 
            LEFT JOIN asset_categories ac ON COALESCE(ai.category_id, a.asset_categories_id) = ac.id 
            LEFT JOIN offices o ON ai.office_id = o.id 
            LEFT JOIN employees e ON ai.employee_id = e.id 
            $where_clause AND ai.inventory_tag IS NOT NULL AND ai.inventory_tag != ''
            ORDER BY ai.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching tags: " . $e->getMessage());
}

// Get offices for filter
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

// Get statistics
$stats = [];
try {
    $sql = "SELECT 
                COUNT(DISTINCT ai.id) as total_tags,
                COUNT(DISTINCT CASE WHEN ai.status = 'serviceable' THEN ai.id END) as serviceable,
                COUNT(DISTINCT CASE WHEN ai.status = 'unserviceable' THEN ai.id END) as unserviceable,
                COUNT(DISTINCT ai.office_id) as offices_with_tags
              FROM asset_items ai 
              WHERE ai.inventory_tag IS NOT NULL AND ai.inventory_tag != ''";
    $result = $conn->query($sql);
    if ($result) {
        $stats = $result->fetch_assoc();
    }
} catch (Exception $e) {
    error_log("Error fetching stats: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Tags - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(25, 27, 169, 0.3);
        }
        
        .stats-number {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            word-wrap: break-word;
            line-height: 1.2;
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .table-container {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin: 0 0.125rem;
        }
        
        .category-badge {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .text-value {
            font-weight: 600;
            color: #191BA9;
        }
        
        .modal-header {
            background: var(--primary-gradient);
            color: white;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(25, 27, 169, 0.05);
        }
        
        .tag-preview {
            max-width: 60px;
            max-height: 60px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .tag-preview:hover {
            transform: scale(1.05);
        }
        
        .filter-section {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            padding-left: 2.5rem;
            border-radius: 25px;
            border: 2px solid #e9ecef;
            transition: var(--transition);
        }
        
        .search-box input:focus {
            border-color: #191ba9;
            box-shadow: 0 0 0 0.2rem rgba(25, 27, 169, 0.25);
        }
        
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .table-container {
                box-shadow: none;
            }
            
            body {
                background: white;
            }
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Inventory Tags';
    ?>
    <!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-tags"></i> Inventory Tags
                    </h1>
                    <p class="text-muted mb-0">View and print inventory tags for all tagged assets</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button type="button" class="btn btn-warning btn-sm me-2" onclick="alert('Test works!')">
                        <i class="bi bi-bug"></i> Test
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm me-2" onclick="window.open('print_inventory_tag.php?id=48', '_blank')">
                        <i class="bi bi-printer"></i> Test Print
                    </button>
                    <button type="button" class="btn btn-success" onclick="printSelectedTags()">
                        <i class="bi bi-printer"></i> Print Selected
                    </button>
                    <button type="button" class="btn btn-info btn-sm ms-2" onclick="printAllTags()">
                        <i class="bi bi-printer-fill"></i> Print All
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm ms-2" onclick="exportToCSV()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>

        
        <!-- Statistics Cards -->
        <div class="row mb-4 no-print">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($stats['total_tags'] ?? 0); ?></div>
                    <div class="stats-label"><i class="bi bi-tags"></i> Total Tags</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($stats['serviceable'] ?? 0); ?></div>
                    <div class="stats-label"><i class="bi bi-check-circle"></i> Serviceable</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($stats['unserviceable'] ?? 0); ?></div>
                    <div class="stats-label"><i class="bi bi-x-circle"></i> Unserviceable</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($stats['offices_with_tags'] ?? 0); ?></div>
                    <div class="stats-label"><i class="bi bi-building"></i> Offices with Tags</div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="filter-section no-print">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" class="form-control" placeholder="Search by tag, property no, description, or employee..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="office" class="form-select">
                        <option value="">All Offices</option>
                        <?php foreach ($offices as $office): ?>
                            <option value="<?php echo $office['id']; ?>" <?php echo $office_filter == $office['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($office['office_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="serviceable" <?php echo $status_filter == 'serviceable' ? 'selected' : ''; ?>>Serviceable</option>
                        <option value="unserviceable" <?php echo $status_filter == 'unserviceable' ? 'selected' : ''; ?>>Unserviceable</option>
                        <option value="in_use" <?php echo $status_filter == 'in_use' ? 'selected' : ''; ?>>In Use</option>
                        <option value="available" <?php echo $status_filter == 'available' ? 'selected' : ''; ?>>Available</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i>
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Inventory Tags Table -->
        <div class="table-container">
            <?php if (!empty($tags)): ?>
                <form id="tagsForm">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes()">
                                    </th>
                                    <th>Inventory Tag</th>
                                    <th>Property No</th>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Office</th>
                                    <th>Person Accountable</th>
                                    <th>Status</th>
                                    <th>QR Code</th>
                                    <th width="80" class="no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tags as $tag): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="selected_tags[]" value="<?php echo $tag['id']; ?>" class="tag-checkbox">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($tag['inventory_tag']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo date('M d, Y', strtotime($tag['created_at'])); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($tag['property_no'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($tag['description']); ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($tag['asset_description']); ?></small>
                                        </td>
                                        <td>
                                            <span class="category-badge">
                                                <?php echo htmlspecialchars($tag['category_code']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($tag['office_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if ($tag['employee_no']): ?>
                                                <?php echo htmlspecialchars($tag['employee_no']); ?>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($tag['firstname'] . ' ' . $tag['lastname']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">Not assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($tag['status']) {
                                                case 'serviceable':
                                                    $status_class = 'bg-success';
                                                    break;
                                                case 'unserviceable':
                                                    $status_class = 'bg-danger';
                                                    break;
                                                case 'in_use':
                                                    $status_class = 'bg-primary';
                                                    break;
                                                case 'available':
                                                    $status_class = 'bg-secondary';
                                                    break;
                                                default:
                                                    $status_class = 'bg-warning';
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst(htmlspecialchars($tag['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($tag['qr_code']): ?>
                                                <img src="../uploads/qr_codes/<?php echo htmlspecialchars($tag['qr_code']); ?>" 
                                                     alt="QR Code" 
                                                     class="img-fluid rounded"
                                                     style="max-width: 60px; max-height: 60px; cursor: pointer;"
                                                     onclick="showImageModal('../uploads/qr_codes/<?php echo htmlspecialchars($tag['qr_code']); ?>')">
                                            <?php else: ?>
                                                <i class="bi bi-qr-code-scan fs-4 text-muted"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td class="no-print">
                                            <button type="button" class="btn btn-sm btn-primary btn-action" onclick="printTag(<?php echo $tag['id']; ?>)">
                                                <i class="bi bi-printer"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-tags"></i>
                    <h5>No Inventory Tags Found</h5>
                    <p class="text-muted">
                        <?php if (!empty($search) || $office_filter > 0 || !empty($status_filter)): ?>
                            No inventory tags match your search criteria. Try adjusting your filters.
                        <?php else: ?>
                            No inventory tags have been created yet. Start by creating tags for your assets.
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($search) || $office_filter > 0 || !empty($status_filter)): ?>
                        <a href="inventory_tags.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-clockwise"></i>
                            Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">QR Code Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="QR Code" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        // Toggle all checkboxes
        function toggleAllCheckboxes() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.tag-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        // Show image modal
        function showImageModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }

        // Print single tag - open in new tab (working version)
        function printTag(tagId) {
            console.log('printTag called with tagId:', tagId);
            window.open('print_inventory_tag.php?id=' + tagId, '_blank');
        }

        // Print selected tags - open in new tab (working version)
        function printSelectedTags() {
            const checkboxes = document.querySelectorAll('.tag-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Please select at least one tag to print.');
                return;
            }

            const tagIds = Array.from(checkboxes).map(cb => cb.value).join(',');
            console.log('Printing selected tags:', tagIds);
            window.open('print_inventory_tags.php?ids=' + tagIds, '_blank');
        }

        // Print all tags - open in new tab (working version)
        function printAllTags() {
            <?php if (!empty($tags)): ?>
                const allTagIds = [<?php echo implode(',', array_column($tags, 'id')); ?>];
                console.log('Printing all tags:', allTagIds.join(','));
                window.open('print_inventory_tags.php?ids=' + allTagIds.join(','), '_blank');
            <?php else: ?>
                alert('No tags available to print.');
            <?php endif; ?>
        }
        function exportToCSV() {
            // Simple CSV export without complex PHP generation
            window.location.href = 'inventory_tags.php?export=csv';
        }
    </script>
    </div>
</body>
</html>
