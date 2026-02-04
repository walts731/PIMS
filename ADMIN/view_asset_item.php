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
                   veh.brand as vehicle_brand, veh.model as vehicle_model, veh.plate_number, veh.color, veh.engine_number, veh.chassis_number, veh.year_manufactured,
                   furn.material, furn.dimensions as furniture_dimensions, furn.color as furniture_color, furn.manufacturer as furniture_manufacturer,
                   mach.machine_type, mach.manufacturer as machinery_manufacturer, mach.model_number, mach.capacity as machinery_capacity, mach.power_requirements, mach.serial_number as machinery_serial_number,
                   oe.brand as office_brand, oe.model as office_model, oe.serial_number as office_serial_number,
                   sw.software_name, sw.version, sw.license_key, sw.license_expiry,
                   land.lot_area, land.address as land_address, land.tax_declaration_number,
                   e.employee_no, e.firstname, e.lastname, e.email,
                   ics.ics_no,
                   par.par_no
            FROM asset_items ai 
            LEFT JOIN assets a ON ai.asset_id = a.id 
            LEFT JOIN asset_categories ac ON a.asset_categories_id = ac.id 
            LEFT JOIN offices o ON ai.office_id = o.id 
            LEFT JOIN asset_computers comp ON ai.id = comp.asset_item_id
            LEFT JOIN asset_vehicles veh ON ai.id = veh.asset_item_id
            LEFT JOIN asset_furniture furn ON ai.id = furn.asset_item_id
            LEFT JOIN asset_machinery mach ON ai.id = mach.asset_item_id
            LEFT JOIN asset_office_equipment oe ON ai.id = oe.asset_item_id
            LEFT JOIN asset_software sw ON ai.id = sw.asset_item_id
            LEFT JOIN asset_land land ON ai.id = land.asset_item_id
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
        'serviceable' => ['Serviceable', 'status-serviceable'],
        'unserviceable' => ['Unserviceable', 'status-unserviceable'],
        'red_tagged' => ['Red Tagged', 'status-red-tagged'],
        'no_tag' => ['No Tag', 'status-no-tag']
    ];
    return $status_map[$status] ?? [$status, 'status-default'];
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
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .status-serviceable { 
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%); 
            color: white; 
            border: 1px solid #28a745;
        }
        
        .status-unserviceable { 
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); 
            color: white; 
            border: 1px solid #dc3545;
        }
        
        .status-red-tagged { 
            background: linear-gradient(135deg, #fd7e14 0%, #e55a00 100%); 
            color: white; 
            border: 1px solid #fd7e14;
        }
        
        .status-no-tag { 
            background: linear-gradient(135deg, #6c757d 0%, #545b62 100%); 
            color: white; 
            border: 1px solid #6c757d;
        }
        
        .status-default { 
            background: linear-gradient(135deg, #e9ecef 0%, #ced4da 100%); 
            color: #495057; 
            border: 1px solid #e9ecef;
        }
        
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
        
        .asset-image-container {
            position: relative;
            display: inline-block;
        }
        
        .asset-image-container img {
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius-md);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .asset-image-container img:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .no-image-placeholder {
            border: 2px dashed #dee2e6;
            border-radius: var(--border-radius-md);
            padding: 20px;
            background-color: #f8f9fa;
            display: inline-block;
        }
        
        .no-image-placeholder svg {
            opacity: 0.5;
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
                    <a href="print_inventory_tag.php?id=<?php echo $item_id; ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                        <i class="bi bi-printer"></i> Print
                    </a>
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
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">End User</div>
                                    <div class="detail-value">
                                        <?php if (!empty($item['end_user'])): ?>
                                            <?php echo htmlspecialchars($item['end_user']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not specified</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Computer Equipment Specific Fields -->
                    <?php if ($item['category_code'] === 'CE' || $item['category_code'] === 'ITS'): ?>
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
                    
                    <!-- Vehicles Specific Fields -->
                    <?php if ($item['category_code'] === 'VH'): ?>
                    <div class="detail-section">
                        <h5 class="mb-3"><i class="bi bi-truck"></i> Vehicle Specifications</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Brand</div>
                                    <div class="detail-value"><?php echo $item['vehicle_brand'] ? htmlspecialchars($item['vehicle_brand']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Model</div>
                                    <div class="detail-value"><?php echo $item['vehicle_model'] ? htmlspecialchars($item['vehicle_model']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Plate Number</div>
                                    <div class="detail-value"><?php echo $item['plate_number'] ? htmlspecialchars($item['plate_number']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Color</div>
                                    <div class="detail-value"><?php echo $item['color'] ? htmlspecialchars($item['color']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Engine Number</div>
                                    <div class="detail-value"><?php echo $item['engine_number'] ? htmlspecialchars($item['engine_number']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Year Manufactured</div>
                                    <div class="detail-value"><?php echo $item['year_manufactured'] ? htmlspecialchars($item['year_manufactured']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Furniture & Fixtures Specific Fields -->
                    <?php if ($item['category_code'] === 'FF'): ?>
                    <div class="detail-section">
                        <h5 class="mb-3"><i class="bi bi-lamp"></i> Furniture & Fixtures Specifications</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Material</div>
                                    <div class="detail-value"><?php echo $item['material'] ? htmlspecialchars($item['material']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Dimensions</div>
                                    <div class="detail-value"><?php echo $item['furniture_dimensions'] ? htmlspecialchars($item['furniture_dimensions']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Color</div>
                                    <div class="detail-value"><?php echo $item['furniture_color'] ? htmlspecialchars($item['furniture_color']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Manufacturer</div>
                                    <div class="detail-value"><?php echo $item['furniture_manufacturer'] ? htmlspecialchars($item['furniture_manufacturer']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Machinery & Equipment Specific Fields -->
                    <?php if ($item['category_code'] === 'ME'): ?>
                    <div class="detail-section">
                        <h5 class="mb-3"><i class="bi bi-gear"></i> Machinery & Equipment Specifications</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Machine Type</div>
                                    <div class="detail-value"><?php echo $item['machine_type'] ? htmlspecialchars($item['machine_type']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Manufacturer</div>
                                    <div class="detail-value"><?php echo $item['machinery_manufacturer'] ? htmlspecialchars($item['machinery_manufacturer']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Model Number</div>
                                    <div class="detail-value"><?php echo $item['model_number'] ? htmlspecialchars($item['model_number']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Capacity</div>
                                    <div class="detail-value"><?php echo $item['machinery_capacity'] ? htmlspecialchars($item['machinery_capacity']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Power Requirements</div>
                                    <div class="detail-value"><?php echo $item['power_requirements'] ? htmlspecialchars($item['power_requirements']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Serial Number</div>
                                    <div class="detail-value"><?php echo $item['machinery_serial_number'] ? htmlspecialchars($item['machinery_serial_number']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Office Equipment Specific Fields -->
                    <?php if ($item['category_code'] === 'OE'): ?>
                    <div class="detail-section">
                        <h5 class="mb-3"><i class="bi bi-printer"></i> Office Equipment Specifications</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Brand</div>
                                    <div class="detail-value"><?php echo $item['office_brand'] ? htmlspecialchars($item['office_brand']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Model</div>
                                    <div class="detail-value"><?php echo $item['office_model'] ? htmlspecialchars($item['office_model']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Serial Number</div>
                                    <div class="detail-value"><?php echo $item['office_serial_number'] ? htmlspecialchars($item['office_serial_number']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Software Specific Fields -->
                    <?php if ($item['category_code'] === 'SW'): ?>
                    <div class="detail-section">
                        <h5 class="mb-3"><i class="bi bi-window"></i> Software Specifications</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Software Name</div>
                                    <div class="detail-value"><?php echo $item['software_name'] ? htmlspecialchars($item['software_name']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Version</div>
                                    <div class="detail-value"><?php echo $item['version'] ? htmlspecialchars($item['version']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">License Key</div>
                                    <div class="detail-value"><?php echo $item['license_key'] ? htmlspecialchars($item['license_key']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">License Expiry</div>
                                    <div class="detail-value"><?php echo $item['license_expiry'] ? date('F j, Y', strtotime($item['license_expiry'])) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Land Specific Fields -->
                    <?php if ($item['category_code'] === 'LD'): ?>
                    <div class="detail-section">
                        <h5 class="mb-3"><i class="bi bi-map"></i> Land Specifications</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Lot Area (sqm)</div>
                                    <div class="detail-value"><?php echo $item['lot_area'] ? htmlspecialchars($item['lot_area']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Address</div>
                                    <div class="detail-value"><?php echo $item['land_address'] ? htmlspecialchars($item['land_address']) : '<span class="text-muted">Not specified</span>'; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Tax Declaration Number</div>
                                    <div class="detail-value"><?php echo $item['tax_declaration_number'] ? htmlspecialchars($item['tax_declaration_number']) : '<span class="text-muted">Not specified</span>'; ?></div>
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
                <!-- Asset Image -->
                <div class="detail-card text-center">
                    <h5 class="mb-3"><i class="bi bi-image"></i> Asset Image</h5>
                    <div class="asset-image-container mb-3">
                        <?php if (!empty($item['image'])): ?>
                            <img src="../uploads/asset_images/<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="Asset Image" 
                                 class="img-fluid rounded shadow-sm"
                                 style="max-height: 300px; width: auto; object-fit: cover;"
                                 onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgdmlld0JveD0iMCAwIDMwMCAzMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMDAiIGhlaWdodD0iMzAwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik0xMjUgMTIwSDE3NVYxNzVIMTI1VjEyMFoiIGZpbGw9IiNEMUQ1REIiLz4KPHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEwIDIwQzEwIDIyLjIwOTEgMTEuNzkwOSAyNCAxNCAyNEgyNkMyOC4yMDkxIDI0IDMwIDIyLjIwOTEgMzAgMjBWMzBIMTBWMjBaTTEwIDEwQzEwIDEyLjIwOTEgMTEuNzkwOSAxNCAxNCAxNEgyNkMyOC4yMDkxIDE0IDMwIDEyLjIwOTEgMzAgMTBWMTBIMTBaIiBmaWxsPSIjRDRERDREIi8+Cjwvc3ZnPgo8L3N2Zz4K';">
                            <div class="mt-2">
                                <small class="text-muted">Image uploaded</small>
                            </div>
                        <?php else: ?>
                            <div class="no-image-placeholder">
                                <svg width="150" height="150" viewBox="0 0 150 150" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="150" height="150" fill="#F5F5F5"/>
                                    <path d="M62.5 60H87.5V87.5H62.5V60Z" fill="#D1D5DB"/>
                                    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" x="55" y="55">
                                        <path d="M10 20C10 22.2091 11.7909 24 14 24H26C28.2091 24 30 22.2091 30 20V30H10V20ZM10 10C10 12.2091 11.7909 14 14 14H26C28.2091 14 30 12.2091 30 10V10H10V10Z" fill="#D4D4D4"/>
                                    </svg>
                                </svg>
                                <div class="mt-2">
                                    <small class="text-muted">No image available</small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- QR Code -->
                <div class="detail-card text-center">
                    <h5 class="mb-3"><i class="bi bi-qr-code"></i> QR Code</h5>
                    <div class="qr-code">
                        <?php if (!empty($item['qr_code'])): ?>
                            <img src="../uploads/qr_codes/<?php echo htmlspecialchars($item['qr_code']); ?>" 
                                 alt="QR Code" 
                                 class="img-fluid rounded"
                                 style="max-width: 150px; max-height: 150px;">
                        <?php else: ?>
                            <i class="bi bi-qr-code-scan fs-1 text-muted"></i>
                        <?php endif; ?>
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
            // Redirect to ITR form with asset item details for auto-filling
            const assetId = <?php echo $item['asset_id']; ?>;
            const itemId = <?php echo $item['id']; ?>;
            const description = '<?php echo addslashes($item['description']); ?>';
            const propertyNo = '<?php echo addslashes($item['property_no'] ?? ''); ?>';
            const value = <?php echo $item['value']; ?>;
            const unitCost = <?php echo $item['unit_cost']; ?>;
            
            const url = `itr_form.php?transfer_asset=1&asset_id=${assetId}&item_id=${itemId}&description=${encodeURIComponent(description)}&property_no=${encodeURIComponent(propertyNo)}&value=${value}&unit_cost=${unitCost}`;
            window.location.href = url;
        }
        
        function addToIirup() {
            // Prepare asset data for IIRUP form
            const assetData = {
                id: <?php echo $item_id; ?>,
                description: '<?php echo addslashes($item['description']); ?>',
                property_no: '<?php echo addslashes($item['property_no'] ?? ''); ?>',
                inventory_tag: '<?php echo addslashes($item['inventory_tag'] ?? ''); ?>',
                acquisition_date: '<?php echo $item['acquisition_date']; ?>',
                value: '<?php echo $item['value']; ?>',
                unit_cost: '<?php echo $item['unit_cost']; ?>',
                office_name: '<?php echo addslashes($item['office_name'] ?? ''); ?>',
                category_name: '<?php echo addslashes($item['category_name'] ?? ''); ?>',
                category_code: '<?php echo addslashes($item['category_code'] ?? ''); ?>',
                asset_description: '<?php echo addslashes($item['asset_description']); ?>',
                unit: '<?php echo addslashes($item['unit']); ?>'
            };
            
            // Create URL with asset data
            const params = new URLSearchParams();
            params.append('asset_id', assetData.id);
            params.append('description', assetData.description);
            params.append('property_no', assetData.property_no);
            params.append('inventory_tag', assetData.inventory_tag);
            params.append('acquisition_date', assetData.acquisition_date);
            params.append('value', assetData.value);
            params.append('unit_cost', assetData.unit_cost);
            params.append('office_name', assetData.office_name);
            params.append('category_name', assetData.category_name);
            params.append('category_code', assetData.category_code);
            params.append('asset_description', assetData.asset_description);
            params.append('unit', assetData.unit);
            params.append('auto_fill', 'true');
            
            // Open IIRUP form with asset data
            window.open('iirup_form.php?' + params.toString(), '_blank');
        }
    </script>
</body>
</html>
