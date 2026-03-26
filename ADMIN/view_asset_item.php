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
$item_sql = "SELECT ai.id, ai.asset_id, ai.property_no, ai.ics_par_no, ai.model, ai.serial_number, ai.description, ai.status, ai.date_counted, ai.image, ai.qr_code, ai.created_at, ai.last_updated, ai.value, ai.acquisition_date, ai.end_user,
                   a.description as asset_description, a.unit, a.quantity as asset_quantity, a.unit_cost,
                   ac.category_name, ac.category_code,
                   subcat.sub_category_name, subcat.sub_category_code,
                   o.office_name,
                   comp.processor, comp.ram_capacity, comp.storage_type, comp.storage_capacity, comp.model as computer_model,
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

// Helper functions for timeline
function getActionIcon($action) {
    $icons = [
        'Created' => 'plus-circle-fill',
        'Updated' => 'pencil-fill',
        'Deleted' => 'trash-fill',
        'Status Changed' => 'arrow-repeat',
        'Assigned' => 'person-check-fill',
        'Transferred' => 'arrow-left-right',
        'Maintenance' => 'tools',
        'Disposed' => 'x-circle-fill',
        'Inspected' => 'eye-fill',
        'Repaired' => 'wrench',
        'Calibrated' => 'speedometer2',
        'Cleaned' => 'brush-fill',
        'Tested' => 'check-circle-fill',
        'Approved' => 'check-square-fill',
        'Rejected' => 'x-square-fill'
    ];
    
    return $icons[$action] ?? 'circle-fill';
}

function getActionColor($action) {
    $colors = [
        'Created' => '#28a745',           // Green
        'Updated' => '#007bff',           // Blue
        'Deleted' => '#dc3545',           // Red
        'Status Changed' => '#dc3545',    // Red (changed from orange)
        'Assigned' => '#17a2b8',          // Cyan
        'Transferred' => '#6f42c1',        // Purple
        'Maintenance' => '#007bff',       // Blue
        'Disposed' => '#dc3545',           // Red
        'Inspected' => '#20c997',          // Teal
        'Repaired' => '#007bff',           // Blue
        'Calibrated' => '#6f42c1',        // Purple
        'Cleaned' => '#28a745',           // Green
        'Tested' => '#20c997',            // Teal
        'Approved' => '#28a745',          // Green
        'Rejected' => '#dc3545'           // Red
    ];
    
    return $colors[$action] ?? '#6c757d';        // Gray default
}

