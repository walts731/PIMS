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

// Check if user has correct role
if ($_SESSION['role'] !== 'system_admin' && $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Get form ID from URL
$form_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($form_id <= 0) {
    header('Location: forms.php?error=Invalid form ID');
    exit();
}

// Get form details
$stmt = $conn->prepare("
    SELECT f.*, u.first_name, u.last_name, u.username 
    FROM forms f 
    LEFT JOIN users u ON f.created_by = u.id 
    WHERE f.id = ?
");

$stmt->bind_param("i", $form_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header('Location: forms.php?error=Form not found');
    exit();
}

$form = $result->fetch_assoc();
$stmt->close();

// Handle form-specific actions based on form type
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($form['form_code'] === 'PAR') {
        // Handle PAR form actions
        if (isset($_POST['add_par'])) {
            $office_id = $_POST['office_id'];
            $received_by_name = $_POST['received_by_name'];
            $issued_by_name = $_POST['issued_by_name'];
            $position_office_left = $_POST['position_office_left'];
            $position_office_right = $_POST['position_office_right'];
            $entity_name = $_POST['entity_name'];
            $fund_cluster = $_POST['fund_cluster'];
            $par_no = $_POST['par_no'];
            $date_received_left = $_POST['date_received_left'];
            $date_received_right = $_POST['date_received_right'];
            
            // Handle header image upload
            $header_image = null;
            if (isset($_FILES['header_image']) && $_FILES['header_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/forms/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_name = time() . '_' . basename($_FILES['header_image']['name']);
                $target_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['header_image']['tmp_name'], $target_path)) {
                    $header_image = $file_name;
                }
            }
            
            $stmt = $conn->prepare("
                INSERT INTO par_form (form_id, office_id, received_by_name, issued_by_name, position_office_left, position_office_right, header_image, entity_name, fund_cluster, par_no, date_received_left, date_received_right) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isssssssssss", $form_id, $office_id, $received_by_name, $issued_by_name, $position_office_left, $position_office_right, $header_image, $entity_name, $fund_cluster, $par_no, $date_received_left, $date_received_right);
            
            if ($stmt->execute()) {
                $par_id = $stmt->insert_id;
                logSystemAction($_SESSION['user_id'], 'create', 'par_form', "Created new PAR: $par_no");
                header("Location: form_details.php?id=$form_id&message=PAR created successfully");
            } else {
                header("Location: form_details.php?id=$form_id&error=Failed to create PAR");
            }
            $stmt->close();
            exit();
        }
        
        if (isset($_POST['add_par_item'])) {
            $par_form_id = $_POST['par_form_id'];
            $asset_id = $_POST['asset_id'];
            $quantity = $_POST['quantity'];
            $unit = $_POST['unit'];
            $description = $_POST['description'];
            $property_no = $_POST['property_no'];
            $date_acquired = $_POST['date_acquired'];
            $unit_price = $_POST['unit_price'];
            $amount = $_POST['amount'];
            
            $stmt = $conn->prepare("
                INSERT INTO par_items (form_id, asset_id, quantity, unit, description, property_no, date_acquired, unit_price, amount) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iidssssddd", $par_form_id, $asset_id, $quantity, $unit, $description, $property_no, $date_acquired, $unit_price, $amount);
            
            if ($stmt->execute()) {
                logSystemAction($_SESSION['user_id'], 'create', 'par_items', "Added item to PAR ID: $par_form_id");
                header("Location: form_details.php?id=$form_id&message=Item added successfully");
            } else {
                header("Location: form_details.php?id=$form_id&error=Failed to add item");
            }
            $stmt->close();
            exit();
        }
    }
}

// Get form-specific data
$form_data = [];
if ($form['form_code'] === 'PAR') {
    // Get PAR forms
    $result = $conn->query("
        SELECT pf.*, o.office_name 
        FROM par_form pf 
        LEFT JOIN offices o ON pf.office_id = o.id 
        WHERE pf.form_id = $form_id 
        ORDER BY pf.created_at DESC
    ");
    while ($row = $result->fetch_assoc()) {
        $form_data[] = $row;
    }
}

// Get header image from forms table
$header_image = '';
$result = $conn->query("SELECT header_image FROM forms WHERE id = $form_id");
if ($result && $row = $result->fetch_assoc()) {
    $header_image = $row['header_image'];
}

// Get offices for dropdown
$offices = [];
$result = $conn->query("SELECT id, office_name FROM offices WHERE status = 'active' ORDER BY office_name");
while ($row = $result->fetch_assoc()) {
    $offices[] = $row;
}

// Get form statistics
$stats = [];
if ($form['form_code'] === 'PAR') {
    $result = $conn->query("SELECT COUNT(*) as count FROM par_form WHERE form_id = $form_id");
    $stats['total_entries'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM par_items WHERE form_id IN (SELECT id FROM par_form WHERE form_id = $form_id)");
    $stats['total_items'] = $result->fetch_assoc()['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($form['form_title']); ?> - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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
            overflow-x: hidden;
        }
        
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
        }
        
        .form-info-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--accent-color);
        }
        
        .entry-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid var(--primary-color);
        }
        
        .entry-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .form-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .form-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .tab-content {
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = $form['form_title'];
    ?>
    <!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
        <!-- Main Content -->
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="bi bi-file-earmark-text"></i> <?php echo htmlspecialchars($form['form_title']); ?>
                        </h1>
                        <p class="text-muted mb-0">
                            Form Code: <strong><?php echo htmlspecialchars($form['form_code']); ?></strong> | 
                            <?php echo htmlspecialchars($form['description']); ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="forms.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Forms
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Message Display -->
            <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Form Information -->
            <div class="form-info-card">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">Form Details</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Form Code:</strong></td><td><?php echo htmlspecialchars($form['form_code']); ?></td></tr>
                            <tr><td><strong>Form Title:</strong></td><td><?php echo htmlspecialchars($form['form_title']); ?></td></tr>
                            <tr><td><strong>Status:</strong></td><td><span class="status-badge bg-<?php echo $form['status'] === 'active' ? 'success' : 'secondary'; ?> text-white"><?php echo htmlspecialchars($form['status']); ?></span></td></tr>
                            <tr><td><strong>Description:</strong></td><td><?php echo htmlspecialchars($form['description'] ?? 'No description'); ?></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">Statistics</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Total Entries:</strong></td><td><?php echo number_format($stats['total_entries'] ?? 0); ?></td></tr>
                            <tr><td><strong>Total Items:</strong></td><td><?php echo number_format($stats['total_items'] ?? 0); ?></td></tr>
                            <tr><td><strong>Created By:</strong></td><td><?php echo htmlspecialchars($form['first_name'] . ' ' . $form['last_name']); ?></td></tr>
                            <tr><td><strong>Created Date:</strong></td><td><?php echo date('M j, Y H:i:s', strtotime($form['created_at'])); ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Form-Specific Content -->
            <?php if ($form['form_code'] === 'PAR'): ?>
                <!-- PAR Form Management -->
                <ul class="nav nav-tabs" id="parTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="par-preview-tab" data-bs-toggle="tab" data-bs-target="#par-preview" type="button" role="tab">
                            <i class="bi bi-eye"></i> PAR Preview
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="par-entries-tab" data-bs-toggle="tab" data-bs-target="#par-entries" type="button" role="tab">
                            <i class="bi bi-list"></i> PAR Entries
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="parTabsContent">
                    <!-- PAR Preview Tab -->
                    <div class="tab-pane fade show active" id="par-preview" role="tabpanel">
                        <div class="card border-0 shadow-lg rounded-4">
                            <div class="card-header bg-info text-white rounded-top-4">
                                <h6 class="mb-0">
                                    <i class="bi bi-eye"></i> PAR Form Preview
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="par-preview-container" style="background: white; border: 2px solid #dee2e6; border-radius: 8px; padding: 20px; font-family: 'Times New Roman', serif;">
                                    <!-- PAR Form Header -->
                                    <div style="text-align: center; margin-bottom: 20px;">
                                        <?php 
                                        // Debug: Check header image
                                        if (!empty($header_image)) {
                                            echo "<!-- Debug: header_image found: " . htmlspecialchars($header_image) . " -->";
                                            echo '<div style="margin-bottom: 10px;">';
                                            echo '<img src="../uploads/forms/' . htmlspecialchars($header_image) . '" alt="Header Image" style="width: 100%; max-height: 120px; object-fit: contain; border: 1px solid red;">';
                                            echo '</div>';
                                        } else {
                                            echo "<!-- Debug: header_image is empty -->";
                                        }
                                        ?>
                                        <div style="text-align: center;">
                                            <p style="margin: 0; font-size: 16px; font-weight: bold;">PROPERTY ACKNOWLEDGEMENT RECEIPT</p>
                                            <p style="margin: 0; font-size: 12px;">MUNICIPALITY OF PILAR</p>
                                            <p style="margin: 0; font-size: 12px;">OMM</p>
                                            <p style="margin: 0; font-size: 12px;">OFFICE/LOCATION</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Entity Name and Fund Cluster -->
                                    <div style="margin-bottom: 15px;">
                                        <table style="width: 100%; border-collapse: collapse;">
                                            <tr>
                                                <td style="width: 33%; padding: 5px; border: 1px solid #000;">
                                                    <strong>Entity Name:</strong> ___________________________
                                                </td>
                                                <td style="width: 33%; padding: 5px; border: 1px solid #000;">
                                                    <strong>Fund Cluster:</strong> ___________________________
                                                </td>
                                                <td style="width: 34%; padding: 5px; border: 1px solid #000;">
                                                    <strong>PAR No:</strong> ___________________________
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                    
                                    <!-- Main Table -->
                                    <div style="margin-bottom: 20px;">
                                        <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                                            <thead>
                                                <tr style="background-color: #f8f9fa;">
                                                    <th style="border: 1px solid #000; padding: 8px; text-align: center;">Qty.</th>
                                                    <th style="border: 1px solid #000; padding: 8px; text-align: center;">Unit</th>
                                                    <th style="border: 1px solid #000; padding: 8px;">Description</th>
                                                    <th style="border: 1px solid #000; padding: 8px;">Property Number</th>
                                                    <th style="border: 1px solid #000; padding: 8px;">Date Acquired</th>
                                                    <th style="border: 1px solid #000; padding: 8px; text-align: right;">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: center;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: center;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: right;">&nbsp;</td>
                                                </tr>
                                                <tr>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: center;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: center;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: right;">&nbsp;</td>
                                                </tr>
                                                <tr>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: center;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: center;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: right;">&nbsp;</td>
                                                </tr>
                                                <tr>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: center;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: center;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: right;">&nbsp;</td>
                                                </tr>
                                                <tr>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: center;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: center;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: right;">&nbsp;</td>
                                                </tr>
                                                <tr>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: center;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: center;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: right;">&nbsp;</td>
                                                </tr>
                                                <tr>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: center;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: center;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: right;">&nbsp;</td>
                                                </tr>
                                                <tr>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: center;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: center;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px;">&nbsp;</td>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: right;">&nbsp;</td>
                                                </tr>
                                                <tr style="font-weight: bold; background-color: #f8f9fa;">
                                                    <td colspan="5" style="border: 1px solid #000; padding: 8px; text-align: right;">TOTAL</td>
                                                    <td style="border: 1px solid #000; padding: 8px; text-align: right;">&nbsp;</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Remarks Section -->
                                    <div style="margin-bottom: 20px;">
                                        <p style="margin: 0; font-weight: bold;">Remarks:</p>
                                        <div style="border: 1px solid #000; padding: 10px; min-height: 60px; font-size: 12px;">
                                            This is to acknowledge receipt of the above-mentioned property items which shall be accounted for in accordance with existing government accounting and auditing rules and regulations.
                                        </div>
                                    </div>
                                    
                                    <!-- Signature Section -->
                                    <div style="margin-top: 40px;">
                                        <table style="width: 100%; border-collapse: collapse;">
                                            <tr>
                                                <td style="width: 50%; padding: 10px; text-align: center; vertical-align: top;">
                                                    <p style="margin: 0; font-weight: bold;">Received by:</p>
                                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 40px;"></div>
                                                    <p style="margin: 5px 0; font-size: 12px;">Signature over Printed Name</p>
                                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 20px;"></div>
                                                    <p style="margin: 5px 0; font-size: 12px;">Position / Office</p>
                                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 20px;"></div>
                                                    <p style="margin: 5px 0; font-size: 12px;">Date</p>
                                                </td>
                                                <td style="width: 50%; padding: 10px; text-align: center; vertical-align: top;">
                                                    <p style="margin: 0; font-weight: bold;">Issued by:</p>
                                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 40px;"></div>
                                                    <p style="margin: 5px 0; font-size: 12px;">Signature over Printed Name</p>
                                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 20px;"></div>
                                                    <p style="margin: 5px 0; font-size: 12px;">Position / Office</p>
                                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 20px;"></div>
                                                    <p style="margin: 5px 0; font-size: 12px;">Date</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                    
                                    <!-- Footer Note -->
                                    <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
                                        <p style="margin: 0;">This form is prescribed by the Commission on Audit (COA)</p>
                                        <p style="margin: 0;">Property Acknowledgement Receipt (PAR) - Government Property Management</p>
                                    </div>
                                </div>
                                
                                <!-- Preview Controls -->
                                <div class="mt-3 text-center">
                                    <button class="btn btn-outline-primary btn-sm" onclick="window.print()">
                                        <i class="bi bi-printer"></i> Print Preview
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="zoomPreview()">
                                        <i class="bi bi-zoom-in"></i> Zoom
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- PAR Entries Tab -->
                    <div class="tab-pane fade" id="par-entries" role="tabpanel">
                        <div class="card border-0 shadow-lg rounded-4">
                            <div class="card-header bg-primary text-white rounded-top-4">
                                <h6 class="mb-0">
                                    <i class="bi bi-list"></i> PAR Entries
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($form_data)): ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-file-earmark-text fs-1 text-muted"></i>
                                        <p class="text-muted mt-3">No PAR entries found</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table id="parTable" class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>PAR No.</th>
                                                    <th>Entity Name</th>
                                                    <th>Received By</th>
                                                    <th>Office</th>
                                                    <th>Date Received</th>
                                                    <th>Items Count</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($form_data as $entry): ?>
                                                    <?php
                                                    // Get item count for this PAR
                                                    $item_count_result = $conn->query("SELECT COUNT(*) as count FROM par_items WHERE form_id = " . $entry['id']);
                                                    $item_count = $item_count_result->fetch_assoc()['count'];
                                                    ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($entry['par_no']); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($entry['entity_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($entry['received_by_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($entry['office_name'] ?? 'Not assigned'); ?></td>
                                                        <td><?php echo date('M j, Y', strtotime($entry['date_received_left'])); ?></td>
                                                        <td><span class="badge bg-info"><?php echo $item_count; ?> items</span></td>
                                                        <td>
                                                            <div class="form-actions">
                                                                <button class="btn btn-sm btn-outline-primary" onclick="viewPAR(<?php echo $entry['id']; ?>)">
                                                                    <i class="bi bi-eye"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-outline-success" onclick="manageItems(<?php echo $entry['id']; ?>)">
                                                                    <i class="bi bi-box"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        <?php require_once 'includes/sidebar-scripts.php'; ?>
        
        $(document).ready(function() {
            // Initialize DataTables
            $('#parTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[0, 'desc']]
            });
        });
        
        // View PAR details
        function viewPAR(parId) {
            // This would open a modal or redirect to PAR details page
            alert('View PAR details functionality would be implemented here');
        }
        
        // Manage PAR items
        function manageItems(parId) {
            // This would open items management modal or page
            alert('Manage items functionality would be implemented here');
        }
        
        // Zoom preview functionality
        let zoomLevel = 1;
        function zoomPreview() {
            const preview = document.querySelector('.par-preview-container');
            zoomLevel = zoomLevel === 1 ? 1.5 : 1;
            preview.style.transform = `scale(${zoomLevel})`;
            preview.style.transformOrigin = 'top center';
            preview.style.transition = 'transform 0.3s ease';
            
            // Update button text
            const zoomBtn = event.target.closest('button');
            if (zoomLevel === 1.5) {
                zoomBtn.innerHTML = '<i class="bi bi-zoom-out"></i> Zoom Out';
            } else {
                zoomBtn.innerHTML = '<i class="bi bi-zoom-in"></i> Zoom';
            }
        }
    </script>
</body>
</html>
