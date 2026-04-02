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
    <title>Office Consumables - PIMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            height: 100vh;
        }
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            min-height: 100vh;
            margin: 0;
            padding: 2rem;
            border-radius: 0;
            animation: slideUp 0.8s ease-out;
            overflow-y: auto;
            height: 100vh;
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: none;
            animation: slideUp 0.6s ease-out 0.2s both;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        .header-section {
            background: linear-gradient(135deg, #6f42c1, #0d6efd);
            color: white;
            padding: 2rem;
            border-radius: 15px;
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
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            animation: slideUp 0.6s ease-out 0.4s both;
        }
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            animation: slideUp 0.6s ease-out 0.8s both;
        }
        .consumables-table {
            border-radius: 10px;
            overflow: hidden;
        }
        .consumables-table thead {
            background: linear-gradient(135deg, #6f42c1, #0d6efd);
            color: white;
        }
        .consumables-table tbody tr {
            animation: slideUp 0.4s ease-out;
            transition: all 0.3s ease;
        }
        .consumables-table tbody tr:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        .badge-consumables {
            background: linear-gradient(135deg, #6f42c1, #0d6efd);
            padding: 0.5rem 1rem;
            border-radius: 20px;
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
            background: linear-gradient(135deg, #6f42c1, #0d6efd);
            color: white;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #6f42c1, #0d6efd);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(111, 66, 193, 0.3);
        }
        .alert {
            animation: slideDown 0.5s ease-out;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
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
    </style>
</head>
<body>
    <?php include 'includes/topbar.php'; ?>
    
    <div class="main-container">
        <!-- Header Section -->
        <div class="header-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="bi bi-box-seam me-3"></i>
                        Office Consumables
                    </h1>
                    <p class="mb-0 opacity-75">Manage and track consumable items by office</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard.php" class="btn btn-light btn-lg">
                        <i class="bi bi-arrow-left me-2"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Summary -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="stats-card h-100">
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
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section mb-4">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <div class="text-muted">
                        <small>
                            <strong>Debug:</strong> Office Filter ID: <?php echo $office_filter; ?><br>
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
            </div>
        </div>

        <!-- Consumables Table -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-1">
                        <i class="bi bi-list-ul text-primary me-2"></i>
                        Consumable Items
                        <span class="badge badge-consumables ms-2">
                            <?php echo count($consumables); ?> 
                            Items
                        </span>
                    </h4>
                </div>
            </div>
            
            <?php if (!empty($consumables)): ?>
                <div class="table-responsive">
                    <table class="table table-hover consumables-table">
                        <thead>
                            <tr>
                                <th><i class="bi bi-box me-1"></i>Item Name</th>
                                <th><i class="bi bi-text-paragraph me-1"></i>Description</th>
                                <th><i class="bi bi-hash me-1"></i>Quantity</th>
                                <th><i class="bi bi-rulers me-1"></i>Unit</th>
                                <th><i class="bi bi-currency-dollar me-1"></i>Unit Cost</th>
                                <th><i class="bi bi-exclamation-triangle me-1"></i>Reorder Level</th>
                                <th><i class="bi bi-truck me-1"></i>Supplier</th>
                                <th><i class="bi bi-building me-1"></i>Office</th>
                            </tr>
                        </thead>
                        <tbody>
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
                                    <td>₱<?php echo number_format($item['unit_cost'] ?? 0, 2); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo number_format($item['reorder_level'] ?? 0); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['supplier'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($item['office_name'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
