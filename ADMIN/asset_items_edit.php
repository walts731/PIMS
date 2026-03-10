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
                   subcat.sub_category_name, subcat.sub_category_code,
                   o.office_name,
                   comp.processor, comp.ram_capacity, comp.storage_type, comp.storage_capacity, 
                   comp.operating_system, comp.serial_number as computer_serial_number,
                   desk.monitor_name, desk.monitor_model, desk.monitor_serial_number, desk.monitor_status,
                   desk.ups_name, desk.ups_model, desk.ups_serial_number, desk.ups_status,
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
            LEFT JOIN asset_sub_categories subcat ON ai.asset_subcategory_id = subcat.id
            LEFT JOIN offices o ON ai.office_id = o.id 
            LEFT JOIN asset_computers comp ON ai.id = comp.asset_item_id
            LEFT JOIN asset_desktop_computers desk ON ai.id = desk.asset_item_id
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

// Decode images from JSON if available
$asset_images = [];
if (!empty($item['image'])) {
    $decoded_images = json_decode($item['image'], true);
    if (is_array($decoded_images)) {
        $asset_images = $decoded_images;
    } elseif (!empty($item['image'])) {
        // Handle case where it's a single filename (not JSON)
        $asset_images = [$item['image']];
    }
}

// Get asset ID for navigation
$asset_id = $item['asset_id'];

// Handle POST request for updating asset item
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_item') {
    $update_fields = [];
    $update_values = [];
    $types = '';
    
    // Basic asset item fields
    if (isset($_POST['description'])) {
        $update_fields[] = "description = ?";
        $update_values[] = trim($_POST['description']);
        $types .= 's';
    }
    
    if (isset($_POST['status'])) {
        $update_fields[] = "status = ?";
        $update_values[] = $_POST['status'];
        $types .= 's';
    }
    
    if (isset($_POST['value'])) {
        $update_fields[] = "value = ?";
        $update_values[] = floatval($_POST['value']);
        $types .= 'd';
    }
    
    if (isset($_POST['acquisition_date'])) {
        $update_fields[] = "acquisition_date = ?";
        $update_values[] = $_POST['acquisition_date'];
        $types .= 's';
    }
    
    if (isset($_POST['end_user'])) {
        $update_fields[] = "end_user = ?";
        $update_values[] = trim($_POST['end_user']);
        $types .= 's';
    }
    
    if (isset($_POST['employee_id'])) {
        $employee_id = intval($_POST['employee_id']);
        if ($employee_id > 0) {
            $update_fields[] = "employee_id = ?";
            $update_values[] = $employee_id;
            $types .= 'i';
        } else {
            $update_fields[] = "employee_id = NULL";
        }
    }
    
    // Update last_updated timestamp
    $update_fields[] = "last_updated = NOW()";
    
    if (!empty($update_fields)) {
        try {
            $update_sql = "UPDATE asset_items SET " . implode(", ", $update_fields) . " WHERE id = ?";
            $update_values[] = $item_id;
            $types .= 'i';
            
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param($types, ...$update_values);
            
            if ($update_stmt->execute()) {
                // Log the update
                logSystemAction($_SESSION['user_id'], 'asset_item_updated', 'asset_management', "Updated asset item: {$item['description']} (ID: {$item_id})");
                
                // Update category-specific fields based on category
                updateCategorySpecificFields($item_id, $item['category_code'], $_POST);
                
                $_SESSION['success'] = 'Asset item updated successfully!';
                header('Location: view_asset_item.php?id=' . $item_id);
                exit();
            } else {
                $_SESSION['error'] = 'Failed to update asset item: ' . $update_stmt->error;
            }
            $update_stmt->close();
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error updating asset item: ' . $e->getMessage();
        }
    }
}

