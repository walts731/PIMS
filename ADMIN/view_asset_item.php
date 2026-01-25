<?php
session_start();
require_once '../config.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

// Get asset item ID from URL
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($item_id === 0) {
    $_SESSION['error'] = 'Invalid asset item ID';
    header('Location: asset_items.php');
    exit();
}

// Get asset item details with related information
$item = null;
$item_sql = "SELECT ai.*, 
                   a.description as asset_description, a.unit, a.quantity as asset_quantity, a.unit_cost,
                   ac.category_name, ac.category_code,
                   o.office_name,
                   comp.processor, comp.ram_capacity, comp.storage_type, comp.storage_capacity, 
                   comp.operating_system, comp.serial_number as computer_serial_number,
                   e.employee_no, e.firstname, e.lastname, e.email,
                   ics.ics_no,
                   par.par_no
            FROM asset_items ai 
            LEFT JOIN assets a ON ai.asset_id = a.id 
            LEFT JOIN asset_categories ac ON a.asset_categories_id = ac.id 
            LEFT JOIN offices o ON ai.office_id = o.id 
            LEFT JOIN asset_computers comp ON ai.id = comp.asset_item_id
            LEFT JOIN employees e ON ai.employee_id = e.id 
            LEFT JOIN ics_forms ics ON ai.ics_id = ics.id 
            LEFT JOIN par_forms par ON ai.par_id = par.id 
            WHERE ai.id = ?";
$item_stmt = $conn->prepare($item_sql);
$item_stmt->bind_param("i", $item_id);
$item_stmt->execute();
$item_result = $item_stmt->get_result();
if ($item_row = $item_result->fetch_assoc()) {
    $item = $item_row;
}
$item_stmt->close();

if (!$item) {
    $_SESSION['error'] = 'Asset item not found';
    header('Location: asset_items.php');
    exit();
}

// Get asset ID for navigation
$asset_id = $item['asset_id'];

// Get other items of the same asset for navigation
$other_items = [];
$other_items_sql = "SELECT id, description, status, property_no FROM asset_items WHERE asset_id = ? AND id != ? ORDER BY id";
$other_items_stmt = $conn->prepare($other_items_sql);
$other_items_stmt->bind_param("ii", $asset_id, $item_id);
$other_items_stmt->execute();
$other_items_result = $other_items_stmt->get_result();
while ($other_row = $other_items_result->fetch_assoc()) {
    $other_items[] = $other_row;
}
$other_items_stmt->close();