function formatTimelineDate($date) {
    $timestamp = strtotime($date);
    $now = time();
    $diff = $now - $timestamp;
    
    // If less than 24 hours, show relative time
    if ($diff < 86400) {
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes == 1 ? '1 minute ago' : $minutes . ' minutes ago';
        } else {
            $hours = floor($diff / 3600);
            return $hours == 1 ? '1 hour ago' : $hours . ' hours ago';
        }
    }
    
    // If within the last week, show day name
    if ($diff < 604800) {
        return date('l \a\t g:i A', $timestamp);
    }
    
    // Otherwise show full date
    return date('M j, Y \a\t g:i A', $timestamp);
}

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
                header('Location: ' . $_SERVER['REQUEST_URI']);
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
        'maintenance' => ['Maintenance', 'status-maintenance'],
        'disposed' => ['Disposed', 'status-disposed'],
        'red_tagged' => ['Red Tagged', 'status-red-tagged'],
        'borrowed' => ['Borrowed', 'status-borrowed'],
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
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    
    <!-- Timeline CSS -->
    <style>
    .timeline {
        position: relative;
        padding: 20px 0;
        margin: 0 auto;
        max-width: 100%;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        height: 100%;
        width: 3px;
        background: linear-gradient(180deg, var(--primary-color) 0%, var(--primary-hover) 100%);
        transform: translateX(-50%);
        border-radius: 3px;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 30px;
        width: 100%;
    }
    
    .timeline-left {
        padding-right: calc(50% + 40px);
        text-align: right;
    }
    
    .timeline-right {
        padding-left: calc(50% + 40px);
        text-align: left;
    }
    
    .timeline-dot {
        position: absolute;
        top: 10px;
        left: 50%;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: white;
        border: 3px solid var(--primary-color);
        transform: translateX(-50%);
        transition: all 0.3s ease;
        z-index: 1;
    }
    
    .timeline-dot:hover {
        transform: translateX(-50%) scale(1.2);
        box-shadow: 0 0 0 6px rgba(var(--primary-rgb), 0.2);
    }
    
    .timeline-dot-inner {
        width: 100%;
        height: 100%;
        background: var(--primary-color);
        border-radius: 50%;
        animation: pulse 2s infinite;
    }
    
    /* Action-specific colors */
    .timeline-dot.action-created { border-color: #28a745; }
    .timeline-dot.action-created .timeline-dot-inner { background: #28a745; }
    .timeline-dot.action-updated { border-color: #007bff; }
    .timeline-dot.action-updated .timeline-dot-inner { background: #007bff; }
    .timeline-dot.action-deleted { border-color: #dc3545; }
    .timeline-dot.action-deleted .timeline-dot-inner { background: #dc3545; }
    .timeline-dot.action-status-changed { border-color: #dc3545; }
    .timeline-dot.action-status-changed .timeline-dot-inner { background: #dc3545; }
    .timeline-dot.action-assigned { border-color: #17a2b8; }
    .timeline-dot.action-assigned .timeline-dot-inner { background: #17a2b8; }
    .timeline-dot.action-transferred { border-color: #6f42c1; }
    .timeline-dot.action-transferred .timeline-dot-inner { background: #6f42c1; }
    .timeline-dot.action-maintenance { border-color: #007bff; }
    .timeline-dot.action-maintenance .timeline-dot-inner { background: #007bff; }
    .timeline-dot.action-disposed { border-color: #dc3545; }
    .timeline-dot.action-disposed .timeline-dot-inner { background: #dc3545; }
    .timeline-dot.action-inspected { border-color: #20c997; }
    .timeline-dot.action-inspected .timeline-dot-inner { background: #20c997; }
    .timeline-dot.action-repaired { border-color: #007bff; }
    .timeline-dot.action-repaired .timeline-dot-inner { background: #007bff; }
    .timeline-dot.action-calibrated { border-color: #6f42c1; }
    .timeline-dot.action-calibrated .timeline-dot-inner { background: #6f42c1; }
    .timeline-dot.action-cleaned { border-color: #28a745; }
    .timeline-dot.action-cleaned .timeline-dot-inner { background: #28a745; }
    .timeline-dot.action-tested { border-color: #20c997; }
    .timeline-dot.action-tested .timeline-dot-inner { background: #20c997; }
    .timeline-dot.action-approved { border-color: #28a745; }
    .timeline-dot.action-approved .timeline-dot-inner { background: #28a745; }
    .timeline-dot.action-rejected { border-color: #dc3545; }
    .timeline-dot.action-rejected .timeline-dot-inner { background: #dc3545; }
    
    /* Action-specific content border colors */
    .timeline-dot.action-created ~ .timeline-content { border-left-color: #28a745; }
    .timeline-dot.action-updated ~ .timeline-content { border-left-color: #007bff; }
    .timeline-dot.action-deleted ~ .timeline-content { border-left-color: #dc3545; }
    .timeline-dot.action-status-changed ~ .timeline-content { border-left-color: #dc3545; }
    .timeline-dot.action-assigned ~ .timeline-content { border-left-color: #17a2b8; }
    .timeline-dot.action-transferred ~ .timeline-content { border-left-color: #6f42c1; }
    .timeline-dot.action-maintenance ~ .timeline-content { border-left-color: #007bff; }
    .timeline-dot.action-disposed ~ .timeline-content { border-left-color: #dc3545; }
    .timeline-dot.action-inspected ~ .timeline-content { border-left-color: #20c997; }
    .timeline-dot.action-repaired ~ .timeline-content { border-left-color: #007bff; }
    .timeline-dot.action-calibrated ~ .timeline-content { border-left-color: #6f42c1; }
    .timeline-dot.action-cleaned ~ .timeline-content { border-left-color: #28a745; }
    .timeline-dot.action-tested ~ .timeline-content { border-left-color: #20c997; }
    .timeline-dot.action-approved ~ .timeline-content { border-left-color: #28a745; }
    .timeline-dot.action-rejected ~ .timeline-content { border-left-color: #dc3545; }
    
    @keyframes pulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.1); opacity: 0.8; }
        100% { transform: scale(1); opacity: 1; }
    }
    
    .timeline-content {
        background: white;
        border-radius: var(--border-radius-lg);
        padding: 20px;
        box-shadow: var(--shadow);
        border-left: 4px solid var(--primary-color);
        position: relative;
        transition: all 0.3s ease;
    }
    
    .timeline-content:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
        border-left-color: var(--primary-hover);
    }
    
    .timeline-left .timeline-content::after {
        content: '';
        position: absolute;
        top: 15px;
        right: -10px;
        width: 0;
        height: 0;
        border-left: 10px solid white;
        border-top: 10px solid transparent;
        border-bottom: 10px solid transparent;
    }
    
    .timeline-right .timeline-content::after {
        content: '';
        position: absolute;
        top: 15px;
        left: -10px;
        width: 0;
        height: 0;
        border-right: 10px solid white;
        border-top: 10px solid transparent;
        border-bottom: 10px solid transparent;
    }
    
    .timeline-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px;
        gap: 15px;
    }
    
    .timeline-left .timeline-header {
        flex-direction: row-reverse;
    }
    
    .timeline-action {
        font-weight: 600;
        color: var(--primary-color);
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .timeline-action i {
        font-size: 1.1rem;
    }
    
    .timeline-date {
        font-size: 0.85rem;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 5px;
        white-space: nowrap;
    }
    
    .timeline-details {
        color: #495057;
        font-size: 0.9rem;
        line-height: 1.5;
        margin: 10px 0;
        padding: 10px;
        background: rgba(var(--primary-rgb), 0.05);
        border-radius: var(--border-radius);
        border-left: 3px solid rgba(var(--primary-rgb), 0.2);
    }
    
    .timeline-user {
        font-size: 0.8rem;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 5px;
        margin-top: 8px;
        font-style: italic;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .timeline::before {
            left: 30px;
        }
        
        .timeline-item {
            padding-left: 60px !important;
            padding-right: 20px !important;
            text-align: left !important;
        }
        
        .timeline-dot {
            left: 30px;
        }
        
        .timeline-content::after {
            left: -10px !important;
            border-right: 10px solid white !important;
            border-left: none !important;
        }
        
        .timeline-header {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 8px !important;
        }
        
        .timeline-action {
            font-size: 0.9rem;
        }
        
        .timeline-date {
            font-size: 0.8rem;
        }
    }
    
    @media (max-width: 576px) {
        .timeline-content {
            padding: 15px;
        }
        
        .timeline-action {
            font-size: 0.85rem;
        }
        
        .timeline-details {
            font-size: 0.85rem;
            padding: 8px;
        }
        
        .timeline-user {
            font-size: 0.75rem;
        }
    }
    
    /* Animation for timeline items */
    .timeline-item {
        opacity: 0;
        transform: translateY(20px);
        animation: fadeInUp 0.5s ease forwards;
    }
    
    .timeline-item:nth-child(1) { animation-delay: 0.1s; }
    .timeline-item:nth-child(2) { animation-delay: 0.2s; }
    .timeline-item:nth-child(3) { animation-delay: 0.3s; }
    .timeline-item:nth-child(4) { animation-delay: 0.4s; }
    .timeline-item:nth-child(5) { animation-delay: 0.5s; }
    .timeline-item:nth-child(6) { animation-delay: 0.6s; }
    .timeline-item:nth-child(7) { animation-delay: 0.7s; }
    .timeline-item:nth-child(8) { animation-delay: 0.8s; }
    .timeline-item:nth-child(9) { animation-delay: 0.9s; }
    .timeline-item:nth-child(10) { animation-delay: 1.0s; }
    
    @keyframes fadeInUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Asset Item Details';
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
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a href="asset_items_edit.php?id=<?php echo $item_id; ?>" class="dropdown-item">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                            </li>
                            <li>
                                <a href="asset_items.php?asset_id=<?php echo $asset_id; ?>" class="dropdown-item">
                                    <i class="bi bi-arrow-left"></i> Back to Items
                                </a>
                            </li>
                            <li>
                                <a href="print_inventory_tag.php?id=<?php echo $item_id; ?>" class="dropdown-item" target="_blank">
                                    <i class="bi bi-printer"></i> Print
                                </a>
                            </li>
                            <li>
                                <a href="export_asset_pdf.php?id=<?php echo $item_id; ?>" class="dropdown-item" target="_blank">
                                    <i class="bi bi-file-pdf"></i> Export PDF
                                </a>
                            </li>
                        </ul>
                    </div>
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
                                    <div class="detail-label">Description</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($item['description']); ?></div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="detail-label">Model</div>
                                    <div class="detail-value"><?php 
                                    $display_model = $item['model'] ? htmlspecialchars($item['model']) : '';
                                    if (empty($display_model)) {
                                        // Fallback to category-specific model if main model is empty
                                        if ($item['category_code'] === '030') {
                                            // Computer Equipment - use model from asset_computers table
                                            $display_model = $item['computer_model'] ? htmlspecialchars($item['computer_model']) : '<span class="text-muted">Not specified</span>';
                                        } elseif ($item['category_code'] === '07') {
                                            // Vehicles
                                            $display_model = $item['vehicle_model'] ? htmlspecialchars($item['vehicle_model']) : '<span class="text-muted">Not specified</span>';
                                        } elseif ($item['category_code'] === '04') {
                                            // Machinery & Equipment
                                            $display_model = $item['model_number'] ? htmlspecialchars($item['model_number']) : '<span class="text-muted">Not specified</span>';
                                        } elseif ($item['category_code'] === '05') {
                                            // Office Equipment
                                            $display_model = $item['office_model'] ? htmlspecialchars($item['office_model']) : '<span class="text-muted">Not specified</span>';
                                        } else {
                                            $display_model = '<span class="text-muted">Not specified</span>';
                                        }
                                    }
                                    echo $display_model;
                                    ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Serial Number</div>
                                    <div class="detail-value"><?php echo $item['serial_number'] ? htmlspecialchars($item['serial_number']) : '<span class="text-muted">Not specified</span>'; ?></div>
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
                                        
                                        // If both ics_no and par_no are empty, check for ics_par_no
                                        if (!$reference && !empty($item['ics_par_no'])) {
                                            $reference = htmlspecialchars($item['ics_par_no']);
                                        }
                                        
                                        echo $reference ? $reference : '<span class="text-muted">Not assigned</span>';
                                        ?>
                                    </div>
                                </div>
                               <div class="mb-3">
                                    <div class="detail-label">Value</div>
                                    <div class="detail-value text-value">₱<?php echo number_format($item['value'] ?? 0, 2); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Property No</div>
                                    <div class="detail-value"><?php echo $item['property_no'] ? htmlspecialchars($item['property_no']) : '<span class="text-muted">Not assigned</span>'; ?></div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="detail-label">Category</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($item['category_code'] . ' - ' . $item['category_name']); ?></div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Unit</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($item['unit']); ?></div>
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
                        <h5 class="mb-3"><i class="bi bi-geo-alt"></i> Location & Assignment</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Office</div>
                                    <div class="detail-value"><?php echo $item['office_name'] ? htmlspecialchars($item['office_name']) : '<span class="text-muted">Not assigned</span>'; ?></div>
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
                                    <div class="detail-label">Assigned Employee</div>
                                    <div class="detail-value">
                                        <?php if ($item['employee_no']): ?>
                                            <?php echo htmlspecialchars($item['firstname'] . ' ' . $item['lastname']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
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
                                    <div class="detail-label">Storage Capacity</div>
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
                                    <div class="detail-label">Model</div>
                                    <div class="detail-value"><?php 
                                    $display_model = $item['model'] ? htmlspecialchars($item['model']) : '';
                                    if (empty($display_model)) {
                                        // Fallback to category-specific model if main model is empty
                                        if ($item['category_code'] === '030') {
                                            // Computer Equipment - use model from asset_computers table
                                            $display_model = $item['computer_model'] ? htmlspecialchars($item['computer_model']) : '<span class="text-muted">Not specified</span>';
                                        } elseif ($item['category_code'] === '07') {
                                            // Vehicles
                                            $display_model = $item['vehicle_model'] ? htmlspecialchars($item['vehicle_model']) : '<span class="text-muted">Not specified</span>';
                                        } elseif ($item['category_code'] === '04') {
                                            // Machinery & Equipment
                                            $display_model = $item['model_number'] ? htmlspecialchars($item['model_number']) : '<span class="text-muted">Not specified</span>';
                                        } elseif ($item['category_code'] === '05') {
                                            // Office Equipment
                                            $display_model = $item['office_model'] ? htmlspecialchars($item['office_model']) : '<span class="text-muted">Not specified</span>';
                                        } else {
                                            $display_model = '<span class="text-muted">Not specified</span>';
                                        }
                                    }
                                    echo $display_model;
                                    ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Peripherals Section -->
                    <?php 
                    // Fetch peripherals for this asset
                    $peripherals_sql = "SELECT p.id, p.name, p.model, p.serial_number, p.status, p.created_at 
                                       FROM peripherals p 
                                       WHERE p.asset_item_id = ? 
                                       ORDER BY p.name, p.created_at";
                    $peripherals_stmt = $conn->prepare($peripherals_sql);
                    $peripherals_stmt->bind_param("i", $item['id']);
                    $peripherals_stmt->execute();
                    $peripherals_result = $peripherals_stmt->get_result();
                    $peripherals = [];
                    while ($row = $peripherals_result->fetch_assoc()) {
                        $peripherals[] = $row;
                    }
                    $peripherals_stmt->close();
                    
                    if (!empty($peripherals)): ?>
                    <div class="detail-section">
                        <h5 class="mb-3"><i class="bi bi-pc-display"></i> Peripherals</h5>
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Name</th>
                                                <th>Model</th>
                                                <th>Serial Number</th>
                                                <th>Status</th>
                                                <th>Added Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($peripherals as $peripheral): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($peripheral['name']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php echo $peripheral['model'] ? htmlspecialchars($peripheral['model']) : '<span class="text-muted">Not specified</span>'; ?>
                                                </td>
                                                <td>
                                                    <?php echo $peripheral['serial_number'] ? htmlspecialchars($peripheral['serial_number']) : '<span class="text-muted">Not specified</span>'; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    switch ($peripheral['status']) {
                                                        case 'serviceable':
                                                            $status_class = 'status-serviceable';
                                                            break;
                                                        case 'unserviceable':
                                                            $status_class = 'status-unserviceable';
                                                            break;
                                                        case 'red_tagged':
                                                            $status_class = 'status-red-tagged';
                                                            break;
                                                        case 'no_tag':
                                                            $status_class = 'status-no-tag';
                                                            break;
                                                        case 'disposed':
                                                            $status_class = 'status-disposed';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="status-badge <?php echo $status_class; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $peripheral['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo date('M d, Y', strtotime($peripheral['created_at'])); ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="mt-3">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="small text-muted">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Total Peripherals: <strong><?php echo count($peripherals); ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <?php
                                            // Count by status
                                            $status_counts = [];
                                            foreach ($peripherals as $peripheral) {
                                                $status_counts[$peripheral['status']] = ($status_counts[$peripheral['status']] ?? 0) + 1;
                                            }
                                            
                                            foreach ($status_counts as $status => $count) {
                                                $status_class = '';
                                                switch ($status) {
                                                    case 'serviceable':
                                                        $status_class = 'status-serviceable';
                                                        break;
                                                    case 'unserviceable':
                                                        $status_class = 'status-unserviceable';
                                                        break;
                                                    case 'red_tagged':
                                                        $status_class = 'status-red-tagged';
                                                        break;
                                                    case 'no_tag':
                                                        $status_class = 'status-no-tag';
                                                        break;
                                                    case 'disposed':
                                                        $status_class = 'status-disposed';
                                                        break;
                                                }
                                                echo '<span class="status-badge ' . $status_class . ' me-2">' . ucfirst(str_replace('_', ' ', $status)) . ': ' . $count . '</span>';
                                            }
                                            ?>
                                        </div>
                                    </div>
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
                    <?php if ($item['category_code'] === '02'): ?>
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
                    <?php if ($item['category_code'] === '04'): ?>
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
                    <?php if ($item['category_code'] === '05'): ?>
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
                    <?php if ($item['category_code'] === '06'): ?>
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
                    <?php if ($item['category_code'] === '03'): ?>
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
                
                <!-- History Timeline -->
                <?php if (!empty($item_history)): ?>
                <div class="detail-card">
                    <h5 class="mb-4"><i class="bi bi-clock-history"></i> Item History Timeline</h5>
                    
                    <div class="timeline">
                        <?php foreach ($item_history as $index => $history): ?>
                            <div class="timeline-item <?php echo $index % 2 === 0 ? 'timeline-left' : 'timeline-right'; ?>">
                                <div class="timeline-dot action-<?php echo strtolower(str_replace(' ', '-', $history['action'])); ?>">
                                    <div class="timeline-dot-inner"></div>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <div class="timeline-action" style="color: <?php echo getActionColor($history['action']); ?>;">
                                            <i class="bi bi-<?php echo getActionIcon($history['action']); ?>"></i>
                                            <?php echo htmlspecialchars($history['action']); ?>
                                        </div>
                                        <div class="timeline-date">
                                            <i class="bi bi-clock"></i>
                                            <?php echo formatTimelineDate($history['created_at']); ?>
                                        </div>
                                    </div>
                                    <?php if ($history['details']): ?>
                                        <div class="timeline-details">
                                            <?php echo htmlspecialchars($history['details']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($history['user_name'] ?? null)): ?>
                                        <div class="timeline-user">
                                            <i class="bi bi-person-circle"></i>
                                            <?php echo htmlspecialchars($history['user_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
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
                            <a href="../uploads/qr_codes/<?php echo htmlspecialchars($item['qr_code']); ?>" 
                               download="qr_code_<?php echo htmlspecialchars($item['property_no'] ?: 'asset_' . $item['id']); ?>.png"
                               style="text-decoration: none; display: inline-block; cursor: pointer;"
                               title="Click to download QR Code">
                                <img src="../uploads/qr_codes/<?php echo htmlspecialchars($item['qr_code']); ?>" 
                                     alt="QR Code" 
                                     class="img-fluid rounded"
                                     style="max-width: 150px; max-height: 150px; transition: transform 0.2s ease;"
                                     onmouseover="this.style.transform='scale(1.05)'"
                                     onmouseout="this.style.transform='scale(1)'">
                            </a>
                        <?php else: ?>
                            <i class="bi bi-qr-code-scan fs-1 text-muted"></i>
                        <?php endif; ?>
                    </div>
                    <a href="print_qrcode.php?id=<?php echo $item['id']; ?>" 
                       target="_blank"
                       class="btn btn-outline-primary btn-sm"
                       style="text-decoration: none; display: inline-flex; align-items: center; gap: 5px;"
                       title="Print QR Code">
                        <i class="bi bi-printer"></i>
                        Print
                    </a>
                    <p class="mt-2 mb-0 text-muted">Property No: <?php echo $item['property_no'] ? htmlspecialchars($item['property_no']) : 'Not assigned'; ?></p>
                </div>
                
                <!-- Actions -->
                <div class="detail-card">
                    <h5 class="mb-3"><i class="bi bi-gear"></i> Actions</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($item['status'] === 'no_tag'): ?>
                            <!-- Show Create Tag button for no_tag assets -->
                            <a href="create_tag.php?id=<?php echo $item_id; ?>" class="btn btn-primary">
                                <i class="bi bi-tag"></i> Create Tag
                            </a>
                        <?php elseif ($item['status'] === 'serviceable'): ?>
                            <!-- Show Transfer and IIRUP buttons for serviceable assets only -->
                            <button class="btn btn-outline-success" onclick="transferItem()">
                                <i class="bi bi-arrow-left-right"></i> Transfer Item
                            </button>
                            <button class="btn btn-outline-info" onclick="addToIirup()">
                                <i class="bi bi-file-earmark-text"></i> Add to IIRUP
                            </button>
                        <?php elseif ($item['status'] === 'red_tagged'): ?>
                            <!-- Show Dispose button for red_tagged assets -->
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#disposeModal" 
                                    onclick="setDisposalData(<?php echo $item_id; ?>, '<?php echo htmlspecialchars($item['description']); ?>', '<?php echo htmlspecialchars($item['property_no'] ?? ''); ?>')">
                                <i class="bi bi-trash"></i> Dispose Asset
                            </button>
                        <?php elseif ($item['status'] === 'unserviceable'): ?>
                            <!-- Show Create Red Tag button for unserviceable assets -->
                            <?php 
                            // Check if multiple components are unserviceable
                            $unserviceable_components = [];
                            if ($item['status'] === 'unserviceable') {
                                $unserviceable_components[] = 'main_asset';
                            }
                            if ($item['monitor_status'] === 'unserviceable') {
                                $unserviceable_components[] = 'monitor';
                            }
                            if ($item['ups_status'] === 'unserviceable') {
                                $unserviceable_components[] = 'ups';
                            }
                            
                            $show_redtag_modal = count($unserviceable_components) > 1;
                            ?>
                            
                            <?php if ($show_redtag_modal): ?>
                            <!-- Show modal button when multiple components are unserviceable -->
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#redtagComponentModal">
                                <i class="bi bi-exclamation-triangle"></i> Create Red Tag
                            </button>
                            <?php else: ?>
                            <!-- Show direct link when only one component is unserviceable -->
                            <?php 
                            $component_type = !empty($unserviceable_components) ? $unserviceable_components[0] : 'main_asset';
                            $component_description = '';
                            
                            if ($component_type === 'monitor' && !empty($item['monitor_name'])) {
                                $component_description = 'Monitor - ' . $item['monitor_name'];
                            } elseif ($component_type === 'ups' && !empty($item['ups_name'])) {
                                $component_description = 'UPS - ' . $item['ups_name'];
                            } else {
                                $component_description = $item['description'];
                            }
                            ?>
                            <a href="create_redtag.php?asset_id=<?php echo $item['id']; ?>&description=<?php echo urlencode($component_description); ?>&property_no=<?php echo urlencode($item['property_no'] ?? ''); ?>&inventory_tag=<?php echo urlencode($item['inventory_tag'] ?? ''); ?>&acquisition_date=<?php echo $item['acquisition_date']; ?>&value=<?php echo $item['value']; ?>&office_name=<?php echo urlencode($item['office_name'] ?? ''); ?>&component_type=<?php echo $component_type; ?>&component_description=<?php echo urlencode($component_description); ?>" class="btn btn-danger">
                                <i class="bi bi-exclamation-triangle"></i> Create Red Tag
                            </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- No action buttons for other statuses -->
                            <div class="text-muted text-center">
                                <i class="bi bi-info-circle"></i> No actions available for this asset status
                            </div>
                        <?php endif; ?>
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
    
    <!-- Disposal Confirmation Modal -->
    <div class="modal fade" id="disposeModal" tabindex="-1" aria-labelledby="disposeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="disposeModalLabel">
                        <i class="bi bi-exclamation-triangle text-warning"></i> Confirm Asset Disposal
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="disposeForm" method="POST" action="process_disposal.php">
                        <input type="hidden" name="asset_item_id" id="disposeAssetItemId">
                        <input type="hidden" name="action" value="dispose">
                        <input type="hidden" name="csrf_token" value="<?php echo bin2hex(random_bytes(32)); ?>">
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Warning:</strong> This action cannot be undone. The asset will be marked as disposed and removed from active inventory.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Asset Description:</strong></label>
                            <p class="form-control-plaintext" id="disposeDescription"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Property No:</strong></label>
                            <p class="form-control-plaintext" id="disposePropertyNo"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label for="disposalReason" class="form-label"><strong>Disposal Reason:</strong></label>
                            <textarea class="form-control" id="disposalReason" name="disposal_reason" rows="3" 
                                      placeholder="Enter reason for disposal..." required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="disposalDate" class="form-label"><strong>Disposal Date:</strong></label>
                            <input type="date" class="form-control" id="disposalDate" name="disposal_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-warning" onclick="confirmDisposal()">
                        <i class="bi bi-trash"></i> Confirm Disposal
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- IIRUP Component Selection Modal -->
    <div class="modal fade" id="iirupComponentModal" tabindex="-1" aria-labelledby="iirupComponentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="iirupComponentModalLabel">
                        <i class="bi bi-file-earmark-text text-info"></i> Select Component for IIRUP Form
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Choose which component you want to add to the IIRUP form:</p>
                    
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-lg" onclick="addAssetToIirup()">
                            <i class="bi bi-box-seam"></i>
                            <div class="mt-2">
                                <strong>Main Asset Item</strong>
                                <div class="small text-muted"><?php echo htmlspecialchars($item['description']); ?></div>
                            </div>
                        </button>
                        
                        <!-- Dynamic Peripherals Display -->
                        <?php if (!empty($peripherals)): ?>
                            <?php foreach ($peripherals as $index => $peripheral): ?>
                                <?php 
                                $is_available = in_array($peripheral['status'], ['serviceable', null]) || $peripheral['status'] === '';
                                $button_class = $is_available ? 'btn-outline-success' : 'btn-outline-secondary';
                                $button_disabled = $is_available ? '' : 'disabled';
                                $status_text = $is_available ? 'Available for IIRUP' : 'Not available (' . ucfirst(str_replace('_', ' ', $peripheral['status'] ?: 'no_status')) . ')';
                                $status_color = $is_available ? 'text-success' : 'text-warning';
                                
                                // Choose appropriate icon based on peripheral name
                                $icon_class = 'bi-pc-display'; // default
                                if (stripos($peripheral['name'], 'monitor') !== false) {
                                    $icon_class = 'bi-display';
                                } elseif (stripos($peripheral['name'], 'keyboard') !== false) {
                                    $icon_class = 'bi-keyboard';
                                } elseif (stripos($peripheral['name'], 'mouse') !== false) {
                                    $icon_class = 'bi-mouse';
                                } elseif (stripos($peripheral['name'], 'ups') !== false) {
                                    $icon_class = 'bi-battery-charging';
                                } elseif (stripos($peripheral['name'], 'printer') !== false) {
                                    $icon_class = 'bi-printer';
                                } elseif (stripos($peripheral['name'], 'scanner') !== false) {
                                    $icon_class = 'bi-upc-scan';
                                } elseif (stripos($peripheral['name'], 'camera') !== false) {
                                    $icon_class = 'bi-camera';
                                } elseif (stripos($peripheral['name'], 'speaker') !== false || stripos($peripheral['name'], 'audio') !== false) {
                                    $icon_class = 'bi-speaker';
                                }
                                ?>
                                <button type="button" class="btn <?php echo $button_class; ?> btn-lg" 
                                        onclick="addPeripheralToIirup(<?php echo $index; ?>)"
                                        data-peripheral-id="<?php echo htmlspecialchars($peripheral['id']); ?>"
                                        data-peripheral-name="<?php echo htmlspecialchars($peripheral['name']); ?>"
                                        data-peripheral-model="<?php echo htmlspecialchars($peripheral['model'] ?? ''); ?>"
                                        data-peripheral-serial="<?php echo htmlspecialchars($peripheral['serial_number'] ?? ''); ?>"
                                        data-peripheral-status="<?php echo htmlspecialchars($peripheral['status']); ?>"
                                        <?php echo $button_disabled; ?>>
                                    <i class="bi <?php echo $icon_class; ?>"></i>
                                    <div class="mt-2">
                                        <strong><?php echo htmlspecialchars($peripheral['name']); ?></strong>
                                        <?php if (!empty($peripheral['model'])): ?>
                                            <div class="small text-muted"><?php echo htmlspecialchars($peripheral['model']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($peripheral['serial_number'])): ?>
                                            <div class="small text-muted">S/N: <?php echo htmlspecialchars($peripheral['serial_number']); ?></div>
                                        <?php endif; ?>
                                        <div class="small <?php echo $status_color; ?>"><?php echo $status_text; ?></div>
                                    </div>
                                </button>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle me-2"></i>
                                No peripherals available for this asset.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Red Tag Component Selection Modal -->
    <div class="modal fade" id="redtagComponentModal" tabindex="-1" aria-labelledby="redtagComponentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="redtagComponentModalLabel">
                        <i class="bi bi-exclamation-triangle text-danger"></i> Select Component for Red Tag
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Choose which component you want to create a red tag for:</p>
                    
                    <div class="d-grid gap-2">
                        <?php if ($item['status'] === 'unserviceable'): ?>
                        <button type="button" class="btn btn-outline-primary btn-lg" onclick="addAssetToRedtag()">
                            <i class="bi bi-box-seam"></i>
                            <div class="mt-2">
                                <strong>Main Asset Item</strong>
                                <div class="small text-muted"><?php echo htmlspecialchars($item['description']); ?></div>
                                <div class="small text-danger">Unserviceable</div>
                            </div>
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($item['sub_category_name'] === 'Desktop Computers' && $has_monitor_details && $item['monitor_status'] === 'unserviceable'): ?>
                        <button type="button" class="btn btn-outline-success btn-lg" onclick="addMonitorToRedtag()">
                            <i class="bi bi-display"></i>
                            <div class="mt-2">
                                <strong>Monitor</strong>
                                <div class="small text-muted"><?php echo htmlspecialchars($item['monitor_name'] ?: 'Monitor'); ?></div>
                                <div class="small text-danger">Unserviceable</div>
                            </div>
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($item['sub_category_name'] === 'Desktop Computers' && $has_ups_details && $item['ups_status'] === 'unserviceable'): ?>
                        <button type="button" class="btn btn-outline-warning btn-lg" onclick="addUpsToRedtag()">
                            <i class="bi bi-battery-charging"></i>
                            <div class="mt-2">
                                <strong>UPS</strong>
                                <div class="small text-muted"><?php echo htmlspecialchars($item['ups_name'] ?: 'UPS'); ?></div>
                                <div class="small text-danger">Unserviceable</div>
                            </div>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    
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
            // Show component selection modal
            const modal = new bootstrap.Modal(document.getElementById('iirupComponentModal'));
            modal.show();
        }
        
        function addAssetToIirup() {
            // Close the modal
            bootstrap.Modal.getInstance(document.getElementById('iirupComponentModal')).hide();
            
            // Prepare main asset data for IIRUP form
            const assetData = {
                id: <?php echo $item_id; ?>,
                description: '<?php echo addslashes($item['description']); ?>',
                property_no: '<?php echo addslashes($item['property_no'] ?? ''); ?>',
                inventory_tag: '<?php echo addslashes($item['inventory_tag'] ?? ''); ?>',
                acquisition_date: '<?php echo $item['acquisition_date']; ?>',
                value: '<?php echo $item['value']; ?>',
                unit_cost: '<?php echo $item['unit_cost']; ?>',
                office_name: '<?php echo addslashes($item['office_name'] ?? ''); ?>',
                employee_name: '<?php echo addslashes(trim(($item['firstname'] ?? '') . ' ' . ($item['lastname'] ?? ''))); ?>',
                category_name: '<?php echo addslashes($item['category_name'] ?? ''); ?>',
                category_code: '<?php echo addslashes($item['category_code'] ?? ''); ?>',
                asset_description: '<?php echo addslashes($item['asset_description']); ?>',
                unit: '<?php echo addslashes($item['unit']); ?>'
            };
            
            openIirupForm(assetData);
        }
        
        function addPeripheralToIirup(peripheralIndex) {
            // Get peripheral data from the button attributes
            const button = document.querySelector(`[onclick="addPeripheralToIirup(${peripheralIndex})"]`);
            const peripheralId = button.getAttribute('data-peripheral-id');
            const peripheralName = button.getAttribute('data-peripheral-name');
            const peripheralModel = button.getAttribute('data-peripheral-model');
            const peripheralSerial = button.getAttribute('data-peripheral-serial');
            const peripheralStatus = button.getAttribute('data-peripheral-status');
            
            // Close the modal
            bootstrap.Modal.getInstance(document.getElementById('iirupComponentModal')).hide();
            
            // Prepare peripheral data for IIRUP form
            const peripheralData = {
                id: peripheralId, // Use peripheral ID instead of asset ID
                asset_id: <?php echo $item_id; ?>, // Keep asset ID for reference
                description: peripheralName + (peripheralModel ? ' - ' + peripheralModel : ''),
                property_no: '<?php echo addslashes($item['property_no'] ?? ''); ?>',
                inventory_tag: '<?php echo addslashes($item['inventory_tag'] ?? ''); ?>',
                acquisition_date: '<?php echo $item['acquisition_date']; ?>',
                value: '<?php echo $item['value']; ?>',
                unit_cost: '<?php echo $item['unit_cost']; ?>',
                office_name: '<?php echo addslashes($item['office_name'] ?? ''); ?>',
                employee_name: '<?php echo addslashes(trim(($item['firstname'] ?? '') . ' ' . ($item['lastname'] ?? ''))); ?>',
                category_name: '<?php echo addslashes($item['category_name'] ?? ''); ?>',
                category_code: '<?php echo addslashes($item['category_code'] ?? ''); ?>',
                asset_description: peripheralName,
                unit: '<?php echo addslashes($item['unit']); ?>',
                component_type: 'peripheral',
                peripheral_name: peripheralName,
                peripheral_model: peripheralModel,
                peripheral_serial_number: peripheralSerial,
                peripheral_status: peripheralStatus
            };
            
            openIirupForm(peripheralData);
        }
        
        function addUpsToIirup() {
            // Close the modal
            bootstrap.Modal.getInstance(document.getElementById('iirupComponentModal')).hide();
            
            // Prepare UPS data for IIRUP form
            const upsData = {
                id: <?php echo $item_id; ?>,
                description: '<?php echo addslashes('UPS - ' . ($item['ups_name'] ?: $item['description'])); ?>',
                property_no: '<?php echo addslashes($item['property_no'] ?? ''); ?>',
                inventory_tag: '<?php echo addslashes($item['inventory_tag'] ?? ''); ?>',
                acquisition_date: '<?php echo $item['acquisition_date']; ?>',
                value: '<?php echo $item['ups_value'] ?? $item['value']; ?>',
                unit_cost: '<?php echo $item['ups_unit_cost'] ?? $item['unit_cost']; ?>',
                office_name: '<?php echo addslashes($item['office_name'] ?? ''); ?>',
                employee_name: '<?php echo addslashes(trim(($item['firstname'] ?? '') . ' ' . ($item['lastname'] ?? ''))); ?>',
                category_name: 'Computer Equipment',
                category_code: '030',
                asset_description: '<?php echo addslashes($item['ups_model'] ?: 'UPS'); ?>',
                unit: 'SET',
                component_type: 'ups'
            };
            
            openIirupForm(upsData);
        }
        
        function openIirupForm(data) {
            // Create URL with component data
            const params = new URLSearchParams();
            params.append('asset_id', data.id);
            params.append('description', data.description);
            params.append('property_no', data.property_no);
            params.append('inventory_tag', data.inventory_tag);
            params.append('acquisition_date', data.acquisition_date);
            params.append('value', data.value);
            params.append('unit_cost', data.unit_cost);
            params.append('office_name', data.office_name);
            params.append('employee_name', data.employee_name || '');
            params.append('category_name', data.category_name);
            params.append('category_code', data.category_code);
            params.append('asset_description', data.asset_description);
            params.append('unit', data.unit);
            params.append('component_type', data.component_type || 'main_asset');
            params.append('auto_fill', 'true');
            
            // Open IIRUP form with component data
            window.open('iirup_form.php?' + params.toString(), '_blank');
        }
        
        // Set disposal data in modal
        window.setDisposalData = function(assetItemId, description, propertyNo) {
            document.getElementById('disposeAssetItemId').value = assetItemId;
            document.getElementById('disposeDescription').textContent = description;
            document.getElementById('disposePropertyNo').textContent = propertyNo || 'Not assigned';
            
            // Reset form fields
            document.getElementById('disposalReason').value = '';
            document.getElementById('disposalDate').value = new Date().toISOString().split('T')[0];
        };
        
        // Confirm disposal and submit form
        window.confirmDisposal = function() {
            const reason = document.getElementById('disposalReason').value.trim();
            const date = document.getElementById('disposalDate').value;
            
            if (!reason) {
                alert('Please enter a disposal reason.');
                return;
            }
            
            if (!date) {
                alert('Please select a disposal date.');
                return;
            }
            
            // Submit the form
            document.getElementById('disposeForm').submit();
        };
        
        // Red Tag component selection functions
        function addAssetToRedtag() {
            // Close the modal
            bootstrap.Modal.getInstance(document.getElementById('redtagComponentModal')).hide();
            
            // Prepare main asset data for Red Tag form
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
                unit: '<?php echo addslashes($item['unit']); ?>',
                component_type: 'main_asset'
            };
            
            openRedtagForm(assetData);
        }
        
        function addMonitorToRedtag() {
            // Close the modal
            bootstrap.Modal.getInstance(document.getElementById('redtagComponentModal')).hide();
            
            // Prepare monitor data for Red Tag form
            const monitorData = {
                id: <?php echo $item_id; ?>,
                description: '<?php echo addslashes('Monitor - ' . ($item['monitor_name'] ?: $item['description'])); ?>',
                property_no: '<?php echo addslashes($item['property_no'] ?? ''); ?>',
                inventory_tag: '<?php echo addslashes($item['inventory_tag'] ?? ''); ?>',
                acquisition_date: '<?php echo $item['acquisition_date']; ?>',
                value: '<?php echo $item['monitor_value'] ?? $item['value']; ?>',
                unit_cost: '<?php echo $item['monitor_unit_cost'] ?? $item['unit_cost']; ?>',
                office_name: '<?php echo addslashes($item['office_name'] ?? ''); ?>',
                category_name: 'Computer Equipment',
                category_code: '030',
                asset_description: '<?php echo addslashes($item['monitor_model'] ?: 'Monitor'); ?>',
                unit: 'SET',
                component_type: 'monitor'
            };
            
            openRedtagForm(monitorData);
        }
        
        function addUpsToRedtag() {
            // Close the modal
            bootstrap.Modal.getInstance(document.getElementById('redtagComponentModal')).hide();
            
            // Prepare UPS data for Red Tag form
            const upsData = {
                id: <?php echo $item_id; ?>,
                description: '<?php echo addslashes('UPS - ' . ($item['ups_name'] ?: $item['description'])); ?>',
                property_no: '<?php echo addslashes($item['property_no'] ?? ''); ?>',
                inventory_tag: '<?php echo addslashes($item['inventory_tag'] ?? ''); ?>',
                acquisition_date: '<?php echo $item['acquisition_date']; ?>',
                value: '<?php echo $item['ups_value'] ?? $item['value']; ?>',
                unit_cost: '<?php echo $item['ups_unit_cost'] ?? $item['unit_cost']; ?>',
                office_name: '<?php echo addslashes($item['office_name'] ?? ''); ?>',
                category_name: 'Computer Equipment',
                category_code: '030',
                asset_description: '<?php echo addslashes($item['ups_model'] ?: 'UPS'); ?>',
                unit: 'SET',
                component_type: 'ups'
            };
            
            openRedtagForm(upsData);
        }
        
        function openRedtagForm(data) {
            // Create URL with component data
            const params = new URLSearchParams();
            params.append('asset_id', data.id);
            params.append('description', data.description);
            params.append('property_no', data.property_no);
            params.append('inventory_tag', data.inventory_tag);
            params.append('acquisition_date', data.acquisition_date);
            params.append('value', data.value);
            params.append('unit_cost', data.unit_cost);
            params.append('office_name', data.office_name);
            params.append('category_name', data.category_name);
            params.append('category_code', data.category_code);
            params.append('asset_description', data.asset_description);
            params.append('unit', data.unit);
            params.append('component_type', data.component_type || 'main_asset');
            params.append('auto_fill', 'true');
            
            // Open Red Tag form with component data
            window.open('create_redtag.php?' + params.toString(), '_blank');
        }
    </script>
</body>
</html>
