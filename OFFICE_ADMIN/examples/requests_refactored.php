<?php
/**
 * REFACTORED: requests.php
 * 
 * Example of optimized OFFICE_ADMIN page using new bootstrap system
 * 
 * BEFORE (4 separate requires):
 * require_once '../config.php';
 * require_once '../includes/system_functions.php';
 * require_once '../includes/logger.php';
 * require_once 'includes/notification_functions.php';
 * 
 * AFTER (1 optimized include):
 * $admin = OfficeAdminInit::getInstance();
 */

// Method 1: Object-Oriented Approach (Recommended)
$admin = OfficeAdminInit::getInstance();

// OR Method 2: Functional Approach (Simpler)
// $admin_data = require_once 'includes/bootstrap.php';

// Now you have access to everything:
$conn = $admin->getConnection();
$office_id = $admin->getOfficeId();
$user_id = $admin->getUserId();

// CSRF protection
$csrf_token = $admin->generateCSRF();

// Log page access
$admin->logActivity('access_requests_page', ['office_id' => $office_id]);

// Rest of your page logic remains the same...
// Example: Get available assets
$assets_query = "SELECT ai.id, ai.description, ai.property_no as property_number, 
                       ac.category_name, o.office_name, o.id as office_id,
                       1 as total_quantity, 1 as available, ac.id as category_id
                FROM asset_items ai
                LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id
                LEFT JOIN assets a ON ai.asset_id = a.id
                JOIN offices o ON ai.office_id = o.id
                WHERE ai.office_id != ? AND ai.status = 'serviceable'
                ORDER BY o.office_name, ai.description";

$stmt = $conn->prepare($assets_query);
$stmt->bind_param("i", $office_id);
$stmt->execute();
$available_assets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Page content...
?>
<!DOCTYPE html>
<html>
<head>
    <title>Asset Requests - Refactored</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="container">
        <h1>Asset Requests (Optimized Version)</h1>
        <p>Office ID: <?= htmlspecialchars($office_id) ?></p>
        <p>User ID: <?= htmlspecialchars($user_id) ?></p>
        <p>Available Assets: <?= count($available_assets) ?></p>
        
        <!-- Your existing page content goes here -->
        <!-- All the modals, forms, JavaScript, etc. remain unchanged -->
    </div>
</body>
</html>
