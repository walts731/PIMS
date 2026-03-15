<?php
/**
 * Asset Specific Tables Manager
 * Handles operations for asset category-specific tables
 */

class AssetSpecificManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Get the specific table name for a given category
     */
    public function getSpecificTableName($category_code) {
        $table_map = [
            'FF' => 'asset_furniture',
            'CE' => 'asset_computers',
            'VH' => 'asset_vehicles',
            'ME' => 'asset_machinery',
            'BI' => 'asset_buildings',
            'LD' => 'asset_land',
            'SW' => 'asset_software',
            'OE' => 'asset_office_equipment'
        ];
        
        return $table_map[$category_code] ?? null;
    }
    
    /**
     * Get specific asset data based on category
     */
    public function getSpecificAssetData($asset_id, $category_code) {
        $table_name = $this->getSpecificTableName($category_code);
        if (!$table_name) {
            return null;
        }
        
        try {
            $sql = "SELECT * FROM $table_name WHERE asset_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $asset_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            
            // Remove the 'id' field to prevent overwriting the main asset ID
            if ($data && isset($data['id'])) {
                unset($data['id']);
            }
            
            return $data;
        } catch (Exception $e) {
            error_log("Error fetching specific asset data: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Save specific asset data
     */
    public function saveSpecificAssetData($asset_id, $category_code, $data, $user_id) {
        $table_name = $this->getSpecificTableName($category_code);
        if (!$table_name) {
            return false;
        }
        
        try {
            // Check if record exists
            $check_sql = "SELECT id FROM $table_name WHERE asset_id = ?";
            $check_stmt = $this->conn->prepare($check_sql);
            $check_stmt->bind_param("i", $asset_id);
            $check_stmt->execute();
            $exists = $check_stmt->get_result()->num_rows > 0;
            
            if ($exists) {
                // Update existing record
                return $this->updateSpecificAssetData($asset_id, $table_name, $data, $user_id);
            } else {
                // Insert new record
                return $this->insertSpecificAssetData($asset_id, $table_name, $data, $user_id);
            }
        } catch (Exception $e) {
            error_log("Error saving specific asset data: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Insert new specific asset data
     */
    private function insertSpecificAssetData($asset_id, $table_name, $data, $user_id) {
        $data['asset_id'] = $asset_id;
        $data['created_by'] = $user_id;
        $data['updated_by'] = $user_id;
        
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $types = str_repeat('s', count($columns));
        
        $sql = "INSERT INTO $table_name (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...array_values($data));
        
        return $stmt->execute();
    }
    
    /**
     * Update existing specific asset data
     */
    private function updateSpecificAssetData($asset_id, $table_name, $data, $user_id) {
        $data['updated_by'] = $user_id;
        unset($data['asset_id']); // Don't update the asset_id
        
        $set_clauses = [];
        $values = [];
        $types = '';
        
        foreach ($data as $column => $value) {
            $set_clauses[] = "$column = ?";
            $values[] = $value;
            $types .= 's';
        }
        
        $values[] = $asset_id; // For WHERE clause
        $types .= 'i';
        
        $sql = "UPDATE $table_name SET " . implode(', ', $set_clauses) . " WHERE asset_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }
    
    /**
     * Delete specific asset data
     */
    public function deleteSpecificAssetData($asset_id, $category_code) {
        $table_name = $this->getSpecificTableName($category_code);
        if (!$table_name) {
            return false;
        }
        
        try {
            $sql = "DELETE FROM $table_name WHERE asset_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $asset_id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error deleting specific asset data: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get form fields for a specific category
     */
    public function getCategoryFormFields($category_code) {
        $fields = [
            'FF' => [
                'furniture_type' => ['type' => 'select', 'label' => 'Furniture Type', 'options' => ['desk', 'chair', 'cabinet', 'shelf', 'table', 'sofa', 'bed', 'other']],
                'material' => ['type' => 'select', 'label' => 'Material', 'options' => ['wood', 'metal', 'plastic', 'glass', 'leather', 'fabric', 'composite']],
                'color' => ['type' => 'text', 'label' => 'Color'],
                'dimensions' => ['type' => 'text', 'label' => 'Dimensions (LxWxH)'],
                'weight_capacity' => ['type' => 'number', 'label' => 'Weight Capacity (kg)'],
                'manufacturer' => ['type' => 'text', 'label' => 'Manufacturer'],
                'model_number' => ['type' => 'text', 'label' => 'Model Number'],
                'purchase_date' => ['type' => 'date', 'label' => 'Purchase Date'],
                'warranty_expiry' => ['type' => 'date', 'label' => 'Warranty Expiry'],
                'condition_status' => ['type' => 'select', 'label' => 'Condition', 'options' => ['excellent', 'good', 'fair', 'poor']],
                'location_building' => ['type' => 'text', 'label' => 'Building'],
                'location_floor' => ['type' => 'text', 'label' => 'Floor'],
                'location_room' => ['type' => 'text', 'label' => 'Room'],
                'assembly_required' => ['type' => 'checkbox', 'label' => 'Assembly Required'],
                'notes' => ['type' => 'textarea', 'label' => 'Notes']
            ],
            'CE' => [
                'processor' => ['type' => 'text', 'label' => 'Processor'],
                'ram_capacity' => ['type' => 'text', 'label' => 'RAM Capacity'],
                'storage_type' => ['type' => 'select', 'label' => 'Storage Type', 'options' => ['hdd', 'ssd', 'hybrid']],
                'storage_capacity' => ['type' => 'text', 'label' => 'Storage Capacity'],
                'graphics_card' => ['type' => 'text', 'label' => 'Graphics Card'],
                'operating_system' => ['type' => 'text', 'label' => 'Operating System'],
                'mac_address' => ['type' => 'text', 'label' => 'MAC Address'],
                'ip_address' => ['type' => 'text', 'label' => 'IP Address'],
                'serial_number' => ['type' => 'text', 'label' => 'Serial Number'],
                'warranty_provider' => ['type' => 'text', 'label' => 'Warranty Provider'],
                'warranty_expiry' => ['type' => 'date', 'label' => 'Warranty Expiry'],
                'purchase_date' => ['type' => 'date', 'label' => 'Purchase Date'],
                'last_service_date' => ['type' => 'date', 'label' => 'Last Service Date'],
                'condition_status' => ['type' => 'select', 'label' => 'Condition', 'options' => ['excellent', 'good', 'fair', 'poor']],
                'assigned_to' => ['type' => 'text', 'label' => 'Assigned To'],
                'department' => ['type' => 'text', 'label' => 'Department'],
                'notes' => ['type' => 'textarea', 'label' => 'Notes']
            ],
            'VH' => [
                'plate_number' => ['type' => 'text', 'label' => 'Plate Number', 'required' => true],
                'engine_number' => ['type' => 'text', 'label' => 'Engine Number'],
                'chassis_number' => ['type' => 'text', 'label' => 'Chassis Number'],
                'color' => ['type' => 'text', 'label' => 'Color'],
                'model' => ['type' => 'text', 'label' => 'Model'],
                'brand' => ['type' => 'text', 'label' => 'Brand'],
                'year_manufactured' => ['type' => 'number', 'label' => 'Year Manufactured'],
                'fuel_type' => ['type' => 'select', 'label' => 'Fuel Type', 'options' => ['gasoline', 'diesel', 'electric', 'hybrid', 'lpg']],
                'transmission_type' => ['type' => 'select', 'label' => 'Transmission', 'options' => ['manual', 'automatic', 'cvt']],
                'registration_date' => ['type' => 'date', 'label' => 'Registration Date'],
                'registration_expiry' => ['type' => 'date', 'label' => 'Registration Expiry'],
                'insurance_provider' => ['type' => 'text', 'label' => 'Insurance Provider'],
                'insurance_policy_number' => ['type' => 'text', 'label' => 'Policy Number'],
                'insurance_expiry' => ['type' => 'date', 'label' => 'Insurance Expiry'],
                'odometer_reading' => ['type' => 'number', 'label' => 'Odometer Reading'],
                'last_maintenance_date' => ['type' => 'date', 'label' => 'Last Maintenance'],
                'next_maintenance_date' => ['type' => 'date', 'label' => 'Next Maintenance'],
                'condition_status' => ['type' => 'select', 'label' => 'Condition', 'options' => ['excellent', 'good', 'fair', 'poor']],
                'notes' => ['type' => 'textarea', 'label' => 'Notes']
            ],
            'ME' => [
                'machine_type' => ['type' => 'text', 'label' => 'Machine Type'],
                'manufacturer' => ['type' => 'text', 'label' => 'Manufacturer'],
                'model_number' => ['type' => 'text', 'label' => 'Model Number'],
                'serial_number' => ['type' => 'text', 'label' => 'Serial Number'],
                'capacity' => ['type' => 'text', 'label' => 'Capacity'],
                'power_requirements' => ['type' => 'text', 'label' => 'Power Requirements'],
                'voltage' => ['type' => 'number', 'label' => 'Voltage'],
                'operating_weight' => ['type' => 'number', 'label' => 'Operating Weight (kg)'],
                'dimensions' => ['type' => 'text', 'label' => 'Dimensions'],
                'installation_date' => ['type' => 'date', 'label' => 'Installation Date'],
                'last_maintenance_date' => ['type' => 'date', 'label' => 'Last Maintenance'],
                'next_maintenance_date' => ['type' => 'date', 'label' => 'Next Maintenance'],
                'maintenance_interval_days' => ['type' => 'number', 'label' => 'Maintenance Interval (days)'],
                'operator_required' => ['type' => 'checkbox', 'label' => 'Operator Required'],
                'safety_certification' => ['type' => 'text', 'label' => 'Safety Certification'],
                'certification_expiry' => ['type' => 'date', 'label' => 'Certification Expiry'],
                'condition_status' => ['type' => 'select', 'label' => 'Condition', 'options' => ['excellent', 'good', 'fair', 'poor']],
                'location_building' => ['type' => 'text', 'label' => 'Building'],
                'location_area' => ['type' => 'text', 'label' => 'Area'],
                'notes' => ['type' => 'textarea', 'label' => 'Notes']
            ],
            'BI' => [
                'building_type' => ['type' => 'select', 'label' => 'Building Type', 'options' => ['office', 'warehouse', 'factory', 'residential', 'commercial', 'other']],
                'address' => ['type' => 'textarea', 'label' => 'Address', 'required' => true],
                'city' => ['type' => 'text', 'label' => 'City'],
                'state' => ['type' => 'text', 'label' => 'State'],
                'postal_code' => ['type' => 'text', 'label' => 'Postal Code'],
                'total_floor_area' => ['type' => 'number', 'label' => 'Total Floor Area (sqm)'],
                'number_of_floors' => ['type' => 'number', 'label' => 'Number of Floors'],
                'year_built' => ['type' => 'number', 'label' => 'Year Built'],
                'year_renovated' => ['type' => 'number', 'label' => 'Year Renovated'],
                'construction_type' => ['type' => 'select', 'label' => 'Construction Type', 'options' => ['concrete', 'wood', 'steel', 'mixed']],
                'roof_type' => ['type' => 'text', 'label' => 'Roof Type'],
                'electrical_capacity' => ['type' => 'text', 'label' => 'Electrical Capacity'],
                'water_supply' => ['type' => 'select', 'label' => 'Water Supply', 'options' => ['municipal', 'well', 'mixed']],
                'sewage_system' => ['type' => 'select', 'label' => 'Sewage System', 'options' => ['municipal', 'septic_tank', 'mixed']],
                'fire_safety_system' => ['type' => 'checkbox', 'label' => 'Fire Safety System'],
                'security_system' => ['type' => 'checkbox', 'label' => 'Security System'],
                'air_conditioning' => ['type' => 'checkbox', 'label' => 'Air Conditioning'],
                'elevator_count' => ['type' => 'number', 'label' => 'Elevator Count'],
                'parking_spaces' => ['type' => 'number', 'label' => 'Parking Spaces'],
                'property_tax_number' => ['type' => 'text', 'label' => 'Property Tax Number'],
                'land_title_number' => ['type' => 'text', 'label' => 'Land Title Number'],
                'zoning_classification' => ['type' => 'text', 'label' => 'Zoning Classification'],
                'condition_status' => ['type' => 'select', 'label' => 'Condition', 'options' => ['excellent', 'good', 'fair', 'poor']],
                'notes' => ['type' => 'textarea', 'label' => 'Notes']
            ],
            'LD' => [
                'land_type' => ['type' => 'select', 'label' => 'Land Type', 'options' => ['commercial', 'residential', 'agricultural', 'industrial', 'mixed']],
                'address' => ['type' => 'textarea', 'label' => 'Address', 'required' => true],
                'city' => ['type' => 'text', 'label' => 'City'],
                'state' => ['type' => 'text', 'label' => 'State'],
                'postal_code' => ['type' => 'text', 'label' => 'Postal Code'],
                'lot_area' => ['type' => 'number', 'label' => 'Lot Area (sqm)'],
                'frontage' => ['type' => 'number', 'label' => 'Frontage (meters)'],
                'depth' => ['type' => 'number', 'label' => 'Depth (meters)'],
                'shape' => ['type' => 'select', 'label' => 'Shape', 'options' => ['regular', 'irregular']],
                'topography' => ['type' => 'select', 'label' => 'Topography', 'options' => ['flat', 'sloping', 'hilly', 'mountainous']],
                'zoning_classification' => ['type' => 'text', 'label' => 'Zoning Classification'],
                'land_classification' => ['type' => 'text', 'label' => 'Land Classification'],
                'tax_declaration_number' => ['type' => 'text', 'label' => 'Tax Declaration Number'],
                'land_title_number' => ['type' => 'text', 'label' => 'Land Title Number'],
                'survey_number' => ['type' => 'text', 'label' => 'Survey Number'],
                'corner_lot' => ['type' => 'checkbox', 'label' => 'Corner Lot'],
                'road_access' => ['type' => 'select', 'label' => 'Road Access', 'options' => ['paved', 'gravel', 'dirt', 'none']],
                'utilities_available' => ['type' => 'select', 'label' => 'Utilities Available', 'options' => ['full', 'partial', 'none']],
                'flood_prone' => ['type' => 'checkbox', 'label' => 'Flood Prone'],
                'encumbrances' => ['type' => 'textarea', 'label' => 'Encumbrances'],
                'condition_status' => ['type' => 'select', 'label' => 'Condition', 'options' => ['excellent', 'good', 'fair', 'poor']],
                'notes' => ['type' => 'textarea', 'label' => 'Notes']
            ],
            'SW' => [
                'software_name' => ['type' => 'text', 'label' => 'Software Name', 'required' => true],
                'version' => ['type' => 'text', 'label' => 'Version'],
                'license_type' => ['type' => 'select', 'label' => 'License Type', 'options' => ['perpetual', 'subscription', 'open_source', 'freemium']],
                'license_key' => ['type' => 'text', 'label' => 'License Key'],
                'number_of_licenses' => ['type' => 'number', 'label' => 'Number of Licenses'],
                'platform' => ['type' => 'select', 'label' => 'Platform', 'options' => ['windows', 'mac', 'linux', 'web', 'mobile', 'multi_platform']],
                'installation_date' => ['type' => 'date', 'label' => 'Installation Date'],
                'license_expiry' => ['type' => 'date', 'label' => 'License Expiry'],
                'renewal_cost' => ['type' => 'number', 'label' => 'Renewal Cost'],
                'vendor' => ['type' => 'text', 'label' => 'Vendor'],
                'support_contact' => ['type' => 'text', 'label' => 'Support Contact'],
                'activation_method' => ['type' => 'select', 'label' => 'Activation Method', 'options' => ['key', 'online', 'usb_dongle', 'account']],
                'server_based' => ['type' => 'checkbox', 'label' => 'Server Based'],
                'concurrent_users' => ['type' => 'number', 'label' => 'Concurrent Users'],
                'hardware_requirements' => ['type' => 'textarea', 'label' => 'Hardware Requirements'],
                'installation_path' => ['type' => 'text', 'label' => 'Installation Path'],
                'assigned_department' => ['type' => 'text', 'label' => 'Assigned Department'],
                'condition_status' => ['type' => 'select', 'label' => 'Status', 'options' => ['active', 'inactive', 'deprecated']],
                'notes' => ['type' => 'textarea', 'label' => 'Notes']
            ],
            'OE' => [
                'equipment_type' => ['type' => 'select', 'label' => 'Equipment Type', 'options' => ['printer', 'scanner', 'photocopier', 'fax', 'telephone', 'projector', 'shredder', 'other']],
                'brand' => ['type' => 'text', 'label' => 'Brand'],
                'model' => ['type' => 'text', 'label' => 'Model'],
                'serial_number' => ['type' => 'text', 'label' => 'Serial Number'],
                'connectivity' => ['type' => 'select', 'label' => 'Connectivity', 'options' => ['usb', 'network', 'wireless', 'bluetooth', 'multi']],
                'network_ip' => ['type' => 'text', 'label' => 'Network IP'],
                'functions' => ['type' => 'text', 'label' => 'Functions'],
                'paper_size' => ['type' => 'text', 'label' => 'Paper Size'],
                'print_speed_ppm' => ['type' => 'number', 'label' => 'Print Speed (PPM)'],
                'scan_resolution' => ['type' => 'text', 'label' => 'Scan Resolution'],
                'color_capability' => ['type' => 'checkbox', 'label' => 'Color Capability'],
                'power_consumption' => ['type' => 'text', 'label' => 'Power Consumption'],
                'warranty_provider' => ['type' => 'text', 'label' => 'Warranty Provider'],
                'warranty_expiry' => ['type' => 'date', 'label' => 'Warranty Expiry'],
                'last_service_date' => ['type' => 'date', 'label' => 'Last Service Date'],
                'next_service_date' => ['type' => 'date', 'label' => 'Next Service Date'],
                'condition_status' => ['type' => 'select', 'label' => 'Condition', 'options' => ['excellent', 'good', 'fair', 'poor']],
                'location_building' => ['type' => 'text', 'label' => 'Building'],
                'location_floor' => ['type' => 'text', 'label' => 'Floor'],
                'location_room' => ['type' => 'text', 'label' => 'Room'],
                'assigned_to' => ['type' => 'text', 'label' => 'Assigned To'],
                'notes' => ['type' => 'textarea', 'label' => 'Notes']
            ]
        ];
        
        return $fields[$category_code] ?? [];
    }
    
    /**
     * Generate HTML form for specific category fields
     */
    public function generateCategoryForm($category_code, $data = null) {
        $fields = $this->getCategoryFormFields($category_code);
        if (empty($fields)) {
            return '';
        }
        
        $html = '<div class="category-specific-fields mt-3">';
        $html .= '<h6 class="mb-3"><i class="bi bi-gear"></i> ' . $this->getCategoryLabel($category_code) . ' Details</h6>';
        
        foreach ($fields as $field_name => $field_config) {
            $value = $data[$field_name] ?? '';
            $required = $field_config['required'] ?? false;
            $required_attr = $required ? 'required' : '';
            
            $html .= '<div class="mb-3">';
            $html .= '<label class="form-label">' . htmlspecialchars($field_config['label']);
            if ($required) {
                $html .= ' <span class="text-danger">*</span>';
            }
            $html .= '</label>';
            
            switch ($field_config['type']) {
                case 'select':
                    $html .= '<select class="form-select" name="' . $field_name . '" ' . $required_attr . '>';
                    $html .= '<option value="">Select ' . htmlspecialchars($field_config['label']) . '</option>';
                    foreach ($field_config['options'] as $option) {
                        $selected = $value == $option ? 'selected' : '';
                        $option_label = ucfirst(str_replace('_', ' ', $option));
                        $html .= '<option value="' . $option . '" ' . $selected . '>' . htmlspecialchars($option_label) . '</option>';
                    }
                    $html .= '</select>';
                    break;
                    
                case 'textarea':
                    $html .= '<textarea class="form-control" name="' . $field_name . '" rows="3" ' . $required_attr . '>' . htmlspecialchars($value) . '</textarea>';
                    break;
                    
                case 'checkbox':
                    $checked = $value ? 'checked' : '';
                    $html .= '<div class="form-check">';
                    $html .= '<input class="form-check-input" type="checkbox" name="' . $field_name . '" value="1" ' . $checked . '>';
                    $html .= '<label class="form-check-label">' . htmlspecialchars($field_config['label']) . '</label>';
                    $html .= '</div>';
                    break;
                    
                case 'number':
                    $html .= '<input type="number" class="form-control" name="' . $field_name . '" value="' . htmlspecialchars($value) . '" ' . $required_attr . '>';
                    break;
                    
                case 'date':
                    $html .= '<input type="date" class="form-control" name="' . $field_name . '" value="' . htmlspecialchars($value) . '" ' . $required_attr . '>';
                    break;
                    
                default:
                    $html .= '<input type="text" class="form-control" name="' . $field_name . '" value="' . htmlspecialchars($value) . '" ' . $required_attr . '>';
                    break;
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Get category display label
     */
    private function getCategoryLabel($category_code) {
        $labels = [
            'FF' => 'Furniture & Fixtures',
            'CE' => 'Computer Equipment',
            'VH' => 'Vehicle',
            'ME' => 'Machinery & Equipment',
            'BI' => 'Building',
            'LD' => 'Land',
            'SW' => 'Software',
            'OE' => 'Office Equipment'
        ];
        
        return $labels[$category_code] ?? 'Asset';
    }
}
?>
