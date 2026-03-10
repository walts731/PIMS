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
        
        .results-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            transition: var(--transition);
        }
        
        .results-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .search-result-item {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 0.75rem;
            border-left: 4px solid var(--primary-color);
            background: rgba(25, 27, 169, 0.05);
            transition: var(--transition);
            cursor: pointer;
        }
        
        .search-result-item:hover {
            background: rgba(25, 27, 169, 0.1);
            transform: translateX(3px);
        }
        
        .result-type-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-weight: 600;
        }
        
        .asset-badge {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
        }
        
        .employee-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .status-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.6rem;
            border-radius: var(--border-radius);
            font-weight: 500;
        }
        
        .status-available {
            background: #d4edda;
            color: #155724;
        }
        
        .status-in_use {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-maintenance {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-disposed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .employment-permanent {
            background: #d4edda;
            color: #155724;
        }
        
        .employment-contractual {
            background: #cce5ff;
            color: #004085;
        }
        
        .employment-job_order {
            background: #fff3cd;
            color: #856404;
        }
        
        .employment-resigned {
            background: #f8d7da;
            color: #721c24;
        }
        
        .employment-retired {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .clearance-cleared {
            background: #d4edda;
            color: #155724;
        }
        
        .clearance-uncleared {
            background: #f8d7da;
            color: #721c24;
        }
        
        .no-results {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .highlight {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            padding: 0.1rem 0.3rem;
            border-radius: 3px;
            font-weight: 500;
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
                        Search query: <span class="highlight"><?php echo htmlspecialchars($query); ?></span>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-outline-primary btn-sm" onclick="history.back()">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Asset Items Results -->
        <?php if (!empty($asset_results)): ?>
            <div class="results-card">
                <h5 class="mb-3">
                    <i class="bi bi-box"></i> Asset Items
                    <span class="badge bg-primary ms-2"><?php echo count($asset_results); ?></span>
                </h5>
                
                <?php foreach ($asset_results as $asset): ?>
                    <div class="search-result-item" onclick="window.location.href='view_asset_item.php?id=<?php echo $asset['id']; ?>'">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="result-type-badge asset-badge me-2">Asset</span>
                                    <strong><?php echo htmlspecialchars($asset['description']); ?></strong>
                                </div>
                                <div class="text-muted small">
                                    <?php if (!empty($asset['property_no'])): ?>
                                        <i class="bi bi-tag"></i> <?php echo htmlspecialchars($asset['property_no']); ?>
                                    <?php endif; ?>
                                    <?php if (!empty($asset['inventory_tag'])): ?>
                                        <span class="ms-3"><i class="bi bi-upc-scan"></i> <?php echo htmlspecialchars($asset['inventory_tag']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($asset['asset_description'])): ?>
                                        <span class="ms-3"><i class="bi bi-archive"></i> <?php echo htmlspecialchars($asset['asset_description']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="status-badge status-<?php echo $asset['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $asset['status'])); ?>
                                </span>
                                <?php if (!empty($asset['office_name'])): ?>
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-building"></i> <?php echo htmlspecialchars($asset['office_name']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Employee Results -->
        <?php if (!empty($employee_results)): ?>
            <div class="results-card">
                <h5 class="mb-3">
                    <i class="bi bi-people"></i> Employees
                    <span class="badge bg-success ms-2"><?php echo count($employee_results); ?></span>
                </h5>
                
                <?php foreach ($employee_results as $employee): ?>
                    <div class="search-result-item" onclick="window.location.href='view_employee.php?id=<?php echo $employee['id']; ?>'">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="result-type-badge employee-badge me-2">Employee</span>
                                    <strong><?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?></strong>
                                </div>
                                <div class="text-muted small">
                                    <i class="bi bi-card-text"></i> <?php echo htmlspecialchars($employee['employee_no']); ?>
                                    <?php if (!empty($employee['position'])): ?>
                                        <span class="ms-3"><i class="bi bi-briefcase"></i> <?php echo htmlspecialchars($employee['position']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($employee['employment_status'])): ?>
                                        <span class="ms-3"><i class="bi bi-person-badge"></i> <?php echo htmlspecialchars($employee['employment_status']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($employee['office_name'])): ?>
                                        <span class="ms-3"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($employee['office_name']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="mb-1">
                                    <span class="status-badge employment-<?php echo $employee['employment_status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $employee['employment_status'])); ?>
                                    </span>
                                </div>
                                <div>
                                    <span class="status-badge clearance-<?php echo $employee['clearance_status']; ?>">
                                        <?php echo ucfirst($employee['clearance_status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- No Results -->
        <?php if (empty($asset_results) && empty($employee_results)): ?>
            <div class="results-card">
                <div class="no-results">
                    <i class="bi bi-search fs-1 text-muted"></i>
                    <h4 class="mt-3">No results found</h4>
                    <p class="text-muted">
                        No asset items or employees found matching "<strong><?php echo htmlspecialchars($query); ?></strong>"
                    </p>
                    <div class="mt-3">
                        <small class="text-muted">
                            Try searching with different keywords or check the spelling
                        </small>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
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
