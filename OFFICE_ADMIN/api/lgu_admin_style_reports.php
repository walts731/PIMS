<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';
require_once '../includes/lgu_compliance_functions.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if user has correct role
if ($_SESSION['role'] !== 'office_admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Initialize LGU Compliance
$office_id = $_SESSION['office_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
$lgu_compliance = new LGUCompliance($office_id, $user_id);

// Get request parameters
$action = $_GET['action'] ?? '';
$report_type = $_GET['report_type'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

switch ($action) {
    case 'export_admin_style_report':
        exportAdminStyleReport($lgu_compliance, $report_type, $date_from, $date_to);
        break;
        
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * Export Admin-Style Report with LGU Compliance
 */
function exportAdminStyleReport($lgu_compliance, $report_type, $date_from, $date_to) {
    global $conn;
    
    try {
        // Generate report ID
        $report_id = $lgu_compliance->generateReportId($report_type);
        
        // Log report generation start
        $lgu_compliance->logReportGeneration($report_id, $report_type, 'manual', [
            'date_from' => $date_from,
            'date_to' => $date_to
        ]);
        
        $start_time = microtime(true);
        
        // Get office information
        $office_query = "SELECT office_name FROM offices WHERE id = ?";
        $stmt = $conn->prepare($office_query);
        $stmt->bind_param("i", $_SESSION['office_id']);
        $stmt->execute();
        $office_result = $stmt->get_result()->fetch_assoc();
        $office_name = $office_result['office_name'] ?? 'Unknown Office';
        
        // Get fiscal year dates
        $fiscal_year = $lgu_compliance->getFiscalYearDates();
        
        // Get signatories
        $signatories_data = $lgu_compliance->getSignatories();
        $signatories = [];
        foreach ($signatories_data as $signatory) {
            $signatories[$signatory['signatory_type']] = $signatory;
        }
        
        // Get document references
        $document_refs = $lgu_compliance->getDocumentReferences();
        
        // Get data integrity issues
        $integrity_issues = $lgu_compliance->checkDataIntegrity();
        
        // Generate report content based on type
        $report_content = generateAdminStyleReportContent($report_type, $date_from, $date_to, $office_name, $fiscal_year, $signatories, $document_refs, $integrity_issues);
        
        $generation_time = microtime(true) - $start_time;
        
        // Create HTML file
        $filename = "LGU_Admin_Style_{$report_type}_Report_" . date('Y-m-d_H-i-s') . ".html";
        $filepath = "../../uploads/reports/{$filename}";
        
        // Ensure directory exists
        if (!is_dir('../../uploads/reports')) {
            mkdir('../../uploads/reports', 0755, true);
        }
        
        file_put_contents($filepath, $report_content);
        $file_size = filesize($filepath);
        
        // Update report generation status
        $lgu_compliance->updateReportGenerationStatus(
            $report_id, 
            'completed', 
            $filepath, 
            $file_size, 
            null, 
            $generation_time
        );
        
        // Log export activity
        $lgu_compliance->logReportActivity($report_id, $report_type, 'exported', [
            'date_from' => $date_from,
            'date_to' => $date_to,
            'file_path' => $filepath
        ], $filepath);
        
        // Return file for download
        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $file_size);
        readfile($filepath);
        
        // Clean up temporary file
        unlink($filepath);
        
    } catch (Exception $e) {
        error_log("Error exporting admin-style report: " . $e->getMessage());
        
        // Update report generation status with error
        if (isset($report_id)) {
            $lgu_compliance->updateReportGenerationStatus($report_id, 'failed', null, null, null, null, $e->getMessage());
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error generating report: ' . $e->getMessage()]);
    }
}

/**
 * Generate Admin-Style Report Content
 */
function generateAdminStyleReportContent($report_type, $date_from, $date_to, $office_name, $fiscal_year, $signatories, $document_refs, $integrity_issues) {
    global $conn, $_SESSION;
    
    $report_id = 'ADM_' . strtoupper(substr($report_type, 0, 3)) . '_' . date('YmdHis') . '_' . str_pad($_SESSION['office_id'], 3, '0', STR_PAD_LEFT) . '_' . str_pad($_SESSION['user_id'], 4, '0', STR_PAD_LEFT);
    $report_date = date('Y-m-d');
    
    // Start building HTML content with ADMIN styling
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($office_name) . ' - ' . ucfirst($report_type) . ' Report</title>
    <style>
        body {
            font-family: \'Arial\', sans-serif;
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
            line-height: 1.2;
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
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        
        .table th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333;
        }
        
        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .alert {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid;
        }
        
        .alert-danger {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .alert-warning {
            background: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }
        
        .alert-info {
            background: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
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
                <img src="../../img/system_logo.png" alt="PIMS" style="max-width: 250px; max-height: 100px;">
            </div>
            
            <!-- Government header on the right -->
            <div style="flex: 1;">
                <div class="gov-header" style="text-align: center; padding: 0; background: none;">
                    <div class="gov-title">Republic of the Philippines</div>
                    <div class="municipality">' . htmlspecialchars($office_name) . '</div>
                    <div class="province">Province of Albay</div>
                    <div class="print-title">' . ucfirst($report_type) . ' Report</div>
                    <div class="print-subtitle">Generated on ' . date('F j, Y g:i A') . '</div>
                    <div class="print-subtitle">Report Period: ' . date('F j, Y', strtotime($date_from)) . ' - ' . date('F j, Y', strtotime($date_to)) . '</div>
                    <div class="print-subtitle">Fiscal Year: ' . date('Y', strtotime($fiscal_year['start_date'])) . ' (' . date('F j, Y', strtotime($fiscal_year['start_date'])) . ' - ' . date('F j, Y', strtotime($fiscal_year['end_date'])) . ')</div>
                    <div class="print-subtitle">Report ID: ' . $report_id . '</div>
                </div>
            </div>
        </div>
    </div>';

    // Add data integrity alerts if any
    if (!empty($integrity_issues)) {
        $html .= '
    <div class="section">
        <div class="section-title">Data Integrity Alerts</div>';
        
        foreach ($integrity_issues as $issue) {
            $alert_class = $issue['severity'] === 'critical' ? 'alert-danger' : ($issue['severity'] === 'high' ? 'alert-warning' : 'alert-info');
            $html .= '
            <div class="alert ' . $alert_class . '">
                <strong>' . htmlspecialchars($issue['description']) . '</strong><br>
                <small>Issue: ' . htmlspecialchars($issue['issue_type']) . ' | Expected: ' . htmlspecialchars($issue['expected']) . ', Actual: ' . htmlspecialchars($issue['actual']) . '</small>
            </div>';
        }
        
        $html .= '
    </div>';
    }

    // Add document references if any
    if (!empty($document_refs)) {
        $html .= '
    <div class="section">
        <div class="section-title">Document References</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Document Type</th>
                    <th>Document Number</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Supplier</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach (array_slice($document_refs, 0, 10) as $doc) {
            $html .= '
                <tr>
                    <td>' . htmlspecialchars($doc['document_type']) . '</td>
                    <td>' . htmlspecialchars($doc['document_number']) . '</td>
                    <td>' . date('M j, Y', strtotime($doc['document_date'])) . '</td>
                    <td>₱' . number_format($doc['reference_amount'], 2) . '</td>
                    <td>' . htmlspecialchars($doc['supplier_name'] ?? 'N/A') . '</td>
                </tr>';
        }
        
        $html .= '
            </tbody>
        </table>
    </div>';
    }

    // Generate report-specific content
    switch ($report_type) {
        case 'inventory':
            $html .= generateInventoryReportContent($date_from, $date_to);
            break;
        case 'asset':
            $html .= generateAssetReportContent($date_from, $date_to);
            break;
        case 'consumable':
            $html .= generateConsumableReportContent($date_from, $date_to);
            break;
        case 'borrow_request':
            $html .= generateBorrowRequestReportContent($date_from, $date_to);
            break;
    }

    // Add signatory section
    $html .= generateAdminStyleSignatorySection($signatories);

    // Add footer
    $html .= '
    <div class="footer">
        <p>Property Inventory Management System (PIMS) - ' . ucfirst($report_type) . ' Report</p>
        <p>This report was generated on ' . date('F j, Y g:i A') . ' by ' . ($_SESSION['firstname'] ?? 'Unknown') . ' ' . ($_SESSION['lastname'] ?? 'User') . '</p>
        <p>Report ID: ' . $report_id . ' | Office: ' . htmlspecialchars($office_name) . '</p>
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
</html>';

    return $html;
}

/**
 * Generate Inventory Report Content (Admin Style)
 */
function generateInventoryReportContent($date_from, $date_to) {
    global $conn, $_SESSION;
    
    $office_id = $_SESSION['office_id'];
    
    $html = '
    <div class="section">
        <div class="section-title">Inventory Summary</div>';
    
    // Asset inventory
    $asset_query = "SELECT 
        COUNT(*) as total_assets,
        SUM(CASE WHEN status IN ('serviceable', 'available', 'in_use') THEN 1 ELSE 0 END) as functional_assets,
        SUM(CASE WHEN status IN ('unserviceable', 'maintenance', 'disposed') THEN 1 ELSE 0 END) as non_functional_assets,
        SUM(CASE WHEN status = 'no_tag' THEN 1 ELSE 0 END) as untagged_assets,
        COALESCE(SUM(value), 0) as total_asset_value
        FROM asset_items WHERE office_id = ?";
    
    $stmt = $conn->prepare($asset_query);
    $stmt->bind_param("i", $office_id);
    $stmt->execute();
    $asset_data = $stmt->get_result()->fetch_assoc();
    
    $html .= '
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Total Assets:</span>
                    <span class="info-value">' . $asset_data['total_assets'] . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Functional Assets:</span>
                    <span class="info-value">' . $asset_data['functional_assets'] . '</span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Non-Functional Assets:</span>
                    <span class="info-value">' . $asset_data['non_functional_assets'] . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Untagged Assets:</span>
                    <span class="info-value">' . $asset_data['untagged_assets'] . '</span>
                </div>
            </div>
        </div>
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Total Asset Value:</span>
                    <span class="info-value text-value">₱' . number_format($asset_data['total_asset_value'], 2) . '</span>
                </div>
            </div>
        </div>';
    
    // Consumable inventory
    $consumable_query = "SELECT 
        COUNT(*) as total_consumables,
        SUM(quantity) as total_quantity,
        SUM(CASE WHEN quantity <= reorder_level THEN 1 ELSE 0 END) as low_stock_items,
        COALESCE(SUM(quantity * unit_cost), 0) as total_value
        FROM consumables WHERE office_id = ?";
    
    $stmt = $conn->prepare($consumable_query);
    $stmt->bind_param("i", $office_id);
    $stmt->execute();
    $consumable_data = $stmt->get_result()->fetch_assoc();
    
    $html .= '
        <h4 style="margin-top: 30px; margin-bottom: 15px;">Consumables</h4>
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Total Consumable Types:</span>
                    <span class="info-value">' . $consumable_data['total_consumables'] . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total Quantity:</span>
                    <span class="info-value">' . $consumable_data['total_quantity'] . '</span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Low Stock Items:</span>
                    <span class="info-value">' . $consumable_data['low_stock_items'] . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total Value:</span>
                    <span class="info-value text-value">₱' . number_format($consumable_data['total_value'], 2) . '</span>
                </div>
            </div>
        </div>
    </div>';
    
    return $html;
}

/**
 * Generate Asset Report Content (Admin Style)
 */
function generateAssetReportContent($date_from, $date_to) {
    global $conn, $_SESSION;
    
    $office_id = $_SESSION['office_id'];
    
    $html = '
    <div class="section">
        <div class="section-title">Asset Details</div>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Description</th>
                    <th>Property No.</th>
                    <th>Status</th>
                    <th>Value</th>
                    <th>Acquisition Date</th>
                    <th>End User</th>
                    <th>Category</th>
                </tr>
            </thead>
            <tbody>';
    
    $query = "SELECT ai.id, ai.description, ai.property_no, ai.status, ai.value, 
                     ai.acquisition_date, ai.end_user, ac.category_name
              FROM asset_items ai
              LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id
              WHERE ai.office_id = ?
              ORDER BY ai.description";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $office_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $status_class = $row['status'];
        $html .= '
                <tr>
                    <td>' . $row['id'] . '</td>
                    <td>' . htmlspecialchars($row['description']) . '</td>
                    <td>' . htmlspecialchars($row['property_no'] ?? 'N/A') . '</td>
                    <td><span class="status-badge status-' . $status_class . '">' . ucfirst($row['status']) . '</span></td>
                    <td>₱' . number_format($row['value'], 2) . '</td>
                    <td>' . ($row['acquisition_date'] ? date('M j, Y', strtotime($row['acquisition_date'])) : 'N/A') . '</td>
                    <td>' . htmlspecialchars($row['end_user'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($row['category_name'] ?? 'N/A') . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
    </div>';
    
    return $html;
}

/**
 * Generate Consumable Report Content (Admin Style)
 */
function generateConsumableReportContent($date_from, $date_to) {
    global $conn, $_SESSION;
    
    $office_id = $_SESSION['office_id'];
    
    $html = '
    <div class="section">
        <div class="section-title">Consumable Details</div>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Unit Cost</th>
                    <th>Total Value</th>
                    <th>Reorder Level</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';
    
    $query = "SELECT id, description, quantity, unit, unit_cost, reorder_level
              FROM consumables 
              WHERE office_id = ?
              ORDER BY description";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $office_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $status = $row['quantity'] <= $row['reorder_level'] ? 'Low Stock' : 'In Stock';
        $status_class = $row['quantity'] <= $row['reorder_level'] ? 'status-red-tagged' : 'status-serviceable';
        
        $html .= '
                <tr>
                    <td>' . $row['id'] . '</td>
                    <td>' . htmlspecialchars($row['description']) . '</td>
                    <td>' . $row['quantity'] . '</td>
                    <td>' . htmlspecialchars($row['unit']) . '</td>
                    <td>₱' . number_format($row['unit_cost'], 2) . '</td>
                    <td>₱' . number_format($row['quantity'] * $row['unit_cost'], 2) . '</td>
                    <td>' . $row['reorder_level'] . '</td>
                    <td><span class="status-badge ' . $status_class . '">' . $status . '</span></td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
    </div>';
    
    return $html;
}

/**
 * Generate Borrow Request Report Content (Admin Style)
 */
function generateBorrowRequestReportContent($date_from, $date_to) {
    global $conn, $_SESSION;
    
    $office_id = $_SESSION['office_id'];
    
    $html = '
    <div class="section">
        <div class="section-title">Borrow Requests</div>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Asset</th>
                    <th>Requested By</th>
                    <th>Purpose</th>
                    <th>Request Date</th>
                    <th>Status</th>
                    <th>Approved By</th>
                    <th>Approval Date</th>
                </tr>
            </thead>
            <tbody>';
    
    $query = "SELECT br.id, ai.description as asset_description, 
                     CONCAT(req.firstname, ' ', req.lastname) as requested_by_name,
                     br.purpose, br.created_at, br.status, br.approved_at,
                     CONCAT(app.firstname, ' ', app.lastname) as approved_by_name
              FROM borrow_requests br
              LEFT JOIN asset_items ai ON br.asset_id = ai.id
              LEFT JOIN employees req ON br.requested_by = req.id
              LEFT JOIN employees app ON br.approved_by = app.id
              WHERE (br.requested_to_office = ? OR br.requested_by_office = ?)
              AND br.created_at BETWEEN ? AND ?
              ORDER BY br.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiss", $office_id, $office_id, $date_from, $date_to);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $html .= '
                <tr>
                    <td>' . $row['id'] . '</td>
                    <td>' . htmlspecialchars($row['asset_description'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($row['requested_by_name']) . '</td>
                    <td>' . htmlspecialchars($row['purpose']) . '</td>
                    <td>' . date('M j, Y H:i', strtotime($row['created_at'])) . '</td>
                    <td>' . htmlspecialchars($row['status']) . '</td>
                    <td>' . htmlspecialchars($row['approved_by_name'] ?? 'N/A') . '</td>
                    <td>' . ($row['approved_at'] ? date('M j, Y H:i', strtotime($row['approved_at'])) : 'N/A') . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
    </div>';
    
    return $html;
}

/**
 * Generate Admin-Style Signatory Section
 */
function generateAdminStyleSignatorySection($signatories) {
    $html = '
    <div class="section">
        <div class="section-title">Certification</div>
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Prepared by:</span>
                    <span class="info-value">' . htmlspecialchars($signatories['prepared']['full_name'] ?? '_____________________') . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Designation:</span>
                    <span class="info-value">' . htmlspecialchars($signatories['prepared']['designation'] ?? '_____________________') . '</span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Noted by:</span>
                    <span class="info-value">' . htmlspecialchars($signatories['noted']['full_name'] ?? '_____________________') . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Designation:</span>
                    <span class="info-value">' . htmlspecialchars($signatories['noted']['designation'] ?? '_____________________') . '</span>
                </div>
            </div>
        </div>
        <div class="info-grid" style="margin-top: 30px;">
            <div>
                <div class="info-item">
                    <span class="info-label">Approved by:</span>
                    <span class="info-value">' . htmlspecialchars($signatories['approved']['full_name'] ?? '_____________________') . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Designation:</span>
                    <span class="info-value">' . htmlspecialchars($signatories['approved']['designation'] ?? '_____________________') . '</span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Certified by:</span>
                    <span class="info-value">' . htmlspecialchars($signatories['certified']['full_name'] ?? '_____________________') . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Designation:</span>
                    <span class="info-value">' . htmlspecialchars($signatories['certified']['designation'] ?? '_____________________') . '</span>
                </div>
            </div>
        </div>
    </div>';
    
    return $html;
}
?>
