<?php
session_start();
require_once '../config.php';

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

// Get asset item ID from URL
$asset_item_id = intval($_GET['id'] ?? 0);
if ($asset_item_id === 0) {
    echo 'Invalid asset item ID';
    exit();
}

// Get asset item details
$sql = "SELECT ai.property_no, ai.description, ai.qr_code, ac.category_name 
        FROM asset_items ai 
        LEFT JOIN assets a ON ai.asset_id = a.id 
        LEFT JOIN asset_categories ac ON a.asset_categories_id = ac.id 
        WHERE ai.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $asset_item_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();

if (!$item) {
    echo 'Asset item not found';
    exit();
}

// Get system settings for logo
$system_settings = [];
try {
    $settings_stmt = $conn->prepare("SELECT setting_name, setting_value FROM system_settings WHERE setting_name IN ('system_logo', 'system_name')");
    $settings_stmt->execute();
    $settings_result = $settings_stmt->get_result();
    if ($settings_result && $settings_result->num_rows > 0) {
        while ($row = $settings_result->fetch_assoc()) {
            $system_settings[$row['setting_name']] = $row['setting_value'];
        }
    }
    $settings_stmt->close();
} catch (Exception $e) {
    // Fallback to default if database fails
    $system_settings['system_logo'] = '';
    $system_settings['system_name'] = 'PIMS';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Print - <?php echo htmlspecialchars($item['property_no'] ?: 'Asset ' . $asset_item_id); ?> | PIMS</title>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <style>
        @page {
            size: 300px 300px;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: white;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .print-container {
            width: 300px;
            height: 300px;
            border: 3px solid #000;
            padding: 20px;
            text-align: center;
            background: white;
            box-sizing: border-box;
        }

        .qr-section {
            margin-bottom: 15px;
        }

        .qr-code img {
            width: 150px;
            height: 150px;
            border: 1px solid #ddd;
            padding: 5px;
            background: white;
        }

        .property-section {
            margin-top: 15px;
        }

        .property-no {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .description {
            font-size: 12px;
            color: #666;
        }

        .logo {
            position: relative;
            float: right;
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
        }

        .no-print {
            display: none;
        }

        @media print {
            body {
                padding: 0;
            }

            .print-container {
                width: 300px;
                height: 300px;
                border: 3px solid #000;
                padding: 20px;
                text-align: center;
                background: white;
                box-sizing: border-box;
                margin: 0 auto;
                page-break-after: always;
            }
        }
    </style>
</head>
<body>
    <!-- Preview Controls -->
    <div class="no-print" style="position: fixed; top: 0; left: 0; right: 0; z-index: 9999; background: #fff; border-bottom: 2px solid #ddd; padding: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <div style="display:flex; align-items:center; justify-content: space-between; gap: 10px; max-width: 1100px; margin: 0 auto;">
            <div style="display:flex; align-items:center; gap: 15px;">
                <div style="font-family: Arial, sans-serif; font-size: 16px; font-weight: bold; color: #333;">
                    <strong>🖨️ Print Preview</strong>
                </div>
            </div>
            <div style="display:flex; gap: 8px;">
                <button type="button" onclick="window.print();" style="border: 1px solid #0d6efd; background: #0d6efd; color: #fff; padding: 8px 16px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 5px; font-weight: bold; font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" title="Click to print QR code">
                    <i class="bi bi-printer"></i> Print QR Code
                </button>
                <button type="button" onclick="window.close();" style="border: 1px solid #6c757d; background: #6c757d; color: #fff; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" title="Close window">Close</button>
            </div>
        </div>
    </div>

    <div class="print-container">
        <?php if (!empty($system_settings['system_logo'])): ?>
            <img src="<?php echo '../' . $system_settings['system_logo']; ?>" alt="Logo" class="logo">
        <?php endif; ?>
        
        <div class="qr-section">
            <?php if (!empty($item['qr_code'])): ?>
                <div class="qr-code">
                    <img src="../uploads/qr_codes/<?php echo htmlspecialchars($item['qr_code']); ?>" alt="QR Code">
                </div>
            <?php else: ?>
                <div style="padding: 50px; color: #999;">
                    No QR Code available
                </div>
            <?php endif; ?>
        </div>
        
        <div class="property-section">
            <div class="property-no"><?php echo htmlspecialchars($item['property_no'] ?: 'Asset ' . $asset_item_id); ?></div>
            <div class="description"><?php echo htmlspecialchars($item['description']); ?></div>
        </div>
    </div>

    <script>
        // Close window after printing (if user chooses to print)
        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>
</html>
