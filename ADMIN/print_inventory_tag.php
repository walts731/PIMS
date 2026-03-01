<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';

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

// Get tag ID
$tag_id = intval($_GET['id'] ?? 0);
if ($tag_id === 0) {
    echo 'Invalid tag ID';
    exit();
}

// Get system settings for logo
$system_settings = [];
try {
    $stmt = $conn->prepare("SELECT setting_name, setting_value FROM system_settings WHERE setting_name IN ('system_logo', 'system_name')");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $system_settings[$row['setting_name']] = $row['setting_value'];
        }
    }
    $stmt->close();
} catch (Exception $e) {
    // Fallback to default if database fails
    $system_settings['system_logo'] = '';
    $system_settings['system_name'] = 'PIMS';
}

// Get tag details with additional fields
$sql = "SELECT ai.*, 
               a.description as asset_description, a.unit_cost,
               ac.category_name, ac.category_code,
               subcat.sub_category_name, subcat.sub_category_code,
               o.office_name, o.address,
               e.employee_no, e.firstname, e.lastname, e.position,
               desk.monitor_name, desk.monitor_model, desk.monitor_serial_number, 
               desk.ups_name, desk.ups_model, desk.ups_serial_number
        FROM asset_items ai 
        LEFT JOIN assets a ON ai.asset_id = a.id 
        LEFT JOIN asset_categories ac ON COALESCE(ai.category_id, a.asset_categories_id) = ac.id 
        LEFT JOIN asset_sub_categories subcat ON ai.asset_subcategory_id = subcat.id
        LEFT JOIN offices o ON ai.office_id = o.id 
        LEFT JOIN employees e ON ai.employee_id = e.id 
        LEFT JOIN asset_desktop_computers desk ON ai.id = desk.asset_item_id
        WHERE ai.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tag_id);
$stmt->execute();
$result = $stmt->get_result();
$tag = $result->fetch_assoc();

if (!$tag) {
    echo 'Tag not found';
    exit();
}

// Log the print action
require_once '../includes/logger.php';
logSystemAction($_SESSION['user_id'], 'print', 'inventory_tag', "Printed inventory tag: {$tag['inventory_tag']}");

// Get additional specific data based on category
$model_no = '';
$serial_no = '';

// Calculate unit quantity based on how many asset items share the same asset_id
$unit_value = 1;
$unit_sets = 1;
$asset_id = $tag['asset_id'] ?? '';

