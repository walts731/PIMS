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

// Get borrow request ID from URL
$borrow_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($borrow_id <= 0) {
    $_SESSION['error'] = 'Invalid borrow request ID.';
    header('Location: borrowing.php');
    exit();
}

// Fetch borrow request details
$query = "SELECT * FROM borrow_form_submissions WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $borrow_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$borrow_request = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$borrow_request) {
    $_SESSION['error'] = 'Borrow request not found.';
    header('Location: borrowing.php');
    exit();
}

// Parse items from JSON
$items_json = $borrow_request['items'];
$items = [];

if (!empty($items_json)) {
    $items = json_decode($items_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        $items = [];
    }
}

// Get system settings
$settings_query = "SELECT * FROM system_settings LIMIT 1";
$settings_result = mysqli_query($conn, $settings_query);
$settings = mysqli_fetch_assoc($settings_result);

// Log borrow slip view
logSystemAction($_SESSION['user_id'], 'view', 'borrow_slip', "Borrow ID: $borrow_id");

// Handle return action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_returned') {
    $borrow_id = $_POST['borrow_id'];
    
    try {
        $conn->begin_transaction();

        // Get borrow details
        $stmt = $conn->prepare("SELECT items FROM borrow_form_submissions WHERE id = ?");
        $stmt->bind_param("i", $borrow_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $borrow_data = $result->fetch_assoc();
        
        // Parse items from JSON format
        $items_json = $borrow_data['items'];
        $items = [];
        
        if (!empty($items_json)) {
            try {
                $items = json_decode($items_json, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("JSON decode error: " . json_last_error_msg());
                    $items = [];
                }
            } catch (Exception $e) {
                error_log("Items decode error: " . $e->getMessage());
                $items = [];
            }
        }
        $stmt->close();

        // Update borrow status
        $update_stmt = $conn->prepare("UPDATE borrow_form_submissions SET status = 'returned', updated_at = NOW() WHERE id = ?");
        $update_stmt->bind_param("i", $borrow_id);
        $update_stmt->execute();
        $update_stmt->close();

        // Update asset items status back to serviceable (if asset_item_id exists)
        foreach ($items as $item) {
            if (isset($item['asset_item_id'])) {
                $asset_item_id = $item['asset_item_id'];
                
                // Update the specific asset item back to serviceable status
                $update_asset_stmt = $conn->prepare("UPDATE asset_items SET status = 'serviceable' WHERE id = ?");
                $update_asset_stmt->bind_param("i", $asset_item_id);
                $update_asset_stmt->execute();
                $update_asset_stmt->close();
                
                error_log("Updated asset item ID {$asset_item_id} back to serviceable status");
            }
        }

        $conn->commit();
        $_SESSION['success'] = "Items marked as returned successfully!";
        logSystemAction($_SESSION['user_id'], 'borrow_items_returned', 'borrow_slip', "Borrow ID: $borrow_id");

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error marking items as returned: " . $e->getMessage();
        logSystemAction($_SESSION['user_id'], 'borrow_return_failed', 'borrow_slip', "Error: " . $e->getMessage());
    }
    header("Location: borrow_slip.php?id=$borrow_id");
    exit();
}