// Function to update category-specific fields
function updateCategorySpecificFields($item_id, $category_code, $post_data) {
    global $conn;
    
    try {
        switch ($category_code) {
            case '030': // Computer Equipment
                $computer_fields = [];
                $computer_values = [];
                $computer_types = '';
                
                if (isset($post_data['processor'])) {
                    $computer_fields[] = "processor = ?";
                    $computer_values[] = trim($post_data['processor']);
                    $computer_types .= 's';
                }
                if (isset($post_data['ram_capacity'])) {
                    $computer_fields[] = "ram_capacity = ?";
                    $computer_values[] = trim($post_data['ram_capacity']);
                    $computer_types .= 's';
                }
                if (isset($post_data['storage_type'])) {
                    $computer_fields[] = "storage_type = ?";
                    $computer_values[] = trim($post_data['storage_type']);
                    $computer_types .= 's';
                }
                if (isset($post_data['storage_capacity'])) {
                    $computer_fields[] = "storage_capacity = ?";
                    $computer_values[] = trim($post_data['storage_capacity']);
                    $computer_types .= 's';
                }
                if (isset($post_data['operating_system'])) {
                    $computer_fields[] = "operating_system = ?";
                    $computer_values[] = trim($post_data['operating_system']);
                    $computer_types .= 's';
                }
                if (isset($post_data['computer_serial_number'])) {
                    $computer_fields[] = "serial_number = ?";
                    $computer_values[] = trim($post_data['computer_serial_number']);
                    $computer_types .= 's';
                }
                
                if (!empty($computer_fields)) {
                    $computer_sql = "UPDATE asset_computers SET " . implode(", ", $computer_fields) . " WHERE asset_item_id = ?";
                    $computer_values[] = $item_id;
                    $computer_types .= 'i';
                    
                    $computer_stmt = $conn->prepare($computer_sql);
                    $computer_stmt->bind_param($computer_types, ...$computer_values);
                    $computer_stmt->execute();
                    $computer_stmt->close();
                }
                break;
                
            case '07': // Vehicles
                $vehicle_fields = [];
                $vehicle_values = [];
                $vehicle_types = '';
                
                if (isset($post_data['vehicle_brand'])) {
                    $vehicle_fields[] = "brand = ?";
                    $vehicle_values[] = trim($post_data['vehicle_brand']);
                    $vehicle_types .= 's';
                }
                if (isset($post_data['vehicle_model'])) {
                    $vehicle_fields[] = "model = ?";
                    $vehicle_values[] = trim($post_data['vehicle_model']);
                    $vehicle_types .= 's';
                }
                if (isset($post_data['plate_number'])) {
                    $vehicle_fields[] = "plate_number = ?";
                    $vehicle_values[] = trim($post_data['plate_number']);
                    $vehicle_types .= 's';
                }
                if (isset($post_data['color'])) {
                    $vehicle_fields[] = "color = ?";
                    $vehicle_values[] = trim($post_data['color']);
                    $vehicle_types .= 's';
                }
                if (isset($post_data['engine_number'])) {
                    $vehicle_fields[] = "engine_number = ?";
                    $vehicle_values[] = trim($post_data['engine_number']);
                    $vehicle_types .= 's';
                }
                if (isset($post_data['chassis_number'])) {
                    $vehicle_fields[] = "chassis_number = ?";
                    $vehicle_values[] = trim($post_data['chassis_number']);
                    $vehicle_types .= 's';
                }
                if (isset($post_data['year_manufactured'])) {
                    $vehicle_fields[] = "year_manufactured = ?";
                    $vehicle_values[] = intval($post_data['year_manufactured']);
                    $vehicle_types .= 'i';
                }
                
                if (!empty($vehicle_fields)) {
                    $vehicle_sql = "UPDATE asset_vehicles SET " . implode(", ", $vehicle_fields) . " WHERE asset_item_id = ?";
                    $vehicle_values[] = $item_id;
                    $vehicle_types .= 'i';
                    
                    $vehicle_stmt = $conn->prepare($vehicle_sql);
                    $vehicle_stmt->bind_param($vehicle_types, ...$vehicle_values);
                    $vehicle_stmt->execute();
                    $vehicle_stmt->close();
                }
                break;
                
            case '02': // Furniture & Fixtures
                $furniture_fields = [];
                $furniture_values = [];
                $furniture_types = '';
                
                if (isset($post_data['material'])) {
                    $furniture_fields[] = "material = ?";
                    $furniture_values[] = trim($post_data['material']);
                    $furniture_types .= 's';
                }
                if (isset($post_data['furniture_dimensions'])) {
                    $furniture_fields[] = "dimensions = ?";
                    $furniture_values[] = trim($post_data['furniture_dimensions']);
                    $furniture_types .= 's';
                }
                if (isset($post_data['furniture_color'])) {
                    $furniture_fields[] = "color = ?";
                    $furniture_values[] = trim($post_data['furniture_color']);
                    $furniture_types .= 's';
                }
                if (isset($post_data['furniture_manufacturer'])) {
                    $furniture_fields[] = "manufacturer = ?";
                    $furniture_values[] = trim($post_data['furniture_manufacturer']);
                    $furniture_types .= 's';
                }
                
                if (!empty($furniture_fields)) {
                    $furniture_sql = "UPDATE asset_furniture SET " . implode(", ", $furniture_fields) . " WHERE asset_item_id = ?";
                    $furniture_values[] = $item_id;
                    $furniture_types .= 'i';
                    
                    $furniture_stmt = $conn->prepare($furniture_sql);
                    $furniture_stmt->bind_param($furniture_types, ...$furniture_values);
                    $furniture_stmt->execute();
                    $furniture_stmt->close();
                }
                break;
                
            case '04': // Machinery & Equipment
                $machinery_fields = [];
                $machinery_values = [];
                $machinery_types = '';
                
                if (isset($post_data['machine_type'])) {
                    $machinery_fields[] = "machine_type = ?";
                    $machinery_values[] = trim($post_data['machine_type']);
                    $machinery_types .= 's';
                }
                if (isset($post_data['machinery_manufacturer'])) {
                    $machinery_fields[] = "manufacturer = ?";
                    $machinery_values[] = trim($post_data['machinery_manufacturer']);
                    $machinery_types .= 's';
                }
                if (isset($post_data['model_number'])) {
                    $machinery_fields[] = "model_number = ?";
                    $machinery_values[] = trim($post_data['model_number']);
                    $machinery_types .= 's';
                }
                if (isset($post_data['machinery_capacity'])) {
                    $machinery_fields[] = "capacity = ?";
                    $machinery_values[] = trim($post_data['machinery_capacity']);
                    $machinery_types .= 's';
                }
                if (isset($post_data['power_requirements'])) {
                    $machinery_fields[] = "power_requirements = ?";
                    $machinery_values[] = trim($post_data['power_requirements']);
                    $machinery_types .= 's';
                }
                if (isset($post_data['machinery_serial_number'])) {
                    $machinery_fields[] = "serial_number = ?";
                    $machinery_values[] = trim($post_data['machinery_serial_number']);
                    $machinery_types .= 's';
                }
                
                if (!empty($machinery_fields)) {
                    $machinery_sql = "UPDATE asset_machinery SET " . implode(", ", $machinery_fields) . " WHERE asset_item_id = ?";
                    $machinery_values[] = $item_id;
                    $machinery_types .= 'i';
                    
                    $machinery_stmt = $conn->prepare($machinery_sql);
                    $machinery_stmt->bind_param($machinery_types, ...$machinery_values);
                    $machinery_stmt->execute();
                    $machinery_stmt->close();
                }
                break;
                
            case '05': // Office Equipment
                $office_fields = [];
                $office_values = [];
                $office_types = '';
                
                if (isset($post_data['office_brand'])) {
                    $office_fields[] = "brand = ?";
                    $office_values[] = trim($post_data['office_brand']);
                    $office_types .= 's';
                }
                if (isset($post_data['office_model'])) {
                    $office_fields[] = "model = ?";
                    $office_values[] = trim($post_data['office_model']);
                    $office_types .= 's';
                }
                if (isset($post_data['office_serial_number'])) {
                    $office_fields[] = "serial_number = ?";
                    $office_values[] = trim($post_data['office_serial_number']);
                    $office_types .= 's';
                }
                
                if (!empty($office_fields)) {
                    $office_sql = "UPDATE asset_office_equipment SET " . implode(", ", $office_fields) . " WHERE asset_item_id = ?";
                    $office_values[] = $item_id;
                    $office_types .= 'i';
                    
                    $office_stmt = $conn->prepare($office_sql);
                    $office_stmt->bind_param($office_types, ...$office_values);
                    $office_stmt->execute();
                    $office_stmt->close();
                }
                break;
                
            case '06': // Software
                $software_fields = [];
                $software_values = [];
                $software_types = '';
                
                if (isset($post_data['software_name'])) {
                    $software_fields[] = "software_name = ?";
                    $software_values[] = trim($post_data['software_name']);
                    $software_types .= 's';
                }
                if (isset($post_data['version'])) {
                    $software_fields[] = "version = ?";
                    $software_values[] = trim($post_data['version']);
                    $software_types .= 's';
                }
                if (isset($post_data['license_key'])) {
                    $software_fields[] = "license_key = ?";
                    $software_values[] = trim($post_data['license_key']);
                    $software_types .= 's';
                }
                if (isset($post_data['license_expiry'])) {
                    $software_fields[] = "license_expiry = ?";
                    $software_values[] = trim($post_data['license_expiry']);
                    $software_types .= 's';
                }
                
                if (!empty($software_fields)) {
                    $software_sql = "UPDATE asset_software SET " . implode(", ", $software_fields) . " WHERE asset_item_id = ?";
                    $software_values[] = $item_id;
                    $software_types .= 'i';
                    
                    $software_stmt = $conn->prepare($software_sql);
                    $software_stmt->bind_param($software_types, ...$software_values);
                    $software_stmt->execute();
                    $software_stmt->close();
                }
                break;
                
            case '03': // Land
                $land_fields = [];
                $land_values = [];
                $land_types = '';
                
                if (isset($post_data['lot_area'])) {
                    $land_fields[] = "lot_area = ?";
                    $land_values[] = trim($post_data['lot_area']);
                    $land_types .= 's';
                }
                if (isset($post_data['land_address'])) {
                    $land_fields[] = "address = ?";
                    $land_values[] = trim($post_data['land_address']);
                    $land_types .= 's';
                }
                if (isset($post_data['tax_declaration_number'])) {
                    $land_fields[] = "tax_declaration_number = ?";
                    $land_values[] = trim($post_data['tax_declaration_number']);
                    $land_types .= 's';
                }
                
                if (!empty($land_fields)) {
                    $land_sql = "UPDATE asset_land SET " . implode(", ", $land_fields) . " WHERE asset_item_id = ?";
                    $land_values[] = $item_id;
                    $land_types .= 'i';
                    
                    $land_stmt = $conn->prepare($land_sql);
                    $land_stmt->bind_param($land_types, ...$land_values);
                    $land_stmt->execute();
                    $land_stmt->close();
                }
                break;
        }
    } catch (Exception $e) {
        error_log("Error updating category-specific fields: " . $e->getMessage());
    }
}

