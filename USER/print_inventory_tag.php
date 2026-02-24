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

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: ../index.php');
    exit();
}

header('Location: assets.php');
exit();

$tag_id = (int)($_GET['id'] ?? 0);
if ($tag_id <= 0) {
    echo 'Invalid tag ID';
    exit();
}

$user_office_id = null;

if (!$conn || $conn->connect_error) {
    echo 'Database connection failed';
    exit();
}

try {
    $user_office_value = null;

    $stmt = $conn->prepare("SELECT office FROM users WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_office_value = $row['office'] ?? null;
    }
    $stmt->close();

    if ($user_office_value !== null && $user_office_value !== '') {
        if (ctype_digit((string)$user_office_value)) {
            $user_office_id = (int)$user_office_value;
        } else {
            $stmt = $conn->prepare("SELECT id FROM offices WHERE office_name = ? OR office_code = ? LIMIT 1");
            $stmt->bind_param('ss', $user_office_value, $user_office_value);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $user_office_id = (int)$row['id'];
            }
            $stmt->close();
        }
    }
} catch (Exception $e) {
    echo 'Error resolving office';
    exit();
}

if (!$user_office_id) {
    echo 'Office not assigned to your account.';
    exit();
}

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
    $system_settings['system_logo'] = '';
    $system_settings['system_name'] = 'PIMS';
}

$sql = "SELECT ai.*, 
               a.description as asset_description, a.unit_cost,
               ac.category_name, ac.category_code,
               o.office_name, o.address,
               e.employee_no, e.firstname, e.lastname, e.position
        FROM asset_items ai 
        LEFT JOIN assets a ON ai.asset_id = a.id 
        LEFT JOIN asset_categories ac ON COALESCE(ai.category_id, a.asset_categories_id) = ac.id 
        LEFT JOIN offices o ON ai.office_id = o.id 
        LEFT JOIN employees e ON ai.employee_id = e.id 
        WHERE ai.id = ? AND ai.office_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $tag_id, $user_office_id);
$stmt->execute();
$result = $stmt->get_result();
$tag = $result->fetch_assoc();
$stmt->close();

if (!$tag) {
    echo 'Tag not found';
    exit();
}

logSystemAction($_SESSION['user_id'], 'print', 'inventory_tag', "Printed inventory tag: {$tag['inventory_tag']}");

$model_no = '';
$serial_no = '';
$unit_value = 1;

if (($tag['category_code'] ?? '') === '030') {
    $comp_sql = "SELECT processor as model_no, serial_number FROM asset_computers WHERE asset_item_id = ?";
    $comp_stmt = $conn->prepare($comp_sql);
    $comp_stmt->bind_param('i', $tag_id);
    $comp_stmt->execute();
    $comp_result = $comp_stmt->get_result();
    if ($comp_row = $comp_result->fetch_assoc()) {
        $model_no = $comp_row['model_no'] ?? '';
        $serial_no = $comp_row['serial_number'] ?? '';
    }
    $comp_stmt->close();
} elseif (($tag['category_code'] ?? '') === '07') {
    $veh_sql = "SELECT model, serial_number FROM asset_vehicles WHERE asset_item_id = ?";
    $veh_stmt = $conn->prepare($veh_sql);
    $veh_stmt->bind_param('i', $tag_id);
    $veh_stmt->execute();
    $veh_result = $veh_stmt->get_result();
    if ($veh_row = $veh_result->fetch_assoc()) {
        $model_no = $veh_row['model'] ?? '';
        $serial_no = $veh_row['serial_number'] ?? '';
    }
    $veh_stmt->close();
} elseif (($tag['category_code'] ?? '') === '04') {
    $mach_sql = "SELECT model_number as model_no, serial_number FROM asset_machinery WHERE asset_item_id = ?";
    $mach_stmt = $conn->prepare($mach_sql);
    $mach_stmt->bind_param('i', $tag_id);
    $mach_stmt->execute();
    $mach_result = $mach_stmt->get_result();
    if ($mach_row = $mach_result->fetch_assoc()) {
        $model_no = $mach_row['model_no'] ?? '';
        $serial_no = $mach_row['serial_number'] ?? '';
    }
    $mach_stmt->close();
} elseif (($tag['category_code'] ?? '') === '05') {
    $oe_sql = "SELECT model, serial_number FROM asset_office_equipment WHERE asset_item_id = ?";
    $oe_stmt = $conn->prepare($oe_sql);
    $oe_stmt->bind_param('i', $tag_id);
    $oe_stmt->execute();
    $oe_result = $oe_stmt->get_result();
    if ($oe_row = $oe_result->fetch_assoc()) {
        $model_no = $oe_row['model'] ?? '';
        $serial_no = $oe_row['serial_number'] ?? '';
    }
    $oe_stmt->close();
}

$acquisition_date = !empty($tag['acquisition_date']) ? date('M d, Y', strtotime($tag['acquisition_date'])) : '';
$date_counted = !empty($tag['date_counted']) ? date('M d, Y', strtotime($tag['date_counted'])) : '';

$person_accountable = '';
if (!empty($tag['firstname']) && !empty($tag['lastname'])) {
    $person_accountable = $tag['firstname'] . ' ' . $tag['lastname'];
    if (!empty($tag['employee_no'])) {
        $person_accountable .= ' (' . $tag['employee_no'] . ')';
    }
} elseif (!empty($tag['employee_no'])) {
    $person_accountable = $tag['employee_no'];
}

