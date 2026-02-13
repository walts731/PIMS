<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';

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

// Get filter parameters
$selected_category = $_GET['category'] ?? '';
$selected_office = $_GET['office'] ?? '';

// Get asset items with PAR ID and filters
$asset_items = [];
if ($conn && !$conn->connect_error) {
    $query = "SELECT 
                ai.id,
                ai.created_at,
                ai.property_no,
                ai.description,
                ai.value,
                ai.par_id,
                ai.employee_id,
                ai.office_id,
                COALESCE(ac.category_code, 'UNCAT') as asset_category,
                COALESCE(o1.office_name, o2.office_name, 'Unassigned') as office_name,
                COALESCE(o1.office_code, o2.office_code, 'NONE') as office_code
              FROM asset_items ai
              LEFT JOIN asset_categories ac ON ai.category_id = ac.id
              LEFT JOIN offices o1 ON ai.office_id = o1.id
              LEFT JOIN employees e ON ai.employee_id = e.id
              LEFT JOIN offices o2 ON e.office_id = o2.id
              WHERE ai.par_id IS NOT NULL AND ai.par_id != ''";
    
    // Add category filter
    if (!empty($selected_category)) {
        $query .= " AND ac.category_code = '" . $conn->real_escape_string($selected_category) . "'";
    }
    
    // Add office filter
    if (!empty($selected_office)) {
        $query .= " AND (o1.office_code = '" . $conn->real_escape_string($selected_office) . "' OR o2.office_code = '" . $conn->real_escape_string($selected_office) . "')";
    }
    
    $query .= " ORDER BY ai.created_at ASC";
    
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Add employee and PAR info separately
            $row['employee_name'] = '';
            $row['employee_no'] = '';
            $row['par_no'] = '';
            
            // Get employee info
            if (!empty($row['employee_id'])) {
                $emp_query = "SELECT CONCAT(firstname, ' ', lastname) as name, employee_no FROM employees WHERE id = " . intval($row['employee_id']);
                $emp_result = $conn->query($emp_query);
                if ($emp_result && $emp_data = $emp_result->fetch_assoc()) {
                    $row['employee_name'] = $emp_data['name'];
                    $row['employee_no'] = $emp_data['employee_no'];
                }
            }
            
            // Get PAR info
            if (!empty($row['par_id'])) {
                $par_query = "SELECT par_no, received_by_name FROM par_forms WHERE id = " . intval($row['par_id']);
                $par_result = $conn->query($par_query);
                if ($par_result && $par_data = $par_result->fetch_assoc()) {
                    $row['par_no'] = $par_data['par_no'];
                    $row['received_by'] = $par_data['received_by_name'];
                }
            }
            
            $asset_items[] = $row;
        }
    }
}

// Calculate summary statistics
$total_items = count($asset_items);
$total_value = array_sum(array_column($asset_items, 'value'));
$total_par_forms = count(array_unique(array_column($asset_items, 'par_id')));

// Group by category
$category_summary = [];
foreach ($asset_items as $item) {
    $category = $item['asset_category'];
    if (!isset($category_summary[$category])) {
        $category_summary[$category] = [
            'count' => 0,
            'value' => 0
        ];
    }
    $category_summary[$category]['count']++;
    $category_summary[$category]['value'] += $item['value'];
}

// Group by office
$office_summary = [];
foreach ($asset_items as $item) {
    $office = $item['office_name'];
    if (!isset($office_summary[$office])) {
        $office_summary[$office] = [
            'count' => 0,
            'value' => 0
        ];
    }
    $office_summary[$office]['count']++;
    $office_summary[$office]['value'] += $item['value'];
}
?>

<div class="summary-header">
    <h2>Property Card Summary</h2>
    <p class="mb-0">Generated on <?php echo date('F d, Y h:i A'); ?></p>
    <?php if ($selected_category || $selected_office): ?>
        <p class="mb-0">
            <small>
                Filters: 
                <?php if ($selected_category): ?>Category: <?php echo htmlspecialchars($selected_category); ?><?php endif; ?>
                <?php if ($selected_category && $selected_office): ?> | <?php endif; ?>
                <?php if ($selected_office): ?>Office: <?php echo htmlspecialchars($selected_office); ?><?php endif; ?>
            </small>
        </p>
    <?php endif; ?>
</div>

<div class="summary-stats">
    <div class="stat-card">
        <h3><?php echo $total_items; ?></h3>
        <p class="mb-0">Total Items</p>
    </div>
    <div class="stat-card">
        <h3>₱<?php echo number_format($total_value, 2); ?></h3>
        <p class="mb-0">Total Value</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $total_par_forms; ?></h3>
        <p class="mb-0">PAR Forms</p>
    </div>
</div>

<?php if (!empty($category_summary)): ?>
<h4>Summary by Category</h4>
<table class="table table-striped">
    <thead>
        <tr>
            <th>Category</th>
            <th>Items</th>
            <th>Total Value</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($category_summary as $category => $data): ?>
            <tr>
                <td><?php echo htmlspecialchars($category); ?></td>
                <td><?php echo $data['count']; ?></td>
                <td>₱<?php echo number_format($data['value'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php if (!empty($office_summary)): ?>
<h4>Summary by Office</h4>
<table class="table table-striped">
    <thead>
        <tr>
            <th>Office</th>
            <th>Items</th>
            <th>Total Value</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($office_summary as $office => $data): ?>
            <tr>
                <td><?php echo htmlspecialchars($office); ?></td>
                <td><?php echo $data['count']; ?></td>
                <td>₱<?php echo number_format($data['value'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
