<?php
session_start();
require_once '../config.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

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

// Get active employees for dropdown
$employees = [];
$employees_sql = "SELECT id, employee_no, firstname, lastname FROM employees WHERE employment_status IN ('permanent', 'contractual', 'job_order') ORDER BY lastname, firstname";
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
    if (!$components) return '';
    
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
    'VH' => [
        'brand' => ['label' => 'Brand', 'type' => 'text', 'required' => true],
        'model' => ['label' => 'Model', 'type' => 'text', 'required' => true],
        'plate_number' => ['label' => 'Plate Number', 'type' => 'text', 'required' => true],
        'color' => ['label' => 'Color', 'type' => 'text', 'required' => false],
        'engine_number' => ['label' => 'Engine Number', 'type' => 'text', 'required' => false],
        'chassis_number' => ['label' => 'Chassis Number', 'type' => 'text', 'required' => false],
        'year_model' => ['label' => 'Year Model', 'type' => 'number', 'required' => false]
    ],
    'ITS' => [
        'processor' => ['label' => 'Processor', 'type' => 'text', 'required' => true],
        'ram' => ['label' => 'RAM (GB)', 'type' => 'text', 'required' => true],
        'storage' => ['label' => 'Storage', 'type' => 'text', 'required' => true],
        'operating_system' => ['label' => 'Operating System', 'type' => 'text', 'required' => false],
        'serial_number' => ['label' => 'Serial Number', 'type' => 'text', 'required' => false]
    ],
    'FF' => [
        'material' => ['label' => 'Material', 'type' => 'text', 'required' => true],
        'dimensions' => ['label' => 'Dimensions (LxWxH)', 'type' => 'text', 'required' => false],
        'color' => ['label' => 'Color', 'type' => 'text', 'required' => false],
        'manufacturer' => ['label' => 'Manufacturer', 'type' => 'text', 'required' => false]
    ],
    'ME' => [
        'manufacturer' => ['label' => 'Manufacturer', 'type' => 'text', 'required' => true],
        'model' => ['label' => 'Model', 'type' => 'text', 'required' => true],
        'capacity' => ['label' => 'Capacity', 'type' => 'text', 'required' => false],
        'power_rating' => ['label' => 'Power Rating', 'type' => 'text', 'required' => false],
        'serial_number' => ['label' => 'Serial Number', 'type' => 'text', 'required' => false]
    ],
    'OE' => [
        'brand' => ['label' => 'Brand', 'type' => 'text', 'required' => true],
        'model' => ['label' => 'Model', 'type' => 'text', 'required' => false],
        'serial_number' => ['label' => 'Serial Number', 'type' => 'text', 'required' => false]
    ],
    'SW' => [
        'software_name' => ['label' => 'Software Name', 'type' => 'text', 'required' => true],
        'version' => ['label' => 'Version', 'type' => 'text', 'required' => true],
        'license_key' => ['label' => 'License Key', 'type' => 'text', 'required' => false],
        'expiry_date' => ['label' => 'Expiry Date', 'type' => 'date', 'required' => false]
    ],
    'LD' => [
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
        
        .form-container {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .asset-info-card {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
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
            box-shadow: 0 5px 15px rgba(25, 27, 169, 0.3);
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
            border-color: #191BA9;
            box-shadow: 0 0 0 0.2rem rgba(25, 27, 169, 0.25);
        }
        
        .required {
            color: #dc3545;
        }
    </style>
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
                    <a href="asset_items.php?asset_id=<?php echo $item['asset_id']; ?>" class="btn btn-back">
                        <i class="bi bi-arrow-left"></i> Back to Items
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Asset Information Card -->
        <div class="asset-info-card">
            <h5 class="mb-3"><i class="bi bi-info-circle"></i> Asset Information</h5>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Asset Description:</strong> <?php echo htmlspecialchars($item['asset_description']); ?></p>
                    <p><strong>Value:</strong> ₱<?php echo number_format($item['value'], 2); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Acquisition Date:</strong> <?php echo date('F j, Y', strtotime($item['acquisition_date'])); ?></p>
                    <p><strong>Office:</strong> <?php echo $item['office_name'] ? htmlspecialchars($item['office_name']) : 'Not assigned'; ?></p>
                    <p><strong>ICS/PAR No:</strong> 
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
                    </p>
                </div>
            </div>
        </div>

        <!-- Tag Creation Form -->
        <div class="form-container">
            <form method="POST" action="process_tag.php" id="tagForm" enctype="multipart/form-data">
                <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                <input type="hidden" name="tag_format_id" value="<?php echo $tag_format['id'] ?? ''; ?>">
                <input type="hidden" name="current_number" value="<?php echo $tag_format['current_number'] ?? ''; ?>">
                
                <div class="row">
                    <div class="col-md-6">
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
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="property_no" class="form-label">Property Number <span class="required">*</span></label>
                            <input type="text" class="form-control" id="property_no" name="property_no" value="<?php echo htmlspecialchars($item['property_no'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="inventory_tag" class="form-label">Inventory Tag Number <span class="required">*</span></label>
                            <input type="text" class="form-control" id="inventory_tag" name="inventory_tag" value="<?php echo htmlspecialchars($generated_inventory_tag); ?>" readonly required>
                            <small class="form-text text-muted">Auto-generated from tag format</small>
                        </div>
                    </div>
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
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="end_user" class="form-label">End User <span class="required">*</span></label>
                            <input type="text" class="form-control" id="end_user" name="end_user" placeholder="Enter end user name" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="date_counted" class="form-label">Date Counted <span class="required">*</span></label>
                            <input type="date" class="form-control" id="date_counted" name="date_counted" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="asset_image" class="form-label">Asset Image</label>
                            <input type="file" class="form-control" id="asset_image" name="asset_image" accept="image/*">
                            <small class="form-text text-muted">Upload a clear image of the asset (JPG, PNG, GIF - Max 5MB)</small>
                            <div id="imagePreview" class="mt-2"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Category-specific fields will be loaded here -->
                <div id="categorySpecificFields"></div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="asset_items.php?asset_id=<?php echo $item['asset_id']; ?>" class="btn btn-secondary">
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
        
        // Function to get category name from code
        function getCategoryName(categoryCode) {
            const categoryNames = {
                'VH': 'Vehicles',
                'ITS': 'Computer Equipment',
                'FF': 'Furniture & Fixtures',
                'ME': 'Machinery & Equipment',
                'OE': 'Office Equipment',
                'SW': 'Software',
                'LD': 'Land'
            };
            return categoryNames[categoryCode] || 'Unknown';
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
            
            // Image preview functionality
            $('#asset_image').on('change', function() {
                const file = this.files[0];
                const preview = $('#imagePreview');
                
                if (file) {
                    // Check file size (5MB limit)
                    if (file.size > 5 * 1024 * 1024) {
                        preview.html('<div class="alert alert-danger">File size must be less than 5MB</div>');
                        this.value = '';
                        return;
                    }
                    
                    // Check file type
                    if (!file.type.startsWith('image/')) {
                        preview.html('<div class="alert alert-danger">Please select an image file</div>');
                        this.value = '';
                        return;
                    }
                    
                    // Show image preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.html(`
                            <div class="border rounded p-2">
                                <img src="${e.target.result}" class="img-fluid" style="max-height: 200px;" alt="Asset image preview">
                                <div class="mt-2">
                                    <small class="text-muted">${file.name} (${(file.size / 1024).toFixed(2)} KB)</small>
                                </div>
                            </div>
                        `);
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.html('');
                }
            });
            
            const categorySelect = document.getElementById('category_id');
            
            // Load fields for current category on page load
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            const categoryCode = selectedOption.getAttribute('data-category-code');
            loadCategoryFields(categoryCode);
            
            // Load fields when category changes
            categorySelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const categoryCode = selectedOption.getAttribute('data-category-code');
                loadCategoryFields(categoryCode);
            });
        });
    </script>
</body>
</html>
