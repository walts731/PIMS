<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['system_admin', 'admin', 'main_user'], true)) {
    header('Location: ../index.php');
    exit();
}

logSystemAction($_SESSION['user_id'], 'access', 'main_user_consumables', 'Main user accessed consumables page');

$consumables = [];
$offices = [];
$error = null;
$office_filter = isset($_GET['office_id']) ? (int)$_GET['office_id'] : 0;

try {
    // Get all offices
    $office_query = "SELECT id, office_name FROM offices ORDER BY office_name";
    $office_result = $conn->query($office_query);
    if ($office_result) {
        while ($row = $office_result->fetch_assoc()) {
            $offices[] = $row;
        }
    }
    
    // Check if consumables table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'consumables'");
    if ($table_check && $table_check->num_rows > 0) {
        // Get consumables for selected office
        $consumable_sql = "SELECT 
                            c.id,
                            c.description as item_name,
                            c.description,
                            c.quantity,
                            c.units,
                            c.unit_cost,
                            c.reorder_level,
                            c.unit,
                            c.office_id,
                            c.created_at as date_acquired,
                            c.updated_at as last_updated,
                            c.for_office_id,
                            c.supplier,
                            o.office_name
                         FROM consumables c
                         LEFT JOIN offices o ON c.office_id = o.id";
        
        $params = [];
        $types = "";
        
        if ($office_filter > 0) {
            $consumable_sql .= " WHERE c.office_id = ?";
            $params[] = $office_filter;
            $types .= "i";
        }
        
        $consumable_sql .= " ORDER BY c.description ASC";
        
        $stmt = $conn->prepare($consumable_sql);
        if ($stmt) {
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $consumables[] = $row;
            }
            $stmt->close();
        } else {
            $error = 'Error preparing query: ' . $conn->error;
        }
    } else {
        $error = 'Consumables table not found. Please contact administrator.';
    }
    
} catch (Exception $e) {
    $error = 'An error occurred: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office Consumables - Main User | PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="../ADMIN/dashboard.css" rel="stylesheet">
    <style>
    .status-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        white-space: nowrap;
    }
    
    .status-available {
        background: #d1ecf1;
        color: #0c5460;
    }
        
        .status-low {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-out {
            background: #f8d7da;
            color: #721c24;
        }
        
        .consumables-table {
            margin: 0;
        }
        
        .consumables-table thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem 0.5rem;
            border: none;
            font-size: 0.85rem;
        }
        
        .consumables-table tbody tr {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .consumables-table tbody tr:hover {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            transform: translateX(5px);
        }
        
        .consumables-table td {
            padding: 0.75rem 0.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            vertical-align: middle;
        }
        
        .section-card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            overflow: hidden;
            margin-bottom: 2rem;
            animation: slideDown 0.8s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .table-responsive {
            border-radius: 20px;
            overflow: hidden;
        }
        
        .consumable-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            animation: bounceIn 0.8s ease-out 0.5s both;
        }
        
        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .items-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .badge-consumables {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            color: white;
        }
        
        /* Mobile UI Fixes */
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
        }
        
        @media (max-width: 768px) {
            .dashboard-header h1 {
                font-size: 1.5rem;
            }
            
            .dashboard-header p {
                font-size: 0.9rem;
            }
            
            .dashboard-header .d-flex {
                flex-direction: column;
                align-items: stretch !important;
                gap: 0.5rem;
            }
            
            .dashboard-header .d-inline-block {
                width: 100% !important;
            }
        }
    </style>
</head>
<body>
    <?php $page_title = 'Office Consumables'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>

        <div class="main-content">
            <div class="dashboard-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-1" style="font-weight: 700; color: #191BA9;">
                            <i class="bi bi-box-seam me-2"></i>Office Consumables
                        </h1>
                        <p class="text-muted mb-0">Manage and track consumable items by office</p>
                        <?php if ($error): ?>
                            <div class="alert alert-warning mt-2 mb-0 py-2" role="alert">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <small><?php echo htmlspecialchars($error); ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="offices.php" class="btn btn-secondary">
                            Back
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statistics Summary -->
            <div class="section-card">
                <div class="d-flex align-items-center">
                    <div class="consumable-icon items-icon me-3">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-2">Total Consumable Items</h6>
                        <h3 class="mb-0 text-info">
                            <?php echo count($consumables); ?>
                            <small>Items</small>
                        </h3>
                        <?php if ($office_filter > 0): ?>
                            <?php 
                            $selected_office_name = '';
                            foreach ($offices as $office) {
                                if ($office['id'] == $office_filter) {
                                    $selected_office_name = $office['office_name'];
                                    break;
                                }
                            }
                            ?>
                            <small class="text-muted">
                                <i class="bi bi-building me-1"></i>
                                <?php echo htmlspecialchars($selected_office_name); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="section-card">
                <div class="text-muted">
                    <small>
                        <?php if ($office_filter > 0): ?>
                            <i class="bi bi-funnel me-1"></i>
                            Showing consumables for 
                            <?php 
                            foreach ($offices as $office) {
                                if ($office['id'] == $office_filter) {
                                    echo '<span class="badge bg-primary">' . htmlspecialchars($office['office_name']) . '</span>';
                                    break;
                                }
                            }
                            ?>
                        <?php else: ?>
                            <i class="bi bi-info-circle me-1"></i>
                            Showing all consumables.
                        <?php endif; ?>
                    </small>
                </div>
            </div>

            <!-- Consumables Table -->
            <div class="section-card">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0 consumables-table">
                        <thead>
                            <tr>
                                <th><i class="bi bi-box me-1"></i>Item Name</th>
                                <th><i class="bi bi-text-paragraph me-1"></i>Description</th>
                                <th><i class="bi bi-hash me-1"></i>Quantity</th>
                                <th><i class="bi bi-rulers me-1"></i>Unit</th>
                                <th><i class="bi bi-building me-1"></i>Office</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($consumables)): ?>
                                <?php foreach ($consumables as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['item_name'] ?? 'N/A'); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                $qty = (int)($item['quantity'] ?? 0);
                                                $reorder = (int)($item['reorder_level'] ?? 0);
                                                if ($qty == 0) echo 'bg-danger text-white';
                                                elseif ($qty <= $reorder) echo 'bg-warning text-dark';
                                                else echo 'bg-success text-white';
                                            ?>">
                                                <?php echo number_format($item['quantity'] ?? 0); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['unit'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($item['office_name'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-box-seam text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No Consumable Items Found</h5>
                    <p class="text-muted">
                        <?php if ($office_filter > 0): ?>
                            No consumable items found for the selected office.
                        <?php else: ?>
                            No consumable items found in the database.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
</body>
</html>
