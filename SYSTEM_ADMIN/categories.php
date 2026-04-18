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
if ($_SESSION['role'] !== 'system_admin') {
    header('Location: ../index.php');
    exit();
}

// Log categories page access
logSystemAction($_SESSION['user_id'], 'access', 'categories', 'System admin accessed categories page');

// Handle CRUD operations
$message = '';
$message_type = '';

// CREATE - Add new category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $category_name = trim($_POST['category_name'] ?? '');
    $category_code = trim($_POST['category_code'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $depreciation_rate = floatval($_POST['depreciation_rate'] ?? 0);
    $useful_life_years = intval($_POST['useful_life_years'] ?? 0);
    
    // Validation
    if (empty($category_name) || empty($category_code)) {
        $message = "Category name and code are required.";
        $message_type = "danger";
    } elseif ($depreciation_rate < 0 || $depreciation_rate > 100) {
        $message = "Depreciation rate must be between 0 and 100.";
        $message_type = "danger";
    } elseif ($useful_life_years < 0) {
        $message = "Useful life years must be a positive number.";
        $message_type = "danger";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO asset_categories (category_name, category_code, description, depreciation_rate, useful_life_years, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssddi", $category_name, $category_code, $description, $depreciation_rate, $useful_life_years, $_SESSION['user_id']);
            $stmt->execute();
            
            $message = "Category added successfully!";
            $message_type = "success";
            
            logSystemAction($_SESSION['user_id'], 'category_added', 'asset_management', "Added category: {$category_name} ({$category_code})");
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $message = "Category name or code already exists.";
            } else {
                $message = "Error adding category: " . $e->getMessage();
            }
            $message_type = "danger";
        }
    }
}

// UPDATE - Edit category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $category_name = trim($_POST['category_name'] ?? '');
    $category_code = trim($_POST['category_code'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $depreciation_rate = floatval($_POST['depreciation_rate'] ?? 0);
    $useful_life_years = intval($_POST['useful_life_years'] ?? 0);
    
    
    // Validation
    if (empty($category_name) || empty($category_code)) {
        $message = "Category name and code are required.";
        $message_type = "danger";
    } elseif ($depreciation_rate < 0 || $depreciation_rate > 100) {
        $message = "Depreciation rate must be between 0 and 100.";
        $message_type = "danger";
    } elseif ($useful_life_years < 0) {
        $message = "Useful life years must be a positive number.";
        $message_type = "danger";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE asset_categories SET category_name = ?, category_code = ?, description = ?, depreciation_rate = ?, useful_life_years = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("sssddii", $category_name, $category_code, $description, $depreciation_rate, $useful_life_years, $_SESSION['user_id'], $id);
            $stmt->execute();
            
            
            $message = "Category updated successfully!";
            $message_type = "success";
            
            logSystemAction($_SESSION['user_id'], 'category_updated', 'asset_management', "Updated category: {$category_name} ({$category_code})");
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $message = "Category name or code already exists.";
            } else {
                $message = "Error updating category: " . $e->getMessage();
            }
            $message_type = "danger";
        }
    }
}


// Get all categories
$categories = [];
try {
    $stmt = $conn->prepare("SELECT ac.*, u1.username as created_by_name, u2.username as updated_by_name FROM asset_categories ac LEFT JOIN users u1 ON ac.created_by = u1.id LEFT JOIN users u2 ON ac.updated_by = u2.id ORDER BY ac.category_name");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
} catch (Exception $e) {
    $message = "Error fetching categories: " . $e->getMessage();
    $message_type = "danger";
}

