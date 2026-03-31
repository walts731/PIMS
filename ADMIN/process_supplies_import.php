<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check if user is logged in and authorized
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excel_json']) && isset($_POST['mapping_conf'])) {
    $json_data = json_decode($_POST['excel_json'], true);
    $mapping = json_decode($_POST['mapping_conf'], true);
    
    if (!$json_data || empty($json_data)) {
        $_SESSION['error_message'] = "Received invalid or empty Excel data.";
        header('Location: consumable_requests.php');
        exit();
    }

    // Helper function for unit normalization (singularize common plural units)
    function normalizeUnit($unit) {
        $unit = trim(strtolower($unit));
        if (empty($unit)) return 'pcs';
        
        $singularRules = [
            'pieces' => 'pc', 'pcs' => 'pc', 'piece' => 'pc',
            'boxes' => 'box', 'packs' => 'pack', 'reams' => 'ream',
            'liters' => 'liter', 'kilograms' => 'kg', 'kgs' => 'kg',
            'units' => 'unit', 'sets' => 'set', 'rolls' => 'roll',
            'bottles' => 'bottle', 'cans' => 'can', 'tubes' => 'tube'
        ];
        
        return $singularRules[$unit] ?? $unit;
    }

    // Segregation logic: Aggregate data per office
    $segregated_data = [];
    foreach ($json_data as $row) {
        // Multi-format support: check for mapped office or use first available key if none
        $office = isset($mapping['office']) && isset($row[$mapping['office']]) ? trim($row[$mapping['office']]) : '';
        $desc = isset($mapping['description']) && isset($row[$mapping['description']]) ? trim($row[$mapping['description']]) : '';
        $raw_unit = isset($mapping['unit']) && isset($row[$mapping['unit']]) ? trim($row[$mapping['unit']]) : 'pcs';
        $unit = normalizeUnit($raw_unit);
        $qty = isset($mapping['quantity']) && isset($row[$mapping['quantity']]) ? floatval($row[$mapping['quantity']]) : 0;

        // Skip invalid rows
        if (empty($desc) || $qty <= 0) continue;
        
        // If office is empty, we attempt to find it from the first column if no mapping is set
        if (empty($office) && !isset($mapping['office'])) {
            $keys = array_keys($row);
            $office = isset($row[$keys[0]]) ? trim($row[$keys[0]]) : 'General';
        }
        
        if (empty($office)) $office = 'General';

        if (!isset($segregated_data[$office])) {
            $segregated_data[$office] = [];
        }

        // Use normalized key for case-insensitive and unit-normalized aggregation
        $norm_desc = strtolower($desc);
        $key = $norm_desc . '|' . $unit;
        
        if (!isset($segregated_data[$office][$key])) {
            $segregated_data[$office][$key] = [
                'desc' => $desc, // Keep original casing for first encounter
                'unit' => $unit,
                'qty' => 0
            ];
        } else {
            // Optionally, we could pick the most common casing or just keep the first one
        }
        $segregated_data[$office][$key]['qty'] += $qty;
    }

    // Database Insertion (Transactions)
    $conn->begin_transaction();
    try {
        $import_count = 0;
        foreach ($segregated_data as $office_name => $items) {
            // Generate unique RIS No for this office import
            $ris_no = "OFFICE-" . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string)$office_name), 0, 3)) . "-" . date('Ymd-His') . "-" . rand(10, 99);
            
            // 1. Insert into ris_forms
            $stmt = $conn->prepare("
                INSERT INTO ris_forms (
                    ris_no, sai_no, code, division, office, responsibility_center, 
                    purpose, status, total_amount, requested_by, requested_by_position, 
                    approved_by, approved_by_position, issued_by, issued_by_position, 
                    received_by, received_by_position, created_by
                ) VALUES (
                    ?, '', '', 'Imported', ?, 'ADMIN', 
                    ?, 'submitted', 0, 'Excel Import', 'System', 
                    'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', ?
                )
            ");
            $purpose = "Office Supplies 2026 - Consolidated Import";
            $user_id = $_SESSION['user_id'];
            $stmt->bind_param("sssi", $ris_no, $office_name, $purpose, $user_id);
            $stmt->execute();
            $form_id = $conn->insert_id;

            // 2. Insert items
            $stock_no = 1;
            foreach ($items as $item) {
                $stmt_item = $conn->prepare("INSERT INTO ris_items (ris_form_id, stock_no, unit, description, quantity, price, total_amount) VALUES (?, ?, ?, ?, ?, 0, 0)");
                $stmt_item->bind_param("iissd", $form_id, $stock_no, $item['unit'], $item['desc'], $item['qty']);
                $stmt_item->execute();
                $stock_no++;
            }
            $import_count++;
        }

        $conn->commit();
        logSystemAction($_SESSION['user_id'], 'Imported Office Supplies', 'consumables', 'Imported and segregated ' . $import_count . ' office groups.');
        $_SESSION['success_message'] = "Successfully segregated and imported supplies for $import_count offices from your Excel file!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error during import: " . $e->getMessage();
    }
}

header('Location: consumable_requests.php');
exit();
?>
