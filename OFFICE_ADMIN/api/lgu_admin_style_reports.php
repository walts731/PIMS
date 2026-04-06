<?php

// Handle session ID from URL for popup windows (before session_start)
if (isset($_GET['PHPSESSID'])) {
    session_id($_GET['PHPSESSID']);
}

session_start();

// Set timezone to Philippine Standard Time
date_default_timezone_set('Asia/Manila');

require_once 'C:\xampp\htdocs\PIMS\config.php';
require_once 'C:\xampp\htdocs\PIMS\includes\system_functions.php';
require_once 'C:\xampp\htdocs\PIMS\includes\logger.php';
require_once '../includes/lgu_compliance_functions.php';

// Check session timeout
checkSessionTimeout();

// Debug: Log session state
error_log("Preview API Session Debug: " . json_encode([
    'session_id' => session_id(),
    'logged_in' => $_SESSION['logged_in'] ?? 'not_set',
    'role' => $_SESSION['role'] ?? 'not_set',
    'office_id' => $_SESSION['office_id'] ?? 'not_set',
    'user_id' => $_SESSION['user_id'] ?? 'not_set',
    'firstname' => $_SESSION['firstname'] ?? 'not_set',
    'lastname' => $_SESSION['lastname'] ?? 'not_set',
    'first_name' => $_SESSION['first_name'] ?? 'not_set',
    'last_name' => $_SESSION['last_name'] ?? 'not_set',
    'all_session_keys' => array_keys($_SESSION)
]));

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access - Session not logged in']);
    exit();
}

// Check if user has correct role
if ($_SESSION['role'] !== 'office_admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied - Invalid role: ' . ($_SESSION['role'] ?? 'not_set')]);
    exit();
}