$serviceable_checked = (($tag['status'] ?? '') === 'serviceable') ? '☑' : '☐';
$unserviceable_checked = (in_array(($tag['status'] ?? ''), ['unserviceable', 'red_tagged'], true)) ? '☑' : '☐';

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
            max-width: 4in;
            margin: 0 auto;
            padding: 0;
            position: relative;
        }

        .tag-container {
            width: 4in;
            height: 4in;
            border: 2px solid #000;
            padding: 15px;
            background: white;
            page-break-inside: avoid;
            display: flex;
            flex-direction: column;
        }

        .tag-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .seal {
            width: 40px;
            height: 40px;
            border: 2px solid #000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 6px;
            text-align: center;
            font-weight: bold;
        }

        .header-text {
            flex: 1;
            text-align: center;
            margin: 0 10px;
        }

        .header-logo {
            max-width: 36px;
            max-height: 36px;
            border-radius: 50%;
            object-fit: contain;
        }

        .header-text h2 {
            margin: 0;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header-text h3 {
            margin: 2px 0 0 0;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .tag-number {
            font-size: 12px;
            font-weight: bold;
            text-align: right;
        }

        .tag-qr-code {
            width: 40px;
            height: 40px;
            border: 1px solid #000;
            border-radius: 4px;
            object-fit: contain;
        }

        .qr-placeholder {
            width: 40px;
            height: 40px;
            border: 1px solid #000;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #666;
        }

        .tag-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 8px;
        }

        .field-row {
            display: flex;
            align-items: flex-start;
            gap: 5px;
        }

        .field-label {
            width: 70px;
            font-weight: bold;
            flex-shrink: 0;
            font-size: 7px;
        }

        .field-value {
            flex: 1;
            border-bottom: 1px solid #000;
            min-height: 12px;
            padding: 1px 2px;
            font-size: 7px;
        }

        .checkbox-row {
            display: flex;
            gap: 15px;
            margin-bottom: 5px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .checkbox {
            font-size: 10px;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
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
            padding-top: 8px;
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
            height: 15px;
            margin-bottom: 3px;
        }

        .signature-label {
            font-size: 6px;
            font-style: italic;
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

            header, nav, .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="tag-container">
            <div class="tag-header">
                <div class="header-row">
                    <div class="seal">
                        <?php
                        $logo_path = '../img/trans_logo.png';
                        if (!empty($system_settings['system_logo'])) {
                            if (file_exists('../' . $system_settings['system_logo'])) {
                                $logo_path = '../' . $system_settings['system_logo'];
                            } elseif (file_exists($system_settings['system_logo'])) {
                                $logo_path = $system_settings['system_logo'];
                            }
                        }
                        ?>
                        <img src="<?php echo $logo_path; ?>" alt="LGU Logo" class="header-logo">
                    </div>
                    <div class="header-text">
                        <h2>BAYAN NG PILAR</h2>
                        <h3>LALAWIGAN NG SORSOGON</h3>
                    </div>
                    <div class="tag-number">
                        <?php if (!empty($tag['qr_code'])): ?>
                            <img src="../uploads/qr_codes/<?php echo htmlspecialchars($tag['qr_code']); ?>" alt="QR Code" class="tag-qr-code">
                        <?php else: ?>
                            <div class="qr-placeholder">
                                <i class="bi bi-qr-code-scan"></i>
                            </div>
                        <?php endif; ?>
                        <br>
                        <small>No. <?php echo htmlspecialchars($tag['property_no'] ?? 'N/A'); ?></small>
                    </div>
                </div>
            </div>

            <div class="tag-body">
                <div class="field-row">
                    <div class="field-label">Office:</div>
                    <div class="field-value"><?php echo htmlspecialchars($tag['office_name'] ?? ''); ?></div>
                </div>

                <div class="field-row">
                    <div class="field-label">Description:</div>
                    <div class="field-value"><?php echo htmlspecialchars($tag['description'] ?? ''); ?></div>
                </div>

                <div class="two-column">
                    <div class="field-row">
                        <div class="field-label">Model:</div>
                        <div class="field-value"><?php echo htmlspecialchars($model_no); ?></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">Serial:</div>
                        <div class="field-value"><?php echo htmlspecialchars($serial_no); ?></div>
                    </div>
                </div>

                <div class="checkbox-row">
                    <div class="checkbox-item">
                        <div class="checkbox"><?php echo $serviceable_checked; ?></div>
                        <div>Serviceable</div>
                    </div>
                    <div class="checkbox-item">
                        <div class="checkbox"><?php echo $unserviceable_checked; ?></div>
                        <div>Unserviceable</div>
                    </div>
                </div>

                <div class="two-column">
                    <div class="field-row">
                        <div class="field-label">Unit/Quantity:</div>
                        <div class="field-value"><?php echo htmlspecialchars($unit_value); ?></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">Acquisition Date/Cost:</div>
                        <div class="field-value"><?php echo htmlspecialchars($acquisition_date); ?> / <?php echo htmlspecialchars((string)($tag['unit_cost'] ?? '')); ?></div>
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-label">Accountable:</div>
                    <div class="field-value"><?php echo htmlspecialchars($person_accountable); ?></div>
                </div>

                <div class="two-column">
                    <div class="field-row">
                        <div class="field-label">Date: (Acquired)</div>
                        <div class="field-value"><?php echo htmlspecialchars($acquisition_date); ?></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">Date: (Counted)</div>
                        <div class="field-value"><?php echo htmlspecialchars($date_counted); ?></div>
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
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };

        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>
</html>
