<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

// Log create tag page access
logSystemAction($_SESSION['user_id'], 'access', 'create_tag', 'Admin accessed create tag page');

// Get asset item ID from URL
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($item_id === 0) {
    $_SESSION['error'] = 'Invalid asset item ID';
    header('Location: asset_items.php');
    exit();
}

// Get asset item details with related information
$item = null;
$item_sql = "SELECT ai.*, 
                   a.description as asset_description, a.unit, a.quantity as asset_quantity, a.unit_cost,
                   ac.category_name, ac.category_code, ac.id as category_id,
                   o.office_name,
                   e.employee_no, e.firstname, e.lastname, e.email,
                   ics.ics_no,
                   par.par_no
            FROM asset_items ai 
            LEFT JOIN assets a ON ai.asset_id = a.id 
            LEFT JOIN asset_categories ac ON a.asset_categories_id = ac.id 
            LEFT JOIN offices o ON ai.office_id = o.id 
            LEFT JOIN employees e ON ai.employee_id = e.id 
            LEFT JOIN ics_forms ics ON ai.ics_id = ics.id 
            LEFT JOIN par_forms par ON ai.par_id = par.id 
            WHERE ai.id = ?";
$item_stmt = $conn->prepare($item_sql);
$item_stmt->bind_param("i", $item_id);
$item_stmt->execute();
$item_result = $item_stmt->get_result();
if ($item_row = $item_result->fetch_assoc()) {
    $item = $item_row;
}
$item_stmt->close();

// Get existing desktop computer data if it's a desktop computer
$desktop_data = [];
if ($item && $item['asset_subcategory_id']) {
    $desktop_sql = "SELECT * FROM asset_desktop_computers WHERE asset_item_id = ?";
    $desktop_stmt = $conn->prepare($desktop_sql);
    $desktop_stmt->bind_param("i", $item_id);
    $desktop_stmt->execute();
    $desktop_result = $desktop_stmt->get_result();
    if ($desktop_row = $desktop_result->fetch_assoc()) {
        $desktop_data = $desktop_row;
    }
    $desktop_stmt->close();
}

if (!$item) {
    $_SESSION['error'] = 'Asset item not found';
    header('Location: asset_items.php');
    exit();
}

// Get all asset categories for dropdown
$categories = [];
$categories_sql = "SELECT id, category_name, category_code FROM asset_categories WHERE status = 'active' ORDER BY category_name";
$categories_result = $conn->query($categories_sql);
while ($category_row = $categories_result->fetch_assoc()) {
    $categories[] = $category_row;
}

// Get subcategories for the selected category (if any)
$subcategories = [];
$selected_category_id = $item['category_id'] ?? 0;
if ($selected_category_id > 0) {
    $subcategories_sql = "SELECT id, sub_category_code, sub_category_name FROM asset_sub_categories WHERE asset_categories_id = ? AND status = 'active' ORDER BY sub_category_code";
    $subcategories_stmt = $conn->prepare($subcategories_sql);
    $subcategories_stmt->bind_param("i", $selected_category_id);
    $subcategories_stmt->execute();
    $subcategories_result = $subcategories_stmt->get_result();
    while ($subcategory_row = $subcategories_result->fetch_assoc()) {
        $subcategories[] = $subcategory_row;
    }
    $subcategories_stmt->close();
}

// Get active employees for dropdown
$employees = [];
$employees_sql = "SELECT id, employee_no, firstname, lastname FROM employees WHERE employment_status = 'permanent' ORDER BY lastname, firstname";
$employees_result = $conn->query($employees_sql);
while ($employee_row = $employees_result->fetch_assoc()) {
    $employees[] = $employee_row;
}

// Get tag format for inventory_tag
$tag_format = null;
$tag_format_sql = "SELECT * FROM tag_formats WHERE tag_type = 'inventory_tag' AND status = 'active' LIMIT 1";
$tag_format_result = $conn->query($tag_format_sql);
if ($tag_format_row = $tag_format_result->fetch_assoc()) {
    $tag_format = $tag_format_row;
}

