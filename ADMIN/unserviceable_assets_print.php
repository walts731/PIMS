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

// Get filters from URL
$office_filter = isset($_GET['office']) ? intval($_GET['office']) : 0;
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Get system settings for header
$system_settings = [];
try {
    $result = $conn->query("SELECT * FROM system_settings ORDER BY id DESC LIMIT 1");
    if ($result) {
        $system_settings = $result->fetch_assoc();
    }
} catch (Exception $e) {
    error_log("Error fetching system settings: " . $e->getMessage());
}

// Build WHERE conditions
$where_conditions = [];
$params = [];
$types = '';

if ($office_filter > 0) {
    $where_conditions[] = "ai.office_id = ?";
    $params[] = $office_filter;
    $types .= 'i';
}

if ($category_filter > 0) {
    $where_conditions[] = "ai.asset_category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

// Always filter for unserviceable assets
$where_conditions[] = "ai.status = 'unserviceable'";

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get unserviceable assets
$unserviceable_assets = [];
$sql = "SELECT ai.*, ac.category_name, 
               ac.category_code, o.office_name, e.firstname, e.lastname, e.position
        FROM asset_items ai 
        LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id 
        LEFT JOIN offices o ON ai.office_id = o.id 
        LEFT JOIN employees e ON ai.employee_id = e.id 
        $where_clause 
        ORDER BY ai.last_updated DESC";

try {
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $unserviceable_assets[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching unserviceable assets: " . $e->getMessage());
}

// Get office and category names for filters display
$office_name = '';
$category_name = '';

if ($office_filter > 0) {
    $office_stmt = $conn->prepare("SELECT office_name FROM offices WHERE id = ?");
    $office_stmt->bind_param("i", $office_filter);
    $office_stmt->execute();
    $office_result = $office_stmt->get_result();
    if ($office_row = $office_result->fetch_assoc()) {
        $office_name = $office_row['office_name'];
    }
    $office_stmt->close();
}

if ($category_filter > 0) {
    $cat_stmt = $conn->prepare("SELECT category_name FROM asset_categories WHERE id = ?");
    $cat_stmt->bind_param("i", $category_filter);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    if ($cat_row = $cat_result->fetch_assoc()) {
        $category_name = $cat_row['category_name'];
    }
    $cat_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Preview - Unserviceable Assets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
        }
        
        @page {
            size: landscape;
            margin: 0.5in;
        }
        
        .print-header {
            text-align: left;
            margin-bottom: 30px;
            padding: 20px;
        }
        
        .print-header img {
            max-width: 200px;
            object-fit: contain;
        }
        
        .gov-header {
            text-align: center;
        }
        
        .gov-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .municipality {
            font-size: 12px;
            margin-bottom: 2px;
        }
        
        .province {
            font-size: 12px;
            margin-bottom: 10px;
        }
        
        .print-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .print-subtitle {
            font-size: 12px;
            color: #666;
        }
        
        .filters-info {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 12px;
        }
        
        .table-container {
            margin-bottom: 20px;
        }
        
        .print-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        
        .print-table th {
            background: #f8f9fa;
            padding: 8px;
            border: 1px solid #dee2e6;
            font-weight: bold;
            text-align: left;
        }
        
        .print-table td {
            padding: 6px;
            border: 1px solid #dee2e6;
            vertical-align: top;
        }
        
        .print-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .no-print {
            display: block;
            margin-bottom: 20px;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
            
            .print-header {
                padding: 10px;
            }
            
            .print-table {
                font-size: 9px;
            }
            
            .print-table th {
                padding: 5px;
            }
            
            .print-table td {
                padding: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer"></i> Print
        </button>
        <button onclick="window.close()" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> Close
        </button>
    </div>
    
    <div class="print-header">
        <div style="display: flex; align-items: flex-start; gap: 20px;">
            <!-- Logo on the left -->
            <div style="flex-shrink: 0;">
                <?php 
                if (!empty($system_settings['system_logo'])) {
                    echo '<img src="../' . htmlspecialchars($system_settings['system_logo']) . '" alt="' . htmlspecialchars($system_settings['system_name'] ?? 'PIMS') . '" style="max-width: 250px; max-height: 100px;">';
                } else {
                    echo '<img src="../img/system_logo.png" alt="' . htmlspecialchars($system_settings['system_name'] ?? 'PIMS') . '" style="max-width: 250px; max-height: 100px;">';
                }
                ?>
            </div>
            
            <!-- Government header on the right -->
            <div style="flex: 1;">
                <div class="gov-header" style="text-align: center; padding: 0;">
                    <div class="gov-title">Republic of the Philippines</div>
                    <div class="municipality">Municipality of Pilar</div>
                    <div class="province">Province of Sorsogon</div>
                    <div class="print-title"><?php echo htmlspecialchars($system_settings['system_name'] ?? 'PIMS'); ?> - Unserviceable Assets Report</div>
                    <div class="print-subtitle">Generated on <?php echo date('F j, Y g:i A'); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($where_conditions)): ?>
        <div class="filters-info">
            <strong>Filters Applied:</strong><br>
            <?php if ($office_filter > 0): ?>
                Office: <?php echo htmlspecialchars($office_name); ?><br>
            <?php endif; ?>
            
            <?php if ($category_filter > 0): ?>
                Category: <?php echo htmlspecialchars($category_name); ?><br>
            <?php endif; ?>
            
            Status: Unserviceable
        </div>
    <?php endif; ?>
    
    <div class="table-container">
        <table class="print-table">
            <thead>
                <tr>
                    <th width="20%">Description</th>
                    <th width="12%">Category</th>
                    <th width="10%">Status</th>
                    <th width="12%">Value</th>
                    <th width="13%">Office</th>
                    <th width="15%">Assigned To</th>
                    <th width="13%">Last Updated</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($unserviceable_assets)): ?>
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 20px;">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mb-0 mt-2">No unserviceable assets found</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($unserviceable_assets as $asset): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($asset['description']); ?></strong>
                                <?php if (!empty($asset['inventory_tag'])): ?>
                                    <br><small>Tag: <?php echo htmlspecialchars($asset['inventory_tag']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if (!empty($asset['category_name'])) {
                                    echo htmlspecialchars($asset['category_name']);
                                } else {
                                    echo 'No Category Assigned';
                                }
                                ?>
                            </td>
                            <td>
                                <span style="background: #dc3545; color: white; padding: 2px 6px; border-radius: 3px; font-size: 10px;">
                                    Unserviceable
                                </span>
                            </td>
                            <td>₱<?php echo number_format($asset['value'], 2); ?></td>
                            <td><?php echo htmlspecialchars($asset['office_name'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if (!empty($asset['firstname'])): ?>
                                    <?php echo htmlspecialchars($asset['firstname'] . ' ' . $asset['lastname']); ?>
                                <?php else: ?>
                                    Unassigned
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($asset['last_updated'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="print-footer">
        <p class="mb-0">Total Records: <?php echo count($unserviceable_assets); ?></p>
        <p class="mb-0">Generated by <?php echo htmlspecialchars($_SESSION['username']); ?> on <?php echo date('F j, Y g:i A'); ?></p>
    </div>
    
    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>
</body>
</html>
