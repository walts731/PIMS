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

logSystemAction($_SESSION['user_id'], 'access', 'main_user_offices', 'Main user accessed offices page');

$offices = [];
$borrowed_items = [];
$error = null;

// Filter parameters
$office_filter = isset($_GET['office_id']) ? (int)$_GET['office_id'] : 0;
$status_filter = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
$category_filter = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

$allowed_statuses = ['serviceable', 'unserviceable', 'red_tagged', 'borrowed', 'no_tag'];
if ($status_filter !== '' && !in_array($status_filter, $allowed_statuses, true)) {
    $status_filter = '';
}

$categories = [];
if (!$conn || $conn->connect_error) {
    $error = 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error');
} else {
    try {
        // Get all categories
        $category_query = "SELECT id, category_name FROM asset_categories ORDER BY category_name ASC";
        $category_result = $conn->query($category_query);
        
        if ($category_result) {
            while ($category = $category_result->fetch_assoc()) {
                $categories[] = $category;
            }
        }
        
        // Get all offices with asset counts and filters
        $office_query = "SELECT 
                            o.id,
                            o.office_name,
                            COUNT(ai.id) as total_assets,
                            COALESCE(SUM(ai.value), 0) as total_value,
                            COUNT(CASE WHEN ai.status = 'serviceable' THEN 1 END) as serviceable_count,
                            COUNT(CASE WHEN ai.status = 'unserviceable' THEN 1 END) as unserviceable_count,
                            COUNT(CASE WHEN ai.status = 'red_tagged' THEN 1 END) as red_tagged_count,
                            COUNT(CASE WHEN ai.status = 'borrowed' THEN 1 END) as borrowed_count,
                            COUNT(CASE WHEN ai.status = 'no_tag' THEN 1 END) as no_tag_count
                        FROM offices o
                        LEFT JOIN asset_items ai ON o.id = ai.office_id";
        
        // Apply filters
        $where_conditions = [];
        $params = [];
        $types = "";
        
        if ($status_filter !== '') {
            $where_conditions[] = "ai.status = ?";
            $params[] = $status_filter;
            $types .= "s";
        }
        
        if ($category_filter > 0) {
            $where_conditions[] = "EXISTS (
                SELECT 1 FROM asset_items ai2 
                LEFT JOIN assets a2 ON ai2.asset_id = a2.id 
                WHERE ai2.office_id = o.id AND a2.asset_categories_id = ?
            )";
            $params[] = $category_filter;
            $types .= "i";
        }
        
        if (!empty($where_conditions)) {
            $office_query .= " WHERE " . implode(" AND ", $where_conditions);
        }
        
        $office_query .= " GROUP BY o.id, o.office_name ORDER BY o.office_name ASC";
        
        $office_stmt = $conn->prepare($office_query);
        if (!empty($params)) {
            $office_stmt->bind_param($types, ...$params);
        }
        $office_stmt->execute();
        $result = $office_stmt->get_result();
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $offices[] = $row;
            }
        }
        
        // Get all borrowed items with office information and filters
        $borrowed_query = "SELECT 
                              ai.id as item_id,
                              ai.property_no,
                              ai.description as item_description,
                              ai.status as item_status,
                              ai.value as item_value,
                              a.description as asset_description,
                              ac.category_name,
                              ac.id as category_id,
                              o.office_name,
                              o.id as office_id
                          FROM asset_items ai
                          LEFT JOIN assets a ON ai.asset_id = a.id
                          LEFT JOIN asset_categories ac ON ac.id = a.asset_categories_id
                          LEFT JOIN offices o ON o.id = ai.office_id
                          WHERE ai.status = 'borrowed'";
        
        // Apply filters to borrowed items
        $borrowed_params = [];
        $borrowed_types = "";
        
        if ($office_filter > 0) {
            $borrowed_query .= " AND o.id = ?";
            $borrowed_params[] = $office_filter;
            $borrowed_types .= "i";
        }
        
        if ($category_filter > 0) {
            $borrowed_query .= " AND ac.id = ?";
            $borrowed_params[] = $category_filter;
            $borrowed_types .= "i";
        }
        
        $borrowed_query .= " ORDER BY ai.id DESC";
        
        $borrowed_stmt = $conn->prepare($borrowed_query);
        if (!empty($borrowed_params)) {
            $borrowed_stmt->bind_param($borrowed_types, ...$borrowed_params);
        }
        $borrowed_stmt->execute();
        $borrowed_result = $borrowed_stmt->get_result();
        
        if ($borrowed_result) {
            while ($row = $borrowed_result->fetch_assoc()) {
                $borrowed_items[] = $row;
            }
        }
        
    } catch (Exception $e) {
        $error = 'Error loading offices: ' . $e->getMessage();
        error_log('Main User Offices Error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offices - Main User | PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="../ADMIN/dashboard.css" rel="stylesheet">
    <style>
    .office-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .office-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    }
    
    .office-header {
        background: var(--primary-gradient);
        color: white;
        padding: 1.5rem;
        position: relative;
    }
    
    .office-header h4 {
        margin: 0;
        font-weight: 700;
        font-size: 1.25rem;
    }
    
    .office-stats {
        display: flex;
        gap: 2rem;
        margin-top: 1rem;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        display: block;
    }
    
    .stat-label {
        font-size: 0.75rem;
        opacity: 0.9;
        text-transform: uppercase;
    }
    
    .office-details {
        padding: 1.5rem;
    }
    
    .status-badges {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-top: 1rem;
    }
    
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
        background: #fff3cd;
        color: #856404;
    }
    
    .status-borrowed {
        background: #cce5ff;
        color: #004085;
    }
    
    .status-no-tag {
        background: #e2e3e5;
        color: #383d41;
    }
    
    .office-actions {
        padding: 1rem 1.5rem;
        background: #f8f9fa;
        border-top: 1px solid #dee2e6;
    }
    
    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border-radius: 6px;
    }
    
    /* Comprehensive Mobile UI Fixes */
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
        
        .office-stats {
            gap: 1rem;
        }
    }
    
    @media (max-width: 768px) {
        .dashboard-header h1 {
            font-size: 1.5rem;
        }
        
        .dashboard-header p {
            font-size: 0.9rem;
        }
        
        .office-card {
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .office-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }
        
        .office-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .office-header h4 {
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
        }
        
        .office-stats {
            gap: 1rem;
            margin-top: 0.75rem;
            position: relative;
            z-index: 1;
        }
        
        .stat-value {
            font-size: 1.25rem;
        }
        
        .stat-label {
            font-size: 0.7rem;
        }
        
        .office-actions {
            padding: 0.75rem 1rem;
        }
        
        .office-actions .d-flex {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .btn-sm {
            font-size: 0.8rem;
            padding: 0.5rem 1rem;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .status-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .status-badge:hover::before {
            left: 100%;
        }
        
        .office-actions {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            flex-direction: column;
            gap: 0.75rem;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        
        .office-actions .btn {
            width: 100%;
            text-align: center;
            padding: 0.75rem;
            border-radius: 15px;
            font-weight: 600;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .office-actions .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
        }
        
        .dashboard-header .row {
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .dashboard-header .col-md-8,
        .dashboard-header .col-md-4 {
            text-align: left !important;
        }
        
        .dashboard-header h1 {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            margin-bottom: 0.5rem;
        }
        
        .dashboard-header p {
            font-size: 1rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 0;
        }
        
        .table-responsive {
            font-size: 0.9rem;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
        }
        
        .table {
            background: white;
            margin: 0;
        }
        
        .table thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem 0.5rem;
            border: none;
            font-size: 0.85rem;
        }
        
        .table tbody tr {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .table tbody tr:hover {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            transform: scale(1.02);
        }
        
        .table td {
            padding: 0.75rem 0.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            vertical-align: middle;
        }
        
        .card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);
            color: white;
            border: none;
            padding: 1.25rem;
            font-weight: 700;
        }
        
        .card-header h5 {
            font-size: 1.1rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .alert {
            border-radius: 15px;
            border: none;
            font-size: 0.9rem;
            padding: 1rem 1.25rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .alert-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);
            color: white;
        }
        
        .alert .badge {
            font-size: 0.75rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 700;
            margin: 0.25rem 0.125rem;
        }
        
        .d-flex.justify-content-md-end {
            justify-content: flex-start !important;
            flex-direction: column;
            gap: 1rem;
        }
        
        .d-inline-block {
            width: 100%;
            margin-bottom: 0.75rem;
        }
        
        .form-select-sm {
            font-size: 1rem;
            padding: 0.75rem;
            border-radius: 15px;
            border: 2px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.1);
            color: white;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }
        
        .btn-sm {
            font-size: 1rem;
            padding: 0.75rem 1.25rem;
            border-radius: 15px;
            font-weight: 700;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .main-content {
            padding: 1rem;
        }
    }
    
    @media (max-width: 576px) {
        .office-card {
            border-radius: 25px;
            margin-bottom: 1.25rem;
        }
        
        .office-header {
            padding: 1.25rem;
        }
        
        .office-header h4 {
            font-size: 1.2rem;
        }
        
        .stat-value {
            font-size: 1.6rem;
        }
        
        .stat-label {
            font-size: 0.7rem;
        }
        
        .office-details {
            padding: 1.25rem;
        }
        
        .status-badge {
            font-size: 0.7rem;
            padding: 0.35rem 0.7rem;
            border-radius: 20px;
        }
        
        .office-actions {
            padding: 0.875rem 1.25rem;
            gap: 0.625rem;
        }
        
        .dashboard-header {
            padding: 1.25rem;
            border-radius: 25px;
        }
        
        .dashboard-header h1 {
            font-size: 1.6rem;
        }
        
        .table-responsive {
            font-size: 0.85rem;
            border-radius: 25px;
        }
        
        .table thead th {
            padding: 0.875rem 0.4rem;
            font-size: 0.8rem;
        }
        
        .table td {
            padding: 0.625rem 0.375rem;
        }
        
        .card {
            border-radius: 25px;
        }
        
        .card-header {
            padding: 1rem;
        }
        
        .card-header h5 {
            font-size: 1rem;
        }
        
        .alert {
            border-radius: 20px;
            padding: 0.875rem 1rem;
            font-size: 0.85rem;
        }
        
        .form-select-sm {
            font-size: 0.95rem;
            padding: 0.625rem;
        }
        
        .btn-sm {
            font-size: 0.95rem;
            padding: 0.625rem 1rem;
        }
        
        .main-content {
            padding: 0.75rem;
        }
    }
    </style>
</head>
<body>
    <?php $page_title = 'Offices'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>

        <div class="main-content">
            <div class="dashboard-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-1" style="font-weight: 700; color: #191BA9;">
                            <i class="bi bi-building me-2"></i>Offices
                        </h1>
                        <p class="text-muted mb-0">Viewing office information and asset statistics with filters.</p>
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
                                <select class="form-select form-select-sm" id="statusFilter" <?php echo $status_filter !== '' ? 'style="background-color: #28a745; color: white; border-color: #1e7e34; font-weight: bold;"' : ''; ?>>
                                    <option value="" <?php echo $status_filter === '' ? 'selected' : ''; ?>>All Statuses</option>
                                    <option value="serviceable" <?php echo $status_filter === 'serviceable' ? 'selected' : ''; ?>>Serviceable</option>
                                    <option value="unserviceable" <?php echo $status_filter === 'unserviceable' ? 'selected' : ''; ?>>Unserviceable</option>
                                    <option value="red_tagged" <?php echo $status_filter === 'red_tagged' ? 'selected' : ''; ?>>Red-Tagged</option>
                                    <option value="borrowed" <?php echo $status_filter === 'borrowed' ? 'selected' : ''; ?>>Borrowed</option>
                                    <option value="no_tag" <?php echo $status_filter === 'no_tag' ? 'selected' : ''; ?>>No Tag</option>
                                </select>
                            </div>
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
                            <a class="btn btn-outline-primary btn-sm" href="offices.php">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Summary -->
            <?php if ($status_filter !== '' || $category_filter > 0): ?>
                <div class="alert alert-info mb-3" role="alert">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-funnel me-2"></i>
                            <strong>Active Filters:</strong>
                            <?php if ($status_filter !== ''): ?>
                                <span class="badge bg-success me-1">Status: <?php echo ucfirst(str_replace('_', ' ', $status_filter)); ?></span>
                            <?php endif; ?>
                            <?php if ($category_filter > 0): ?>
                                <span class="badge bg-info me-1">Category: <?php echo htmlspecialchars(array_column($categories, 'category_name', 'id')[$category_filter] ?? 'Unknown'); ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="offices.php" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$error && !empty($offices)): ?>
                <div class="row">
                    <?php foreach ($offices as $office): ?>
                        <div class="col-lg-6 col-xl-4">
                            <div class="office-card">
                                <div class="office-header">
                                    <h4><?php echo htmlspecialchars($office['office_name'] ?? ''); ?></h4>
                                    
                                    <div class="office-stats">
                                        <div class="stat-item">
                                            <span class="stat-value"><?php echo number_format((int)($office['total_assets'] ?? 0)); ?></span>
                                            <span class="stat-label">Total Assets</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="office-details">
                                    <div class="status-badges">
                                        <?php if (($office['serviceable_count'] ?? 0) > 0): ?>
                                            <span class="status-badge status-serviceable">
                                                Serviceable: <?php echo (int)$office['serviceable_count']; ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (($office['unserviceable_count'] ?? 0) > 0): ?>
                                            <span class="status-badge status-unserviceable">
                                                Unserviceable: <?php echo (int)$office['unserviceable_count']; ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (($office['red_tagged_count'] ?? 0) > 0): ?>
                                            <span class="status-badge status-red-tagged">
                                                Red-Tagged: <?php echo (int)$office['red_tagged_count']; ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (($office['borrowed_count'] ?? 0) > 0): ?>
                                            <span class="status-badge status-borrowed">
                                                Borrowed: <?php echo (int)$office['borrowed_count']; ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (($office['no_tag_count'] ?? 0) > 0): ?>
                                            <span class="status-badge status-no-tag">
                                                No Tag: <?php echo (int)$office['no_tag_count']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="office-actions">
                                    <a href="assets_per_office.php?office_id=<?php echo (int)$office['id']; ?>" class="btn btn-outline-primary btn-sm me-2">
                                        <i class="bi bi-box-seam"></i> View Assets
                                    </a>
                                    <a href="assets.php?office_id=<?php echo (int)$office['id']; ?>" class="btn btn-outline-info btn-sm me-2">
                                        <i class="bi bi-collection"></i> Asset Items
                                    </a>
                                    <a href="assets.php?office=<?php echo urlencode($office['office_name']); ?>&status=borrowed" class="btn btn-outline-warning btn-sm">
                                        <i class="bi bi-arrow-left-right"></i> Borrowed
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-building" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">No Offices Found</h4>
                    <p>No offices have been set up in the system yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusFilter = document.getElementById('statusFilter');
            const categoryFilter = document.getElementById('categoryFilter');

            function applyFilters() {
                const currentUrl = new URL(window.location.href);

                // Apply status filter
                const statusValue = statusFilter.value || '';
                if (statusValue) {
                    currentUrl.searchParams.set('status', statusValue);
                } else {
                    currentUrl.searchParams.delete('status');
                }

                // Apply category filter
                const categoryValue = parseInt(categoryFilter.value || '0', 10);
                if (categoryValue > 0) {
                    currentUrl.searchParams.set('category_id', String(categoryValue));
                } else {
                    currentUrl.searchParams.delete('category_id');
                }

                window.location.href = currentUrl.toString();
            }

            // Add event listeners
            if (statusFilter) {
                statusFilter.addEventListener('change', applyFilters);
            }
            if (categoryFilter) {
                categoryFilter.addEventListener('change', applyFilters);
            }

            // Add loading state to filter buttons
            const filterButtons = document.querySelectorAll('.form-select');
            filterButtons.forEach(button => {
                button.addEventListener('change', function() {
                    this.style.opacity = '0.7';
                    setTimeout(() => {
                        this.style.opacity = '1';
                    }, 300);
                });
            });

            // Return item function
            function returnItem(itemId) {
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
        });
    </script>