// Status badge helper
$status = strtolower($borrow_request['status'] ?? 'pending');
$badge_class = match($status) {
    'approved' => 'status-approved',
    'returned' => 'status-returned',
    'rejected' => 'status-rejected',
    default    => 'status-pending',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Slip #<?php echo $borrow_request['id']; ?> - PIMS</title>

     <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/admin-unified.css" rel="stylesheet">

    <style>
        /* ══════════════════════════════════════
           STATUS BADGES
        ══════════════════════════════════════ */
        .status-badge {
            display: inline-block;
            padding: 3px 14px;
            border-radius: 14px;
            font-size: 11.5px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-pending  { background: #fff3cd; color: #856404; }
        .status-approved { background: #d1e7dd; color: #0f5132; }
        .status-returned { background: #cff4fc; color: #055160; }
        .status-rejected { background: #f8d7da; color: #842029; }

        /* ══════════════════════════════════════
           SLIP WRAPPER (white paper look)
        ══════════════════════════════════════ */
        #slip-wrapper {
            background: #fff;
            color: #000;
            font-family: Arial, sans-serif;
            font-size: 13px;
            max-width: 860px;
            margin: 24px auto 40px;
            padding: 32px 40px 40px;
            border: 1px solid #d0d5dd;
            border-radius: 6px;
            box-shadow: 0 2px 14px rgba(0,0,0,.08);
        }

        /* ══════════════════════════════════════
           LGU HEADER
        ══════════════════════════════════════ */
        .slip-gov-header {
            display: grid;
            grid-template-columns: 86px 1fr 178px;
            align-items: center;
            gap: 12px;
            padding-bottom: 12px;
            border-bottom: 2.5px solid #1a3c6e;
            margin-bottom: 4px;
        }
        .slip-gov-header .logo-col img {
            width: 76px;
            height: 76px;
            object-fit: contain;
        }
        .slip-gov-header .title-col {
            text-align: center;
            line-height: 1.45;
        }
        .slip-gov-header .title-col p {
            margin: 0;
            font-size: 12.5px;
        }
        .slip-gov-header .title-col h2 {
            margin: 5px 0 0;
            font-size: 18px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: #1a3c6e;
        }
        .slip-gov-header .doc-col {
            text-align: right;
            font-size: 10.5px;
            line-height: 1.65;
        }
        .slip-gov-header .doc-col strong {
            font-size: 11px;
        }

        /* ══════════════════════════════════════
           SLIP TITLE
        ══════════════════════════════════════ */
        .slip-title {
            text-align: center;
            margin: 20px 0 22px;
        }
        .slip-title h3 {
            display: inline-block;
            font-size: 18px;
            font-weight: 900;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #1a3c6e;
            margin: 0;
        }

        /* ══════════════════════════════════════
           FORM FIELDS  (label above / underline below)
        ══════════════════════════════════════ */
        .slip-field {
            margin-bottom: 18px;
        }
        .slip-field label {
            display: block;
            font-weight: 700;
            font-size: 12px;
            margin-bottom: 5px;
            color: #111;
        }
        .slip-field .slip-value {
            border-bottom: 1.5px solid #333;
            min-height: 26px;
            padding-bottom: 3px;
            font-size: 13px;
            color: #222;
        }

        /* ══════════════════════════════════════
           ITEMS TABLE
        ══════════════════════════════════════ */
        .slip-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #9aaaca;
            margin-bottom: 0;
        }
        .slip-table thead tr {
            background: #dde4ef;
        }
        .slip-table th {
            border: 1px solid #9aaaca;
            text-align: center;
            font-size: 11.5px;
            font-weight: 800;
            padding: 9px 12px;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #1a3c6e;
        }
        .slip-table td {
            border: 1px solid #9aaaca;
            padding: 9px 12px;
            font-size: 13px;
            vertical-align: middle;
        }
        .slip-table .qty-col { text-align: center; width: 80px; }
        .slip-table .rem-col { width: 30%; }

        /* ══════════════════════════════════════
           SIGNATURE SECTION
        ══════════════════════════════════════ */
        .sig-block {
            text-align: center;
        }
        .sig-block .sig-line {
            border-bottom: 1.5px solid #000;
            height: 46px;
            margin: 0 16px 6px;
        }
        .sig-block .sig-label {
            font-weight: 800;
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: #1a3c6e;
        }

        /* ══════════════════════════════════════
           PRINT OVERRIDES
        ══════════════════════════════════════ */
        @media print {
            .no-print { display: none !important; }
            body, .main-content { background: #fff !important; }
            #slip-wrapper {
                border: none;
                box-shadow: none;
                padding: 0;
                margin: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
<?php $page_title = 'Borrow Slip'; ?>

<!-- Main Wrapper -->
<div class="main-wrapper" id="mainWrapper">
    <?php require_once 'includes/sidebar-toggle.php'; ?>
    <?php require_once 'includes/sidebar.php'; ?>
    <?php require_once 'includes/topbar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">

        <!-- ── Page Header (hidden on print) ──────────── -->
        <div class="page-header no-print">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-1">
                        <i class="bi bi-file-earmark-text"></i>
                        Borrow Slip
                        <span class="text-muted fw-normal">#<?php echo $borrow_request['id']; ?></span>
                    </h1>
                    <p class="text-muted mb-0">View and print borrow slip details</p>
                    <span class="status-badge <?php echo $badge_class; ?> mt-2">
                        <?php echo ucfirst($status); ?>
                    </span>

                    <?php if (isset($_SESSION['error']) && $_SESSION['error']): ?>
                        <div class="alert alert-danger mt-2">
                            <i class="bi bi-exclamation-triangle"></i>
                            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['success']) && $_SESSION['success']): ?>
                        <div class="alert alert-success mt-2">
                            <i class="bi bi-check-circle"></i>
                            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
                            <li>
                                <button class="dropdown-item" onclick="window.open('print_borrow_form.php?id=<?php echo $borrow_request['id']; ?>', '_blank')">
                                    <i class="bi bi-printer"></i> Print
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if ($status === 'approved'): ?>
                            <li>
                                <button class="dropdown-item" onclick="markAsReturned(<?php echo $borrow_request['id']; ?>)">
                                    <i class="bi bi-check-circle"></i> Mark as Returned
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li>
                                <button class="dropdown-item" onclick="window.location.href='borrowing.php'">
                                    <i class="bi bi-arrow-left"></i> Back to Borrowing
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div><!-- /page-header -->

        <!-- ════════════════════════════════════════
             BORROW SLIP — official paper format
        ════════════════════════════════════════ -->
        <div id="slip-wrapper">

            <!-- LGU Government Header -->
            <div class="slip-gov-header">
                <div class="logo-col">
                    <?php if ($settings && !empty($settings['system_logo'])): ?>
                        <img src="../<?php echo htmlspecialchars($settings['system_logo']); ?>" alt="LGU Logo">
                    <?php else: ?>
                        <img src="../img/system_logo.png" alt="LGU Logo">
                    <?php endif; ?>
                </div>
                <div class="title-col">
                    <p>Republic of the Philippines</p>
                    <p>Province of Sorsogon</p>
                    <h2>Local Government Unit of Pilar</h2>
                </div>
                <div class="doc-col">
                    <strong>Document Code: PS-DIT-01-F03-01-01</strong><br>
                    Effective Date:<br>
                    22 May 2023
                </div>
            </div>

            <!-- Slip Title -->
            <div class="slip-title">
                <h3>Borrow Slip</h3>
            </div>

            <!-- Row 1: Name | Date Borrowed | Schedule of Return -->
            <div class="row g-4 mb-1">
                <div class="col-md-5">
                    <div class="slip-field">
                        <label>Name:</label>
                        <div class="slip-value"><?php echo htmlspecialchars($borrow_request['guest_name'] ?? ''); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="slip-field">
                        <label>Date Borrowed:</label>
                        <div class="slip-value">
                            <?php echo !empty($borrow_request['date_borrowed'])
                                ? date('m/d/Y', strtotime($borrow_request['date_borrowed']))
                                : '&nbsp;'; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="slip-field">
                        <label>Schedule of Return:</label>
                        <div class="slip-value">
                            <?php echo !empty($borrow_request['schedule_return'])
                                ? date('m/d/Y', strtotime($borrow_request['schedule_return']))
                                : '&nbsp;'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 2: Contact No. | Barangay | Borrower Signature -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="slip-field">
                        <label>Contact No.:</label>
                        <div class="slip-value"><?php echo htmlspecialchars($borrow_request['contact'] ?? ''); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="slip-field">
                        <label>Barangay:</label>
                        <div class="slip-value"><?php echo htmlspecialchars($borrow_request['barangay'] ?? ''); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="slip-field">
                        <label>Borrower Signature:</label>
                        <div class="slip-value">&nbsp;</div>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <table class="slip-table">
                <thead>
                    <tr>
                        <th>Things Borrowed</th>
                        <th class="qty-col">QTY</th>
                        <th class="rem-col">Remarks</th>
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
                                    'category' => $item['category'] ?? '',
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
                                    <?php echo nl2br(htmlspecialchars($item['description'])); ?>
                                    <?php if (!empty($item['property_numbers'])): ?>
                                        <br><small style="color:#888;">
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

            <!-- Releasing Officer & Approved By (name fields) -->
            <div class="row g-4 mt-3 mb-1">
                <div class="col-md-6">
                    <div class="slip-field">
                        <label>Releasing Officer:</label>
                        <div class="slip-value"><?php echo htmlspecialchars($borrow_request['releasing_officer'] ?? ''); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="slip-field">
                        <label>Approved by:</label>
                        <div class="slip-value"><?php echo htmlspecialchars($borrow_request['approved_by'] ?? ''); ?></div>
                    </div>
                </div>
            </div>

            <!-- Signature Lines -->
            <div class="row g-4 mt-2">
                <div class="col-md-6">
                    <div class="sig-block">
                        <div class="sig-line"></div>
                        <div class="sig-label">Releasing Officer Signature</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="sig-block">
                        <div class="sig-line"></div>
                        <div class="sig-label">Approved by Signature</div>
                    </div>
                </div>
            </div>

        </div><!-- /slip-wrapper -->

    </div><!-- /main-content -->
</div><!-- /main-wrapper -->

<?php require_once 'includes/logout-modal.php'; ?>
<?php require_once 'includes/change-password-modal.php'; ?>
<?php require_once 'includes/footer.php'; ?>

<!-- Return Confirmation Modal -->
<div class="modal fade" id="returnConfirmModal" tabindex="-1" aria-labelledby="returnConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="returnConfirmModalLabel">
                    <i class="bi bi-check-circle text-success me-2"></i>
                    Confirm Return
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to mark these items as returned?</p>
                <p class="mb-0"><small class="text-muted">This will update the asset items status back to 'serviceable' and mark the borrow request as 'returned'.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmReturnBtn">
                    <i class="bi bi-check-circle"></i> Confirm Return
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    let currentBorrowId = null;

    function markAsReturned(borrowId) {
        currentBorrowId = borrowId;
        const modal = new bootstrap.Modal(document.getElementById('returnConfirmModal'));
        modal.show();
    }

    document.getElementById('confirmReturnBtn').addEventListener('click', function() {
        if (currentBorrowId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="mark_returned">
                <input type="hidden" name="borrow_id" value="${currentBorrowId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });

    // Auto-print if ?print=1
    window.onload = function () {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === '1') {
            setTimeout(() => window.print(), 500);
        }
    };

</script>

<?php include 'includes/sidebar-scripts.php'; ?>
</body>
</html>