// Initialize LGU Compliance
$office_id = $_SESSION['office_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
$lgu_compliance = new LGUCompliance($office_id, $user_id);

// Get user details for display
$user_firstname = $_SESSION['firstname'] ?? $_SESSION['first_name'] ?? null;
$user_lastname = $_SESSION['lastname'] ?? $_SESSION['last_name'] ?? null;

// If firstname/lastname not in session, fetch from database
if (!$user_firstname || !$user_lastname) {
    try {
        // Try users table first (with underscore field names)
        $user_query = "SELECT first_name, last_name FROM users WHERE id = ?";
        $stmt = $conn->prepare($user_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_result = $stmt->get_result()->fetch_assoc();
        if ($user_result) {
            $user_firstname = $user_result['first_name'];
            $user_lastname = $user_result['last_name'];
            // Store in session for future use
            $_SESSION['firstname'] = $user_firstname;
            $_SESSION['lastname'] = $user_lastname;
            $_SESSION['first_name'] = $user_firstname;
            $_SESSION['last_name'] = $user_lastname;
        } else {
            // Try employees table as fallback
            $emp_query = "SELECT firstname, lastname FROM employees WHERE id = ?";
            $stmt = $conn->prepare($emp_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $emp_result = $stmt->get_result()->fetch_assoc();
            if ($emp_result) {
                $user_firstname = $emp_result['firstname'];
                $user_lastname = $emp_result['lastname'];
                // Store in session for future use
                $_SESSION['firstname'] = $user_firstname;
                $_SESSION['lastname'] = $user_lastname;
                $_SESSION['first_name'] = $user_firstname;
                $_SESSION['last_name'] = $user_lastname;
            }
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching user details: " . $e->getMessage());
    }
}

// Get request parameters
$action = $_GET['action'] ?? '';
$report_type = $_GET['report_type'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-t'); // Last day of current month

switch ($action) {
    case 'export_admin_style_report':
        exportAdminStyleReport($lgu_compliance, $report_type, $date_from, $date_to, $user_firstname, $user_lastname);
        break;
        
    case 'preview_admin_style_report':
        previewAdminStyleReport($lgu_compliance, $report_type, $date_from, $date_to, $user_firstname, $user_lastname);
        break;
        
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * Preview Admin-Style Report with LGU Compliance
 */
function previewAdminStyleReport($lgu_compliance, $report_type, $date_from, $date_to, $user_firstname, $user_lastname) {
    global $conn;
    
    try {
        // Get system settings for logo
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
            $system_settings['system_logo'] = '';
            $system_settings['system_name'] = 'PIMS';
        }
        
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
        
        // Generate report content
        $report_content = generateAdminStyleReportContent($report_type, $date_from, $date_to, $office_name, $fiscal_year, $signatories, $document_refs, $integrity_issues, $system_settings, $user_firstname, $user_lastname);
        
        // Return HTML content for preview
        header('Content-Type: text/html; charset=UTF-8');
        echo $report_content;
        
    } catch (Exception $e) {
        error_log("Error previewing admin-style report: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error generating preview: ' . $e->getMessage()]);
    }
}

/**
 * Export Admin-Style Report with LGU Compliance
 */
function exportAdminStyleReport($lgu_compliance, $report_type, $date_from, $date_to, $user_firstname, $user_lastname) {
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
        
        // Get system settings for logo
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
            // Fallback to default if database fails
            $system_settings['system_logo'] = '';
            $system_settings['system_name'] = 'PIMS';
        }
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
        $report_content = generateAdminStyleReportContent($report_type, $date_from, $date_to, $office_name, $fiscal_year, $signatories, $document_refs, $integrity_issues, $system_settings, $user_firstname, $user_lastname);
        
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
function generateAdminStyleReportContent($report_type, $date_from, $date_to, $office_name, $fiscal_year, $signatories, $document_refs, $integrity_issues, $system_settings, $user_firstname, $user_lastname) {
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
            width: 100%;
            min-height: 100vh;
            transform: rotate(0deg);
            transform-origin: top left;
        }
        
        .print-header {
            text-align: left;
            margin-bottom: 15px;
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
            padding: 15px;
            margin-bottom: 5px;
        }
        
        .section {
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #0c5460;
            border-bottom: 2px solid #0c5460;
            padding-bottom: 5px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        
        .data-table th,
        .data-table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }
        
        .data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            white-space: nowrap;
        }
        
        .data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .summary-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        
        .summary-label {
            font-weight: bold;
            color: #0c5460;
        }
        
        .summary-value {
            font-size: 14px;
            font-weight: bold;
        }
        
        .alert {
            padding: 8px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid;
            font-size: 11px;
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
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="print-header">
        <!-- Top section with all elements -->
        <div style="display: flex; align-items: flex-start; gap: 20px; margin-bottom: 20px;">
            <!-- Logo on the left -->
            <div style="flex-shrink: 0;">
                <img src="' . (!empty($system_settings['system_logo']) ? '../../' . htmlspecialchars($system_settings['system_logo']) : '../../img/system_logo.png') . '" alt="' . htmlspecialchars($system_settings['system_name']) . '" style="max-width: 250px; max-height: 100px;">
            </div>
            
            <!-- Government header centered in the middle -->
            <div style="flex: 1; text-align: center;">
                <div class="gov-header" style="padding: 0; background: none;">
                    <div class="gov-title"><h1>Republic of the Philippines</h1></div>
                    <div class="municipality"><h4>' . htmlspecialchars($office_name) . '</h4></div>
                    <div class="province"><h3>Province of Sorsogon</h3></div>
                    <div class="print-title">' . ucfirst($report_type) . ' Report</div>
                    <div class="print-subtitle">Report Period: ' . date('F j, Y', strtotime($date_from)) . ' - ' . date('F j, Y', strtotime($date_to)) . '</div>
                </div>
            </div>
            
            <!-- Additional info on the right -->
            <div style="flex-shrink: 0;">
                <div style="text-align: right; font-size: 12px; color: #666;">
                    <div>System: ' . htmlspecialchars($system_settings['system_name'] ?? 'PIMS') . '</div>
                    <div>Generated by: ' . htmlspecialchars($user_firstname ?? 'Unknown') . ' ' . htmlspecialchars($user_lastname ?? 'User') . '</div>
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
        case 'asset_consumable':
            // Combined report with assets and consumables on same level
            $html .= generateAssetConsumableReportContent($date_from, $date_to);
            break;
        default:
            $html .= '<p>Report type not supported.</p>';
            break;
    }

    // Add signatory section
    $html .= generateAdminStyleSignatorySection($signatories);

    // Add footer
    $html .= '
    <div class="footer">
        <p>Property Inventory Management System (PIMS) - ' . ucfirst($report_type) . ' Report</p>
        <p>This report was generated on ' . date('F j, Y g:i A') . ' by ' . htmlspecialchars($user_firstname ?? 'Unknown') . ' ' . htmlspecialchars($user_lastname ?? 'User') . '</p>
        <p>Report ID: ' . $report_id . ' | Office: ' . htmlspecialchars($office_name) . '</p>
    </div>

    <div class="print-actions" style="text-align: center; margin: 20px 0; display: block;">
        <button onclick="printReport()" style="background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px; margin: 0 10px;">
            🖨 Print Report
        </button>
        <button onclick="window.close()" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px; margin: 0 10px;">
            ❌ Close Preview
        </button>
    </div>

    <script>
        // Remove auto-print for preview mode
        // User can print manually after filling certification
        
        // Function to check if certification inputs have values
        function checkCertificationInputs() {
            const inputs = document.querySelectorAll(\'.certification-input\');
            const rows = document.querySelectorAll(\'.certification-row\');
            
            // Debug: Log counts
            console.log(\'Found inputs:\', inputs.length);
            console.log(\'Found rows:\', rows.length);
            
            // Check each row - if ANY input in the row is filled, show the row
            rows.forEach((row, rowIndex) => {
                const rowInputs = row.querySelectorAll(\'.certification-input\');
                let hasContent = false;
                
                // Check if any input in this row has content
                rowInputs.forEach(input => {
                    if (input.value.trim() !== \'\') {
                        hasContent = true;
                    }
                });
                
                if (hasContent) {
                    row.classList.add(\'has-content\');
                    row.classList.remove(\'empty-content\');
                } else {
                    row.classList.add(\'empty-content\');
                    row.classList.remove(\'has-content\');
                }
            });
        }
        
        // Check inputs on page load
        window.onload = function() {
            setTimeout(function() {
                checkCertificationInputs();
                alert("Please fill in the certification details and click the Print Report button when ready.");
            }, 1000);
        };
        
        // Check inputs on input change
        document.addEventListener(\'input\', function(e) {
            if (e.target.classList.contains(\'certification-input\')) {
                checkCertificationInputs();
            }
        });
        
        // Print function
        function printReport() {
            checkCertificationInputs(); // Ensure latest state before printing
            window.print();
        }
        
        // Close window after printing (if user uses browser print)
        window.onafterprint = function() {
            setTimeout(function() {
                if (confirm(\'Report printed successfully. Close this window?\')) {
                    window.close();
                }
            }, 500);
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
    
    $html = '
    <div class="section">
        <div class="section-title">Inventory Summary</div>
        
        <div class="inventory-grid">
            <!-- Assets Section -->
            <div class="inventory-group">
                <h4 class="group-title">Assets</h4>
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
                </div>
            </div>
            
            <!-- Consumables Section -->
            <div class="inventory-group">
                <h4 class="group-title">Consumables</h4>
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
 * Generate Combined Asset-Consumable Report Content (Admin Style)
 */
function generateAssetConsumableReportContent($date_from, $date_to) {
    global $conn, $_SESSION;
    
    $office_id = $_SESSION['office_id'];
    
    $html = '
    <div class="combined-report">
        <div class="report-row">
            <div class="report-section">
                <div class="section-title">Asset Summary</div>';
    
    // Asset data
    $asset_query = "SELECT 
        COUNT(*) as total_assets,
        SUM(CASE WHEN status IN ('serviceable', 'available', 'in_use') THEN 1 ELSE 0 END) as functional_assets,
        COALESCE(SUM(value), 0) as total_asset_value
        FROM asset_items WHERE office_id = ?";
    
    $stmt = $conn->prepare($asset_query);
    $stmt->bind_param("i", $office_id);
    $stmt->execute();
    $asset_data = $stmt->get_result()->fetch_assoc();
    
    $html .= '
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">Total Assets:</div>
                        <div class="summary-value">' . $asset_data['total_assets'] . '</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Functional:</div>
                        <div class="summary-value">' . $asset_data['functional_assets'] . '</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Total Value:</div>
                        <div class="summary-value">₱' . number_format($asset_data['total_asset_value'], 2) . '</div>
                    </div>
                </div>
            </div>
            
            <div class="report-section">
                <div class="section-title">Consumable Summary</div>';
    
    // Consumable data
    $consumable_query = "SELECT 
        COUNT(*) as total_consumables,
        SUM(quantity) as total_quantity,
        SUM(CASE WHEN quantity <= reorder_level THEN 1 ELSE 0 END) as low_stock
        FROM consumables WHERE office_id = ?";
    
    $stmt = $conn->prepare($consumable_query);
    $stmt->bind_param("i", $office_id);
    $stmt->execute();
    $consumable_data = $stmt->get_result()->fetch_assoc();
    
    $html .= '
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">Total Items:</div>
                        <div class="summary-value">' . $consumable_data['total_consumables'] . '</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Total Quantity:</div>
                        <div class="summary-value">' . $consumable_data['total_quantity'] . '</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Low Stock:</div>
                        <div class="summary-value">' . $consumable_data['low_stock'] . '</div>
                    </div>
                </div>
            </div>
        </div>
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
    <div class="report-section">
        <h3 class="section-title">Borrow Requests Report</h3>
        <p class="text-muted">Showing all requests for Office ID: ' . $office_id . ' (both incoming and outgoing)</p>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Asset Description</th>
                    <th>Requested By</th>
                    <th>From Office</th>
                    <th>To Office</th>
                    <th>Purpose</th>
                    <th>Request Date</th>
                    <th>Status</th>
                    <th>Approved By</th>
                    <th>Approval Date</th>
                </tr>
            </thead>
            <tbody>';
    
    $query = "SELECT br.id, ai.description as asset_description, 
                     CONCAT(req.first_name, ' ', req.last_name) as requested_by_name,
                     req_o.office_name as requested_by_office,
                     to_o.office_name as requested_to_office,
                     br.purpose, br.created_at, br.status, br.approved_at,
                     CONCAT(app.first_name, ' ', app.last_name) as approved_by_name,
                     br.quantity_requested, br.start_date, br.end_date
              FROM borrow_requests br
              LEFT JOIN asset_items ai ON br.asset_id = ai.id
              LEFT JOIN users req ON br.requested_by = req.id
              LEFT JOIN offices req_o ON br.requested_by_office = req_o.id
              LEFT JOIN offices to_o ON br.requested_to_office = to_o.id
              LEFT JOIN users app ON br.approved_by = app.id
              WHERE (br.requested_to_office = ? OR br.requested_by_office = ?)
              AND DATE(br.created_at) BETWEEN ? AND ?
              ORDER BY br.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiss", $office_id, $office_id, $date_from, $date_to);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $request_count = 0;
    while ($row = $result->fetch_assoc()) {
        $request_count++;
        $html .= '
                <tr>
                    <td>' . $row['id'] . '</td>
                    <td>' . htmlspecialchars($row['asset_description'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($row['requested_by_name'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($row['requested_by_office'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($row['requested_to_office'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($row['purpose']) . '</td>
                    <td>' . date('M j, Y H:i', strtotime($row['created_at'])) . '</td>
                    <td>' . htmlspecialchars(ucfirst($row['status'])) . '</td>
                    <td>' . htmlspecialchars($row['approved_by_name'] ?? 'N/A') . '</td>
                    <td>' . ($row['approved_at'] ? date('M j, Y H:i', strtotime($row['approved_at'])) : 'N/A') . '</td>
                </tr>';
    }
    
    if ($request_count == 0) {
        $html .= '
                <tr>
                    <td colspan="10" class="text-center text-muted">No borrow requests found for your office in the selected period.</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
        <p class="text-muted">Total requests found: ' . $request_count . '</p>
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
        <div class="certification-section">
            <div class="certification-row">
                <div class="certification-item">
                    <label>Prepared by:</label>
                    <div class="certification-input-wrapper">
                        <input type="text" class="certification-input certification-name" value="' . htmlspecialchars($signatories['prepared']['full_name'] ?? '') . '" placeholder="Enter name">
                        <div class="signature-line"></div>
                        <input type="text" class="certification-input certification-designation" value="' . htmlspecialchars($signatories['prepared']['designation'] ?? '') . '" placeholder="Enter designation">
                    </div>
                </div>
                <div class="certification-item">
                    <label>Noted by:</label>
                    <div class="certification-input-wrapper">
                        <input type="text" class="certification-input certification-name" value="' . htmlspecialchars($signatories['noted']['full_name'] ?? '') . '" placeholder="Enter name">
                        <div class="signature-line"></div>
                        <input type="text" class="certification-input certification-designation" value="' . htmlspecialchars($signatories['noted']['designation'] ?? '') . '" placeholder="Enter designation">
                    </div>
                </div>
            </div>
            <div class="certification-row">
                <div class="certification-item">
                    <label>Approved by:</label>
                    <div class="certification-input-wrapper">
                        <input type="text" class="certification-input certification-name" value="' . htmlspecialchars($signatories['approved']['full_name'] ?? '') . '" placeholder="Enter name">
                        <div class="signature-line"></div>
                        <input type="text" class="certification-input certification-designation" value="' . htmlspecialchars($signatories['approved']['designation'] ?? '') . '" placeholder="Enter designation">
                    </div>
                </div>
                <div class="certification-item">
                    <label>Certified by:</label>
                    <div class="certification-input-wrapper">
                        <input type="text" class="certification-input certification-name" value="' . htmlspecialchars($signatories['certified']['full_name'] ?? '') . '" placeholder="Enter name">
                        <div class="signature-line"></div>
                        <input type="text" class="certification-input certification-designation" value="' . htmlspecialchars($signatories['certified']['designation'] ?? '') . '" placeholder="Enter designation">
                    </div>
                </div>
            </div>
        </div>
    </div>';
    
    // Add CSS for certification section
    $html .= '
    <style>
        .certification-section {
            margin: 20px 0;
        }
        
        .certification-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .certification-item {
            flex: 1;
        }
        
        .certification-item label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #0c5460;
        }
        
        .certification-input-wrapper {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .signature-line {
            height: 1px;
            background: #333;
            margin: 4px 0;
        }

        .certification-input {
            border: none;
            background: transparent;
            padding: 2px 0;
            font-size: 11px;
            width: 100%;
            outline: none;
            text-align: center;
        }

        .certification-name {
            font-weight: bold;
        }

        .certification-designation {
            font-weight: normal;
        }

        .certification-input:focus {
            background: #f0f8ff;
        }
        
        p.status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .combined-report {
            margin-bottom: 20px;
        }
        
        .report-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .report-section {
            flex: 1;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        
        .report-section .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #0c5460;
            border-bottom: 1px solid #0c5460;
            padding-bottom: 5px;
        }
        
        .report-section .summary-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }
        
        .report-section .summary-item {
            background: white;
            padding: 8px;
            border-radius: 3px;
            border: 1px solid #e9ecef;
        }
        
        .report-section .summary-label {
            font-size: 11px;
            font-weight: bold;
            color: #0c5460;
        }
        
        .report-section .summary-value {
            font-size: 12px;
            font-weight: bold;
        }
        
        /* Inventory Grid Layout */
        .inventory-grid {
            display: flex;
            gap: 30px;
            margin-bottom: 20px;
        }
        
        .inventory-group {
            flex: 1;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .group-title {
            font-size: 16px;
            font-weight: bold;
            color: #0c5460;
            margin: 0 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #0c5460;
            text-align: center;
        }
        
        .inventory-group .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 15px;
        }
        
        .inventory-group .info-item {
            background: white;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #e9ecef;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        
        .inventory-group .info-label {
            font-size: 11px;
            font-weight: bold;
            color: #0c5460;
            display: block;
            margin-bottom: 3px;
        }
        
        .inventory-group .info-value {
            font-size: 13px;
            font-weight: bold;
            color: #000;
        }
        
        .inventory-group .info-value.text-value {
            color: #28a745;
            font-size: 14px;
        }
        
        /* Preview mode - match print layout exactly */
        @media screen {
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                line-height: 1.4;
                margin: 0.25in 0.5in 0.25in 0.5in;
                padding: 0;
                color: #000;
                background: white;
                width: calc(14in - 1in);
                min-height: calc(8.5in - 0.5in);
            }
            
            .print-header {
                margin-bottom: 20px;
                padding: 0;
            }
            
            .section {
                margin-bottom: 20px;
                page-break-inside: avoid;
            }
            
            .section-title {
                font-size: 16px;
                font-weight: bold;
                margin-bottom: 10px;
                color: #0c5460;
                border-bottom: 2px solid #0c5460;
                padding-bottom: 5px;
            }
            
            .data-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
                font-size: 11px;
            }
            
            .data-table th,
            .data-table td {
                border: 1px solid #ddd;
                padding: 6px;
                text-align: left;
                vertical-align: top;
            }
            
            .data-table th {
                background-color: #f2f2f2;
                font-weight: bold;
                white-space: nowrap;
            }
            
            .data-table tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            
            .summary-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-bottom: 20px;
            }
            
            .summary-item {
                background: #f8f9fa;
                padding: 10px;
                border-radius: 5px;
                border: 1px solid #dee2e6;
            }
            
            .summary-label {
                font-weight: bold;
                color: #0c5460;
            }
            
            .summary-value {
                font-size: 14px;
                font-weight: bold;
            }
            
            .alert {
                padding: 8px;
                margin: 10px 0;
                border-radius: 4px;
                border-left: 4px solid;
                font-size: 11px;
            }
            
            .status-badge {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 11px;
                font-weight: bold;
                text-transform: uppercase;
            }
            
            .combined-report {
                margin-bottom: 20px;
            }
            
            .report-row {
                display: flex;
                gap: 20px;
                margin-bottom: 20px;
            }
            
            .report-section {
                flex: 1;
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                border: 1px solid #dee2e6;
            }
            
            .report-section .section-title {
                font-size: 14px;
                font-weight: bold;
                margin-bottom: 10px;
                color: #0c5460;
                border-bottom: 1px solid #0c5460;
                padding-bottom: 5px;
            }
            
            .report-section .summary-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .report-section .summary-item {
                background: white;
                padding: 8px;
                border-radius: 3px;
                border: 1px solid #e9ecef;
            }
            
            .report-section .summary-label {
                font-size: 11px;
                font-weight: bold;
                color: #0c5460;
            }
            
            .report-section .summary-value {
                font-size: 12px;
                font-weight: bold;
            }
            
            .footer {
                margin-top: 20px;
                padding-top: 15px;
                border-top: 1px solid #ccc;
                text-align: center;
                font-size: 11px;
            }
        }
        
        @media print {
            body {
                font-family: Arial, sans-serif;
                font-size: 10px;
                line-height: 1.2;
                margin: 0.15in 0.4in 0.15in 0.4in;
                padding: 0;
                color: #000;
                background: white;
                width: calc(14in - 0.8in);
                min-height: calc(8.5in - 0.3in);
            }
            
            .print-header {
                margin-bottom: 10px !important;
            }
            
            .gov-header {
                margin-bottom: 0px !important;
                padding: 10px !important;
                font-size: 11px !important;
            }
            
            .gov-title h1 {
                font-size: 14px !important;
                margin: 0 !important;
            }
            
            .municipality, .province {
                font-size: 10px !important;
            }
            
            .print-title, .print-subtitle {
                font-size: 9px !important;
            }
            
            .section {
                margin-bottom: 12px !important;
            }
            
            .section-title {
                font-size: 12px !important;
                font-weight: bold;
                margin-bottom: 6px !important;
                color: #0c5460;
                border-bottom: 1px solid #0c5460;
                padding-bottom: 3px !important;
            }
            
            .data-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 12px !important;
                font-size: 9px !important;
            }
            
            .data-table th,
            .data-table td {
                border: 1px solid #ddd;
                padding: 4px !important;
                text-align: left;
                vertical-align: top;
            }
            
            .data-table th {
                background-color: #f2f2f2;
                font-weight: bold;
                white-space: nowrap;
                font-size: 9px !important;
            }
            
            .summary-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 10px !important;
                margin-bottom: 12px !important;
            }
            
            .summary-item {
                background: #f8f9fa;
                padding: 6px !important;
                border-radius: 3px;
                border: 1px solid #dee2e6;
            }
            
            .summary-label {
                font-weight: bold;
                color: #0c5460;
                font-size: 9px !important;
            }
            
            .summary-value {
                font-size: 10px !important;
                font-weight: bold;
            }
            
            .inventory-grid {
                gap: 20px !important;
                margin-bottom: 12px !important;
            }
            
            .inventory-group {
                padding: 12px !important;
            }
            
            .group-title {
                font-size: 12px !important;
                margin: 0 0 10px 0 !important;
                padding-bottom: 5px !important;
                border-bottom: 1px solid #0c5460;
            }
            
            .inventory-group .info-grid {
                gap: 8px !important;
                margin-bottom: 10px !important;
            }
            
            .inventory-group .info-item {
                padding: 6px !important;
            }
            
            .inventory-group .info-label {
                font-size: 9px !important;
                margin-bottom: 2px !important;
            }
            
            .inventory-group .info-value {
                font-size: 10px !important;
            }
            
            .inventory-group .info-value.text-value {
                font-size: 11px !important;
            }
            
            .certification-section {
                margin: 15px 0 !important;
            }

            .certification-row {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 40px !important;
                margin-bottom: 25px !important;
            }

            .certification-input-wrapper {
                display: flex;
                flex-direction: column;
                gap: 2px !important;
            }

            .signature-line {
                display: block !important;
                height: 1px !important;
                background: #333 !important;
                margin: 6px 0 !important;
                width: 100% !important;
            }

            .certification-input {
                border: none !important;
                background: transparent !important;
                font-size: 10px !important;
                text-align: center !important;
                padding: 1px 0 !important;
            }

            .certification-name {
                font-weight: bold !important;
                font-size: 10px !important;
            }

            .certification-designation {
                font-weight: normal !important;
                font-size: 9px !important;
            }

            .certification-row.empty-content {
                display: none !important;
            }

            .certification-row.has-content {
                display: grid !important;
            }
            
            .print-actions {
                display: none !important;
            }
            
            .footer {
                text-align: center !important;
                margin-top: 15px !important;
                padding-top: 10px !important;
                border-top: 1px solid #ccc;
                font-size: 9px !important;
            }
            
            @page {
                size: legal landscape;
                margin: 0.15in 0.4in 0.15in 0.4in;
            }
            
            html {
                overflow: hidden;
            }
            
            .combined-report {
                page-break-inside: avoid;
                margin-bottom: 15px;
            }
            
            .report-row {
                gap: 15px;
                margin-bottom: 15px;
            }
            
            .report-section {
                padding: 10px;
            }
            
            .report-section .section-title {
                font-size: 12px;
            }
            
            .report-section .summary-item {
                padding: 6px;
            }
            
            .report-section .summary-label {
                font-size: 10px;
            }
            
            .report-section .summary-value {
                font-size: 11px;
            }
        }
    </style>';
    
    return $html;
}
?>