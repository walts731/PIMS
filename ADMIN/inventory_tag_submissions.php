<?php
session_start();
require_once '../config.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

// Get filter parameters
$search = $_GET['search'] ?? '';

// Build WHERE clause
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(it.tag_number LIKE ? OR it.property_number LIKE ? OR it.item_description LIKE ? OR e.firstname LIKE ? OR e.lastname LIKE ? OR ac.category_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_fill(0, 6, $search_param);
    $types = str_repeat('s', 6);
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get submissions with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$count_sql = "SELECT COUNT(*) as total FROM inventory_tags it 
              LEFT JOIN employees e ON it.person_accountable = e.id 
              $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

$sql = "SELECT it.*, 
               e.firstname, e.lastname, e.employee_no,
               ac.category_name, ac.category_code,
               creator.first_name as creator_firstname, creator.last_name as creator_lastname
        FROM inventory_tags it
        LEFT JOIN employees e ON it.person_accountable = e.id
        LEFT JOIN asset_categories ac ON it.category_id = ac.id
        LEFT JOIN users creator ON it.created_by = creator.id
        $where_clause
        ORDER BY it.created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $all_params = array_merge($params, [$per_page, $offset]);
    $stmt->bind_param($types . 'ii', ...$all_params);
} else {
    $stmt->bind_param('ii', $per_page, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

// Get statistics
$stats_sql = "SELECT COUNT(*) as total FROM inventory_tags";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Tag Submissions | PIMS</title>
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
            background: linear-gradient(135deg, #F6F6F6 0%, #D6E4F0 100%);
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
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .submissions-table {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .status-processed { background-color: #cce5ff; color: #004085; }
        
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
    </style>
</head>
<body>
    <?php require_once 'includes/sidebar-toggle.php'; ?>
    
    <div class="main-wrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="container-fluid">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-2">Inventory Tag Submissions</h1>
                            <p class="text-muted mb-0">Manage and review inventory tag submissions</p>
                        </div>
                        <a href="asset_items.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Create New Tag
                        </a>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-1">Total Tags Created</h6>
                                    <div class="stats-number"><?php echo $stats['total']; ?></div>
                                </div>
                                <div class="text-primary">
                                    <i class="bi bi-tags" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filters and Search -->
                <div class="submissions-table">
                    <div class="p-3 border-bottom">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" placeholder="Search tags..." value="<?php echo htmlspecialchars($search); ?>" id="searchInput">
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <button class="btn btn-outline-primary" onclick="exportSubmissions()">
                                    <i class="bi bi-download"></i> Export
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Tag Number</th>
                                    <th>Property Number</th>
                                    <th>Item Description</th>
                                    <th>Category</th>
                                    <th>Person Accountable</th>
                                    <th>Submitted Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($row['tag_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['property_number']); ?></td>
                                            <td><?php echo htmlspecialchars($row['item_description']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($row['category_code'] . ' - ' . $row['category_name']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($row['employee_no'] . ' - ' . $row['lastname'] . ', ' . $row['firstname']); ?>
                                            </td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewSubmission(<?php echo $row['id']; ?>)">
                                                        <i class="bi bi-eye"></i> View
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="bi bi-inbox" style="font-size: 2rem; color: #6c757d;"></i>
                                            <p class="text-muted mt-2">No tag submissions found</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="p-3 border-top">
                            <nav>
                                <ul class="pagination mb-0 justify-content-center">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    <?php require_once 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                const search = this.value;
                window.location.href = `?search=${encodeURIComponent(search)}`;
            }
        });
        
        // Export submissions
        function exportSubmissions() {
            const search = document.getElementById('searchInput').value;
            window.location.href = `export_tag_submissions.php?search=${encodeURIComponent(search)}`;
        }
        
        // View submission
        function viewSubmission(id) {
            // Implementation for viewing submission details
            window.location.href = `view_tag_submission.php?id=${id}`;
        }
    </script>
</body>
</html>
