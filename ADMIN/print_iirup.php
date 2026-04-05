<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

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

// Get IIRUP ID from URL
$iirup_id = $_GET['id'] ?? 0;
if (empty($iirup_id)) {
    header('Location: iirup_entries.php');
    exit();
}

// Get IIRUP form details
$iirup_form = null;
$stmt = $conn->prepare("SELECT * FROM iirup_forms WHERE id = ?");
$stmt->bind_param("i", $iirup_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $iirup_form = $result->fetch_assoc();
}
$stmt->close();

if (!$iirup_form) {
    header('Location: iirup_entries.php');
    exit();
}

// Get IIRUP items
$iirup_items = [];
$stmt = $conn->prepare("SELECT * FROM iirup_items WHERE form_id = ? ORDER BY item_order");
$stmt->bind_param("i", $iirup_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $iirup_items[] = $row;
}
$stmt->close();

// Get header image from forms table
$header_image = '';
$result = $conn->query("SELECT header_image FROM forms WHERE form_code = 'IIRUP'");
if ($result && $row = $result->fetch_assoc()) {
    $header_image = $row['header_image'];
}

logSystemAction($_SESSION['user_id'], 'Printed IIRUP Form', 'forms', "IIRUP ID: $iirup_id, Form No: {$iirup_form['form_number']}");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IIRUP PRINT - <?php echo htmlspecialchars($iirup_form['form_number']); ?> - PIMS</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0.3in;
        }
        
        body {
            font-family: 'Times New Roman', serif;
            font-size: 10px;
            line-height: 1.2;
            color: #000;
            background: white;
            margin: 0;
            padding: 0;
        }
        
        .print-container {
            width: 100%;
            max-width: 11.5in;
            margin: 0 auto;
            padding: 10px;
            position: relative;
        }
        
        .iirup-header {
            text-align: center;
            margin-bottom: 15px;
        }

        .iirup-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .iirup-subtitle {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 4px;
            color: #333;
        }

        .iirup-form-number {
            font-size: 11px;
            font-weight: bold;
            padding: 4px 10px;
            background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
            color: white;
            border-radius: 3px;
            display: inline-block;
            margin: 6px 0;
        }

        .accountable-section {
            border: 2px solid #333;
            padding: 12px;
            margin: 12px 0;
            text-align: center;
            background-color: #f8f9fa;
        }

        .accountable-label {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .accountable-name {
            font-size: 11px;
            font-weight: bold;
            border-bottom: 2px solid #333;
            padding-bottom: 2px;
            margin-bottom: 2px;
        }

        .iirup-table {
            border: 2px solid #333;
            font-size: 8px;
            table-layout: fixed;
            width: 100%;
            border-collapse: collapse;
        }

        .iirup-table th {
            background-color: #e9ecef;
            border: 1px solid #333;
            font-weight: bold;
            text-align: center;
            padding: 5px 2px;
            font-size: 7px;
            vertical-align: middle;
        }

        .iirup-table td {
            border: 1px solid #333;
            padding: 3px 2px;
            font-size: 8px;
            text-align: center;
            vertical-align: middle;
        }

        .iirup-table td.text-left {
            text-align: left;
        }

        .header-inventory {
            background-color: #d4edda !important;
            color: #155724;
            font-weight: bold;
            font-size: 9px;
        }

        .header-disposal {
            background-color: #fff3cd !important;
            color: #856404;
            font-weight: bold;
            font-size: 9px;
        }

        .certification-section {
            margin: 15px 0;
            padding: 12px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }

        .certification-text {
            font-style: italic;
            margin-bottom: 10px;
            line-height: 1.2;
            font-size: 9px;
        }

        .signature-section {
            border-top: 2px solid #333;
            padding-top: 20px;
            margin-top: 20px;
            border: 2px solid #333;
            padding: 15px;
            background-color: #f8f9fa;
        }

        .signature-box {
            text-align: center;
            margin-bottom: 12px;
        }

        .signature-title {
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
            font-size: 9px;
        }

        .signature-line {
            border-bottom: 2px solid #333;
            padding-bottom: 2px;
            margin-bottom: 2px;
            min-height: 20px;
        }

        .signature-label {
            font-size: 8px;
            color: #666;
            margin-bottom: 10px;
        }

        .footer-section {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #666;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .print-container {
                padding: 0;
                max-width: none;
            }
            
            @page {
                size: A4 landscape;
                margin: 0.3in;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Form Header -->
        <div class="iirup-header">
            <?php 
            if (!empty($header_image)) {
                echo '<div style="margin-bottom: 12px;">';
                echo '<img src="../uploads/forms/' . htmlspecialchars($header_image) . '" alt="Header Image" style="width: 100%; max-height: 100px; object-fit: contain;">';
                echo '</div>';
            }
            ?>
            
            <div style="font-style: italic; margin-top: 6px; font-size: 10px;">As of <?php echo htmlspecialchars($iirup_form['as_of_year']); ?></div>
        </div>

        <!-- Accountable Officer Information -->
        <div class="accountable-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <div style="width: 31%;">
                    <div class="accountable-label">Accountable Officer</div>
                    <div class="accountable-name"><?php echo htmlspecialchars($iirup_form['accountable_officer']); ?></div>
                    <div style="font-size: 8px; margin-top: 2px;"><?php echo htmlspecialchars($iirup_form['accountable_officer_designation']); ?></div>
                </div>
                <div style="width: 31%;">
                    <div class="accountable-label">Designation</div>
                    <div class="accountable-name"><?php echo htmlspecialchars($iirup_form['designation']); ?></div>
                </div>
                <div style="width: 31%;">
                    <div class="accountable-label">Department/Office</div>
                    <div class="accountable-name"><?php echo htmlspecialchars($iirup_form['department_office']); ?></div>
                </div>
            </div>
        </div>

        <!-- Items and Disposal Table -->
        <table class="iirup-table">
            <thead>
                <tr>
                    <th colspan="10" class="header-inventory">INVENTORY</th>
                    <th colspan="11" class="header-disposal">INSPECTION AND DISPOSAL</th>
                </tr>
                <tr>
                    <th style="width: 5%;">Date Acquired</th>
                    <th style="width: 14%;">Particulars</th>
                    <th style="width: 6%;">Property No.</th>
                    <th style="width: 5%;">Qty</th>
                    <th style="width: 6%;">Unit Cost</th>
                    <th style="width: 6%;">Total Cost</th>
                    <th style="width: 7%;">Accum. Depreciation</th>
                    <th style="width: 7%;">Accum. Impairment losses</th>
                    <th style="width: 7%;">Carrying amount</th>
                    <th style="width: 4%;">Remarks</th>
                    <th style="width: 5%;">Sale</th>
                    <th style="width: 5%;">Transfer</th>
                    <th style="width: 5%;">Destruction</th>
                    <th style="width: 4%;">Others</th>
                    <th style="width: 5%;">Total</th>
                    <th style="width: 5%;">Appraised value</th>
                    <th style="width: 4%;">OR no.</th>
                    <th style="width: 4%;">Amount</th>
                    <th style="width: 4%;">Dept</th>
                    <th style="width: 3%;">Code</th>
                    <th style="width: 5%;">Date received</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($iirup_items as $item): ?>
                    <tr>
                        <td><?php echo !empty($item['date_acquired']) ? date('M d, Y', strtotime($item['date_acquired'])) : ''; ?></td>
                        <td class="text-left"><?php echo htmlspecialchars($item['particulars']); ?></td>
                        <td><?php echo htmlspecialchars($item['property_no']); ?></td>
                        <td><?php echo number_format($item['quantity'], 2); ?></td>
                        <td>₱<?php echo number_format($item['unit_cost'], 2); ?></td>
                        <td>₱<?php echo number_format($item['total_cost'], 2); ?></td>
                        <td>₱<?php echo number_format($item['accumulated_depreciation'], 2); ?></td>
                        <td>₱<?php echo number_format($item['impairment_losses'], 2); ?></td>
                        <td>₱<?php echo number_format($item['carrying_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($item['inventory_remarks']); ?></td>
                        <td>₱<?php echo number_format($item['disposal_sale'], 2); ?></td>
                        <td>₱<?php echo number_format($item['disposal_transfer'], 2); ?></td>
                        <td>₱<?php echo number_format($item['disposal_destruction'], 2); ?></td>
                        <td><?php echo htmlspecialchars($item['disposal_others']); ?></td>
                        <td>₱<?php echo number_format($item['disposal_total'], 2); ?></td>
                        <td>₱<?php echo number_format($item['appraised_value'], 2); ?></td>
                        <td><?php echo htmlspecialchars($item['or_no']); ?></td>
                        <td>₱<?php echo number_format($item['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($item['dept_office']); ?></td>
                        <td><?php echo htmlspecialchars($item['control_no']); ?></td>
                        <td><?php echo !empty($item['date_received']) ? date('M d, Y', strtotime($item['date_received'])) : ''; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Certification Section -->
        <div class="certification-section">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <div style="width: 48%;">
                    <div class="certification-text">
                        I HEREBY request inspection and disposition, pursuant to Section 79 of PD 1445, of property enumerated above.
                    </div>
                </div>
                <div style="width: 24%;">
                    <div class="certification-text">
                        I CERTIFY that I have inspected each and every article enumerated in this report, and that disposition made thereof was, in my judgment, best for public interest.
                    </div>
                </div>
                <div style="width: 24%;">
                    <div class="certification-text">
                        I CERTIFY that I have witnessed disposition of articles enumerated on this report this _____ day of _____.
                    </div>
                </div>
            </div>
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <div style="display: flex; justify-content: space-between;">
                <div style="width: 23%;">
                    <div class="signature-box">
                        <div class="signature-title">REQUESTED BY:</div>
                        <div class="signature-line"><?php echo htmlspecialchars($iirup_form['accountable_officer_name']); ?></div>
                        <div class="signature-label">(Signature over Printed Name)</div>
                        <div class="signature-line"><?php echo htmlspecialchars($iirup_form['accountable_officer_designation']); ?></div>
                        <div class="signature-label">(Designation)</div>
                    </div>
                </div>
                <div style="width: 23%;">
                    <div class="signature-box">
                        <div class="signature-title">APPROVED BY:</div>
                        <div class="signature-line"><?php echo htmlspecialchars($iirup_form['authorized_official_name']); ?></div>
                        <div class="signature-label">(Signature over Printed Name)</div>
                        <div class="signature-line"><?php echo htmlspecialchars($iirup_form['authorized_official_designation']); ?></div>
                        <div class="signature-label">(Designation)</div>
                    </div>
                </div>
                <div style="width: 23%;">
                    <div class="signature-box">
                        <div class="signature-title">INSPECTED BY:</div>
                        <div class="signature-line"><?php echo htmlspecialchars($iirup_form['inspection_officer_name']); ?></div>
                        <div class="signature-label">(Signature over Printed Name)</div>
                        <div class="signature-line"><?php echo htmlspecialchars($iirup_form['inspection_officer_designation'] ?? ''); ?></div>
                        <div class="signature-label">(Designation)</div>
                    </div>
                </div>
                <div style="width: 23%;">
                    <div class="signature-box">
                        <div class="signature-title">WITNESSED BY:</div>
                        <div class="signature-line"><?php echo htmlspecialchars($iirup_form['witness_name']); ?></div>
                        <div class="signature-label">(Signature over Printed Name)</div>
                        <div class="signature-line"><?php echo htmlspecialchars($iirup_form['witness_designation'] ?? ''); ?></div>
                        <div class="signature-label">(Designation)</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Section -->
        <div class="footer-section">
            <div style="margin-bottom: 5px;">
                <strong>Prepared by:</strong> <?php echo htmlspecialchars($_SESSION['username'] ?? 'System'); ?> | 
                <strong>Date Prepared:</strong> <?php echo date('F j, Y'); ?>
            </div>
            <div>
                <em>This form is generated electronically and is valid without signature.</em>
            </div>
        </div>
    </div>

    <script>
        // Preview before print
        function showPrintPreview() {
            // Show print preview dialog
            if (confirm('Do you want to print this IIRUP form?')) {
                window.print();
            }
        }

        // Auto-print when page loads
        window.onload = function() {
            // Add a small delay to ensure content is fully loaded
            setTimeout(function() {
                // Show print preview dialog
                if (confirm('Do you want to print this IIRUP form?')) {
                    window.print();
                }
            }, 500);
        };
        
        // Close window after printing
        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>
</html>
