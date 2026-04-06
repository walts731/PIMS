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
if ($_SESSION['role'] !== 'office_admin' && $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'system_admin') {
    header('Location: ../index.php');
    exit();
}

// Set page title for topbar
$page_title = 'Office Assets';

// Handle filter parameters
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Get office-specific assets using similar approach as ADMIN/assets.php
$assets = [];
$stats = [
    'total_assets' => 0,
    'total_quantity' => 0,
    'total_value' => 0,
    'serviceable_count' => 0,
    'unserviceable_count' => 0,
    'no_tag_count' => 0
];

// Use office_id directly from session
$office_id = $_SESSION['office_id'] ?? null;

if ($office_id && $conn) {
    try {
        // Debug: Check session and office_id values
        error_log("DEBUG: Session office_id = " . ($office_id ?? 'NULL'));
        error_log("DEBUG: Session office = " . ($_SESSION['office'] ?? 'NOT SET'));
        error_log("DEBUG: Session email = " . ($_SESSION['email'] ?? 'NOT SET'));
        
        // Fetch assets for this office with filters using similar query as ADMIN
        $query = "SELECT a.*, ac.category_name, ac.category_code, o.office_name,
                       sc.sub_category_name, sc.sub_category_code,
                       (SELECT ai.status FROM asset_items ai WHERE ai.asset_id = a.id GROUP BY ai.status ORDER BY COUNT(*) DESC LIMIT 1) as most_common_status,
                       (SELECT COUNT(ai.id) FROM asset_items ai WHERE ai.asset_id = a.id) as total_quantity,
                       (SELECT SUM(ai.value) FROM asset_items ai WHERE ai.asset_id = a.id) as total_value
                FROM assets a 
                LEFT JOIN asset_categories ac ON a.asset_categories_id = ac.id 
                LEFT JOIN asset_sub_categories sc ON a.asset_subcategory_id = sc.id
                LEFT JOIN offices o ON a.office_id = o.id 
                WHERE a.office_id = ?";
        
        $params = [$office_id];
        $types = 'i';
        
        if ($category_filter > 0) {
            $query .= " AND a.asset_categories_id = ?";
            $params[] = $category_filter;
            $types .= 'i';
        }
        
        if (!empty($status_filter)) {
            $query .= " AND EXISTS (SELECT 1 FROM asset_items ai WHERE ai.asset_id = a.id AND ai.status = ?)";
            $params[] = $status_filter;
            $types .= 's';
        }
        
        $query .= " ORDER BY a.created_at DESC";
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        } else {
            $stmt->bind_param("i", $office_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        error_log("DEBUG: Query executed, rows found: " . $result->num_rows);
        
        while ($row = $result->fetch_assoc()) {
            $assets[] = $row;
            
            // Calculate statistics
            $stats['total_assets']++;
            $stats['total_quantity'] += $row['total_quantity'] ?? 0;
            $stats['total_value'] += $row['total_value'] ?? 0;
            
            // Get detailed status counts
            $status_query = "SELECT status, COUNT(*) as count FROM asset_items WHERE asset_id = ? GROUP BY status";
            $status_stmt = $conn->prepare($status_query);
            $status_stmt->bind_param("i", $row['id']);
            $status_stmt->execute();
            $status_result = $status_stmt->get_result();
            
            while ($status_row = $status_result->fetch_assoc()) {
                switch($status_row['status']) {
                    case 'available':
                    case 'serviceable':
                        $stats['serviceable_count'] += $status_row['count'];
                        break;
                    case 'in_use':
                    case 'maintenance':
                    case 'unserviceable':
                    case 'disposed':
                        $stats['unserviceable_count'] += $status_row['count'];
                        break;
                    case 'no_tag':
                        $stats['no_tag_count'] += $status_row['count'];
                        break;
                }
            }
            $status_stmt->close();
        }
        
    } catch (Exception $e) {
        error_log("Error fetching assets: " . $e->getMessage());
    }
}

// Get asset categories for dropdown filter
$categories = [];
try {
    $result = $conn->query("SELECT id, description FROM asset_categories ORDER BY description");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office Assets - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="dashboard.css?v=<?php echo time(); ?>" rel="stylesheet">
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
        
        .subcategory-badge {
            background: #6c757d;
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
        
        .search-box {
            background: white;
            border: 2px solid var(--accent-color);
            border-radius: var(--border-radius-lg);
            padding: 0.75rem 1rem;
            transition: var(--transition);
        }
        
        .search-box:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(25, 27, 169, 0.25);
            outline: none;
        }
        
        .form-control {
            background: var(--light-color);
            border: 2px solid var(--accent-color);
            border-radius: var(--border-radius-lg);
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(25, 27, 169, 0.25);
            outline: none;
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: var(--border-radius-lg);
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(25, 27, 169, 0.3);
        }
        
        /* Custom scrollbar for webkit browsers */
        .main-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .main-content::-webkit-scrollbar-track {
            background: rgba(25, 27, 169, 0.1);
            border-radius: 4px;
        }
        
        .main-content::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            border-radius: 4px;
        }
        
        .main-content::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5CC2F2 0%, #191BA9 100%);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
                max-height: calc(100vh - 60px);
            }
            
            .navbar-brand {
                font-size: 1.2rem;
            }
        }
        
        /* Modal z-index fixes */
        .modal {
            z-index: 1055;
        }
        
        .modal-backdrop {
            z-index: 1050;
        }
        
        .modal-dialog {
            z-index: 1060;
        }
        
        /* Ensure sidebar overlay doesn't interfere with modals */
        .sidebar-overlay {
            z-index: 1040;
        }
        
        /* Fix modal backdrop issues */
        .modal.show {
            display: block !important;
        }
        
        .modal-backdrop.show {
            display: block !important;
            opacity: 0.5;
        }
        
        /* Ensure modal buttons are clickable */
        .modal-footer button,
        .modal-header button,
        .modal-footer a {
            z-index: 1061;
            position: relative;
        }
    </style>