// Get employees for dropdown
$employees = [];
try {
    $result = $conn->query("SELECT id, employee_no, firstname, lastname FROM employees WHERE clearance_status = 'cleared' ORDER BY employee_no");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching employees: " . $e->getMessage());
}

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
    <title>Edit Asset Item - <?php echo htmlspecialchars($item['description']); ?> | PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght=300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Edit Asset Item - ' . htmlspecialchars($item['description']);
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
                        <i class="bi bi-pencil"></i> Edit Asset Item
                    </h1>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($item['description']); ?></p>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger mt-2" role="alert">
                            <i class="bi bi-exclamation-triangle"></i>
                            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success mt-2" role="alert">
                            <i class="bi bi-check-circle"></i>
                            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="view_asset_item.php?id=<?php echo $item_id; ?>" class="btn btn-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Back to View
                    </a>
                    <a href="print_inventory_tag.php?id=<?php echo $item_id; ?>" class="btn btn-outline-primary btn-sm me-2" target="_blank">
                        <i class="bi bi-printer"></i> Print
                    </a>
                    <a href="export_asset_pdf.php?id=<?php echo $item_id; ?>" class="btn btn-danger btn-sm" target="_blank">
                        <i class="bi bi-file-pdf"></i> Export PDF
                    </a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Main Details Column -->
            <div class="col-lg-8">
                <form method="POST">
                    <input type="hidden" name="action" value="update_item">
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
                                    <label class="detail-label">Description *</label>
                                    <input type="text" class="form-control" name="description" value="<?php echo htmlspecialchars($item['description']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="detail-label">Status *</label>
                                    <select class="form-select" name="status" required>
                                        <option value="serviceable" <?php echo $item['status'] === 'serviceable' ? 'selected' : ''; ?>>Serviceable</option>
                                        <option value="unserviceable" <?php echo $item['status'] === 'unserviceable' ? 'selected' : ''; ?>>Unserviceable</option>
                                        <option value="red_tagged" <?php echo $item['status'] === 'red_tagged' ? 'selected' : ''; ?>>Red Tagged</option>
                                        <option value="no_tag" <?php echo $item['status'] === 'no_tag' ? 'selected' : ''; ?>>No Tag</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="detail-label">Value *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" class="form-control" name="value" step="0.01" min="0" value="<?php echo $item['value']; ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="detail-label">Acquisition Date *</label>
                                    <input type="date" class="form-control" name="acquisition_date" value="<?php echo $item['acquisition_date']; ?>" required>
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
                                    <div class="detail-label">Category</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($item['category_code'] . ' - ' . $item['category_name']); ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Unit</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($item['unit']); ?></div>
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
                                    <label class="detail-label">Assigned Employee</label>
                                    <select class="form-select" name="employee_id">
                                        <option value="">Not assigned</option>
                                        <?php foreach ($employees as $employee): ?>
                                            <option value="<?php echo $employee['id']; ?>" <?php echo $item['employee_id'] == $employee['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($employee['employee_no'] . ' - ' . $employee['firstname'] . ' ' . $employee['lastname']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="detail-label">End User</label>
                                    <input type="text" class="form-control" name="end_user" value="<?php echo htmlspecialchars($item['end_user'] ?? ''); ?>" placeholder="Enter end user">
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
                                    <label class="detail-label">Processor</label>
                                    <input type="text" class="form-control" name="processor" value="<?php echo htmlspecialchars($item['processor'] ?? ''); ?>" placeholder="Enter processor">
                                </div>
                                <div class="mb-3">
                                    <label class="detail-label">RAM (GB)</label>
                                    <input type="text" class="form-control" name="ram_capacity" value="<?php echo htmlspecialchars($item['ram_capacity'] ?? ''); ?>" placeholder="e.g. 8GB, 16GB">
                                </div>
                                <div class="mb-3">
                                    <label class="detail-label">Storage Capacity</label>
                                    <input type="text" class="form-control" name="storage_capacity" value="<?php echo htmlspecialchars($item['storage_capacity'] ?? ''); ?>" placeholder="e.g. 500GB, 1TB">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="detail-label">Operating System</label>
                                    <input type="text" class="form-control" name="operating_system" value="<?php echo htmlspecialchars($item['operating_system'] ?? ''); ?>" placeholder="e.g. Windows 10, Ubuntu">
                                </div>
                                <div class="mb-3">
                                    <label class="detail-label">Serial Number</label>
                                    <input type="text" class="form-control" name="computer_serial_number" value="<?php echo htmlspecialchars($item['computer_serial_number'] ?? ''); ?>" placeholder="Enter serial number">
                                </div>
                                <div class="mb-3">
                                    <label class="detail-label">Storage Type</label>
                                    <select class="form-select" name="storage_type">
                                        <option value="">Not specified</option>
                                        <option value="ssd" <?php echo ($item['storage_type'] === 'ssd') ? 'selected' : ''; ?>>SSD</option>
                                        <option value="hdd" <?php echo ($item['storage_type'] === 'hdd') ? 'selected' : ''; ?>>HDD</option>
                                        <option value="nvme" <?php echo ($item['storage_type'] === 'nvme') ? 'selected' : ''; ?>>NVMe</option>
                                        <option value="hybrid" <?php echo ($item['storage_type'] === 'hybrid') ? 'selected' : ''; ?>>Hybrid</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Vehicles Specific Fields -->
                    <?php if ($item['category_code'] === '07'): ?>
                    <div class="detail-section">
                        <h5 class="mb-3"><i class="bi bi-truck"></i> Vehicle Specifications</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="detail-label">Brand</label>
                                    <input type="text" class="form-control" name="vehicle_brand" value="<?php echo htmlspecialchars($item['vehicle_brand'] ?? ''); ?>" placeholder="Enter vehicle brand">
                                </div>
                                <div class="mb-3">
                                    <label class="detail-label">Model</label>
                                    <input type="text" class="form-control" name="vehicle_model" value="<?php echo htmlspecialchars($item['vehicle_model'] ?? ''); ?>" placeholder="Enter vehicle model">
                                </div>
                                <div class="mb-3">
                                    <label class="detail-label">Plate Number</label>
                                    <input type="text" class="form-control" name="plate_number" value="<?php echo htmlspecialchars($item['plate_number'] ?? ''); ?>" placeholder="Enter plate number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="detail-label">Color</label>
                                    <input type="text" class="form-control" name="color" value="<?php echo htmlspecialchars($item['color'] ?? ''); ?>" placeholder="Enter color">
                                </div>
                                <div class="mb-3">
                                    <label class="detail-label">Engine Number</label>
                                    <input type="text" class="form-control" name="engine_number" value="<?php echo htmlspecialchars($item['engine_number'] ?? ''); ?>" placeholder="Enter engine number">
                                </div>
                                <div class="mb-3">
                                    <label class="detail-label">Year Manufactured</label>
                                    <input type="number" class="form-control" name="year_manufactured" value="<?php echo htmlspecialchars($item['year_manufactured'] ?? ''); ?>" placeholder="Enter year">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Furniture & Fixtures Specific Fields -->
                    <?php if ($item['category_code'] === '02'): ?>
                    <div class="detail-section">
                        <h5 class="mb-3"><i class="bi bi-lamp"></i> Furniture & Fixtures Specifications</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="detail-label">Material</label>
                                    <input type="text" class="form-control" name="material" value="<?php echo htmlspecialchars($item['material'] ?? ''); ?>" placeholder="Enter material">
                                </div>
                                <div class="mb-3">
                                    <label class="detail-label">Dimensions</label>
                                    <input type="text" class="form-control" name="furniture_dimensions" value="<?php echo htmlspecialchars($item['furniture_dimensions'] ?? ''); ?>" placeholder="Enter dimensions">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="detail-label">Color</label>
                                    <input type="text" class="form-control" name="furniture_color" value="<?php echo htmlspecialchars($item['furniture_color'] ?? ''); ?>" placeholder="Enter color">
                                </div>
                                <div class="mb-3">
                                    <label class="detail-label">Manufacturer</label>
                                    <input type="text" class="form-control" name="furniture_manufacturer" value="<?php echo htmlspecialchars($item['furniture_manufacturer'] ?? ''); ?>" placeholder="Enter manufacturer">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Machinery & Equipment Specific Fields -->
                    <?php if ($item['category_code'] === '04'): ?>
                    <div class="detail-section">
                        <h5 class="mb-3"><i class="bi bi-gear"></i> Machinery & Equipment Specifications</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="detail-label">Machine Type</label>
                                    <input type="text" class="form-control" name="machine_type" value="<?php echo htmlspecialchars($item['machine_type'] ?? ''); ?>" placeholder="Enter machine type">
                                </div>
                                <div class="mb-3">
                                    <label class="detail-label">Manufacturer</label>
                                    <input type="text" class="form-control" name="machinery_manufacturer" value="<?php echo htmlspecialchars($item['machinery_manufacturer'] ?? ''); ?>" placeholder="Enter manufacturer">
                                </div>
                                <div class="mb-3">
                                    <label class="detail-label">Model Number</label>
                                    <input type="text" class="form-control" name="model_number" value="<?php echo htmlspecialchars($item['model_number'] ?? ''); ?>" placeholder="Enter model number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="detail-label">Capacity</label>
                                    <input type="text" class="form-control" name="machinery_capacity" value="<?php echo htmlspecialchars($item['machinery_capacity'] ?? ''); ?>" placeholder="Enter capacity">
                                </div>
                                <div class="mb-3">
                                    <label class="detail-label">Power Requirements</label>
                                    <input type="text" class="form-control" name="power_requirements" value="<?php echo htmlspecialchars($item['power_requirements'] ?? ''); ?>" placeholder="Enter power requirements">
                                </div>
                                <div class="mb-3">
                                    <label class="detail-label">Serial Number</label>
                                    <input type="text" class="form-control" name="machinery_serial_number" value="<?php echo htmlspecialchars($item['machinery_serial_number'] ?? ''); ?>" placeholder="Enter serial number">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Office Equipment Specific Fields -->
                    <?php if ($item['category_code'] === '05'): ?>
                    <div class="detail-section">
                        <h5 class="mb-3"><i class="bi bi-printer"></i> Office Equipment Specifications</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="detail-label">Brand</label>
                                    <input type="text" class="form-control" name="office_brand" value="<?php echo htmlspecialchars($item['office_brand'] ?? ''); ?>" placeholder="Enter brand">
                                </div>
                                <div class="mb-3">
                                    <label class="detail-label">Model</label>
                                    <input type="text" class="form-control" name="office_model" value="<?php echo htmlspecialchars($item['office_model'] ?? ''); ?>" placeholder="Enter model">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="detail-label">Serial Number</label>
                                    <input type="text" class="form-control" name="office_serial_number" value="<?php echo htmlspecialchars($item['office_serial_number'] ?? ''); ?>" placeholder="Enter serial number">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Software Specific Fields -->
                    <?php if ($item['category_code'] === '06'): ?>
                    <div class="detail-section">
                        <h5 class="mb-3"><i class="bi bi-window"></i> Software Specifications</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="detail-label">Software Name</label>
                                    <input type="text" class="form-control" name="software_name" value="<?php echo htmlspecialchars($item['software_name'] ?? ''); ?>" placeholder="Enter software name">
                                </div>
                                <div class="mb-3">
                                    <label class="detail-label">Version</label>
                                    <input type="text" class="form-control" name="version" value="<?php echo htmlspecialchars($item['version'] ?? ''); ?>" placeholder="Enter version">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="detail-label">License Key</label>
                                    <input type="text" class="form-control" name="license_key" value="<?php echo htmlspecialchars($item['license_key'] ?? ''); ?>" placeholder="Enter license key">
                                </div>
                                <div class="mb-3">
                                    <label class="detail-label">License Expiry</label>
                                    <input type="date" class="form-control" name="license_expiry" value="<?php echo htmlspecialchars($item['license_expiry'] ?? ''); ?>" placeholder="Select expiry date">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Land Specific Fields -->
                    <?php if ($item['category_code'] === '03'): ?>
                    <div class="detail-section">
                        <h5 class="mb-3"><i class="bi bi-map"></i> Land Specifications</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="detail-label">Lot Area (sqm)</label>
                                    <input type="text" class="form-control" name="lot_area" value="<?php echo htmlspecialchars($item['lot_area'] ?? ''); ?>" placeholder="Enter lot area">
                                </div>
                                <div class="mb-3">
                                    <label class="detail-label">Address</label>
                                    <input type="text" class="form-control" name="land_address" value="<?php echo htmlspecialchars($item['land_address'] ?? ''); ?>" placeholder="Enter address">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="detail-label">Tax Declaration Number</label>
                                    <input type="text" class="form-control" name="tax_declaration_number" value="<?php echo htmlspecialchars($item['tax_declaration_number'] ?? ''); ?>" placeholder="Enter tax declaration number">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Action Buttons -->
                    <div class="detail-section">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="view_asset_item.php?id=<?php echo $item_id; ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </div>
                
                </form>
            </div>
            
            <!-- Sidebar Column -->
            <div class="col-lg-4">
                <!-- Asset Images Carousel -->
                <div class="detail-card text-center">
                    <h5 class="mb-3"><i class="bi bi-images"></i> Asset Images</h5>
                    <?php if (!empty($asset_images)): ?>
                        <div id="assetImageCarousel" class="carousel slide asset-carousel mb-3" data-bs-ride="carousel">
                            <?php if (count($asset_images) > 1): ?>
                                <div class="carousel-indicators">
                                    <?php foreach ($asset_images as $index => $image): ?>
                                        <button type="button" data-bs-target="#assetImageCarousel" data-bs-slide-to="<?php echo $index; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>" aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-label="Slide <?php echo $index + 1; ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="carousel-inner">
                                <?php foreach ($asset_images as $index => $image): ?>
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <img src="../uploads/asset_images/<?php echo htmlspecialchars($image); ?>" 
                                             class="d-block w-100" 
                                             alt="Asset Image <?php echo $index + 1; ?>"
                                             onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgdmlld0JveD0iMCAwIDMwMCAzMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMDAiIGhlaWdodD0iMzAwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik0xMjUgMTIwSDE3NVYxNzVIMTI1VjEyMFoiIGZpbGw9IiNEMUQ1REIiLz4KPHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEwIDIwQzEwIDIyLjIwOTEgMTEuNzkwOSAyNCAxNCAyNEgyNkMyOC4yMDkxIDI0IDMwIDIyLjIwOTEgMzAgMjBWMzBIMTBWMjBaTTEwIDEwQzEwIDEyLjIwOTEgMTEuNzkwOSAxNCAxNCAxNEgyNkMyOC4yMDkxIDE0IDMwIDEyLjIwOTEgMzAgMTBWMTBIMTBaIiBmaWxsPSIjRDRERDREIi8+Cjwvc3ZnPgo8L3N2Zz4K';">
                                        <?php if (count($asset_images) > 1): ?>
                                            <div class="image-counter"><?php echo ($index + 1) . ' / ' . count($asset_images); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (count($asset_images) > 1): ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#assetImageCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#assetImageCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <?php echo count($asset_images); ?> image(s) available
                                <?php if (count($asset_images) > 1): ?>
                                    - Use arrows or dots to navigate
                                <?php endif; ?>
                            </small>
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
                                <small class="text-muted">No images available</small>
                            </div>
                        </div>
                    <?php endif; ?>
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
            </div>
        </div>
        
    </div>
    </div> <!-- Close main wrapper -->
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
</body>
</html>
