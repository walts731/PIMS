<?php
session_start();
require_once '../../config.php';
require_once '../../includes/system_functions.php';
require_once '../../includes/logger.php';
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
    case 'export_lgu_report':
        exportLGUComplianceReport($lgu_compliance, $report_type, $date_from, $date_to);
        break;
        
    case 'add_document_reference':
        addDocumentReference($lgu_compliance);
        break;
        
    case 'get_document_references':
        getDocumentReferences($lgu_compliance);
        break;
        
    case 'schedule_report':
        scheduleReport($lgu_compliance);
        break;
        
    case 'get_scheduled_reports':
        getScheduledReports($lgu_compliance);
        break;
        
    case 'get_report_history':
        getReportHistory($lgu_compliance);
        break;
        
    case 'check_data_integrity':
        checkDataIntegrity($lgu_compliance);
        break;
        
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * Export LGU Compliance Report
 */
function exportLGUComplianceReport($lgu_compliance, $report_type, $date_from, $date_to) {
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
        
        // Generate report content based on type
        $report_content = generateReportContent($report_type, $date_from, $date_to, $office_name, $fiscal_year, $signatories);
        
        $generation_time = microtime(true) - $start_time;
        
        // Create HTML file
        $filename = "LGU_{$report_type}_Report_" . date('Y-m-d_H-i-s') . ".html";
        $filepath = "../uploads/reports/{$filename}";
        
        // Ensure directory exists
        if (!is_dir('../uploads/reports')) {
            mkdir('../uploads/reports', 0755, true);
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
        error_log("Error exporting LGU report: " . $e->getMessage());
        
        // Update report generation status with error
        if (isset($report_id)) {
            $lgu_compliance->updateReportGenerationStatus($report_id, 'failed', null, null, null, null, $e->getMessage());
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error generating report: ' . $e->getMessage()]);
    }
}

/**
 * Generate report content based on type
 */
function generateReportContent($report_type, $date_from, $date_to, $office_name, $fiscal_year, $signatories) {
    global $conn, $lgu_compliance;
    
    $report_id = $lgu_compliance->generateReportId($report_type);
    $report_date = date('Y-m-d');
    
    // Start building HTML content
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . ucfirst($report_type) . ' Report - ' . $office_name . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 30px; }
        .section { margin-bottom: 30px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; font-weight: bold; }
        .alert { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .alert-danger { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .alert-warning { background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .alert-info { background-color: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .signatures { margin-top: 50px; }
        .signature-box { width: 200px; border-bottom: 1px solid #000; height: 40px; margin: 0 auto; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>';

    // Add LGU header
    $html .= formatLGUReportHeader($office_name, ucfirst($report_type) . ' Report', $report_date, $report_id);
    
    // Add report period
    $html .= '
    <div class="section">
        <h3>Report Period</h3>
        <p><strong>From:</strong> ' . date('F j, Y', strtotime($date_from)) . '</p>
        <p><strong>To:</strong> ' . date('F j, Y', strtotime($date_to)) . '</p>
        <p><strong>Fiscal Year:</strong> ' . date('Y', strtotime($fiscal_year['start_date'])) . ' (' . date('F j, Y', strtotime($fiscal_year['start_date'])) . ' - ' . date('F j, Y', strtotime($fiscal_year['end_date'])) . ')</p>
    </div>';

    // Add data integrity alerts
    $integrity_issues = $lgu_compliance->checkDataIntegrity();
    if (!empty($integrity_issues)) {
        $html .= '
        <div class="section">
            <h3>Data Integrity Alerts</h3>';
        
        foreach ($integrity_issues as $issue) {
            $alert_class = $issue['severity'] === 'critical' ? 'alert-danger' : ($issue['severity'] === 'high' ? 'alert-warning' : 'alert-info');
            $html .= '
            <div class="alert ' . $alert_class . '">
                <strong>' . htmlspecialchars($issue['description']) . '</strong><br>
                <small>Issue: ' . htmlspecialchars($issue['issue_type']) . ' | Expected: ' . htmlspecialchars($issue['expected']) . ', Actual: ' . htmlspecialchars($issue['actual']) . '</small>
            </div>';
        }
        
        $html .= '</div>';
    }

    // Generate report-specific content
    switch ($report_type) {
        case 'inventory':
            $html .= generateInventoryReport($date_from, $date_to);
            break;
        case 'asset':
            $html .= generateAssetReport($date_from, $date_to);
            break;
        case 'consumable':
            $html .= generateConsumableReport($date_from, $date_to);
            break;
        case 'borrow_request':
            $html .= generateBorrowRequestReport($date_from, $date_to);
            break;
    }

    // Add document references
    $document_refs = $lgu_compliance->getDocumentReferences();
    if (!empty($document_refs)) {
        $html .= '
        <div class="section">
            <h3>Document References</h3>
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
        
        foreach ($document_refs as $doc) {
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

    // Add signatory section
    $html .= formatLGUSignatorySection($signatories);

    // Add footer
    $html .= '
    <div class="section no-print">
        <hr>
        <p style="text-align: center; font-size: 12px; color: #666;">
            Report generated on ' . date('F j, Y H:i:s') . ' | Report ID: ' . $report_id . ' | 
            Generated by: ' . ($_SESSION['firstname'] ?? 'Unknown') . ' ' . ($_SESSION['lastname'] ?? 'User') . '
        </p>
    </div>';

    $html .= '
</body>
</html>';

    return $html;
}

/**
 * Generate Inventory Report Content
 */
function generateInventoryReport($date_from, $date_to) {
    global $conn, $_SESSION;
    
    $office_id = $_SESSION['office_id'];
    $content = '
    <div class="section">
        <h3>Inventory Summary</h3>';
    
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
    
    $content .= '
        <h4>Assets</h4>
        <table class="table">
            <tr><td>Total Assets</td><td>' . $asset_data['total_assets'] . '</td></tr>
            <tr><td>Functional Assets</td><td>' . $asset_data['functional_assets'] . '</td></tr>
            <tr><td>Non-Functional Assets</td><td>' . $asset_data['non_functional_assets'] . '</td></tr>
            <tr><td>Untagged Assets</td><td>' . $asset_data['untagged_assets'] . '</td></tr>
            <tr><td>Total Value</td><td>₱' . number_format($asset_data['total_asset_value'], 2) . '</td></tr>
        </table>';
    
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
    
    $content .= '
        <h4>Consumables</h4>
        <table class="table">
            <tr><td>Total Consumable Types</td><td>' . $consumable_data['total_consumables'] . '</td></tr>
            <tr><td>Total Quantity</td><td>' . $consumable_data['total_quantity'] . '</td></tr>
            <tr><td>Low Stock Items</td><td>' . $consumable_data['low_stock_items'] . '</td></tr>
            <tr><td>Total Value</td><td>₱' . number_format($consumable_data['total_value'], 2) . '</td></tr>
        </table>
    </div>';
    
    return $content;
}

/**
 * Generate Asset Report Content
 */
function generateAssetReport($date_from, $date_to) {
    global $conn, $_SESSION;
    
    $office_id = $_SESSION['office_id'];
    $content = '
    <div class="section">
        <h3>Asset Details</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Description</th>
                    <th>Property No.</th>
                    <th>Status</th>
                    <th>Value</th>
                    <th>Acquisition Date</th>
                    <th>User</th>
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
        $content .= '
                <tr>
                    <td>' . $row['id'] . '</td>
                    <td>' . htmlspecialchars($row['description']) . '</td>
                    <td>' . htmlspecialchars($row['property_no'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($row['status']) . '</td>
                    <td>₱' . number_format($row['value'], 2) . '</td>
                    <td>' . ($row['acquisition_date'] ? date('M j, Y', strtotime($row['acquisition_date'])) : 'N/A') . '</td>
                    <td>' . htmlspecialchars($row['end_user'] ?? 'N/A') . '</td>
                </tr>';
    }
    
    $content .= '
            </tbody>
        </table>
    </div>';
    
    return $content;
}

/**
 * Generate Consumable Report Content
 */
function generateConsumableReport($date_from, $date_to) {
    global $conn, $_SESSION;
    
    $office_id = $_SESSION['office_id'];
    $content = '
    <div class="section">
        <h3>Consumable Details</h3>
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
        $status_class = $row['quantity'] <= $row['reorder_level'] ? 'alert-warning' : '';
        
        $content .= '
                <tr class="' . $status_class . '">
                    <td>' . $row['id'] . '</td>
                    <td>' . htmlspecialchars($row['description']) . '</td>
                    <td>' . $row['quantity'] . '</td>
                    <td>' . htmlspecialchars($row['unit']) . '</td>
                    <td>₱' . number_format($row['unit_cost'], 2) . '</td>
                    <td>₱' . number_format($row['quantity'] * $row['unit_cost'], 2) . '</td>
                    <td>' . $row['reorder_level'] . '</td>
                    <td>' . $status . '</td>
                </tr>';
    }
    
    $content .= '
            </tbody>
        </table>
    </div>';
    
    return $content;
}

/**
 * Generate Borrow Request Report Content
 */
function generateBorrowRequestReport($date_from, $date_to) {
    global $conn, $_SESSION;
    
    $office_id = $_SESSION['office_id'];
    $content = '
    <div class="section">
        <h3>Borrow Requests</h3>
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
        $content .= '
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
    
    $content .= '
            </tbody>
        </table>
    </div>';
    
    return $content;
}

/**
 * Add Document Reference
 */
function addDocumentReference($lgu_compliance) {
    $document_type = $_POST['document_type'] ?? '';
    $document_number = $_POST['document_number'] ?? '';
    $document_date = $_POST['document_date'] ?? '';
    $reference_amount = $_POST['reference_amount'] ?? 0;
    $supplier_name = $_POST['supplier_name'] ?? '';
    
    if (empty($document_type) || empty($document_number) || empty($document_date)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        return;
    }
    
    try {
        $result = $lgu_compliance->addDocumentReference($document_type, $document_number, $document_date, $reference_amount, $supplier_name);
        
        if ($result) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Document reference added successfully']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to add document reference']);
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

/**
 * Get Document References
 */
function getDocumentReferences($lgu_compliance) {
    $document_type = $_GET['document_type'] ?? null;
    $date_from = $_GET['date_from'] ?? null;
    $date_to = $_GET['date_to'] ?? null;
    
    try {
        $references = $lgu_compliance->getDocumentReferences($document_type, $date_from, $date_to);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $references]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

/**
 * Schedule Report
 */
function scheduleReport($lgu_compliance) {
    $schedule_name = $_POST['schedule_name'] ?? '';
    $report_type = $_POST['report_type'] ?? '';
    $frequency = $_POST['frequency'] ?? '';
    $schedule_day = $_POST['schedule_day'] ?? 1;
    $schedule_time = $_POST['schedule_time'] ?? '08:00';
    $recipients = $_POST['recipients'] ?? [];
    
    if (empty($schedule_name) || empty($report_type) || empty($frequency)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        return;
    }
    
    try {
        $result = $lgu_compliance->scheduleReport($schedule_name, $report_type, $frequency, $schedule_day, $schedule_time, $recipients);
        
        if ($result) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Report scheduled successfully']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to schedule report']);
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

/**
 * Get Scheduled Reports
 */
function getScheduledReports($lgu_compliance) {
    try {
        $reports = $lgu_compliance->getScheduledReports();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $reports]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

/**
 * Get Report History
 */
function getReportHistory($lgu_compliance) {
    $report_type = $_GET['report_type'] ?? null;
    $limit = $_GET['limit'] ?? 50;
    
    try {
        $history = $lgu_compliance->getReportHistory($report_type, $limit);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $history]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

/**
 * Check Data Integrity
 */
function checkDataIntegrity($lgu_compliance) {
    try {
        $issues = $lgu_compliance->checkDataIntegrity();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $issues]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>
