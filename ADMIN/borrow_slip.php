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

if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "Borrow request ID not provided.";
    header("Location: borrowing.php");
    exit();
}

$borrow_id = $_GET['id'];

// Get borrow request details
$stmt = $conn->prepare("SELECT * FROM borrow_form_submissions WHERE id = ?");
$stmt->bind_param("i", $borrow_id);
$stmt->execute();
$result = $stmt->get_result();
$borrow_request = $result->fetch_assoc();
$stmt->close();

if (!$borrow_request) {
    $_SESSION['error_message'] = "Borrow request not found.";
    header("Location: borrowing.php");
    exit();
}

// Parse items from JSON wrapper
$wrapper_json = $borrow_request['items'];
$items = [];

if (!empty($wrapper_json)) {
    try {
        $wrapper = json_decode($wrapper_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            $items = [];
        } elseif (isset($wrapper['data'])) {
            // Use the data field directly
            $items = $wrapper['data'];
        } elseif (isset($wrapper['encoded'])) {
            // Fallback to base64 encoded data
            $items_json = base64_decode($wrapper['encoded']);
            $items = json_decode($items_json, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON decode error from encoded: " . json_last_error_msg());
                $items = [];
            }
        } else {
            error_log("Invalid wrapper structure");
            $items = [];
        }
    } catch (Exception $e) {
        error_log("Wrapper decode error: " . $e->getMessage());
        $items = [];
    }
}

// Log borrow slip view
logSystemAction($_SESSION['user_id'], 'view', 'borrow_slip', "Borrow ID: $borrow_id");