// Get item history/audit trail if available
$item_history = [];
$history_sql = "SELECT * FROM asset_item_history WHERE item_id = ? ORDER BY created_at DESC LIMIT 10";
$history_stmt = $conn->prepare($history_sql);
$history_stmt->bind_param("i", $item_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();
while ($history_row = $history_result->fetch_assoc()) {
    $item_history[] = $history_row;
}
$history_stmt->close();

// Format status for display
function formatStatus($status) {
    $status_map = [
        'available' => ['Serviceable', 'status-serviceable'],
        'in_use' => ['Unserviceable', 'status-unserviceable'],
        'maintenance' => ['Red-Tagged', 'status-red-tagged'],
        'disposed' => ['Disposed', 'status-borrowed']
    ];
    return $status_map[$status] ?? [$status, ''];
}

// Get item status display
$status_display = formatStatus($item['status']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Item Details - <?php echo htmlspecialchars($item['description']); ?> | PIMS</title>
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
        
        .detail-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .detail-section {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .detail-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }
        
        .detail-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            font-size: 1.1rem;
            font-weight: 500;
            color: #212529;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-xl);
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-serviceable { background-color: #d4edda; color: #155724; }
        .status-unserviceable { background-color: #cce5ff; color: #004085; }
        .status-red-tagged { background-color: #f8d7da; color: #721c24; }
        .status-borrowed { background-color: #fff3cd; color: #856404; }
        
        .text-value {
            font-weight: 700;
            color: #191BA9;
            font-size: 1.2rem;
        }
        
        .btn-back {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-lg);
            transition: var(--transition);
        }
        
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(25, 27, 169, 0.3);
            color: white;
        }
        
        .history-item {
            border-left: 3px solid #191BA9;
            padding-left: 1rem;
            margin-bottom: 1rem;
        }
        
        .history-date {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .related-item {
            border: 1px solid #e9ecef;
            border-radius: var(--border-radius-md);
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            transition: var(--transition);
        }
        
        .related-item:hover {
            border-color: #191BA9;
            background-color: rgba(25, 27, 169, 0.05);
        }
        
        .qr-code {
            width: 150px;
            height: 150px;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Asset Item Details - ' . htmlspecialchars($item['description']);
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
                        <i class="bi bi-box"></i> Asset Item Details
                    </h1>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($item['description']); ?></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="asset_items.php?asset_id=<?php echo $asset_id; ?>" class="btn btn-back me-2">
                        <i class="bi bi-arrow-left"></i> Back to Items
                    </a>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Main Details Column -->
            <div class="col-lg-8">
                <!-- Item Information -->
                <div class="detail-card">
                    <div class="detail-section">
                        <h5 class="mb-3"><i class="bi bi-info-circle"></i> Item Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Property No</div>
                                    <div class="detail-value"><?php echo $item['property_no'] ? htmlspecialchars($item['property_no']) : '<span class="text-muted">Not assigned</span>'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Inventory Tag</div>
                                    <div class="detail-value"><?php echo $item['inventory_tag'] ? htmlspecialchars($item['inventory_tag']) : '<span class="text-muted">Not assigned</span>'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">ICS No/PAR No</div>
                                    <div class="detail-value">
                                        <?php 
                                        $reference = '';
                                        if ($item['ics_no']) {
                                            $reference = 'ICS No: ' . htmlspecialchars($item['ics_no']);
                                        }
                                        if ($item['par_no']) {
                                            $reference = $reference ? $reference . ' / PAR No: ' . htmlspecialchars($item['par_no']) : 'PAR No: ' . htmlspecialchars($item['par_no']);
                                        }
                                        echo $reference ? $reference : '<span class="text-muted">Not assigned</span>';
                                        ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Description</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($item['description']); ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Status</div>
                                    <div class="detail-value">
                                        <span class="status-badge <?php echo $status_display[1]; ?>">
                                            <?php echo $status_display[0]; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Value</div>
                                    <div class="detail-value text-value">₱<?php echo number_format($item['value'], 2); ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Acquisition Date</div>
                                    <div class="detail-value"><?php echo date('F j, Y', strtotime($item['acquisition_date'])); ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Last Updated</div>
                                    <div class="detail-value"><?php echo date('F j, Y g:i A', strtotime($item['last_updated'])); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h5 class="mb-3"><i class="bi bi-archive"></i> Asset Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Asset Description</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($item['asset_description']); ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Category</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($item['category_code'] . ' - ' . $item['category_name']); ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Unit</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($item['unit']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Unit Cost</div>
                                    <div class="detail-value">₱<?php echo number_format($item['unit_cost'], 2); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h5 class="mb-3"><i class="bi bi-geo-alt"></i> Location & Assignment</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Office</div>
                                    <div class="detail-value"><?php echo $item['office_name'] ? htmlspecialchars($item['office_name']) : '<span class="text-muted">Not assigned</span>'; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Assigned Employee</div>
                                    <div class="detail-value">
                                        <?php if ($item['employee_no']): ?>
                                            <?php echo htmlspecialchars($item['employee_no'] . ' - ' . $item['firstname'] . ' ' . $item['lastname']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Computer Equipment Specific Fields -->
                    <?php if ($item['category_code'] === 'CE' && ($item['processor'] || $item['ram_capacity'] || $item['storage_capacity'] || $item['operating_system'] || $item['computer_serial_number'])): ?>
                    <div class="detail-section">
                        <h5 class="mb-3"><i class="bi bi-cpu"></i> Computer Equipment Specifications</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Processor</div>
                                    <div class="detail-value"><?php echo $item['processor'] ? htmlspecialchars($item['processor']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">RAM (GB)</div>
                                    <div class="detail-value"><?php echo $item['ram_capacity'] ? htmlspecialchars($item['ram_capacity']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Storage</div>
                                    <div class="detail-value"><?php echo $item['storage_capacity'] ? htmlspecialchars($item['storage_capacity']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Operating System</div>
                                    <div class="detail-value"><?php echo $item['operating_system'] ? htmlspecialchars($item['operating_system']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Serial Number</div>
                                    <div class="detail-value"><?php echo $item['computer_serial_number'] ? htmlspecialchars($item['computer_serial_number']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Storage Type</div>
                                    <div class="detail-value"><?php echo $item['storage_type'] ? htmlspecialchars(ucfirst($item['storage_type'])) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- History -->
                <?php if (!empty($item_history)): ?>
                <div class="detail-card">
                    <h5 class="mb-3"><i class="bi bi-clock-history"></i> Item History</h5>
                    <?php foreach ($item_history as $history): ?>
                        <div class="history-item">
                            <div class="history-date"><?php echo date('F j, Y g:i A', strtotime($history['created_at'])); ?></div>
                            <div class="mt-1">
                                <strong><?php echo htmlspecialchars($history['action']); ?></strong>
                                <?php if ($history['details']): ?>
                                    <p class="mb-0 mt-1"><?php echo htmlspecialchars($history['details']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar Column -->
            <div class="col-lg-4">
                <!-- QR Code -->
                <div class="detail-card text-center">
                    <h5 class="mb-3"><i class="bi bi-qr-code"></i> QR Code</h5>
                    <div class="qr-code">
                        <i class="bi bi-qr-code-scan fs-1 text-muted"></i>
                    </div>
                    <p class="mt-2 mb-0 text-muted">Property No: <?php echo $item['property_no'] ? htmlspecialchars($item['property_no']) : 'Not assigned'; ?></p>
                </div>
                
                <!-- Actions -->
                <div class="detail-card">
                    <h5 class="mb-3"><i class="bi bi-gear"></i> Actions</h5>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-success" onclick="transferItem()">
                            <i class="bi bi-arrow-left-right"></i> Transfer Item
                        </button>
                        <button class="btn btn-outline-info" onclick="addToIirup()">
                            <i class="bi bi-file-earmark-text"></i> Add to IIRUP
                        </button>
                    </div>
                </div>
                
                <!-- Related Items -->
                <?php if (!empty($other_items)): ?>
                <div class="detail-card">
                    <h5 class="mb-3"><i class="bi bi-link"></i> Other Items in Asset</h5>
                    <?php foreach ($other_items as $other_item): ?>
                        <?php $other_status = formatStatus($other_item['status']); ?>
                        <a href="view_asset_item.php?id=<?php echo $other_item['id']; ?>" class="related-item text-decoration-none">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-medium"><?php echo htmlspecialchars($other_item['description']); ?></div>
                                    <small class="text-muted">Property No: <?php echo $other_item['property_no'] ? htmlspecialchars($other_item['property_no']) : 'Not assigned'; ?></small>
                                </div>
                                <span class="status-badge <?php echo $other_status[1]; ?> small">
                                    <?php echo $other_status[0]; ?>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
    </div> <!-- Close main wrapper -->
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        // Action functions
        function transferItem() {
            // Placeholder for transfer functionality
            alert('Transfer functionality will be implemented');
        }
        
        function addToIirup() {
            // Placeholder for IIRUP functionality
            alert('Add to IIRUP functionality will be implemented');
        }
    </script>
</body>
</html>