if (!empty($asset_id)) {
    // Get all asset items with this asset_id, ordered by ID
    $items_sql = "SELECT id FROM asset_items WHERE asset_id = ? ORDER BY id";
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param("i", $asset_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    $all_items = [];
    while ($row = $items_result->fetch_assoc()) {
        $all_items[] = $row['id'];
    }
    $items_stmt->close();
    
    // Calculate current position and total
    $unit_sets = count($all_items);
    $current_position = array_search($tag_id, $all_items) + 1; // +1 because array is 0-indexed
    $unit_value = $current_position;
}

if ($tag['category_code'] === '030') {
    // Computer Equipment
    $comp_sql = "SELECT processor as model_no, serial_number FROM asset_computers WHERE asset_item_id = ?";
    $comp_stmt = $conn->prepare($comp_sql);
    $comp_stmt->bind_param("i", $tag_id);
    $comp_stmt->execute();
    $comp_result = $comp_stmt->get_result();
    if ($comp_row = $comp_result->fetch_assoc()) {
        $model_no = $comp_row['model_no'] ?? '';
        $serial_no = $comp_row['serial_number'] ?? '';
    }
    $comp_stmt->close();
} elseif ($tag['category_code'] === '07') {
    // Vehicles
    $veh_sql = "SELECT model, serial_number FROM asset_vehicles WHERE asset_item_id = ?";
    $veh_stmt = $conn->prepare($veh_sql);
    $veh_stmt->bind_param("i", $tag_id);
    $veh_stmt->execute();
    $veh_result = $veh_stmt->get_result();
    if ($veh_row = $veh_result->fetch_assoc()) {
        $model_no = $veh_row['model'] ?? '';
        $serial_no = $veh_row['serial_number'] ?? '';
    }
    $veh_stmt->close();
} elseif ($tag['category_code'] === '04') {
    // Machinery & Equipment
    $mach_sql = "SELECT model_number as model_no, serial_number FROM asset_machinery WHERE asset_item_id = ?";
    $mach_stmt = $conn->prepare($mach_sql);
    $mach_stmt->bind_param("i", $tag_id);
    $mach_stmt->execute();
    $mach_result = $mach_stmt->get_result();
    if ($mach_row = $mach_result->fetch_assoc()) {
        $model_no = $mach_row['model_no'] ?? '';
        $serial_no = $mach_row['serial_number'] ?? '';
    }
    $mach_stmt->close();
} elseif ($tag['category_code'] === '05') {
    // Office Equipment
    $oe_sql = "SELECT model, serial_number FROM asset_office_equipment WHERE asset_item_id = ?";
    $oe_stmt = $conn->prepare($oe_sql);
    $oe_stmt->bind_param("i", $tag_id);
    $oe_stmt->execute();
    $oe_result = $oe_stmt->get_result();
    if ($oe_row = $oe_result->fetch_assoc()) {
        $model_no = $oe_row['model'] ?? '';
        $serial_no = $oe_row['serial_number'] ?? '';
    }
    $oe_stmt->close();
}

// Format dates
$acquisition_date = $tag['acquisition_date'] ? date('M d, Y', strtotime($tag['acquisition_date'])) : '';
$date_counted = $tag['date_counted'] ? date('M d, Y', strtotime($tag['date_counted'])) : '';

// Person accountable
$person_accountable = '';
if ($tag['firstname'] && $tag['lastname']) {
    $person_accountable = $tag['firstname'] . ' ' . $tag['lastname'];
    if ($tag['employee_no']) {
        $person_accountable .= ' (' . $tag['employee_no'] . ')';
    }
} elseif ($tag['employee_no']) {
    $person_accountable = $tag['employee_no'];
}

// Status checkboxes
$serviceable_checked = ($tag['status'] === 'serviceable') ? '☑' : '☐';
$unserviceable_checked = ($tag['status'] === 'unserviceable' || $tag['status'] === 'red_tagged') ? '☑' : '☐';

// Prepare stickers data
$stickers = [];

// Main asset sticker
$stickers[] = [
    'type' => 'main',
    'description' => $tag['description'],
    'model_no' => $model_no,
    'serial_no' => $serial_no,
    'property_no' => $tag['property_no'] ?? 'N/A',
    'qr_code' => $tag['qr_code'] ?? null
];

// Additional stickers for Desktop Computers
if ($tag['sub_category_name'] === 'Desktop Computers') {
    // Monitor sticker
    if (!empty($tag['monitor_name']) || !empty($tag['monitor_model'])) {
        $monitor_desc = trim(($tag['monitor_name'] ?? '') . ' ' . ($tag['monitor_model'] ?? ''));
        $stickers[] = [
            'type' => 'monitor',
            'description' => $monitor_desc ?: 'Monitor',
            'model_no' => $tag['monitor_model'] ?? '',
            'serial_no' => $tag['monitor_serial_number'] ?? '',
            'property_no' => ($tag['property_no'] ?? 'N/A') . '-MON',
            'qr_code' => $tag['qr_code'] ?? null // Use same QR code as main asset
        ];
    }

    // UPS sticker
    if (!empty($tag['ups_name']) || !empty($tag['ups_model'])) {
        $ups_desc = trim(($tag['ups_name'] ?? '') . ' ' . ($tag['ups_model'] ?? ''));
        $stickers[] = [
            'type' => 'ups',
            'description' => $ups_desc ?: 'UPS',
            'model_no' => $tag['ups_model'] ?? '',
            'serial_no' => $tag['ups_serial_number'] ?? '',
            'property_no' => ($tag['property_no'] ?? 'N/A') . '-UPS',
            'qr_code' => $tag['qr_code'] ?? null // Use same QR code as main asset
        ];
    }
}

// Function to generate sticker HTML
function generateStickerHTML($sticker, $tag, $system_settings, $serviceable_checked, $unserviceable_checked, $acquisition_date, $date_counted, $person_accountable, $unit_value, $unit_sets)
{
    // Get logo path
    $logo_path = '../img/trans_logo.png'; // default
    if (!empty($system_settings['system_logo'])) {
        if (file_exists('../' . $system_settings['system_logo'])) {
            $logo_path = '../' . $system_settings['system_logo'];
        } elseif (file_exists($system_settings['system_logo'])) {
            $logo_path = $system_settings['system_logo'];
        }
    }

    $sticker_type_label = '';
    if ($sticker['type'] === 'monitor') {
        $sticker_type_label = ' - MONITOR';
    } elseif ($sticker['type'] === 'ups') {
        $sticker_type_label = ' - UPS';
    }

    return '
    <div class="tag-container" style="page-break-inside: avoid; margin-bottom: 20px;">
        <div class="tag-header">
        <div class="property">
                <small>No. ' . htmlspecialchars($sticker['property_no']) . '</small>
            </div>
            <div class="header-row">
                <div class="seal">
                    <img src="' . $logo_path . '" alt="LGU Logo" class="header-logo">
                </div>
                <div class="header-text">
                    <h2>BAYAN NG PILAR</h2>
                    <h3>LALAWIGAN NG SORSOGON</h3>
                </div>
                <div class="tag-number">
                    <br>
                    ' . ($sticker['qr_code'] ?
        '<img src="../uploads/qr_codes/' . htmlspecialchars($sticker['qr_code']) . '" alt="QR Code" class="tag-qr-code">' :
        '<div class="qr-placeholder"><i class="bi bi-qr-code-scan"></i></div>'
    ) . '
                </div>
            </div>
             <div class="field-row">
                <div class=" office-name-field">' . htmlspecialchars($tag['office_name'] ?? '') . '</div>
            </div>
             <div class="field-row office-location-row">
                <div class="field-label text-right">Office/Location:</div>
            </div>
        </div>
        
        <div class="tag-body">
           
            
            <div class="field-row">
                <div class="field-label">Description:</div>
                <div class="field-value">' . htmlspecialchars($sticker['description']) . $sticker_type_label . '</div>
            </div>
            
            <div class="two-column">
                <div class="field-row">
                    <div class="field-label">Model:</div>
                    <div class="field-value">' . htmlspecialchars($sticker['model_no']) . '</div>
                </div>
                <div class="field-row">
                    <div class="field-label">Serial:</div>
                    <div class="field-value">' . htmlspecialchars($sticker['serial_no']) . '</div>
                </div>
            </div>
            
            <div class="checkbox-row">
                <div class="checkbox-item">
                    <div>Serviceable:</div>
                    <div class="checkbox">' . $serviceable_checked . '</div>
                </div>
                <div class="checkbox-item">
                    <div>Unserviceable:</div>
                    <div class="checkbox">' . $unserviceable_checked . '</div>
                </div>
            </div>
            
            <div class="two-column">
                <div class="field-row">
                    <div class="field-label">Unit/Quantity:</div>
                    <div class="field-value">' . ($unit_sets > 1 ? htmlspecialchars($unit_value) . ' of ' . htmlspecialchars($unit_sets) . ' sets' : htmlspecialchars($unit_value)) . '</div>
                </div>
                <div class="field-row">
                    <div class="field-label">Acquisition Date/Cost:</div>
                    <div class="field-value-no-border">' . htmlspecialchars($acquisition_date) . ' / ' . htmlspecialchars($tag['unit_cost']) . '</div>
                </div>
            </div>
            
            <div class="field-row">
                <div class="field-label">Accountable:</div>
                <div class="field-value">' . htmlspecialchars($person_accountable) . '</div>
            </div>
            
            <div class="two-column">
                <div class="field-row">
                    <div class="field-label">Date: (Acquired)</div>
                    <div class="field-value">' . htmlspecialchars($acquisition_date) . '</div>
                </div>
                <div class="field-row">
                    <div class="field-label">Date: (Counted)</div>
                    <div class="field-value">' . htmlspecialchars($date_counted) . '</div>
                </div>
            </div>
        </div>
        
        <div class="signature-section">
            <div class="signature-row">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">COA Representative</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Signature of the Inventory Committee</div>
                </div>
            </div>
        </div>
    </div>';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INVENTORY TAG</title>
    <style>
        @page {
            size: Letter;
            margin: 0.5in;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: white;
        }

        .print-container {
            width: 100%;
            max-width: 8.5in;
            margin: 0 auto;
            padding: 20px;
            position: relative;
        }

        .stickers-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: flex-start;
        }

        .tag-container {
            width: 3.5in;
            height: 3in;
            border: 2px solid #000;
            padding: 12px;
            background: white;
            page-break-inside: avoid;
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;
        }

        @media print {
            .stickers-wrapper {
                display: block;
                column-count: 2;
                column-gap: 15px;
            }

            .tag-container {
                break-inside: avoid;
                display: inline-block;
                width: 100%;
                margin-bottom: 0;
                margin-top: 0;
            }

            .tag-container:nth-child(even) {
                break-before: column;
            }
        }

        .tag-header {
            text-align: center;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .property {
            text-align: right;
            margin-top: 5px;
            font-size: 8px;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .seal {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5px;
            text-align: center;
            font-weight: bold;
            flex-shrink: 0;
        }

        .header-text {
            flex: 0.8;
            text-align: center;
            margin: 0 8px 0 2px;
        }

        .header-logo {
            max-width: 31px;
            max-height: 31px;
            border-radius: 50%;
            object-fit: contain;
        }

        .header-text h2 {
            margin: 0;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header-text h3 {
            margin: 2px 0 0 0;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .tag-number {
            font-size: 10px;
            font-weight: bold;
            text-align: right;
        }

        .tag-qr-code {
            width: 35px;
            height: 35px;
            object-fit: contain;
        }

        .qr-placeholder {
            width: 35px;
            height: 35px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #666;
        }

        .tag-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 7px;
        }

        .field-row {
            display: flex;
            align-items: flex-start;
            gap: 5px;
        }

        .field-label {
            width: 60px;
            font-weight: bold;
            flex-shrink: 0;
            font-size: 6px;
            text-align: left;
        }

        .office-location-row {
            justify-content: center;
        }

        .text-right {
            text-align: right;
        }

        .field-value {
            flex: 1;
            border-bottom: 1px solid #000;
            min-height: 8px;
            padding: 1px 2px;
            font-size: 6px;
            width: fit-content;
            max-width: 100%;
        }

        .field-value-no-border {
            flex: 1;
            min-height: 8px;
            padding: 1px 2px;
            font-size: 6px;
            width: fit-content;
            max-width: 100%;
        }

        .checkbox-row {
            display: flex;
            gap: 20px;
            margin-bottom: 5px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 50px;
        }

        .checkbox {
            font-size: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            flex-shrink: 0;
        }
        
        .checkbox::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: -20px;
            right: -20px;
            height: 8px;
            border-bottom: 1px solid #000;
        }

        .two-column {
            display: flex;
            gap: 10px;
        }

        .two-column .field-row {
            flex: 1;
        }

        .signature-section {
            margin-top: auto;
            border-top: 1px solid #000;
            padding-top: 6px;
        }

        .signature-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .signature-box {
            flex: 1;
            text-align: center;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            height: 12px;
            margin-bottom: 2px;
        }

        .signature-label {
            font-size: 5px;
            font-style: italic;
        }

        .office-name-field {
            text-decoration: underline;
            font-weight: bold;
            font-size: 8px;
            color: #000;
            font-family: Arial, sans-serif;
            text-align: center;
            width: 200px;
            height: 12px;
            margin-bottom: 3px;
            display: block;
            margin-left: auto;
            margin-right: auto;
            padding: 2px;
        }

    @media print {
        body {
            margin: 0;
            padding: 0;
        }

        .print-container {
            padding: 0;
        }

        @page {
            size: Letter;
            margin: 0.5in;
        }

        html {
            overflow: hidden;
        }

        header,
        nav,
        .no-print {
            display: none !important;
        }
        
        .field-value-no-border {
            flex: 1;
            min-height: 8px;
            padding: 1px 2px;
            font-size: 6px;
            width: fit-content;
            max-width: 100%;
        }
    }
</style>
</head>

<body>
    <div class="print-container">
        <div class="stickers-wrapper">
            <?php
            // Generate HTML for each sticker
            foreach ($stickers as $sticker) {
                echo generateStickerHTML($sticker, $tag, $system_settings, $serviceable_checked, $unserviceable_checked, $acquisition_date, $date_counted, $person_accountable, $unit_value, $unit_sets);
            }
            ?>
        </div>
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