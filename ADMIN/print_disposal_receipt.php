<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Get red tag ID
$red_tag_id = intval($_GET['id'] ?? 0);
if ($red_tag_id === 0) {
    die('Invalid red tag ID');
}

// Get red tag details
$red_tag = [];
try {
    $sql = "SELECT rt.*, ai.description as asset_description, ai.property_no, ai.inventory_tag, 
                   ai.value, ai.acquisition_date, a.description as category_name, o.office_name,
                   CONCAT(e.firstname, ' ', e.lastname) as disposed_by_name, e.employee_no
            FROM red_tags rt
            LEFT JOIN asset_items ai ON rt.asset_item_id = ai.id
            LEFT JOIN assets a ON ai.asset_id = a.id
            LEFT JOIN offices o ON rt.office_id = o.id
            LEFT JOIN employees e ON rt.updated_by = e.id
            WHERE rt.id = ? AND rt.action = 'disposed'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $red_tag_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        die('Disposal record not found');
    }
    
    $red_tag = $result->fetch_assoc();
    $stmt->close();
    
} catch (Exception $e) {
    die('Error fetching disposal record: ' . $e->getMessage());
}

// Get system settings
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
    // Fallback to default
    $system_settings['system_logo'] = '';
    $system_settings['system_name'] = 'PIMS';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disposal Receipt - <?php echo htmlspecialchars($red_tag['control_no']); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        @page {
            size: A4;
            margin: 0.5in;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: white;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 2rem;
            border-bottom: 2px solid #000;
            padding-bottom: 1rem;
        }
        
        .receipt-header img {
            max-height: 60px;
            margin-bottom: 0.5rem;
        }
        
        .receipt-header h3 {
            margin: 0.5rem 0;
            font-weight: 600;
            color: #191BA9;
        }
        
        .receipt-header h4 {
            margin: 0;
            font-weight: 500;
            color: #dc3545;
        }
        
        .receipt-section {
            margin-bottom: 1.5rem;
        }
        
        .receipt-section h5 {
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #191BA9;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 0.25rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.25rem 0;
        }
        
        .info-label {
            font-weight: 500;
            color: #495057;
        }
        
        .info-value {
            font-weight: 400;
            text-align: right;
        }
        
        .disposal-reason {
            background: #f8f9fa;
            padding: 0.75rem;
            border-radius: 4px;
            border-left: 4px solid #dc3545;
            margin: 1rem 0;
        }
        
        .receipt-footer {
            margin-top: 3rem;
            text-align: center;
            font-size: 10px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            padding-top: 1rem;
        }
        
        .signature-section {
            margin-top: 2rem;
        }
        
        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .signature-box {
            text-align: center;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            margin-bottom: 0.5rem;
            height: 40px;
        }
        
        .signature-label {
            font-size: 10px;
            color: #495057;
        }
        
        @media print {
            body {
                font-size: 11px;
            }
            
            .receipt-header {
                margin-bottom: 1.5rem;
            }
            
            .receipt-section {
                margin-bottom: 1rem;
            }
            
            .signature-section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Receipt Header -->
        <div class="receipt-header">
            <?php 
            $logo_path = !empty($system_settings['system_logo']) ? '../' . htmlspecialchars($system_settings['system_logo']) : '../img/trans_logo.png';
            $system_name = htmlspecialchars($system_settings['system_name'] ?? 'PIMS');
            ?>
            <img src="<?php echo $logo_path; ?>" alt="<?php echo $system_name; ?> Logo">
            <h3><?php echo $system_name; ?></h3>
            <h4>DISPOSAL RECEIPT</h4>
            <p class="mb-0"><strong>Control No:</strong> <?php echo htmlspecialchars($red_tag['control_no']); ?></p>
            <p class="mb-0"><strong>Red Tag No:</strong> <?php echo htmlspecialchars($red_tag['red_tag_no']); ?></p>
        </div>
        
        <!-- Item Information -->
        <div class="receipt-section">
            <h5><i class="bi bi-box"></i> Item Information</h5>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Item Description:</span>
                    <span class="info-value"><?php echo htmlspecialchars($red_tag['item_description']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Category:</span>
                    <span class="info-value"><?php echo htmlspecialchars($red_tag['category_name'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Property No.:</span>
                    <span class="info-value"><?php echo htmlspecialchars($red_tag['property_no'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Inventory Tag:</span>
                    <span class="info-value"><?php echo htmlspecialchars($red_tag['inventory_tag'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Acquisition Date:</span>
                    <span class="info-value"><?php echo $red_tag['acquisition_date'] ? date('M d, Y', strtotime($red_tag['acquisition_date'])) : 'N/A'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Item Value:</span>
                    <span class="info-value">₱<?php echo number_format($red_tag['value'] ?? 0, 2); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Disposal Information -->
        <div class="receipt-section">
            <h5><i class="bi bi-trash3"></i> Disposal Information</h5>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Disposal Date:</span>
                    <span class="info-value"><?php echo date('M d, Y', strtotime($red_tag['disposal_date'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Office:</span>
                    <span class="info-value"><?php echo htmlspecialchars($red_tag['office_name'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date Received:</span>
                    <span class="info-value"><?php echo date('M d, Y', strtotime($red_tag['date_received'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tagged By:</span>
                    <span class="info-value"><?php echo htmlspecialchars($red_tag['tagged_by']); ?></span>
                </div>
            </div>
            
            <div class="disposal-reason">
                <strong>Disposal Reason:</strong><br>
                <?php echo nl2br(htmlspecialchars($red_tag['disposal_reason'])); ?>
            </div>
        </div>
        
        <!-- Signatures -->
        <div class="signature-section">
            <h5><i class="bi bi-pen"></i> Signatures</h5>
            <div class="signature-grid">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Disposed By</div>
                    <div class="signature-label"><?php echo htmlspecialchars($red_tag['disposed_by_name'] ?? 'N/A'); ?></div>
                    <div class="signature-label"><?php echo htmlspecialchars($red_tag['employee_no'] ?? ''); ?></div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Approved By</div>
                    <div class="signature-label">_________________________</div>
                    <div class="signature-label">Signature & Date</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Witnessed By</div>
                    <div class="signature-label">_________________________</div>
                    <div class="signature-label">Signature & Date</div>
                </div>
            </div>
        </div>
        
        <!-- Receipt Footer -->
        <div class="receipt-footer">
            <p class="mb-0">
                <strong>Generated on:</strong> <?php echo date('F d, Y h:i A'); ?> | 
                <strong>System:</strong> <?php echo $system_name; ?> | 
                <strong>User:</strong> <?php echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']); ?>
            </p>
            <p class="mb-0">This is an official document of the Property Inventory Management System (PIMS)</p>
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