// Get system settings for header
$system_name = "PIMS";
$system_logo = "../img/system_logo.png";
try {
    $settings_stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'system_name'");
    $settings_stmt->execute();
    $settings_result = $settings_stmt->get_result();
    if ($settings_row = $settings_result->fetch_assoc()) {
        $system_name = $settings_row['setting_value'];
    }
    $settings_stmt->close();
    
    $logo_stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'system_logo'");
    $logo_stmt->execute();
    $logo_result = $logo_stmt->get_result();
    if ($logo_row = $logo_result->fetch_assoc()) {
        $system_logo = "../uploads/" . $logo_row['setting_value'];
    }
    $logo_stmt->close();
} catch (Exception $e) {
    // Use defaults if settings not found
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Slip #<?php echo htmlspecialchars($borrow_id); ?> - <?php echo htmlspecialchars($system_name); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .borrow-slip-container {
            background: white;
            border-radius: var(--border-radius-xl);
            box-shadow: var(--shadow);
            margin: 0 auto;
            max-width: 900px;
            overflow: hidden;
        }
        
        .government-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        
        .government-header .seal {
            position: absolute;
            left: 30px;
            top: 50%;
            transform: translateY(-50%);
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #1e3c72;
            font-size: 12px;
            text-align: center;
        }
        
        .government-header h1 {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
            letter-spacing: 1px;
        }
        
        .government-header h2 {
            font-size: 1rem;
            font-weight: 500;
            margin: 5px 0;
            opacity: 0.9;
        }
        
        .government-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 5px 0 0 0;
            color: #ffd700;
        }
        
        .document-info {
            position: absolute;
            right: 30px;
            top: 20px;
            text-align: right;
            font-size: 0.85rem;
        }
        
        .document-info div {
            margin: 2px 0;
        }
        
        .slip-body {
            padding: 40px;
        }
        
        .slip-title {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
            color: #191ba9;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .borrower-info {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .borrower-info h4 {
            color: #191ba9;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 15px;
            align-items: center;
        }
        
        .info-label {
            font-weight: 600;
            min-width: 150px;
            color: #495057;
        }
        
        .info-value {
            flex: 1;
            padding: 8px 12px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            min-height: 38px;
            display: flex;
            align-items: center;
        }
        
        .signature-box {
            margin-top: 10px;
            height: 60px;
            border: 2px dashed #ced4da;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-style: italic;
            background: white;
        }
        
        .items-table {
            margin-bottom: 30px;
        }
        
        .items-table h4 {
            color: #191ba9;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .items-table table {
            border: 2px solid #191ba9;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .items-table th {
            background: #191ba9;
            color: white;
            font-weight: 600;
            text-align: center;
            padding: 15px 10px;
            border: 1px solid #191ba9;
        }
        
        .items-table td {
            padding: 12px 10px;
            border: 1px solid #dee2e6;
            vertical-align: top;
        }
        
        .signatures-section {
            margin-top: 40px;
        }
        
        .signature-row {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        
        .signature-box-official {
            text-align: center;
            width: 45%;
        }
        
        .signature-line {
            border-top: 2px solid #333;
            margin: 40px 0 5px 0;
            height: 2px;
        }
        
        .signature-title {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }
        
        .slip-footer {
            background: #f8f9fa;
            padding: 20px 40px;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .submission-info {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .status-badge {
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-returned {
            background: #cce5ff;
            color: #004085;
            border: 1px solid #b3d7ff;
        }
        
        .action-buttons {
            padding: 20px 40px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            text-align: center;
        }
        
        .btn-print {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 5px;
            font-weight: 600;
            margin: 0 5px;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 5px;
            font-weight: 600;
            margin: 0 5px;
        }
        
        .btn-return {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 5px;
            font-weight: 600;
            margin: 0 5px;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .borrow-slip-container {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .action-buttons {
                display: none;
            }
            
            .government-header {
                background: #1e3c72 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <?php 
    $page_title = 'Borrow Slip';
    ?>
    <!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="borrow-slip-container">
                <!-- Government Header -->
                <div class="government-header">
                    <div class="seal">
                        LGU<br>PILAR<br>SEAL
                    </div>
                    <h1>REPUBLIC OF THE PHILIPPINES</h1>
                    <h2>Province of Sorsogon</h2>
                    <h3>LOCAL GOVERNMENT UNIT OF PILAR</h3>
                    
                    <div class="document-info">
                        <div><strong>Document Code:</strong> BFS-<?php echo str_pad($borrow_id, 5, '0', STR_PAD_LEFT); ?></div>
                        <div><strong>Effective Date:</strong> <?php echo date('Y-m-d'); ?></div>
                    </div>
                </div>
                
                <!-- Slip Body -->
                <div class="slip-body">
                    <h2 class="slip-title">Borrow Slip</h2>
                    
                    <!-- Borrower Information -->
                    <div class="borrower-info">
                        <h4><i class="bi bi-person"></i> Borrower Details</h4>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-row">
                                    <div class="info-label">Name:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($borrow_request['guest_name']); ?></div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-label">Contact No.:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($borrow_request['contact']); ?></div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-label">Barangay:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($borrow_request['barangay']); ?></div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="info-row">
                                    <div class="info-label">Date Borrowed:</div>
                                    <div class="info-value"><?php echo date('M d, Y', strtotime($borrow_request['date_borrowed'])); ?></div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-label">Schedule of Return:</div>
                                    <div class="info-value"><?php echo date('M d, Y', strtotime($borrow_request['schedule_return'])); ?></div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-label">Borrower Signature:</div>
                                    <div class="signature-box">_________________________</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Items Table -->
                    <div class="items-table">
                        <h4><i class="bi bi-box"></i> Things Borrowed</h4>
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 60%;">Things Borrowed</th>
                                    <th style="width: 15%;">QTY</th>
                                    <th style="width: 25%;">REMARKS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($items) && is_array($items)): ?>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td>
                                                <?php echo nl2br(htmlspecialchars($item['thing'])); ?>
                                                <?php if (isset($item['category']) && $item['category']): ?>
                                                    <br><small class="text-muted">Category: <?php echo htmlspecialchars($item['category']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center"><?php echo htmlspecialchars($item['qty']); ?></td>
                                            <td><?php echo htmlspecialchars($item['remarks'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No items borrowed</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Signatures Section -->
                    <div class="signatures-section">
                        <div class="signature-row">
                            <div class="signature-box-official">
                                <div class="signature-title">Releasing Officer</div>
                                <div class="signature-line"></div>
                                <div><?php echo htmlspecialchars($borrow_request['releasing_officer']); ?></div>
                            </div>
                            
                            <div class="signature-box-official">
                                <div class="signature-title">Approved by</div>
                                <div class="signature-line"></div>
                                <div><?php echo htmlspecialchars($borrow_request['approved_by']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="slip-footer">
                    <div class="submission-info">
                        <strong>Submission #BFS-<?php echo str_pad($borrow_id, 5, '0', STR_PAD_LEFT); ?></strong> • 
                        Submitted: <?php echo date('M d, Y h:i A', strtotime($borrow_request['submitted_at'])); ?>
                    </div>
                    <div>
                        <?php if ($borrow_request['status'] === 'approved'): ?>
                            <span class="status-badge status-approved">Approved</span>
                        <?php elseif ($borrow_request['status'] === 'returned'): ?>
                            <span class="status-badge status-returned">Returned</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="btn-print" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <a href="borrowing.php" class="btn-back">
                        <i class="bi bi-arrow-left"></i> Back to Borrowing
                    </a>
                    <?php if ($borrow_request['status'] === 'approved'): ?>
                        <button class="btn-return" onclick="returnItems(<?php echo $borrow_id; ?>)">
                            <i class="bi bi-check-circle"></i> Mark as Returned
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function returnItems(borrowId) {
            if (confirm('Are you sure you want to mark this borrow request as returned? This will update the asset items status back to serviceable.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'borrowing.php';
                form.innerHTML = `
                    <input type="hidden" name="action" value="mark_returned">
                    <input type="hidden" name="borrow_id" value="${borrowId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>

    <?php include 'includes/sidebar-scripts.php'; ?>
</body>
</html>
