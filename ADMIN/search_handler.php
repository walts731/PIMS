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

// Get search query
$query = trim($_GET['q'] ?? '');
if (empty($query)) {
    header('Location: dashboard.php');
    exit();
}

// Log search action
logSystemAction($_SESSION['user_id'], 'search', 'global_search', "Searched for: $query");

try {
    // Search in asset_items
    $asset_results = [];
    $asset_stmt = $conn->prepare("
        SELECT ai.id, ai.description, ai.status, ai.property_no, ai.inventory_tag,
               a.description as asset_description, o.office_name,
               CONCAT(e.firstname, ' ', e.lastname) as employee_name
        FROM asset_items ai
        LEFT JOIN assets a ON ai.asset_id = a.id
        LEFT JOIN offices o ON ai.office_id = o.id
        LEFT JOIN employees e ON ai.employee_id = e.id
        WHERE ai.description LIKE ? OR ai.property_no LIKE ? OR ai.inventory_tag LIKE ?
        ORDER BY ai.last_updated DESC
        LIMIT 10
    ");
    $search_pattern = "%$query%";
    $asset_stmt->bind_param("sss", $search_pattern, $search_pattern, $search_pattern);
    $asset_stmt->execute();
    $asset_result = $asset_stmt->get_result();
    while ($row = $asset_result->fetch_assoc()) {
        $asset_results[] = $row;
    }
    $asset_stmt->close();

    // Search in employees
    $employee_results = [];
    $employee_stmt = $conn->prepare("
        SELECT e.id, e.employee_no, e.firstname, e.lastname, e.position, e.employment_status, e.clearance_status, o.office_name
        FROM employees e
        LEFT JOIN offices o ON e.office_id = o.id
        WHERE e.employee_no LIKE ? OR e.firstname LIKE ? OR e.lastname LIKE ? 
           OR CONCAT(e.firstname, ' ', e.lastname) LIKE ? OR e.position LIKE ? OR e.employment_status LIKE ?
        ORDER BY e.lastname, e.firstname
        LIMIT 10
    ");
    $employee_stmt->bind_param("ssssss", $search_pattern, $search_pattern, $search_pattern, $search_pattern, $search_pattern, $search_pattern);
    $employee_stmt->execute();
    $employee_result = $employee_stmt->get_result();
    while ($row = $employee_result->fetch_assoc()) {
        $employee_results[] = $row;
    }
    $employee_stmt->close();

    // If only one result found, redirect directly
    if (count($asset_results) === 1 && count($employee_results) === 0) {
        header('Location: view_asset_item.php?id=' . $asset_results[0]['id']);
        exit();
    } elseif (count($employee_results) === 1 && count($asset_results) === 0) {
        header('Location: view_employee.php?id=' . $employee_results[0]['id']);
        exit();
    }

} catch (Exception $e) {
    error_log("Search Error: " . $e->getMessage());
    $asset_results = [];
    $employee_results = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    <style>
        /* Search Results Specific Styles */
        .search-results-container {
            padding: 20px 0;
        }
        
        .search-summary {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-sm);
            border-left: 4px solid var(--primary-color);
        }
        
        .search-query-highlight {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
            color: #856404;
        }
        
        .results-section {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }
        
        .results-section:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .result-count {
            background: var(--primary-gradient);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .search-item {
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
            background: rgba(var(--primary-rgb), 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .search-item:hover {
            background: rgba(var(--primary-rgb), 0.1);
            transform: translateX(5px);
            box-shadow: var(--shadow-sm);
        }
        
        .search-item-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .type-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-asset {
            background: var(--primary-gradient);
            color: white;
        }
        
        .badge-employee {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .item-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
        }
        
        .item-details {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .detail-item i {
            font-size: 0.85rem;
            color: var(--primary-color);
        }
        
        .status-badges {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        /* Status Colors */
        .status-serviceable { background: #d4edda; color: #155724; }
        .status-in_use { background: #cce5ff; color: #004085; }
        .status-maintenance { background: #fff3cd; color: #856404; }
        .status-disposed { background: #f8d7da; color: #721c24; }
        .status-unserviceable { background: #f8d7da; color: #721c24; }
        .status-no_tag { background: #e2e3e5; color: #383d41; }
        .status-red_tagged { background: #dc3545; color: white; }
        .status-borrowed { background: #17a2b8; color: white; }
        
        /* Employment Status Colors */
        .employment-permanent { background: #d4edda; color: #155724; }
        .employment-contractual { background: #cce5ff; color: #004085; }
        .employment-job_order { background: #fff3cd; color: #856404; }
        .employment-resigned { background: #f8d7da; color: #721c24; }
        .employment-retired { background: #e2e3e5; color: #383d41; }
        
        /* Clearance Status Colors */
        .clearance-cleared { background: #d4edda; color: #155724; }
        .clearance-uncleared { background: #f8d7da; color: #721c24; }
        
        .no-results-container {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .no-results-icon {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .no-results-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .no-results-text {
            font-size: 1rem;
            margin-bottom: 20px;
        }
        
        .search-tips {
            background: rgba(var(--primary-rgb), 0.05);
            padding: 15px;
            border-radius: var(--border-radius);
            border-left: 3px solid var(--primary-color);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .search-item {
                padding: 15px;
            }
            
            .search-item-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .item-details {
                flex-direction: column;
                gap: 8px;
            }
            
            .status-badges {
                align-items: flex-start;
                margin-top: 10px;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Search Results';
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
                        <i class="bi bi-search"></i> Search Results
                    </h1>
                    <p class="text-muted mb-0">
                        Search query: <span class="search-query-highlight"><?php echo htmlspecialchars($query); ?></span>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-outline-primary btn-sm" onclick="history.back()">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                </div>
            </div>
        </div>
        
        <div class="search-results-container">
            <!-- Asset Items Results -->
            <?php if (!empty($asset_results)): ?>
                <div class="results-section">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="bi bi-box"></i>
                            Asset Items
                        </div>
                        <div class="result-count">
                            <?php echo count($asset_results); ?> results
                        </div>
                    </div>
                    
                    <?php foreach ($asset_results as $asset): ?>
                        <div class="search-item" onclick="window.location.href='view_asset_item.php?id=<?php echo $asset['id']; ?>'">
                            <div class="search-item-header">
                                <span class="type-badge badge-asset">Asset</span>
                                <div class="item-title"><?php echo htmlspecialchars($asset['description']); ?></div>
                            </div>
                            
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="item-details">
                                        <?php if (!empty($asset['property_no'])): ?>
                                            <div class="detail-item">
                                                <i class="bi bi-tag"></i>
                                                <span><?php echo htmlspecialchars($asset['property_no']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($asset['inventory_tag'])): ?>
                                            <div class="detail-item">
                                                <i class="bi bi-upc-scan"></i>
                                                <span><?php echo htmlspecialchars($asset['inventory_tag']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($asset['asset_description'])): ?>
                                            <div class="detail-item">
                                                <i class="bi bi-archive"></i>
                                                <span><?php echo htmlspecialchars($asset['asset_description']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($asset['office_name'])): ?>
                                            <div class="detail-item">
                                                <i class="bi bi-building"></i>
                                                <span><?php echo htmlspecialchars($asset['office_name']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="status-badges">
                                        <div class="status-badge status-<?php echo $asset['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $asset['status'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Employee Results -->
            <?php if (!empty($employee_results)): ?>
                <div class="results-section">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="bi bi-people"></i>
                            Employees
                        </div>
                        <div class="result-count">
                            <?php echo count($employee_results); ?> results
                        </div>
                    </div>
                    
                    <?php foreach ($employee_results as $employee): ?>
                        <div class="search-item" onclick="window.location.href='view_employee.php?id=<?php echo $employee['id']; ?>'">
                            <div class="search-item-header">
                                <span class="type-badge badge-employee">Employee</span>
                                <div class="item-title"><?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?></div>
                            </div>
                            
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="item-details">
                                        <div class="detail-item">
                                            <i class="bi bi-card-text"></i>
                                            <span><?php echo htmlspecialchars($employee['employee_no']); ?></span>
                                        </div>
                                        <?php if (!empty($employee['position'])): ?>
                                            <div class="detail-item">
                                                <i class="bi bi-briefcase"></i>
                                                <span><?php echo htmlspecialchars($employee['position']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($employee['office_name'])): ?>
                                            <div class="detail-item">
                                                <i class="bi bi-geo-alt"></i>
                                                <span><?php echo htmlspecialchars($employee['office_name']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="status-badges">
                                        <div class="status-badge employment-<?php echo $employee['employment_status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $employee['employment_status'])); ?>
                                        </div>
                                        <div class="status-badge clearance-<?php echo $employee['clearance_status']; ?>">
                                            <?php echo ucfirst($employee['clearance_status']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- No Results -->
            <?php if (empty($asset_results) && empty($employee_results)): ?>
                <div class="results-section">
                    <div class="no-results-container">
                        <div class="no-results-icon">
                            <i class="bi bi-search"></i>
                        </div>
                        <div class="no-results-title">No results found</div>
                        <div class="no-results-text">
                            No asset items or employees found matching "<strong><?php echo htmlspecialchars($query); ?></strong>"
                        </div>
                        <div class="search-tips">
                            <h6><i class="bi bi-lightbulb"></i> Search Tips:</h6>
                            <ul class="text-start small mb-0">
                                <li>Try searching with different keywords</li>
                                <li>Check the spelling of your search terms</li>
                                <li>Use partial words (e.g., "comp" for "computer")</li>
                                <li>Search by property numbers, employee IDs, or names</li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div> <!-- Close main wrapper -->
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        // Auto-redirect if only one result after a delay
        setTimeout(() => {
            const assetResults = <?php echo count($asset_results); ?>;
            const employeeResults = <?php echo count($employee_results); ?>;
            
            if (assetResults === 1 && employeeResults === 0) {
                // Already handled in PHP
            } else if (employeeResults === 1 && assetResults === 0) {
                // Already handled in PHP
            }
        }, 100);
    </script>
</body>
</html>