</head>
<body>
<!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
        <?php require_once 'includes/notification_js.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-box-seam"></i> Office Assets
                    </h1>
                    <p class="text-muted mb-0">Manage office assets, equipment, and inventory</p>
                </div>
            </div>
        </div>
        
        <!-- Asset Statistics -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_assets']; ?></div>
                    <div class="stats-label"><i class="bi bi-box"></i> Total Assets</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_quantity']; ?></div>
                    <div class="stats-label"><i class="bi bi-box"></i> Total Items</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['serviceable_count']; ?></div>
                    <div class="stats-label"><i class="bi bi-check-circle"></i> Serviceable</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['unserviceable_count']; ?></div>
                    <div class="stats-label"><i class="bi bi-x-circle"></i> Unserviceable</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['no_tag_count']; ?></div>
                    <div class="stats-label"><i class="bi bi-x-circle"></i> No Tag</div>
                </div>
            </div>
        </div>
        
        <!-- Search and Filter -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-header bg-primary text-white rounded-top-4">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h6 class="mb-0"><i class="bi bi-box-seam"></i> Office Assets Inventory</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <select class="form-select form-select-sm" id="categoryFilter">
                                            <option value="">All Categories</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['description']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <select class="form-select form-select-sm" id="statusFilter">
                                            <option value="">All Status</option>
                                            <option value="available" <?php echo $status_filter == 'available' ? 'selected' : ''; ?>>Available</option>
                                            <option value="in_use" <?php echo $status_filter == 'in_use' ? 'selected' : ''; ?>>In Use</option>
                                            <option value="maintenance" <?php echo $status_filter == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                            <option value="unserviceable" <?php echo $status_filter == 'unserviceable' ? 'selected' : ''; ?>>Unserviceable</option>
                                            <option value="disposed" <?php echo $status_filter == 'disposed' ? 'selected' : ''; ?>>Disposed</option>
                                            <option value="no_tag" <?php echo $status_filter == 'no_tag' ? 'selected' : ''; ?>>No Tag</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="assetsTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Subcategory</th>
                                        <th>Description</th>
                                        <th>Quantity</th>
                                        <th>Office</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($assets)): ?>
                                        <?php foreach ($assets as $asset): ?>
                                            <tr>
                                                <td>
                                                    <span class="category-badge">
                                                        <?php echo htmlspecialchars($asset['category_code'] ?? 'N/A'); ?>
                                                    </span>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($asset['category_name'] ?? 'N/A'); ?></small>
                                                </td>
                                                <td>
                                                    <?php if (!empty($asset['sub_category_code'])): ?>
                                                        <span class="subcategory-badge">
                                                            <?php echo htmlspecialchars($asset['sub_category_code']); ?>
                                                        </span>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($asset['sub_category_name']); ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">No subcategory</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($asset['description']); ?></td>
                                                <td><?php echo $asset['quantity']; ?></td>
                                                <td><?php echo htmlspecialchars($asset['office_name'] ?? 'N/A'); ?></td>
                                                <td><small><?php echo date('M j, Y', strtotime($asset['created_at'])); ?></small></td>
                                                <td>
                                                    <a href="asset_items.php?asset_id=<?php echo $asset['id']; ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="bi bi-eye"></i> View Items
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td class="text-center text-muted py-4">
                                                <i class="bi bi-inbox fs-1"></i>
                                                <p class="mt-2">No assets found in your office.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Edit Asset Modal -->
    <div class="modal fade" id="editAssetModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Asset</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="editAssetForm">
                    <input type="hidden" name="action" value="edit_asset">
                    <input type="hidden" name="asset_id" id="editAssetId">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_asset_name" class="form-label">Asset Name</label>
                                    <input type="text" class="form-control" id="edit_asset_name" name="asset_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_asset_type" class="form-label">Asset Type</label>
                                    <select class="form-control" id="edit_asset_type" name="asset_type" required>
                                        <option value="">Select Type</option>
                                        <option value="electronics">Electronics</option>
                                        <option value="furniture">Furniture</option>
                                        <option value="vehicle">Vehicle</option>
                                        <option value="equipment">Equipment</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="edit_description" class="form-label">Description</label>
                                    <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_serial_number" class="form-label">Serial Number</label>
                                    <input type="text" class="form-control" id="edit_serial_number" name="serial_number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_location" class="form-label">Location</label>
                                    <input type="text" class="form-control" id="edit_location" name="location" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_purchase_date" class="form-label">Purchase Date</label>
                                    <input type="date" class="form-control" id="edit_purchase_date" name="purchase_date" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_purchase_cost" class="form-label">Purchase Cost (₱)</label>
                                    <input type="number" class="form-control" id="edit_purchase_cost" name="purchase_cost" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_current_value" class="form-label">Current Value (₱)</label>
                                    <input type="number" class="form-control" id="edit_current_value" name="current_value" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_quantity" class="form-label">Quantity</label>
                                    <input type="number" class="form-control" id="edit_quantity" name="quantity" value="1" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_status" class="form-label">Status</label>
                                    <select class="form-control" id="edit_status" name="status" required>
                                        <option value="available">Available</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="disposed">Disposed</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Update Asset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#assetsTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[5, 'desc']], // Sort by Created date column (index 5) by default
                columnDefs: [
                    {
                        targets: 0, // Category column
                        orderable: true,
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return data;
                            }
                            return data.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();
                        }
                    },
                    {
                        targets: 5, // Created date column
                        orderable: true,
                        render: function(data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                // Convert date string to timestamp for sorting
                                return new Date(data).getTime();
                            }
                            return data;
                        }
                    },
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
            
            // Category filter - reload page with filter parameter
            $('#categoryFilter').on('change', function() {
                const categoryValue = this.value;
                const currentUrl = new URL(window.location);
                
                if (categoryValue) {
                    currentUrl.searchParams.set('category', categoryValue);
                } else {
                    currentUrl.searchParams.delete('category');
                }
                currentUrl.searchParams.delete('page'); // Reset pagination
                window.location.href = currentUrl.toString();
            });
            
            // Status filter - reload page with filter parameter
            $('#statusFilter').on('change', function() {
                const statusValue = this.value;
                const currentUrl = new URL(window.location);
                
                if (statusValue) {
                    currentUrl.searchParams.set('status', statusValue);
                } else {
                    currentUrl.searchParams.delete('status');
                }
                currentUrl.searchParams.delete('page'); // Reset pagination
                window.location.href = currentUrl.toString();
            });
        });
        
        // Edit asset function
        function editAsset(assetId) {
            // This will be implemented when backend is added
            console.log('Edit asset:', assetId);
        }
        
        // Clear form on edit modal close
        document.getElementById('editAssetModal').addEventListener('hidden.bs.modal', function () {
            this.querySelector('form').reset();
        });
    </script>
    
    <!-- Bootstrap-based Notification Script -->
    <?php require_once 'includes/notification_script_bootstrap.php'; ?>
    
    <!-- Sidebar Scripts -->
    <script src="../assets/js/sidebar.js"></script>
</body>
</html>