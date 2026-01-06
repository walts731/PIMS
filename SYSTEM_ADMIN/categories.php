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
    } elseif (!preg_match('/^[A-Z]{2,5}$/', $category_code)) {
        $message = "Category code must be 2-5 uppercase letters.";
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
    } elseif (!preg_match('/^[A-Z]{2,5}$/', $category_code)) {
        $message = "Category code must be 2-5 uppercase letters.";
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

// DELETE - Delete category
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    try {
        // Get category info before deletion
        $stmt = $conn->prepare("SELECT category_name, category_code FROM asset_categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $category = $result->fetch_assoc();
        
        if ($category) {
            $stmt = $conn->prepare("DELETE FROM asset_categories WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $message = "Category deleted successfully!";
            $message_type = "success";
            
            logSystemAction($_SESSION['user_id'], 'category_deleted', 'asset_management', "Deleted category: {$category['category_name']} ({$category['category_code']})");
        }
    } catch (Exception $e) {
        $message = "Error deleting category: " . $e->getMessage();
        $message_type = "danger";
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
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
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
                    <div class="btn-group" role="group">
                        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#importCategoriesModal">
                            <i class="bi bi-upload"></i> Import
                        </button>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="bi bi-plus-circle"></i> Add Category
                        </button>
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
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['category_name']); ?>')">
                                                        <i class="bi bi-trash"></i>
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
                                       pattern="[A-Z]{2,5}" placeholder="e.g., FF" required>
                                <small class="form-text text-muted">2-5 uppercase letters</small>
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
                                       pattern="[A-Z]{2,5}" placeholder="e.g., FF" required>
                                <small class="form-text text-muted">2-5 uppercase letters</small>
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
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the category "<span id="deleteCategoryName"></span>"?</p>
                    <p class="text-muted small">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="deleteConfirmBtn">Delete</button>
                </div>
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
    
    function deleteCategory(id, name) {
        document.getElementById('deleteCategoryName').textContent = name;
        document.getElementById('deleteConfirmBtn').href = `categories.php?action=delete&id=${id}`;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
    
    // Auto-uppercase category codes
    document.getElementById('category_code').addEventListener('input', function(e) {
        e.target.value = e.target.value.toUpperCase();
    });
    
    document.getElementById('edit_category_code').addEventListener('input', function(e) {
        e.target.value = e.target.value.toUpperCase();
    });
    
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
</script>
</body>
</html>