// Get category for editing
$edit_category = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $conn->prepare("SELECT * FROM asset_categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $edit_category = $result->fetch_assoc();
    } catch (Exception $e) {
        $message = "Error fetching category: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Get system settings for theme
$system_settings = [];
try {
    $stmt = $conn->prepare("SELECT setting_name, setting_value FROM system_settings");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $system_settings[$row['setting_name']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Fallback to default
    $system_settings['system_name'] = 'PIMS';
}

// Set page title for topbar
$page_title = 'Categories';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - <?php echo htmlspecialchars($system_settings['system_name'] ?? 'PIMS'); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/admin-unified.css" rel="stylesheet">
<?php require_once 'includes/dark-mode-init.php'; ?>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            min-height: 100vh;
        }
        
        /* Sidebar Toggle Styles */
        .sidebar-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1051;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .sidebar-toggle:hover {
            background: var(--primary-hover);
            transform: scale(1.05);
        }
        
        .sidebar-toggle.sidebar-active {
            left: 300px;
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
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .main-wrapper.sidebar-active {
            margin-left: 0;
        }
        
        @media (max-width: 768px) {
            .sidebar-toggle.sidebar-active {
                left: 20px;
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
        
        /* Remove scrollbar from sidebar */
        .sidebar {
            overflow: hidden;
        }
        
        .sidebar * {
            scrollbar-width: none; /* Firefox */
        }
        
        .sidebar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Edge */
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
                        <i class="bi bi-tags"></i> Asset Categories
                    </h1>
                    <p class="text-muted mb-0">Manage asset categories for classification and depreciation tracking</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="categoryActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="categoryActionsDropdown">
                            <li>
                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#importCategoriesModal">
                                    <i class="bi bi-upload text-info"></i> Import Categories
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                    <i class="bi bi-plus-circle text-primary"></i> Add Category
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="dropdown-item" onclick="exportCategories()">
                                    <i class="bi bi-download text-success"></i> Export Categories
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="refreshCategories()">
                                    <i class="bi bi-arrow-clockwise text-warning"></i> Refresh Data
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="printCategories()">
                                    <i class="bi bi-printer text-secondary"></i> Print List
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Statistics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo count($categories); ?></div>
                            <div class="text-muted">Total Categories</div>
                            <small class="text-success">
                                <i class="bi bi-tags"></i> 
                                Asset Types
                            </small>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-tags fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo count(array_filter($categories, fn($c) => !empty($c['status']) && $c['status'] == 'active')); ?></div>
                            <div class="text-muted">Active Categories</div>
                            <small class="text-success">Ready for Use</small>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-check-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo count(array_filter($categories, fn($c) => !empty($c['status']) && $c['status'] == 'inactive')); ?></div>
                            <div class="text-muted">Inactive Categories</div>
                            <small class="text-warning">Disabled</small>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-pause-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo count(array_unique(array_column($categories, 'category_code'))); ?></div>
                            <div class="text-muted">Unique Codes</div>
                            <small class="text-info">No Duplicates</small>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-code-square fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Categories Table -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-header bg-primary text-white rounded-top-4">
                        <h6 class="mb-0"><i class="bi bi-tags"></i> Categories Management</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="categoriesTable">
                                <thead>
                                    <tr>
                                        <th>Category Name</th>
                                        <th>Code</th>
                                        <th>Description</th>
                                        <th>Depreciation Rate</th>
                                        <th>Useful Life</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($category['category_name']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($category['category_code']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($category['description'] ?? '-'); ?></td>
                                            <td><?php echo number_format($category['depreciation_rate'], 2); ?>%</td>
                                            <td><?php echo $category['useful_life_years']; ?> years</td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input status-switch" type="checkbox" 
                                                           id="status_<?php echo $category['id']; ?>" 
                                                           data-category-id="<?php echo $category['id']; ?>"
                                                           <?php echo (!empty($category['status']) && $category['status'] == 'active') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="status_<?php echo $category['id']; ?>">
                                                        <span class="badge bg-<?php echo !empty($category['status']) && $category['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                            <?php echo !empty($category['status']) && $category['status'] == 'active' ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                    </label>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y', strtotime($category['created_at'])); ?>
                                                    <br>by <?php echo htmlspecialchars($category['created_by_name'] ?? 'System'); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="editCategory(<?php echo $category['id']; ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category_name" class="form-label">Category Name *</label>
                                <input type="text" class="form-control" id="category_name" name="category_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="category_code" class="form-label">Category Code *</label>
                                <input type="text" class="form-control" id="category_code" name="category_code" 
                                       placeholder="e.g., FF" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="depreciation_rate" class="form-label">Depreciation Rate (%)</label>
                                <input type="number" class="form-control" id="depreciation_rate" name="depreciation_rate" 
                                       min="0" max="100" step="0.01" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="useful_life_years" class="form-label">Useful Life (Years)</label>
                                <input type="number" class="form-control" id="useful_life_years" name="useful_life_years" 
                                       min="0" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_category_name" class="form-label">Category Name *</label>
                                <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_category_code" class="form-label">Category Code *</label>
                                <input type="text" class="form-control" id="edit_category_code" name="category_code" 
                                       placeholder="e.g., FF" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_depreciation_rate" class="form-label">Depreciation Rate (%)</label>
                                <input type="number" class="form-control" id="edit_depreciation_rate" name="depreciation_rate" 
                                       min="0" max="100" step="0.01" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_useful_life_years" class="form-label">Useful Life (Years)</label>
                                <input type="number" class="form-control" id="edit_useful_life_years" name="useful_life_years" 
                                       min="0" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
        
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
<!-- Import Categories Modal -->
    <div class="modal fade" id="importCategoriesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-upload"></i> Import Categories
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="importCategoriesForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Import Instructions:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Upload a CSV file with category data</li>
                                <li>Required columns: Category Name, Category Code</li>
                                <li>Optional columns: Description, Depreciation Rate, Useful Life Years</li>
                                <li>First row should contain headers</li>
                                <li>Duplicate category codes will be skipped</li>
                            </ul>
                        </div>
                        
                        <div class="mb-3">
                            <label for="importFile" class="form-label">Select CSV File</label>
                            <input type="file" class="form-control" id="importFile" name="importFile" 
                                   accept=".csv" required>
                            <div class="form-text">Only CSV files are allowed. Maximum file size: 5MB.</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="skipDuplicates" name="skipDuplicates" checked>
                                <label class="form-check-label" for="skipDuplicates">
                                    Skip duplicate category codes
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="updateExisting" name="updateExisting">
                                <label class="form-check-label" for="updateExisting">
                                    Update existing categories with same code
                                </label>
                            </div>
                        </div>
                        
                        <div id="importPreview" class="d-none">
                            <h6 class="mt-4 mb-3">Import Preview</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered" id="previewTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Category Name</th>
                                            <th>Category Code</th>
                                            <th>Description</th>
                                            <th>Depreciation Rate</th>
                                            <th>Useful Life</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="previewBody">
                                        <!-- Preview data will be inserted here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-info" id="previewBtn">
                            <i class="bi bi-eye"></i> Preview
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Import Categories
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
        
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
</div> <!-- Close main wrapper -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
<?php require_once 'includes/sidebar-scripts.php'; ?>

// Fix modal backdrop issues
document.addEventListener('DOMContentLoaded', function() {
    const logoutModal = document.getElementById('logoutModal');
    if (logoutModal) {
        logoutModal.addEventListener('show.bs.modal', function () {
            // Ensure proper backdrop
            document.body.classList.add('modal-open');
        });
        
        logoutModal.addEventListener('hidden.bs.modal', function () {
            // Clean up backdrop
            document.body.classList.remove('modal-open');
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
        });
        
        // Ensure cancel button works properly
        const cancelButton = logoutModal.querySelector('[data-bs-dismiss="modal"]');
        if (cancelButton) {
            cancelButton.addEventListener('click', function(e) {
                e.preventDefault();
                const modal = bootstrap.Modal.getInstance(logoutModal);
                if (modal) {
                    modal.hide();
                }
            });
        }
    }
});

    // Initialize DataTables
    $(document).ready(function() {
        $('#categoriesTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            order: [[0, 'asc']],
            language: {
                search: "Search categories:",
                lengthMenu: "Show _MENU_ categories",
                info: "Showing _START_ to _END_ of _TOTAL_ categories",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });
    });

    function editCategory(id) {
        fetch(`ajax/get_category.php?action=edit&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('edit_id').value = data.category.id;
                    document.getElementById('edit_category_name').value = data.category.category_name;
                    document.getElementById('edit_category_code').value = data.category.category_code;
                    document.getElementById('edit_description').value = data.category.description || '';
                    document.getElementById('edit_depreciation_rate').value = data.category.depreciation_rate;
                    document.getElementById('edit_useful_life_years').value = data.category.useful_life_years;
                    
                    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
                } else {
                    alert('Error fetching category: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error fetching category data');
            });
    }
    
        
    // Handle status switch changes
    document.querySelectorAll('.status-switch').forEach(switchElement => {
        switchElement.addEventListener('change', function() {
            const categoryId = this.dataset.categoryId;
            const newStatus = this.checked ? 'active' : 'inactive';
            
            // Show loading state
            const badge = this.nextElementSibling.querySelector('span');
            const originalText = badge.textContent;
            badge.textContent = 'Updating...';
            badge.className = 'badge bg-warning';
            
            // Send AJAX request to update status
            fetch('ajax/update_category_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `category_id=${categoryId}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update badge
                    badge.textContent = newStatus === 'active' ? 'Active' : 'Inactive';
                    badge.className = `badge bg-${newStatus === 'active' ? 'success' : 'secondary'}`;
                    
                    // Show success message
                    showAlert(data.message, 'success');
                } else {
                    // Revert switch and show error
                    this.checked = !this.checked;
                    badge.textContent = originalText;
                    badge.className = `badge bg-${this.checked ? 'success' : 'secondary'}`;
                    showAlert(data.message || 'Error updating status', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert switch and show error
                this.checked = !this.checked;
                badge.textContent = originalText;
                badge.className = `badge bg-${this.checked ? 'success' : 'secondary'}`;
                showAlert('Error updating status', 'danger');
            });
        });
    });
    
    function showAlert(message, type) {
        // Remove existing alerts
        document.querySelectorAll('.alert').forEach(alert => alert.remove());
        
        // Create new alert
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insert after page header
        const pageHeader = document.querySelector('.page-header');
        pageHeader.parentNode.insertBefore(alertDiv, pageHeader.nextSibling);
        
        // Auto-dismiss after 3 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 3000);
    }
    
    // Export categories function
    function exportCategories() {
        window.location.href = 'export_categories.php';
    }
    
    // Refresh categories function
    function refreshCategories() {
        // Show loading state
        showAlert('Refreshing categories data...', 'info');
        
        // Reload the page after a short delay
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }
    
    // Print categories function
    function printCategories() {
        // Get all data from DataTable (not just current page)
        const table = $('#categoriesTable').DataTable();
        const allData = table.data().toArray();
        
        if (allData.length === 0) {
            showAlert('No categories data to print', 'warning');
            return;
        }
        
        // Create a print preview window
        const printWindow = window.open('', '_blank', 'width=1000,height=800');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Asset Categories - Print Preview</title>
                <style>
                    @page {
                        size: A4;
                        margin: 0.5in;
                    }
                    
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                    }
                    
                    body {
                        font-family: Arial, sans-serif;
                        font-size: 12px;
                        color: #333;
                        background: white;
                    }
                    
                    .preview-toolbar {
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        z-index: 1000;
                        background: #191BA9;
                        color: white;
                        padding: 12px 20px;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
                    }
                    
                    .preview-toolbar .title {
                        font-weight: bold;
                        font-size: 14px;
                        display: flex;
                        align-items: center;
                    }
                    
                    .preview-toolbar .actions {
                        display: flex;
                        gap: 10px;
                    }
                    
                    .preview-toolbar button {
                        padding: 6px 12px;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 12px;
                        transition: all 0.3s ease;
                    }
                    
                    .preview-toolbar .btn-print {
                        background: #28a745;
                        color: white;
                    }
                    
                    .preview-toolbar .btn-print:hover {
                        background: #218838;
                        transform: translateY(-1px);
                    }
                    
                    .preview-toolbar .btn-close {
                        background: #6c757d;
                        color: white;
                    }
                    
                    .preview-toolbar .btn-close:hover {
                        background: #5a6268;
                        transform: translateY(-1px);
                    }
                    
                    .print-container {
                        width: 100%;
                        max-width: 8.5in;
                        margin: 0 auto;
                        padding: 60px 20px 20px;
                        background: white;
                        min-height: 11in;
                        position: relative;
                    }
                    
                    @media screen {
                        body {
                            background: #525659;
                            padding: 0;
                        }
                        .print-container {
                            background: white;
                            box-shadow: 0 0 20px rgba(0,0,0,0.5);
                            margin: 60px auto 20px;
                        }
                    }
                    
                    @media print {
                        .no-print { display: none !important; }
                        body { background: white; margin: 0; padding: 0; }
                        .print-container { 
                            box-shadow: none; 
                            margin: 0 auto; 
                            padding: 20px;
                            width: 100%;
                        }
                    }
                    
                    .report-header {
                        text-align: center;
                        margin-bottom: 30px;
                        border-bottom: 2px solid #191BA9;
                        padding-bottom: 15px;
                    }
                    
                    .report-header h1 {
                        color: #191BA9;
                        font-size: 24px;
                        font-weight: bold;
                        margin-bottom: 5px;
                        text-transform: uppercase;
                    }
                    
                    .report-header .subtitle {
                        color: #666;
                        font-size: 14px;
                        margin-bottom: 10px;
                    }
                    
                    .report-header .meta {
                        color: #333;
                        font-size: 12px;
                        font-weight: bold;
                    }
                    
                    .categories-table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 20px 0;
                    }
                    
                    .categories-table th,
                    .categories-table td {
                        border: 1px solid #333;
                        padding: 10px;
                        text-align: left;
                        vertical-align: top;
                    }
                    
                    .categories-table th {
                        background-color: #f8f9fa;
                        font-weight: bold;
                        color: #333;
                        text-transform: uppercase;
                        font-size: 11px;
                    }
                    
                    .categories-table .category-name {
                        font-weight: bold;
                        min-width: 150px;
                    }
                    
                    .categories-table .category-code {
                        font-family: monospace;
                        background-color: #f8f9fa;
                        padding: 4px 8px;
                        border-radius: 3px;
                        font-size: 11px;
                        min-width: 100px;
                        text-align: center;
                    }
                    
                    .categories-table .description {
                        max-width: 250px;
                        word-wrap: break-word;
                        font-size: 11px;
                    }
                    
                    .categories-table .rate {
                        text-align: right;
                        font-weight: bold;
                        min-width: 80px;
                    }
                    
                    .categories-table .years {
                        text-align: center;
                        font-weight: bold;
                        min-width: 80px;
                    }
                    
                    .categories-table .status-active {
                        color: #28a745;
                        font-weight: bold;
                        text-align: center;
                        text-transform: uppercase;
                        font-size: 11px;
                    }
                    
                    .categories-table .status-inactive {
                        color: #6c757d;
                        font-weight: bold;
                        text-align: center;
                        text-transform: uppercase;
                        font-size: 11px;
                    }
                    
                    .report-footer {
                        margin-top: 40px;
                        padding-top: 20px;
                        border-top: 1px solid #ddd;
                        font-size: 11px;
                        color: #666;
                        text-align: center;
                    }
                    
                    .report-footer .summary {
                        font-weight: bold;
                        margin-bottom: 5px;
                        color: #333;
                    }
                    
                    .report-footer .user-info {
                        font-style: italic;
                    }
                    
                    /* Alternating row colors */
                    .categories-table tbody tr:nth-child(even) {
                        background-color: #f9f9f9;
                    }
                    
                    .categories-table tbody tr:hover {
                        background-color: #f0f8ff;
                    }
                </style>
            </head>
            <body>
                <div class="preview-toolbar no-print">
                    <div class="title">
                        <i class="bi bi-printer-fill me-2"></i>Asset Categories Print Preview
                    </div>
                    <div class="actions">
                        <button onclick="window.print()" class="btn-print">
                            <i class="bi bi-printer me-1"></i>Print Report
                        </button>
                        <button onclick="window.close()" class="btn-close">
                            <i class="bi bi-x-lg me-1"></i>Close Preview
                        </button>
                    </div>
                </div>
                
                <div class="print-container">
                    <div class="report-header">
                        <h1>Asset Categories Report</h1>
                        <div class="subtitle">Property and Inventory Management System</div>
                        <div class="meta">
                            Generated on: ${new Date().toLocaleString()} | Total Categories: ${allData.length}
                        </div>
                    </div>
                    
                    <table class="categories-table">
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th>Category Code</th>
                                <th>Description</th>
                                <th>Depreciation Rate</th>
                                <th>Useful Life</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${allData.map((row, index) => {
                                // Extract data from DataTable row
                                const categoryName = row[0] || '';
                                const categoryCode = row[1] || '';
                                const description = row[2] || '-';
                                const depreciationRate = row[3] || '0%';
                                const usefulLife = row[4] || '0 years';
                                const status = row[5] || 'inactive';
                                
                                // Clean text content
                                const cleanName = categoryName.replace(/<[^>]*>/g, '').trim();
                                const cleanCode = categoryCode.replace(/<[^>]*>/g, '').trim();
                                const cleanDescription = description.replace(/<[^>]*>/g, '').trim();
                                const cleanRate = depreciationRate.replace(/<[^>]*>/g, '').trim();
                                const cleanLife = usefulLife.replace(/<[^>]*>/g, '').trim();
                                const cleanStatus = status.replace(/<[^>]*>/g, '').trim().toLowerCase();
                                
                                const statusClass = cleanStatus === 'active' ? 'status-active' : 'status-inactive';
                                
                                return `
                                    <tr>
                                        <td class="category-name">${cleanName}</td>
                                        <td><span class="category-code">${cleanCode}</span></td>
                                        <td class="description">${cleanDescription}</td>
                                        <td class="rate">${cleanRate}</td>
                                        <td class="years">${cleanLife}</td>
                                        <td class="${statusClass}">${cleanStatus.charAt(0).toUpperCase() + cleanStatus.slice(1)}</td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                    
                    <div class="report-footer">
                        <div class="summary">
                            Report Summary: ${allData.length} categories exported from PIMS Asset Management System
                        </div>
                        <div class="user-info">
                            Printed by: ${document.querySelector('.user-info')?.textContent?.trim() || 'System Administrator'} | 
                            Date: ${new Date().toLocaleDateString()} | 
                            Page: <span class="page-number"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Bootstrap Icons -->
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
            </body>
            </html>
        `);
        printWindow.document.close();
        
        // Add page numbering
        printWindow.onload = function() {
            // Add page numbers
            const pageNumbers = printWindow.document.querySelectorAll('.page-number');
            pageNumbers.forEach((element, index) => {
                element.textContent = index + 1;
            });
        };
    }
    
    // Import categories functionality
    let importData = [];
    
    // Preview button click handler
    document.getElementById('previewBtn').addEventListener('click', function() {
        const fileInput = document.getElementById('importFile');
        const file = fileInput.files[0];
        
        if (!file) {
            showAlert('Please select a CSV file first', 'warning');
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) { // 5MB limit
            showAlert('File size exceeds 5MB limit', 'danger');
            return;
        }
        
        if (file.type !== 'text/csv' && !file.name.endsWith('.csv')) {
            showAlert('Please select a CSV file', 'warning');
            return;
        }
        
        // Read and parse CSV file
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const csvData = e.target.result;
                importData = parseCSV(csvData);
                
                if (importData.length === 0) {
                    showAlert('No valid data found in CSV file', 'warning');
                    return;
                }
                
                // Show preview
                showImportPreview(importData);
                showAlert(`Preview loaded: ${importData.length} categories found`, 'success');
                
            } catch (error) {
                console.error('Error parsing CSV:', error);
                showAlert('Error parsing CSV file: ' + error.message, 'danger');
            }
        };
        reader.readAsText(file);
    });
    
    // Parse CSV function
    function parseCSV(csvData) {
        const lines = csvData.split('\n').filter(line => line.trim());
        if (lines.length < 2) {
            throw new Error('CSV file must have at least a header row and one data row');
        }
        
        const headers = lines[0].split(',').map(h => h.trim().replace(/"/g, ''));
        const data = [];
        
        for (let i = 1; i < lines.length; i++) {
            const values = lines[i].split(',').map(v => v.trim().replace(/"/g, ''));
            
            if (values.length >= 2 && values[0] && values[1]) {
                data.push({
                    category_name: values[0] || '',
                    category_code: values[1] || '',
                    description: values[2] || '',
                    depreciation_rate: parseFloat(values[3]) || 0,
                    useful_life_years: parseInt(values[4]) || 0,
                    status: values[5] || 'active',
                    row_number: i + 1
                });
            }
        }
        
        return data;
    }
    
    // Show import preview
    function showImportPreview(data) {
        const previewBody = document.getElementById('previewBody');
        const previewDiv = document.getElementById('importPreview');
        
        previewBody.innerHTML = '';
        
        data.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.category_name}</td>
                <td>${item.category_code}</td>
                <td>${item.description || '-'}</td>
                <td>${item.depreciation_rate}%</td>
                <td>${item.useful_life_years || '-'}</td>
                <td><span class="badge bg-${item.status === 'active' ? 'success' : 'secondary'}">${item.status}</span></td>
            `;
            previewBody.appendChild(row);
        });
        
        previewDiv.classList.remove('d-none');
    }
    
    // Import form submit handler
    document.getElementById('importCategoriesForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (importData.length === 0) {
            showAlert('No data to import. Please preview the file first.', 'warning');
            return;
        }
        
        const skipDuplicates = document.getElementById('skipDuplicates').checked;
        const updateExisting = document.getElementById('updateExisting').checked;
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Importing...';
        submitBtn.disabled = true;
        
        // Send import request
        const formData = new FormData();
        formData.append('action', 'import');
        formData.append('data', JSON.stringify(importData));
        formData.append('skipDuplicates', skipDuplicates);
        formData.append('updateExisting', updateExisting);
        
        fetch('ajax/category_import.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                
                // Close modal and refresh page after delay
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('importCategoriesModal'));
                    modal.hide();
                    
                    // Reset form
                    document.getElementById('importCategoriesForm').reset();
                    document.getElementById('importPreview').classList.add('d-none');
                    importData = [];
                    
                    // Refresh page to show new data
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }, 2000);
            } else {
                showAlert(data.message || 'Import failed', 'danger');
            }
        })
        .catch(error => {
            console.error('Import error:', error);
            showAlert('Error during import: ' + error.message, 'danger');
        })
        .finally(() => {
            // Restore button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
    
    // Reset preview when file input changes
    document.getElementById('importFile').addEventListener('change', function() {
        document.getElementById('importPreview').classList.add('d-none');
        importData = [];
    });
</script>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>
