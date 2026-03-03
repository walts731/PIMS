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

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($item_id <= 0) {
    header('Location: asset_items.php');
    exit();
}

logSystemAction($_SESSION['user_id'], 'access', 'main_user_view_asset_item', 'Main user viewed asset item ID: ' . $item_id);

$item = null;
$related_items = [];

if (!$conn || $conn->connect_error) {
    header('Location: asset_items.php');
    exit();
}

try {
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

    $stmt = $conn->prepare($item_sql);
    $stmt->bind_param('i', $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $item = $result->fetch_assoc();
    }
    $stmt->close();

    if (!$item) {
        header('Location: asset_items.php');
        exit();
    }

    $related_sql = "SELECT ai.id, ai.description, ai.status, ai.value, ai.property_no
                    FROM asset_items ai
                    WHERE ai.asset_id = ?
                    ORDER BY ai.id DESC
                    LIMIT 5";
    $stmt = $conn->prepare($related_sql);
    $stmt->bind_param('i', $item['asset_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $related_items[] = $row;
    }
    $stmt->close();

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

} catch (Exception $e) {
    error_log('Main User View Asset Item Error: ' . $e->getMessage());
    header('Location: asset_items.php');
    exit();
}

// Format status for display
function formatStatus($status) {
    $status_map = [
        'serviceable' => ['Serviceable', 'status-serviceable'],
        'unserviceable' => ['Unserviceable', 'status-unserviceable'],
        'red_tagged' => ['Red Tagged', 'status-red-tagged'],
        'no_tag' => ['No Tag', 'status-no-tag'],
        'borrowed' => ['Borrowed', 'status-borrowed']
    ];
    return $status_map[$status] ?? [$status, 'status-default'];
}

$status_display = formatStatus($item['status'] ?? '');
$page_title = 'Asset Item Details - ' . htmlspecialchars($item['description'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Item Details - PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="../ADMIN/dashboard.css" rel="stylesheet">
    <style>
        .detail-card {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e9ecef;
        }
        
        .detail-section {
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .detail-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        
        .detail-value {
            font-weight: 500;
            color: #2c3e50;
            font-size: 1rem;
        }
        
        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }
        
        .status-serviceable { 
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%); 
            color: white; 
            border: 1px solid #28a745;
        }
        
        .status-unserviceable { 
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%); 
            color: white; 
            border: 1px solid #dc3545;
        }
        
        .status-red-tagged { 
            background: linear-gradient(135deg, #fd7e14 0%, #dc3545 100%); 
            color: white; 
            border: 1px solid #fd7e14;
        }
        
        .status-no-tag { 
            background: linear-gradient(135deg, #6c757d 0%, #545b62 100%); 
            color: white; 
            border: 1px solid #6c757d;
        }
        
        .status-borrowed {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); 
            color: white; 
            border: 1px solid #007bff;
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
        
        .page-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 2rem;
            border-radius: var(--border-radius-lg);
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
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
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .page-header .row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .page-header h1 {
                font-size: 1.5rem;
            }
            
            .page-header .col-md-4 {
                text-align: left !important;
            }
            
            .detail-card {
                margin-bottom: 1rem;
            }
            
            .detail-section h5 {
                font-size: 1.1rem;
            }
            
            .detail-label {
                font-size: 0.85rem;
            }
            
            .detail-value {
                font-size: 0.95rem;
            }
            
            .text-value {
                font-size: 1rem;
            }
            
            .status-badge {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }
            
            .asset-image-container img {
                max-height: 200px !important;
            }
            
            .qr-code img {
                max-width: 120px !important;
                max-height: 120px !important;
            }
            
            .related-item {
                padding: 0.5rem;
            }
            
            .history-item {
                padding-left: 0.75rem;
                margin-bottom: 0.75rem;
            }
        }
        
        @media (max-width: 576px) {
            .page-header h1 {
                font-size: 1.25rem;
            }
            
            .page-header p {
                font-size: 0.9rem;
            }
            
            .detail-section h5 {
                font-size: 1rem;
            }
            
            .detail-label {
                font-size: 0.8rem;
            }
            
            .detail-value {
                font-size: 0.9rem;
            }
            
            .text-value {
                font-size: 0.95rem;
            }
            
            .status-badge {
                font-size: 0.7rem;
                padding: 0.2rem 0.4rem;
            }
            
            .btn-back {
                padding: 0.4rem 0.8rem;
                font-size: 0.9rem;
            }
            
            .asset-image-container img {
                max-height: 150px !important;
            }
            
            .qr-code img {
                max-width: 100px !important;
                max-height: 100px !important;
            }
            
            .related-item {
                padding: 0.4rem;
                margin-bottom: 0.4rem;
            }
            
            .history-item {
                padding-left: 0.5rem;
                margin-bottom: 0.5rem;
            }
            
            .history-date {
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>

        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="bi bi-box"></i> Asset Item Details
                        </h1>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                        <p class="text-muted mb-0"><small>Office: <?php echo htmlspecialchars($item['office_name'] ?? ''); ?></small></p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="asset_items.php" class="btn btn-back">
                            <i class="bi bi-arrow-left"></i> Back to Items
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
                                        <div class="detail-value"><?php echo htmlspecialchars($item['description'] ?? ''); ?></div>
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
                                        <div class="detail-value text-value">₱<?php echo number_format((float)($item['value'] ?? 0), 2); ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="detail-label">Acquisition Date</div>
                                        <div class="detail-value"><?php echo !empty($item['acquisition_date']) ? date('F j, Y', strtotime($item['acquisition_date'])) : 'N/A'; ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="detail-label">Last Updated</div>
                                        <div class="detail-value"><?php echo !empty($item['last_updated']) ? date('F j, Y g:i A', strtotime($item['last_updated'])) : 'N/A'; ?></div>
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
                                        <div class="detail-value"><?php echo htmlspecialchars($item['asset_description'] ?? ''); ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="detail-label">Category</div>
                                        <div class="detail-value"><?php echo htmlspecialchars(($item['category_code'] ?? '') . ' - ' . ($item['category_name'] ?? '')); ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="detail-label">Unit</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($item['unit'] ?? ''); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="detail-label">Unit Cost</div>
                                        <div class="detail-value">₱<?php echo number_format((float)($item['unit_cost'] ?? 0), 2); ?></div>
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
                        <?php if ($item['category_code'] === '030'): ?>
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
                        
                        <!-- Item History -->
                        <div class="detail-section">
                            <h5 class="mb-3"><i class="bi bi-clock-history"></i> Item History</h5>
                            <?php if (!empty($item_history)): ?>
                                <?php foreach ($item_history as $history): ?>
                                    <div class="history-item">
                                        <div class="history-date">
                                            <?php echo date('F j, Y g:i A', strtotime($history['created_at'])); ?>
                                        </div>
                                        <div class="history-action">
                                            <strong><?php echo htmlspecialchars($history['action'] ?? ''); ?></strong>
                                            <?php if (!empty($history['details'])): ?>
                                                - <?php echo htmlspecialchars($history['details']); ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($history['user_id'])): ?>
                                            <div class="history-user text-muted small">
                                                By User ID: <?php echo (int)$history['user_id']; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-clock-history fs-1"></i>
                                    <div class="mt-2">No history records found.</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Column -->
                <div class="col-lg-4">
                    <!-- Asset Image -->
                    <div class="detail-card mb-4">
                        <div class="detail-section">
                            <h5 class="mb-3"><i class="bi bi-image"></i> Asset Image</h5>
                            <div class="text-center">
                                <div class="asset-image-container mb-3">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="../uploads/asset_images/<?php echo htmlspecialchars($item['image']); ?>" 
                                             alt="Asset Image" 
                                             class="img-fluid rounded shadow-sm"
                                             style="max-height: 300px; width: auto; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="no-image-placeholder">
                                            <i class="bi bi-image fs-1 text-muted"></i>
                                            <div class="mt-2">
                                                <small class="text-muted">No image available</small>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- QR Code -->
                    <div class="detail-card mb-4">
                        <div class="detail-section">
                            <h5 class="mb-3"><i class="bi bi-qr-code"></i> QR Code</h5>
                            <div class="text-center">
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
                                <p class="mt-2 mb-0 text-muted">Property No: <?php echo ($item['property_no'] ?? '') !== '' ? htmlspecialchars((string)$item['property_no']) : 'Not assigned'; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Related Items -->
                    <div class="detail-card">
                        <div class="detail-section">
                            <h5 class="mb-3"><i class="bi bi-link-45deg"></i> Related Items</h5>
                            <?php if (!empty($related_items)): ?>
                                <?php foreach ($related_items as $r): ?>
                                    <div class="related-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-500"><?php echo htmlspecialchars($r['description'] ?? ''); ?></div>
                                                <small class="text-muted">Property No: <?php echo htmlspecialchars($r['property_no'] ?? 'N/A'); ?></small>
                                            </div>
                                            <div class="text-end">
                                                <span class="status-badge <?php echo formatStatus($r['status'] ?? '')[1]; ?>" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                                                    <?php echo formatStatus($r['status'] ?? '')[0]; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1"></i>
                                    <div class="mt-2">No related items found.</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
</body>
</html>
