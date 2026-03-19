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
$category_filter = intval($_GET['category'] ?? 0);
$status_filter = $_GET['status'] ?? '';

// Build WHERE clause
$where_conditions = [];
$params = [];
$types = '';

// Always filter for serviceable items only
$where_conditions[] = "ai.status = 'serviceable'";

// Only show items that have property numbers (required for inventory tags)
$where_conditions[] = "ai.property_no IS NOT NULL AND ai.property_no != ''";

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

if ($category_filter > 0) {
    $where_conditions[] = "COALESCE(ai.category_id, a.asset_categories_id) = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

if (!empty($status_filter)) {
    $where_conditions[] = "ai.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

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
            $where_clause AND ai.property_no IS NOT NULL AND ai.property_no != ''
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

// Get categories for filter
$categories = [];
try {
    $result = $conn->query("SELECT id, category_name, category_code FROM asset_categories WHERE status = 'active' ORDER BY category_name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
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
              WHERE ai.property_no IS NOT NULL AND ai.property_no != '' AND ai.status = 'serviceable'";
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
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
</head>
<body>
    <?php $page_title = 'Inventory Tags'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-qr-code"></i> Inventory Tags
                    </h1>
                    <p class="text-muted mb-0">View and print inventory tags for all serviceable assets with property numbers</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex gap-2 justify-content-md-end">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="printAllTags()">
                            <i class="bi bi-printer"></i> Print All
                        </button>
                        <button type="button" class="btn btn-success btn-sm" onclick="printSelectedTags()">
                            <i class="bi bi-check2"></i> Print Selected
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row g-3 mb-4">
<<<<<<< HEAD
            <div class="col-6 col-md-4">
=======
            <div class="col-6 col-md-3">
>>>>>>> 1b538331e37148a87bb4fe58e2b12354b716a520
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($stats['total_tags'] ?? 0); ?></div>
                    <div class="stats-label"><i class="bi bi-qr-code"></i> Serviceable Tags</div>
                </div>
            </div>
<<<<<<< HEAD
            <div class="col-6 col-md-4">
=======
            <div class="col-6 col-md-3">
>>>>>>> 1b538331e37148a87bb4fe58e2b12354b716a520
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($stats['offices_with_tags'] ?? 0); ?></div>
                    <div class="stats-label"><i class="bi bi-building"></i> Offices with Tags</div>
                </div>
            </div>
<<<<<<< HEAD
           
            <div class="col-6 col-md-4">
=======
            <div class="col-6 col-md-3">
>>>>>>> 1b538331e37148a87bb4fe58e2b12354b716a520
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($tags); ?></div>
                    <div class="stats-label"><i class="bi bi-list-check"></i> Current Results</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($tags); ?></div>
                    <div class="stats-label"><i class="bi bi-list-check"></i> Current Results</div>
                </div>
            </div>
        </div>

        <div class="section-card mb-4">
            <div class="section-title">
                <i class="bi bi-funnel"></i> Search & Filters
            </div>
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" id="searchInput" class="form-control" placeholder="Search by tag, property no, description, or employee..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="office" id="officeFilter" class="form-select">
                        <option value="">All Offices</option>
                        <?php foreach ($offices as $office): ?>
                            <option value="<?php echo $office['id']; ?>" <?php echo $office_filter == $office['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($office['office_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="category" id="categoryFilter" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category_code'] . ' - ' . $category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" id="statusFilter" class="form-select">
                        <option value="">All Serviceable Items</option>
                        <option value="serviceable" <?php echo $status_filter == 'serviceable' ? 'selected' : ''; ?>>Serviceable</option>
                    </select>
                </div>
            </form>
        </div>

        <div class="section-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="section-title mb-0">
                    <i class="bi bi-qr-code"></i> Inventory Tags
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm btn-action" onclick="selectAllTags()">
                        <i class="bi bi-check-all"></i> Select All
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-action" onclick="clearSelection()">
                        <i class="bi bi-x-square"></i> Clear Selection
                    </button>
                </div>
            </div>
            <?php if (!empty($tags)): ?>
                <form id="tagsForm">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes()">
                                    </th>
                                    <th>Property No</th>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Office</th>
                                    <th>Person Accountable</th>
                                    <th>Status</th>
                                    <th width="80" class="no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tags as $tag): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="selected_tags[]" value="<?php echo $tag['id']; ?>" class="tag-checkbox">
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
                        <?php if (!empty($search) || $office_filter > 0 || $category_filter > 0 || !empty($status_filter)): ?>
                            No serviceable assets with property numbers match your search criteria. Try adjusting your filters.
                        <?php else: ?>
                            No serviceable assets with property numbers found. Assets need property numbers to generate inventory tags.
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($search) || $office_filter > 0 || $category_filter > 0 || !empty($status_filter)): ?>
                        <a href="inventory_tags.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-clockwise"></i>
                            Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
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

    <!-- Selection Alert Modal -->
    <div class="modal fade" id="selectionAlertModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> No Selection</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="bi bi-check-square fs-1 text-warning mb-3"></i>
                    <h6>Please select at least one tag to print.</h6>
                    <p class="text-muted mb-0">Choose one or more inventory tags from the list before clicking the Print Selected button.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        <i class="bi bi-check-circle"></i> OK
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
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

        // Select all tags
        function selectAllTags() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.tag-checkbox');
            selectAll.checked = true;
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
        }

        // Clear selection
        function clearSelection() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.tag-checkbox');
            selectAll.checked = false;
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
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
                // Show modal instead of alert
                const modal = new bootstrap.Modal(document.getElementById('selectionAlertModal'));
                modal.show();
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

        // Automatic filtering
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const officeFilter = document.getElementById('officeFilter');
            const categoryFilter = document.getElementById('categoryFilter');
            const statusFilter = document.getElementById('statusFilter');
            const filterForm = document.getElementById('filterForm');

            // Function to submit form with current filter values
            function applyFilters() {
                const formData = new FormData(filterForm);
                const params = new URLSearchParams();
                
                // Add all form parameters to URL
                for (let [key, value] of formData.entries()) {
                    if (value) {
                        params.append(key, value);
                    }
                }
                
                // Redirect to current page with filter parameters
                window.location.href = 'inventory_tags.php?' + params.toString();
            }

            // Add event listeners for automatic filtering
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(applyFilters, 500); // Wait 500ms after typing stops
                });
            }

            if (officeFilter) {
                officeFilter.addEventListener('change', applyFilters);
            }

            if (categoryFilter) {
                categoryFilter.addEventListener('change', applyFilters);
            }

            if (statusFilter) {
                statusFilter.addEventListener('change', applyFilters);
            }
        });
    </script>
    </div>
</body>
</html>
