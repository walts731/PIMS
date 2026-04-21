<?php
session_start();
require_once '../config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query)) {
    echo json_encode([
        'success' => true,
        'assets' => []
    ]);
    exit();
}

try {
    // Debug: Log the search query
    error_log("Search query: " . $query);
    
    // Search for assets with desktop computer specifications
    $search_sql = "SELECT ai.id, ai.description, ai.property_no, ai.inventory_tag, ai.value, 
                   ai.acquisition_date, ai.office_name, ai.model, ai.serial_number,
                   ac.category_name, ac.category_code,
                   subcat.sub_category_name, subcat.sub_category_code,
                   e.firstname, e.lastname,
                   comp.processor, comp.ram_capacity, comp.storage_type, comp.storage_capacity, 
                   comp.operating_system, comp.brand as computer_brand,
                   desk.monitor_name, desk.monitor_model, desk.monitor_serial_number, desk.monitor_status,
                   desk.ups_name, desk.ups_model, desk.ups_serial_number, desk.ups_status
            FROM asset_items ai 
            LEFT JOIN assets a ON ai.asset_id = a.id 
            LEFT JOIN asset_categories ac ON a.asset_categories_id = ac.id 
            LEFT JOIN asset_sub_categories subcat ON ai.asset_subcategory_id = subcat.id
            LEFT JOIN employees e ON ai.employee_id = e.id 
            LEFT JOIN asset_computers comp ON ai.id = comp.asset_item_id
            LEFT JOIN asset_desktop_computers desk ON ai.id = desk.asset_item_id
            WHERE (ai.description LIKE ? OR ai.property_no LIKE ? OR ai.inventory_tag LIKE ? OR ai.model LIKE ?)
            AND ai.status = 'serviceable'
            AND ai.employee_id IS NOT NULL
            AND ac.category_code NOT IN ('LND', 'OInfra', 'Buildings', 'Land Imp')
            ORDER BY ai.description, ai.property_no
            LIMIT 20";
    
    $search_param = "%{$query}%";
    $stmt = $conn->prepare($search_sql);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        throw new Exception("Database prepare failed");
    }
    
    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    error_log("Search result count: " . $result->num_rows);
    
    $assets = [];
    while ($row = $result->fetch_assoc()) {
        $asset = [
            'id' => $row['id'],
            'description' => $row['description'],
            'property_no' => $row['property_no'],
            'inventory_tag' => $row['inventory_tag'],
            'value' => $row['value'],
            'acquisition_date' => $row['acquisition_date'],
            'office_name' => $row['office_name'],
            'model' => $row['model'],
            'serial_number' => $row['serial_number'],
            'category_name' => $row['category_name'],
            'category_code' => $row['category_code'],
            'sub_category_name' => $row['sub_category_name'],
            'sub_category_code' => $row['sub_category_code'],
            'employee_name' => trim($row['firstname'] . ' ' . $row['lastname']),
            'status' => 'serviceable' // Since we're filtering for serviceable assets
        ];
        
        // Add desktop computer specifications if available
        if ($row['sub_category_name'] === 'COMPUTER DESKTOP') {
            $asset['desktop_specs'] = [
                'processor' => $row['processor'],
                'ram_capacity' => $row['ram_capacity'],
                'storage_type' => $row['storage_type'],
                'storage_capacity' => $row['storage_capacity'],
                'operating_system' => $row['operating_system'],
                'computer_brand' => $row['computer_brand'],
                'monitor_name' => $row['monitor_name'],
                'monitor_model' => $row['monitor_model'],
                'monitor_serial_number' => $row['monitor_serial_number'],
                'monitor_status' => $row['monitor_status'],
                'ups_name' => $row['ups_name'],
                'ups_model' => $row['ups_model'],
                'ups_serial_number' => $row['ups_serial_number'],
                'ups_status' => $row['ups_status']
            ];
        }
        
        // Create display text with specifications
        $display_text = $row['description'];
        
        if ($row['sub_category_name'] === 'COMPUTER DESKTOP' && !empty($row['processor'])) {
            $specs = [];
            if (!empty($row['processor'])) $specs[] = $row['processor'];
            if (!empty($row['ram_capacity'])) $specs[] = $row['ram_capacity'] . 'GB RAM';
            if (!empty($row['storage_capacity'])) $specs[] = $row['storage_capacity'] . 'GB ' . $row['storage_type'];
            if (!empty($row['operating_system'])) $specs[] = $row['operating_system'];
            
            if (!empty($specs)) {
                $display_text .= ' (' . implode(', ', $specs) . ')';
            }
        }
        
        $asset['display_text'] = $display_text;
        $assets[] = $asset;
    }
    
    echo json_encode([
        'success' => true,
        'assets' => $assets
    ]);
    
} catch (Exception $e) {
    error_log("Search error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>
