<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

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

// Get borrow ID from URL parameter
$borrow_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($borrow_id <= 0) {
    die("Invalid borrow request ID");
}

// Get borrow request details
try {
    $stmt = $conn->prepare("SELECT * FROM borrow_form_submissions WHERE id = ?");
    $stmt->bind_param("i", $borrow_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Borrow request not found");
    }

    $borrow_request = $result->fetch_assoc();
    $stmt->close();

    // Parse items from JSON
    $items = [];
    if (!empty($borrow_request['items'])) {
        $items = json_decode($borrow_request['items'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $items = [];
        }
    }

} catch (Exception $e) {
    die("Error loading borrow request: " . $e->getMessage());
}

// Log the print action
logSystemAction($_SESSION['user_id'], 'print_borrow_form', 'borrowing', "Borrow ID: $borrow_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Slip - PIMS</title>
    <style>
        /* ===== Reset & Base ===== */
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            background: #e8ecf0;
        }

        /* ===== Screen wrapper ===== */
        @media screen {
            .page-wrapper {
                padding: 30px 20px;
            }

            .slip-card {
                background: #fff;
                max-width: 820px;
                margin: 0 auto;
                padding: 32px 36px 36px;
                box-shadow: 0 4px 24px rgba(0,0,0,.12);
                border-radius: 4px;
            }

            .no-print {
                display: flex;
                gap: 10px;
                justify-content: center;
                margin-bottom: 24px;
            }

            .btn-action {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                padding: 9px 22px;
                font-size: 13px;
                font-weight: 600;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                text-decoration: none;
                transition: opacity .2s;
            }
            .btn-action:hover { opacity: .85; }
            .btn-print  { background: #1a56db; color: #fff; }
            .btn-close2 { background: #6b7280; color: #fff; }
        }

        /* ===== Print rules ===== */
        @media print {
            @page { size: A4; margin: 14mm 16mm; }
            body   { background: #fff; }
            .no-print { display: none !important; }
            .page-wrapper { padding: 0; }
            .slip-card { box-shadow: none; padding: 0; max-width: 100%; }
        }

        /* ===== LGU Header ===== */
        .lgu-header {
            display: grid;
            grid-template-columns: 90px 1fr auto;
            align-items: center;
            gap: 12px;
            padding-bottom: 10px;
            border-bottom: 2.5px solid #1a3c6e;
            margin-bottom: 6px;
        }

        .lgu-header .logo img {
            width: 78px;
            height: 78px;
            object-fit: contain;
        }

        .lgu-header .agency-text {
            text-align: center;
            line-height: 1.4;
        }
        .lgu-header .agency-text .republic {
            font-size: 12px;
        }
        .lgu-header .agency-text .province {
            font-size: 12px;
        }
        .lgu-header .agency-text .lgu-name {
            font-size: 18px;
            font-weight: 900;
            letter-spacing: .5px;
            color: #1a3c6e;
            text-transform: uppercase;
        }

        .lgu-header .doc-code {
            font-size: 9.5px;
            text-align: right;
            line-height: 1.6;
            white-space: nowrap;
        }
        .lgu-header .doc-code strong { font-size: 10px; }

        /* ===== Slip Title ===== */
        .slip-title {
            text-align: center;
            font-size: 17px;
            font-weight: 900;
            text-decoration: underline;
            letter-spacing: 1.5px;
            margin: 18px 0 18px;
            color: #1a3c6e;
        }

        /* ===== Borrower Info Grid ===== */
        .info-grid {
            display: grid;
            gap: 0;
            margin-bottom: 4px;
        }

        .info-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0 24px;
            margin-bottom: 14px;
        }
        .info-row.two-col {
            grid-template-columns: 1fr 1fr;
        }
        .info-row.four-col {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .info-field {}
        .info-field .field-label {
            font-weight: 700;
            font-size: 11.5px;
            margin-bottom: 3px;
            display: block;
        }
        .info-field .field-value {
            border-bottom: 1.5px solid #333;
            min-height: 22px;
            padding: 2px 4px 3px;
            font-size: 12px;
            color: #222;
            display: block;
        }

        /* ===== Items Table ===== */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0 12px;
            border: 1.5px solid #b0b8c9;
        }

        .items-table thead tr {
            background: #dde4ef;
        }

        .items-table th {
            border: 1px solid #b0b8c9;
            padding: 8px 10px;
            text-align: center;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #1a3c6e;
        }

        .items-table td {
            border: 1px solid #b0b8c9;
            padding: 7px 10px;
            font-size: 12px;
            vertical-align: middle;
        }

        .items-table td.qty-col { text-align: center; }

        /* ===== Signature Section ===== */
        .sig-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 32px;
        }

        .sig-box {}
        .sig-box .sig-label {
            font-weight: 700;
            font-size: 11.5px;
            margin-bottom: 6px;
        }
        .sig-box .sig-name-value {
            border-bottom: 1.5px solid #333;
            min-height: 22px;
            padding: 2px 4px 3px;
            font-size: 12px;
            text-align: center;
        }
        .sig-box .sig-line {
            border-bottom: 1.5px solid #333;
            height: 40px;
            margin-top: 20px;
        }
        .sig-box .sig-title {
            font-weight: 800;
            font-size: 11px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-top: 5px;
            color: #1a3c6e;
        }
    </style>
</head>
<body>
<div class="page-wrapper">

    <!-- Action Buttons (screen only) -->
    <div class="no-print">
        <button class="btn-action btn-print" onclick="window.print()">
            🖨️ Print Slip
        </button>
        <button class="btn-action btn-close2" onclick="window.close()">
            ✕ Close
        </button>
    </div>

    <div class="slip-card">

        <!-- LGU Header -->
        <div class="lgu-header">
            <div class="logo">
                <img src="../img/system_logo.png" alt="LGU Logo">
            </div>
            <div class="agency-text">
                <div class="republic">Republic of the Philippines</div>
                <div class="province">Province of Sorsogon</div>
                <div class="lgu-name">Local Government Unit of Pilar</div>
            </div>
            <div class="doc-code">
                <strong>Document Code: PS-DIT-01-F03-01-01</strong><br>
                Effective Date:<br>
                22 May 2023
            </div>
        </div>

        <!-- Slip Title -->
        <div class="slip-title">BORROW SLIP</div>

        <!-- Row 1: Name | Date Borrowed | Schedule of Return -->
        <div class="info-grid">
            <div class="info-row">
                <div class="info-field">
                    <span class="field-label">Name:</span>
                    <span class="field-value"><?php echo htmlspecialchars($borrow_request['guest_name'] ?? ''); ?></span>
                </div>
                <div class="info-field">
                    <span class="field-label">Date Borrowed:</span>
                    <span class="field-value">
                        <?php
                            echo !empty($borrow_request['date_borrowed'])
                                ? date('m/d/Y', strtotime($borrow_request['date_borrowed']))
                                : '';
                        ?>
                    </span>
                </div>
                <div class="info-field">
                    <span class="field-label">Schedule of Return:</span>
                    <span class="field-value">
                        <?php
                            echo !empty($borrow_request['schedule_return'])
                                ? date('m/d/Y', strtotime($borrow_request['schedule_return']))
                                : '';
                        ?>
                    </span>
                </div>
            </div>

            <!-- Row 2: Contact No. | Barangay | Borrower Signature -->
            <div class="info-row">
                <div class="info-field">
                    <span class="field-label">Contact No.:</span>
                    <span class="field-value"><?php echo htmlspecialchars($borrow_request['contact'] ?? ''); ?></span>
                </div>
                <div class="info-field">
                    <span class="field-label">Barangay:</span>
                    <span class="field-value"><?php echo htmlspecialchars($borrow_request['barangay'] ?? ''); ?></span>
                </div>
                <div class="info-field">
                    <span class="field-label">Borrower Signature:</span>
                    <span class="field-value">&nbsp;</span>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th width="50%">Things Borrowed</th>
                    <th width="10%">QTY</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (!empty($items) && is_array($items)):
                    // Group items by description only
                    $grouped_items = [];
                    foreach ($items as $item) {
                        $description = $item['description'] ?? 'Unknown Item';
                        if (!isset($grouped_items[$description])) {
                            $grouped_items[$description] = [
                                'description' => $description,
                                'property_numbers' => [],
                                'remarks' => $item['remarks'] ?? '',
                                'quantity' => 0
                            ];
                        }
                        $grouped_items[$description]['property_numbers'][] = $item['property_no'] ?? '';
                        $grouped_items[$description]['quantity']++;
                    }
                    
                    foreach ($grouped_items as $item): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($item['description']); ?>
                                <?php if (!empty($item['property_numbers'])): ?>
                                    <br><small style="font-size: 0.85em; color: #666;">
                                    <?php 
                                    foreach ($item['property_numbers'] as $index => $prop_no) {
                                        if (!empty($prop_no)) {
                                            echo ($index > 0 ? '- ' : '') . htmlspecialchars($prop_no);
                                            if ($index < count($item['property_numbers']) - 1) {
                                                echo '<br>';
                                            }
                                        }
                                    }
                                    ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td class="qty-col"><?php echo $item['quantity']; ?></td>
                            <td><?php echo htmlspecialchars($item['remarks']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php
                        // Pad to at least 5 visible rows
                        $pad = max(0, 5 - count($grouped_items));
                        for ($i = 0; $i < $pad; $i++): ?>
                        <tr>
                            <td>&nbsp;</td>
                            <td class="qty-col">&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                    <?php endfor; ?>
                <?php else: ?>
                    <?php for ($i = 0; $i < 5; $i++): ?>
                        <tr>
                            <td>&nbsp;</td>
                            <td class="qty-col">&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                    <?php endfor; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Signature Section -->
        <div class="sig-section">
            <!-- Releasing Officer -->
            <div class="sig-box">
                <div class="sig-label">Releasing Officer:</div>
                <div class="sig-name-value"><?php echo htmlspecialchars($borrow_request['releasing_officer'] ?? ''); ?></div>
                <div class="sig-line"></div>
                <div class="sig-title">Releasing Officer Signature</div>
            </div>

            <!-- Approved By -->
            <div class="sig-box">
                <div class="sig-label">Approved by:</div>
                <div class="sig-name-value"><?php echo htmlspecialchars($borrow_request['approved_by'] ?? ''); ?></div>
                <div class="sig-line"></div>
                <div class="sig-title">Approved by Signature</div>
            </div>
        </div>

    </div><!-- /.slip-card -->
</div><!-- /.page-wrapper -->
</body>
</html>