// Function to generate tag number based on format
function generateTagNumber($format) {
    if (!$format) return '';
    
    $components = json_decode($format['format_components'], true);
    // Handle double-encoded JSON - if we get a string, decode it again
    if (is_string($components)) {
        $components = json_decode($components, true);
    }
    if (!$components || !is_array($components)) return '';
    
    $tag_number = '';
    $current_number = $format['current_number'] + 1;
    $separator = $format['separator'] ?? '-';
    
    foreach ($components as $component) {
        switch ($component['type']) {
            case 'text':
                $tag_number .= $component['value'];
                break;
            case 'digits':
                $digits = $component['digits'] ?? 4;
                $tag_number .= str_pad($current_number, $digits, '0', STR_PAD_LEFT);
                break;
            case 'year':
                $tag_number .= date('Y');
                break;
            case 'month':
                $tag_number .= date('m');
                break;
            case 'day':
                $tag_number .= date('d');
                break;
        }
        
        // Add separator except for last component
        if ($component !== end($components)) {
            $tag_number .= $separator;
        }
    }
    
    return $tag_number;
}

// Generate inventory tag number
$generated_inventory_tag = '';
if ($tag_format) {
    $generated_inventory_tag = generateTagNumber($tag_format);
}
$category_fields = [
    '07' => [
        'brand' => ['label' => 'Brand', 'type' => 'text', 'required' => true],
        'model' => ['label' => 'Model', 'type' => 'text', 'required' => true],
        'plate_number' => ['label' => 'Plate Number', 'type' => 'text', 'required' => true],
        'color' => ['label' => 'Color', 'type' => 'text', 'required' => false],
        'engine_number' => ['label' => 'Engine Number', 'type' => 'text', 'required' => true],
        'chassis_number' => ['label' => 'Chassis Number', 'type' => 'text', 'required' => true],
        'year_model' => ['label' => 'Year Model', 'type' => 'number', 'required' => false]
    ],
    '030' => [
        'processor' => ['label' => 'Processor', 'type' => 'text', 'required' => false],
        'ram' => ['label' => 'RAM (GB)', 'type' => 'text', 'required' => false],
        'storage' => ['label' => 'Storage', 'type' => 'text', 'required' => false],
        'model' => ['label' => 'Model', 'type' => 'text', 'required' => true],
        'operating_system' => ['label' => 'Operating System', 'type' => 'text', 'required' => false],
        'serial_number' => ['label' => 'Serial Number', 'type' => 'text', 'required' => true]
    ],
    '02' => [
        'material' => ['label' => 'Material', 'type' => 'text', 'required' => true],
        'dimensions' => ['label' => 'Dimensions (LxWxH)', 'type' => 'text', 'required' => false],
        'color' => ['label' => 'Color', 'type' => 'text', 'required' => false],
        'manufacturer' => ['label' => 'Manufacturer', 'type' => 'text', 'required' => false]
    ],
    '04' => [
        'manufacturer' => ['label' => 'Manufacturer', 'type' => 'text', 'required' => true],
        'model' => ['label' => 'Model', 'type' => 'text', 'required' => true],
        'capacity' => ['label' => 'Capacity', 'type' => 'text', 'required' => false],
        'power_rating' => ['label' => 'Power Rating', 'type' => 'text', 'required' => false],
        'serial_number' => ['label' => 'Serial Number', 'type' => 'text', 'required' => true]
    ],
    '05' => [
        'brand' => ['label' => 'Brand', 'type' => 'text', 'required' => true],
        'model' => ['label' => 'Model', 'type' => 'text', 'required' => true],
        'serial_number' => ['label' => 'Serial Number', 'type' => 'text', 'required' => true]
    ],
    '06' => [
        'software_name' => ['label' => 'Software Name', 'type' => 'text', 'required' => true],
        'version' => ['label' => 'Version', 'type' => 'text', 'required' => true],
        'license_key' => ['label' => 'License Key', 'type' => 'text', 'required' => false],
        'expiry_date' => ['label' => 'Expiry Date', 'type' => 'date', 'required' => false]
    ],
    '03' => [
        'lot_number' => ['label' => 'Lot Number', 'type' => 'text', 'required' => true],
        'area_size' => ['label' => 'Area Size (sqm)', 'type' => 'text', 'required' => true],
        'location' => ['label' => 'Location', 'type' => 'text', 'required' => true],
        'tax_declaration' => ['label' => 'Tax Declaration No', 'type' => 'text', 'required' => false]
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Tag - <?php echo htmlspecialchars($item['description']); ?> | PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
<<<<<<< HEAD
    <link href="assets/css/admin-unified.css" rel="stylesheet">
=======
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--light-color) 0%, var(--light-accent) 100%);
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
        
        .form-container {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .asset-info-card {
            background: var(--primary-gradient);
            color: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .category-fields {
            background: #f8f9fa;
            border-radius: var(--border-radius-md);
            padding: 1.5rem;
            margin-top: 1rem;
            border-left: 3px solid var(--primary-color);
        }
        
        .btn-back {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-lg);
            transition: var(--transition);
        }
        
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(var(--primary-rgb), 0.3);
            color: white;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border-radius: var(--border-radius-md);
            border: 1px solid #dee2e6;
            transition: var(--transition);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(var(--primary-rgb), 0.25);
        }
        
        .required {
            color: #dc3545;
        }
    </style>
