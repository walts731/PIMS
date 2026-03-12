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

// Get asset item details with related information (same query as view_asset_item.php)
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

// Format status for display
function formatStatus($status) {
    $status_map = [
        'serviceable' => ['Serviceable', 'Serviceable'],
        'unserviceable' => ['Unserviceable', 'Unserviceable'],
        'red_tagged' => ['Red Tagged', 'Red Tagged'],
        'no_tag' => ['No Tag', 'No Tag']
    ];
    return $status_map[$status] ?? [$status, $status];
}

// Get item status display
$status_display = formatStatus($item['status']);

// Get system settings for logo
$system_settings = [];
try {
    $stmt = $conn->prepare("SELECT setting_name, setting_value FROM system_settings");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $system_settings[$row['setting_name']] = $row['setting_value'];
    }
    $stmt->close();
} catch (Exception $e) {
    // Fallback to default if database fails
    $system_settings['system_logo'] = '';
    $system_settings['system_name'] = 'PIMS';
}

// Set headers for HTML to PDF conversion
header('Content-Type: text/html; charset=UTF-8');
header('Content-Disposition: inline; filename="Asset_Item_' . $item_id . '_' . date('Y-m-d') . '.html"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($system_settings['system_name']); ?> - Asset Item Details - <?php echo htmlspecialchars($item['description']); ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #000;
        }
        
        .print-header {
            text-align: left;
            margin-bottom: 30px;
            padding: 20px;
        }
        
        .print-header img {
            max-width: 200px;
            object-fit: contain;
            float: left;
            margin-right: 20px;
        }
        
        .gov-header {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
        }
        
        .gov-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
            line-height: 1.2;
        }
        
        .municipality {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #000;
        }
        
        .province {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #000;
        }
        
        .print-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        .print-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-item {
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 140px;
        }
        
        .info-value {
            color: #000;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-serviceable { background: #d4edda; color: #155724; }
        .status-unserviceable { background: #f8d7da; color: #721c24; }
        .status-red-tagged { background: #fff3cd; color: #856404; }
        .status-no-tag { background: #e2e3e5; color: #383d41; }
        
        .text-value {
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .no-data {
            color: #999;
            font-style: italic;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
                font-size: 10px;
            }
            
            .print-header {
                padding: 10px;
            }
            
            /* Hide browser print headers and footers */
            @page {
                size: A4;
                margin: 0.5in;
            }
            
            html {
                overflow: hidden;
            }
        }
    </style>
</head>
<body>
    <div class="print-header">
        <div style="display: flex; align-items: flex-start; gap: 20px;">
            <!-- Logo on the left -->
            <div style="flex-shrink: 0;">
                <?php 
                if (!empty($system_settings['system_logo'])) {
                    echo '<img src="../' . htmlspecialchars($system_settings['system_logo']) . '" alt="' . htmlspecialchars($system_settings['system_name']) . '" style="max-width: 250px; max-height: 100px;">';
                } else {
                    echo '<img src="../img/system_logo.png" alt="' . htmlspecialchars($system_settings['system_name']) . '" style="max-width: 250px; max-height: 100px;">';
                }
                ?>
            </div>
            
            <!-- Government header on the right -->
            <div style="flex: 1;">
                <div class="gov-header" style="text-align: center; padding: 0; background: none;">
                    <div class="gov-title">Republic of the Philippines</div>
                    <div class="municipality">Municipality of Pilar</div>
                    <div class="province">Province of Sorsogon</div>
                    <div class="print-title"><?php echo htmlspecialchars($system_settings['system_name']); ?> - Asset Item Details</div>
                    <div class="print-subtitle">Generated on <?php echo date('F j, Y g:i A'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Item Information</div>
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Property No:</span>
                    <span class="info-value"><?php echo $item['property_no'] ? htmlspecialchars($item['property_no']) : '<span class="no-data">Not assigned</span>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Inventory Tag:</span>
                    <span class="info-value"><?php echo $item['inventory_tag'] ? htmlspecialchars($item['inventory_tag']) : '<span class="no-data">Not assigned</span>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">ICS No/PAR No:</span>
                    <span class="info-value">
                        <?php 
                        $reference = '';
                        if ($item['ics_no']) {
                            $reference = 'ICS No: ' . htmlspecialchars($item['ics_no']);
                        }
                        if ($item['par_no']) {
                            $reference = $reference ? $reference . ' / PAR No: ' . htmlspecialchars($item['par_no']) : 'PAR No: ' . htmlspecialchars($item['par_no']);
                        }
                        echo $reference ? $reference : '<span class="no-data">Not assigned</span>';
                        ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Description:</span>
                    <span class="info-value"><?php echo htmlspecialchars($item['description']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="status-badge status-<?php echo $item['status']; ?>"><?php echo $status_display[0]; ?></span>
                    </span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Value:</span>
                    <span class="info-value text-value">₱<?php echo number_format($item['value'], 2); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Acquisition Date:</span>
                    <span class="info-value"><?php echo date('F j, Y', strtotime($item['acquisition_date'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Last Updated:</span>
                    <span class="info-value"><?php echo date('F j, Y g:i A', strtotime($item['last_updated'])); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Asset Information</div>
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Asset Description:</span>
                    <span class="info-value"><?php echo htmlspecialchars($item['asset_description']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Category:</span>
                    <span class="info-value"><?php echo htmlspecialchars($item['category_code'] . ' - ' . $item['category_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Unit:</span>
                    <span class="info-value"><?php echo htmlspecialchars($item['unit']); ?></span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Unit Cost:</span>
                    <span class="info-value">₱<?php echo number_format($item['unit_cost'], 2); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Location & Assignment</div>
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Office:</span>
                    <span class="info-value"><?php echo $item['office_name'] ? htmlspecialchars($item['office_name']) : '<span class="no-data">Not assigned</span>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Assigned Employee:</span>
                    <span class="info-value">
                        <?php if ($item['employee_no']): ?>
                            <?php echo htmlspecialchars($item['employee_no'] . ' - ' . $item['firstname'] . ' ' . $item['lastname']); ?>
                        <?php else: ?>
                            <span class="no-data">Not assigned</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">End User:</span>
                    <span class="info-value">
                        <?php if (!empty($item['end_user'])): ?>
                            <?php echo htmlspecialchars($item['end_user']); ?>
                        <?php else: ?>
                            <span class="no-data">Not specified</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Computer Equipment Specific Fields -->
    <?php if ($item['category_code'] === 'CE' || $item['category_code'] === 'ITS'): ?>
    <div class="section">
        <div class="section-title">Computer Equipment Specifications</div>
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Processor:</span>
                    <span class="info-value"><?php echo $item['processor'] ? htmlspecialchars($item['processor']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">RAM (GB):</span>
                    <span class="info-value"><?php echo $item['ram_capacity'] ? htmlspecialchars($item['ram_capacity']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Storage:</span>
                    <span class="info-value"><?php echo $item['storage_capacity'] ? htmlspecialchars($item['storage_capacity']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Operating System:</span>
                    <span class="info-value"><?php echo $item['operating_system'] ? htmlspecialchars($item['operating_system']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Serial Number:</span>
                    <span class="info-value"><?php echo $item['computer_serial_number'] ? htmlspecialchars($item['computer_serial_number']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Storage Type:</span>
                    <span class="info-value"><?php echo $item['storage_type'] ? htmlspecialchars(ucfirst($item['storage_type'])) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Vehicles Specific Fields -->
    <?php if ($item['category_code'] === 'VH'): ?>
    <div class="section">
        <div class="section-title">Vehicle Specifications</div>
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Brand:</span>
                    <span class="info-value"><?php echo $item['vehicle_brand'] ? htmlspecialchars($item['vehicle_brand']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Model:</span>
                    <span class="info-value"><?php echo $item['vehicle_model'] ? htmlspecialchars($item['vehicle_model']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Plate Number:</span>
                    <span class="info-value"><?php echo $item['plate_number'] ? htmlspecialchars($item['plate_number']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Color:</span>
                    <span class="info-value"><?php echo $item['color'] ? htmlspecialchars($item['color']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Engine Number:</span>
                    <span class="info-value"><?php echo $item['engine_number'] ? htmlspecialchars($item['engine_number']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Year Manufactured:</span>
                    <span class="info-value"><?php echo $item['year_manufactured'] ? htmlspecialchars($item['year_manufactured']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Furniture & Fixtures Specific Fields -->
    <?php if ($item['category_code'] === 'FF'): ?>
    <div class="section">
        <div class="section-title">Furniture & Fixtures Specifications</div>
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Material:</span>
                    <span class="info-value"><?php echo $item['material'] ? htmlspecialchars($item['material']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Dimensions:</span>
                    <span class="info-value"><?php echo $item['furniture_dimensions'] ? htmlspecialchars($item['furniture_dimensions']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Color:</span>
                    <span class="info-value"><?php echo $item['furniture_color'] ? htmlspecialchars($item['furniture_color']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Manufacturer:</span>
                    <span class="info-value"><?php echo $item['furniture_manufacturer'] ? htmlspecialchars($item['furniture_manufacturer']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Machinery & Equipment Specific Fields -->
    <?php if ($item['category_code'] === 'ME'): ?>
    <div class="section">
        <div class="section-title">Machinery & Equipment Specifications</div>
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Machine Type:</span>
                    <span class="info-value"><?php echo $item['machine_type'] ? htmlspecialchars($item['machine_type']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Manufacturer:</span>
                    <span class="info-value"><?php echo $item['machinery_manufacturer'] ? htmlspecialchars($item['machinery_manufacturer']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Model Number:</span>
                    <span class="info-value"><?php echo $item['model_number'] ? htmlspecialchars($item['model_number']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Capacity:</span>
                    <span class="info-value"><?php echo $item['machinery_capacity'] ? htmlspecialchars($item['machinery_capacity']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Power Requirements:</span>
                    <span class="info-value"><?php echo $item['power_requirements'] ? htmlspecialchars($item['power_requirements']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Serial Number:</span>
                    <span class="info-value"><?php echo $item['machinery_serial_number'] ? htmlspecialchars($item['machinery_serial_number']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Office Equipment Specific Fields -->
    <?php if ($item['category_code'] === 'OE'): ?>
    <div class="section">
        <div class="section-title">Office Equipment Specifications</div>
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Brand:</span>
                    <span class="info-value"><?php echo $item['office_brand'] ? htmlspecialchars($item['office_brand']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Model:</span>
                    <span class="info-value"><?php echo $item['office_model'] ? htmlspecialchars($item['office_model']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Serial Number:</span>
                    <span class="info-value"><?php echo $item['office_serial_number'] ? htmlspecialchars($item['office_serial_number']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Software Specific Fields -->
    <?php if ($item['category_code'] === 'SW'): ?>
    <div class="section">
        <div class="section-title">Software Specifications</div>
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Software Name:</span>
                    <span class="info-value"><?php echo $item['software_name'] ? htmlspecialchars($item['software_name']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Version:</span>
                    <span class="info-value"><?php echo $item['version'] ? htmlspecialchars($item['version']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">License Key:</span>
                    <span class="info-value"><?php echo $item['license_key'] ? htmlspecialchars($item['license_key']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">License Expiry:</span>
                    <span class="info-value"><?php echo $item['license_expiry'] ? date('F j, Y', strtotime($item['license_expiry'])) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Land Specific Fields -->
    <?php if ($item['category_code'] === 'LD'): ?>
    <div class="section">
        <div class="section-title">Land Specifications</div>
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Lot Area (sqm):</span>
                    <span class="info-value"><?php echo $item['lot_area'] ? htmlspecialchars($item['lot_area']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Address:</span>
                    <span class="info-value"><?php echo $item['land_address'] ? htmlspecialchars($item['land_address']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Tax Declaration Number:</span>
                    <span class="info-value"><?php echo $item['tax_declaration_number'] ? htmlspecialchars($item['tax_declaration_number']) : '<span class="no-data">Not specified</span>'; ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="footer">
        <p>Property Inventory Management System (PIMS) - Asset Item Report</p>
        <p>This report was generated on <?php echo date('F j, Y g:i A'); ?> by <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></p>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        
        // Close window after printing
        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>
</html>

<?php
// Log the export action
require_once '../includes/logger.php';
logSystemAction($_SESSION['user_id'], 'export_pdf', 'asset_items', "Exported asset item PDF: ID {$item_id} - " . htmlspecialchars($item['description']));
?>