>>>>>>> 1b538331e37148a87bb4fe58e2b12354b716a520
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Create Tag - ' . htmlspecialchars($item['description']);
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
                        <i class="bi bi-tag"></i> Create Asset Tag
                    </h1>
                    <p class="text-muted mb-0">Creating tag for: <?php echo htmlspecialchars($item['description']); ?></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a href="asset_items.php?asset_id=<?php echo $item['asset_id']; ?>" class="dropdown-item">
                                    <i class="bi bi-arrow-left"></i> Back to Items
                                </a>
                            </li>
                            <li>
                                <a href="view_asset_item.php?id=<?php echo $item_id; ?>" class="dropdown-item">
                                    <i class="bi bi-eye"></i> View Asset
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Asset Information Card -->
        <div class="detail-card">
            <h5 class="mb-3"><i class="bi bi-info-circle"></i> Asset Information</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <div class="detail-label">Asset Description</div>
                        <div class="detail-value"><?php echo htmlspecialchars($item['asset_description']); ?></div>
                    </div>
                    <div class="mb-3">
                        <div class="detail-label">Value</div>
                        <div class="detail-value">₱<?php echo number_format($item['value'], 2); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <div class="detail-label">Acquisition Date</div>
                        <div class="detail-value"><?php echo date('F j, Y', strtotime($item['acquisition_date'])); ?></div>
                    </div>
                    <div class="mb-3">
                        <div class="detail-label">Office</div>
                        <div class="detail-value"><?php echo $item['office_name'] ? htmlspecialchars($item['office_name']) : 'Not assigned'; ?></div>
                    </div>
                    <div class="mb-3">
                        <div class="detail-label">ICS/PAR No</div>
                        <div class="detail-value">
                            <?php 
                            $reference = '';
                            if ($item['ics_no']) {
                                $reference = 'ICS No: ' . htmlspecialchars($item['ics_no']);
                            }
                            if ($item['par_no']) {
                                $reference = $reference ? $reference . ' / PAR No: ' . htmlspecialchars($item['par_no']) : 'PAR No: ' . htmlspecialchars($item['par_no']);
                            }
                            echo $reference ? $reference : 'Not assigned';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages Display -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php 
                echo htmlspecialchars($_SESSION['success']); 
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php 
                echo htmlspecialchars($_SESSION['error']); 
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tag Creation Form -->
        <div class="detail-card">
            <h5 class="mb-4"><i class="bi bi-tag"></i> Tag Creation</h5>
            <form method="POST" action="process_tag.php" id="tagForm" enctype="multipart/form-data">
                <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                <input type="hidden" name="tag_format_id" value="<?php echo $tag_format['id'] ?? ''; ?>">
                <input type="hidden" name="current_number" value="<?php echo $tag_format['current_number'] ?? ''; ?>">
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category <span class="required">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" data-category-code="<?php echo $category['category_code']; ?>" <?php echo ($category['id'] == $item['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_code'] . ' - ' . $category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="subcategory_id" class="form-label">Subcategory</label>
                            <select class="form-select" id="subcategory_id" name="subcategory_id" <?php echo $selected_category_id == 0 ? 'disabled' : ''; ?>>
                                <option value="">Select Subcategory</option>
                                <?php foreach ($subcategories as $subcategory): ?>
                                    <option value="<?php echo $subcategory['id']; ?>" <?php echo ($subcategory['id'] == $item['asset_subcategory_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subcategory['sub_category_code'] . ' - ' . $subcategory['sub_category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="office_name" class="form-label">Office <span class="required">*</span></label>
                            <input type="text" class="form-control" id="office_name" name="office_name" value="<?php echo htmlspecialchars($item['office_name'] ?? ''); ?>" placeholder="Enter office name" required>
                            <small class="form-text text-muted">Enter the office name where this asset is located</small>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="property_no" class="form-label">Property Number <span class="required">*</span></label>
                            <input type="text" class="form-control" id="property_no" name="property_no" value="<?php echo htmlspecialchars($item['property_no'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="end_user" class="form-label">End User <span class="required">*</span></label>
                            <input type="text" class="form-control" id="end_user" name="end_user" placeholder="Enter end user name" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="person_accountable" class="form-label">Person Accountable <span class="required">*</span></label>
                            <select class="form-select" id="person_accountable" name="person_accountable" required>
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>" <?php echo ($employee['id'] == $item['employee_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($employee['employee_no'] . ' - ' . $employee['lastname'] . ', ' . $employee['firstname']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="date_counted" class="form-label">Date Counted <span class="required">*</span></label>
                            <input type="date" class="form-control" id="date_counted" name="date_counted" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                </div>
                
                <!-- Category-specific fields will be loaded here -->
                <div id="categorySpecificFields"></div>
                
                <!-- Subcategory-specific fields will be loaded here -->
                <div id="subcategorySpecificFields"></div>
                
<<<<<<< HEAD
=======
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="asset_images" class="form-label">Asset Images</label>
                            
                            <?php
                            // Display existing images if any
                            $existing_images = [];
                            if (!empty($item['image']) && $item['image'] !== 'NULL') {
                                $decoded_images = json_decode($item['image'], true);
                                if (is_array($decoded_images)) {
                                    $existing_images = $decoded_images;
                                } elseif (!empty($item['image'])) {
                                    // Handle case where it's a single filename (not JSON)
                                    $existing_images = [$item['image']];
                                }
                            }
                            
                            if (!empty($existing_images)) {
                                echo '<div class="mb-3">';
                                echo '<h6>Existing Images:</h6>';
                                echo '<div class="row">';
                                
                                foreach ($existing_images as $index => $image) {
                                    $image_path = '../uploads/asset_images/' . $image;
                                    if (file_exists($image_path)) {
                                        echo '<div class="col-md-3 mb-2 position-relative">';
                                        echo '<div class="card">';
                                        echo '<img src="' . $image_path . '" class="card-img-top" style="height: 150px; object-fit: cover;" alt="Asset Image">';
                                        echo '<div class="card-body p-2">';
                                        echo '<small class="text-muted d-block text-truncate">' . htmlspecialchars($image) . '</small>';
                                        echo '</div>';
                                        echo '<button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1" onclick="deleteImage(\'' . htmlspecialchars($image) . '\')" title="Delete image">';
                                        echo '<i class="bi bi-trash"></i>';
                                        echo '</button>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                }
                                
                                echo '</div>';
                                echo '<small class="text-info">Existing images will be preserved. New images will be added to the collection.</small>';
                                echo '</div>';
                            }
                            ?>
                            
                            <input type="file" class="form-control" id="asset_images" name="asset_images[]" accept="image/*" multiple>
                            <small class="form-text text-muted">Upload additional images of the asset (JPG, PNG, GIF - Max 5MB each, Max 5 files)</small>
                            <div id="imagePreview" class="mt-2"></div>
                            
                            <!-- Hidden field to store existing images for JavaScript -->
                            <?php
                            // Make sure $existing_images is available here
                            if (!isset($existing_images)) {
                                $existing_images = [];
                            }
                            ?>
                            <input type="hidden" id="existingImagesData" value="<?php echo htmlspecialchars(json_encode($existing_images)); ?>">
                        </div>
                    </div>
                </div>
                
>>>>>>> 1b538331e37148a87bb4fe58e2b12354b716a520
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="asset_images" class="form-label">Asset Images</label>
                            
                            <?php
                            // Display existing images if any
                            $existing_images = [];
                            if (!empty($item['image']) && $item['image'] !== 'NULL') {
                                $decoded_images = json_decode($item['image'], true);
                                if (is_array($decoded_images)) {
                                    $existing_images = $decoded_images;
                                } elseif (!empty($item['image'])) {
                                    // Handle case where it's a single filename (not JSON)
                                    $existing_images = [$item['image']];
                                }
                            }
                            
                            if (!empty($existing_images)) {
                                echo '<div class="mb-3">';
                                echo '<h6 class="mb-3"><i class="bi bi-images"></i> Existing Images</h6>';
                                echo '<div class="row">';
                                
                                foreach ($existing_images as $index => $image) {
                                    $image_path = '../uploads/asset_images/' . $image;
                                    if (file_exists($image_path)) {
                                        echo '<div class="col-md-3 mb-3">';
                                        echo '<div class="card image-card">';
                                        echo '<img src="' . $image_path . '" class="card-img-top" style="height: 150px; object-fit: cover;" alt="Asset Image">';
                                        echo '<div class="card-body p-2">';
                                        echo '<small class="text-muted d-block text-truncate">' . htmlspecialchars($image) . '</small>';
                                        echo '</div>';
                                        echo '<button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2" onclick="deleteImage(\'' . htmlspecialchars($image) . '\')" title="Delete image">';
                                        echo '<i class="bi bi-trash"></i>';
                                        echo '</button>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                }
                                
                                echo '</div>';
                                echo '<small class="text-info"><i class="bi bi-info-circle"></i> Existing images will be preserved. New images will be added to the collection.</small>';
                                echo '</div>';
                            }
                            ?>
                            
                            <input type="file" class="form-control" id="asset_images" name="asset_images[]" accept="image/*" multiple>
                            <small class="form-text text-muted">Upload additional images of the asset (JPG, PNG, GIF - Max 5MB each, Max 5 files)</small>
                            <div id="imagePreview" class="mt-2"></div>
                            
                            <!-- Hidden field to store existing images for JavaScript -->
                            <?php
                            // Make sure $existing_images is available here
                            if (!isset($existing_images)) {
                                $existing_images = [];
                            }
                            ?>
                            <input type="hidden" id="existingImagesData" value="<?php echo htmlspecialchars(json_encode($existing_images)); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="asset_items.php?asset_id=<?php echo $item['asset_id']; ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create Tag
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
    </div>
    </div> <!-- Close main wrapper -->
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        // Category-specific fields configuration
        const categoryFields = <?php echo json_encode($category_fields); ?>;
        
        // Subcategory-specific fields configuration
        const subcategoryFields = {
            '03': { // Desktop Computers
                'monitor_name': {'label': 'Monitor Name', 'type': 'text', 'required': false},
                'monitor_model': {'label': 'Monitor Model', 'type': 'text', 'required': false},
                'monitor_serial_number': {'label': 'Monitor Serial Number', 'type': 'text', 'required': false},
                'monitor_status': {'label': 'Monitor Status', 'type': 'select', 'required': false, 'options': [
                    {'value': 'serviceable', 'text': 'Serviceable'},
                    {'value': 'unserviceable', 'text': 'Unserviceable'},
                    {'value': 'red_tagged', 'text': 'Red Tagged'},
                    {'value': 'no_tag', 'text': 'No Tag'}
                ]},
                'ups_name': {'label': 'UPS Name', 'type': 'text', 'required': false},
                'ups_model': {'label': 'UPS Model', 'type': 'text', 'required': false},
                'ups_serial_number': {'label': 'UPS Serial Number', 'type': 'text', 'required': false},
                'ups_status': {'label': 'UPS Status', 'type': 'select', 'required': false, 'options': [
                    {'value': 'serviceable', 'text': 'Serviceable'},
                    {'value': 'unserviceable', 'text': 'Unserviceable'},
                    {'value': 'red_tagged', 'text': 'Red Tagged'},
                    {'value': 'no_tag', 'text': 'No Tag'}
                ]}
            }
        };
        
        // Function to load category-specific fields
        function loadCategoryFields(categoryCode) {
            const container = document.getElementById('categorySpecificFields');
            
            if (!categoryCode || !categoryFields[categoryCode]) {
                container.innerHTML = '';
                return;
            }
            
            let fieldsHtml = '<div class="category-fields"><h6 class="mb-3"><i class="bi bi-gear"></i> ' + getCategoryName(categoryCode) + ' Specific Fields</h6><div class="row">';
            
            const fields = categoryFields[categoryCode];
            let fieldCount = 0;
            
            for (const [fieldName, fieldConfig] of Object.entries(fields)) {
                const isHalfWidth = ['text', 'number', 'date'].includes(fieldConfig.type);
                const columnClass = isHalfWidth ? 'col-md-6' : 'col-md-12';
                
                fieldsHtml += `
                    <div class="${columnClass}">
                        <div class="mb-3">
                            <label for="${fieldName}" class="form-label">${fieldConfig.label} ${fieldConfig.required ? '<span class="required">*</span>' : ''}</label>
                            <input type="${fieldConfig.type}" class="form-control" id="${fieldName}" name="${fieldName}" ${fieldConfig.required ? 'required' : ''}>
                        </div>
                    </div>
                `;
                
                fieldCount++;
            }
            
            fieldsHtml += '</div></div>';
            container.innerHTML = fieldsHtml;
        }
        
        // Function to load subcategory-specific fields
        function loadSubcategoryFields(subcategoryCode) {
            const container = document.getElementById('subcategorySpecificFields');
            
            // Debug logging
            console.log('Loading subcategory fields for code:', subcategoryCode);
            console.log('Available subcategory fields:', subcategoryFields);
            
            if (!subcategoryCode || !subcategoryFields[subcategoryCode]) {
                console.log('No subcategory code or no fields found for:', subcategoryCode);
                container.innerHTML = '';
                return;
            }
            
            let fieldsHtml = '<div class="category-fields"><h6 class="mb-3"><i class="bi bi-gear"></i> Desktop Computer Specific Fields</h6><div class="row">';
            
            const fields = subcategoryFields[subcategoryCode];
            console.log('Fields to render:', fields);
            
            let fieldCount = 0;
            
            for (const [fieldName, fieldConfig] of Object.entries(fields)) {
                const isHalfWidth = ['text', 'number', 'date'].includes(fieldConfig.type);
                const columnClass = isHalfWidth ? 'col-md-6' : 'col-md-12';
                
                let fieldHtml = '';
                if (fieldConfig.type === 'select') {
                    fieldHtml = `
                    <div class="${columnClass}">
                        <div class="mb-3">
                            <label for="${fieldName}" class="form-label">${fieldConfig.label} ${fieldConfig.required ? '<span class="required">*</span>' : ''}</label>
                            <select class="form-select" id="${fieldName}" name="${fieldName}" ${fieldConfig.required ? 'required' : ''}>
                                <option value="">Select Status</option>
                                ${fieldConfig.options.map(option => `<option value="${option.value}" ${window.existingDesktopData && window.existingDesktopData[fieldName] === option.value ? 'selected' : ''}>${option.text}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    `;
                } else {
                    fieldHtml = `
                    <div class="${columnClass}">
                        <div class="mb-3">
                            <label for="${fieldName}" class="form-label">${fieldConfig.label} ${fieldConfig.required ? '<span class="required">*</span>' : ''}</label>
                            <input type="${fieldConfig.type}" class="form-control" id="${fieldName}" name="${fieldName}" value="${window.existingDesktopData && window.existingDesktopData[fieldName] ? window.existingDesktopData[fieldName] : ''}" ${fieldConfig.required ? 'required' : ''}>
                        </div>
                    </div>
                    `;
                }
                
                fieldsHtml += fieldHtml;
                
                fieldCount++;
            }
            
            fieldsHtml += '</div></div>';
            console.log('Generated HTML:', fieldsHtml);
            container.innerHTML = fieldsHtml;
        }
        
        // Function to get category name from code
        function getCategoryName(categoryCode) {
            const categoryNames = {
                '07': 'Vehicles',
                '030': 'Computer Equipment',
                '02': 'Furniture & Fixtures',
                '04': 'Machinery & Equipment',
                '05': 'Office Equipment',
                '06': 'Software',
                '03': 'Land'
            };
            return categoryNames[categoryCode] || 'Unknown';
        }
        
        // Function to load subcategories dynamically
        function loadSubcategories(categoryId) {
            const subcategorySelect = document.getElementById('subcategory_id');
            
            if (!categoryId || categoryId <= 0) {
                subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
                subcategorySelect.disabled = true;
                // Clear subcategory-specific fields
                loadSubcategoryFields('');
                return;
            }
            
            fetch('../api/get_dropdown_data.php?action=get_subcategories&category_id=' + categoryId)
                .then(response => response.json())
                .then(data => {
                    console.log('Subcategories loaded:', data);
                    if (data.success) {
                        subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
                        data.subcategories.forEach(subcategory => {
                            const option = document.createElement('option');
                            option.value = subcategory.id;
                            option.textContent = subcategory.sub_category_code + ' - ' + subcategory.sub_category_name;
                            option.setAttribute('data-subcategory-code', subcategory.sub_category_code);
                            console.log('Adding subcategory option:', subcategory.sub_category_code, subcategory.sub_category_name);
                            subcategorySelect.appendChild(option);
                        });
                        subcategorySelect.disabled = false;
                        console.log('Subcategories loaded successfully, dropdown enabled');
                    } else {
                        console.error('Error loading subcategories:', data.error);
                        subcategorySelect.innerHTML = '<option value="">Error loading subcategories</option>';
                        subcategorySelect.disabled = true;
                    }
                })
                .catch(error => {
                    console.error('Error loading subcategories:', error);
                    subcategorySelect.innerHTML = '<option value="">Error loading subcategories</option>';
                    subcategorySelect.disabled = true;
                });
        }
        
        // Event listener for category change
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2 for person accountable dropdown
            $('#person_accountable').select2({
                theme: 'bootstrap-5',
                placeholder: 'Search and select employee...',
                allowClear: true,
                width: '100%'
            });
            
            // Initialize Select2 for subcategory dropdown
            $('#subcategory_id').select2({
                theme: 'bootstrap-5',
                placeholder: 'Search and select subcategory...',
                allowClear: true,
                width: '100%'
            });
            
            // Add event listener for subcategory changes using Select2 event
            $('#subcategory_id').on('change', function() {
                console.log('Select2 change event triggered');
                console.log('Selected value:', $(this).val());
                
                const selectedOption = this.options[this.selectedIndex];
                console.log('Native selected option:', selectedOption);
                
                const subcategoryCode = selectedOption ? selectedOption.getAttribute('data-subcategory-code') : '';
                console.log('Extracted subcategory code:', subcategoryCode);
                console.log('All data attributes:', selectedOption ? selectedOption.dataset : 'No option selected');
                
                // Load subcategory-specific fields
                loadSubcategoryFields(subcategoryCode);
            });
            
<<<<<<< HEAD
            // Load fields for current subcategory on page load
            setTimeout(() => {
                const subcategorySelect = document.getElementById('subcategory_id');
                if (subcategorySelect && subcategorySelect.value) {
                    // Get the selected option using Select2's data
                    const selectedOption = $('#subcategory_id option:selected');
                    const subcategoryCode = selectedOption.attr('data-subcategory-code') || '';
                    
                    console.log('Initial subcategory code:', subcategoryCode);
                    loadSubcategoryFields(subcategoryCode);
                }
=======
            // Load fields for current subcategory on page load (with delay to ensure subcategories are loaded)
            setTimeout(() => {
                const subcategorySelect = document.getElementById('subcategory_id');
                const currentSubcategoryOption = subcategorySelect.options[subcategorySelect.selectedIndex];
                const currentSubcategoryCode = currentSubcategoryOption ? currentSubcategoryOption.getAttribute('data-subcategory-code') : '';
                
                console.log('Initial subcategory code:', currentSubcategoryCode);
                loadSubcategoryFields(currentSubcategoryCode);
>>>>>>> 1b538331e37148a87bb4fe58e2b12354b716a520
            }, 500);
            
            // Image preview functionality - append new images to existing preview
            $('#asset_images').on('change', function() {
                const preview = $('#imagePreview');
                
                // Get existing images from hidden field
                const existingImagesData = $('#existingImagesData').val();
                console.log('Raw existing images data from hidden field:', existingImagesData);
                
                const existingImages = existingImagesData && existingImagesData !== '' && existingImagesData !== '[]' 
                    ? JSON.parse(existingImagesData) 
                    : [];
                console.log('Parsed existing images:', existingImages);
                console.log('Number of existing images:', existingImages.length);
                
                // Add new images being uploaded
                const files = this.files;
                console.log('New files selected:', files.length);
                let validFiles = true;
                
                // Validate all files first
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    
                    // Check file size
                    if (file.size > 5 * 1024 * 1024) {
                        preview.append('<div class="alert alert-danger">File size must be less than 5MB</div>');
                        validFiles = false;
                        break;
                    }
                    
                    // Check file type
                    if (!file.type.startsWith('image/')) {
                        preview.append('<div class="alert alert-danger">Only image files are allowed</div>');
                        validFiles = false;
                        break;
                    }
                }
                
                // Check file count limit
                if (files.length > 5) {
                    preview.append('<div class="alert alert-danger">Maximum 5 files allowed</div>');
                    validFiles = false;
                }
                
                if (!validFiles) {
                    this.value = '';
                    return;
                }
                
                // If no files selected, do nothing
                if (files.length === 0) {
                    return;
                }
                
                // Create or ensure preview container exists
                if (preview.find('.preview-container').length === 0) {
                    // If this is the first time, create the container structure
                    if (existingImages.length > 0) {
                        // If there are existing images, add a separator
                        preview.html(`
                            <div class="preview-container">
                                <h6 class="mb-3">All Images</h6>
                                <div class="row existing-images-row">
                                </div>
                                <hr class="my-3">
                                <h6 class="mb-2">New Images Being Added:</h6>
                                <div class="row new-images-row">
                                </div>
                            </div>
                        `);
                        
                        // Add existing images to their row
                        const existingRow = preview.find('.existing-images-row');
                        existingImages.forEach(function(imageName) {
                            const existingImageHtml = `
                                <div class="col-md-3 mb-2">
                                    <div class="card">
                                        <img src="../uploads/asset_images/${imageName}" class="card-img-top" style="height: 150px; object-fit: cover;" alt="Existing Image">
                                        <div class="card-body p-2">
                                            <small class="text-muted d-block text-truncate">${imageName}</small>
                                            <span class="badge bg-success">Existing</span>
                                        </div>
                                    </div>
                                </div>
                            `;
                            existingRow.append(existingImageHtml);
                        });
                    } else {
                        // No existing images, just create container for new images
                        preview.html(`
                            <div class="preview-container">
                                <h6 class="mb-3">New Images Being Added:</h6>
                                <div class="row new-images-row">
                                </div>
                            </div>
                        `);
                    }
                }
                
                // Get the new images row
                const newImagesRow = preview.find('.new-images-row');
                
                // Process and append new images
                let processedCount = 0;
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const newImageHtml = `
                            <div class="col-md-3 mb-2">
                                <div class="card border-primary">
                                    <img src="${e.target.result}" class="card-img-top" style="height: 150px; object-fit: cover;" alt="New Image">
                                    <div class="card-body p-2">
                                        <small class="text-muted d-block text-truncate">${file.name}</small>
                                        <span class="badge bg-primary">New</span>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Append the new image to the new images row
                        newImagesRow.append(newImageHtml);
                        processedCount++;
                        
                        console.log(`Processed ${processedCount} of ${files.length} new images`);
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Function to delete an image
            function deleteImage(imageFilename) {
                if (!confirm('Are you sure you want to delete this image? This action cannot be undone.')) {
                    return;
                }
                
                $.ajax({
                    url: 'delete_asset_image.php',
                    method: 'POST',
                    data: {
                        item_id: <?php echo $item_id; ?>,
                        image_filename: imageFilename
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            const alertDiv = $('<div class="alert alert-success alert-dismissible fade show" role="alert">')
                                .html('<i class="bi bi-check-circle-fill me-2"></i>' + response.message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
                            $('.page-header').after(alertDiv);
                            
                            // Reload the page to update the image display
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            // Show error message
                            const alertDiv = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">')
                                .html('<i class="bi bi-exclamation-triangle-fill me-2"></i>' + response.error + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
                            $('.page-header').after(alertDiv);
                        }
                    },
                    error: function(xhr, status, error) {
                        // Show error message
                        const alertDiv = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">')
                            .html('<i class="bi bi-exclamation-triangle-fill me-2"></i>Error deleting image. Please try again.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
                        $('.page-header').after(alertDiv);
                    }
                });
            }
            
            // Category change functionality
            const categorySelect = document.getElementById('category_id');
            
            // Load fields for current category on page load
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            const categoryCode = selectedOption.getAttribute('data-category-code');
            loadCategoryFields(categoryCode);
            
            // Load subcategories for current category on page load
            const selectedCategoryId = categorySelect.value;
            if (selectedCategoryId) {
                loadSubcategories(selectedCategoryId);
            }
            
            // Event listener for category change
            categorySelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const categoryCode = selectedOption.getAttribute('data-category-code');
                const categoryId = this.value;
                
                console.log('Category changed to:', categoryCode, 'ID:', categoryId);
                
                // Load category-specific fields
                loadCategoryFields(categoryCode);
                
                // Load subcategories dynamically
                loadSubcategories(categoryId);
            });
            
            // Auto-fill property number if empty
            const propertyNoField = document.getElementById('property_no');
            if (propertyNoField && !propertyNoField.value.trim()) {
                // Add increment field for property number generation
                const incrementField = document.createElement('input');
                incrementField.type = 'hidden';
                incrementField.name = 'increment_property_counter';
                incrementField.value = '1';
                document.querySelector('form').appendChild(incrementField);
                
                // Generate property number via AJAX
                fetch('../includes/system_functions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=generateNextTag&tag_type=property_no'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.tag_number) {
                        propertyNoField.value = data.tag_number;
                        propertyNoField.readOnly = true;
                        propertyNoField.classList.add('bg-light');
                        
                        // Add info text
                        const infoDiv = document.createElement('small');
                        infoDiv.className = 'form-text text-muted';
                        infoDiv.textContent = 'Property number auto-generated';
                        propertyNoField.parentNode.appendChild(infoDiv);
                    }
                })
                .catch(error => {
                    console.error('Error generating property number:', error);
                });
            }
        });
    </script>
</body>
</html>
