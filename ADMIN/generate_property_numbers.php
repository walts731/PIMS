<?php
require_once '../config.php';
require_once '../includes/logger.php';

// Check if user is logged in and has admin rights
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    try {
        $conn->begin_transaction();
        
        // Get current year
        $current_year = date('Y');
        
        // Get all serviceable assets without property numbers
        $stmt = $conn->prepare("SELECT ai.id, ai.asset_id, a.description, ai.description as item_description 
                                FROM asset_items ai 
                                JOIN assets a ON ai.asset_id = a.id 
                                WHERE ai.status = 'serviceable' AND (ai.property_number IS NULL OR ai.property_number = '')");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $updated_count = 0;
        
        while ($row = $result->fetch_assoc()) {
            // Get next series number
            $series_stmt = $conn->prepare("SELECT MAX(CAST(RIGHT(SUBSTRING_INDEX(SUBSTRING_INDEX(property_no, '-', -2), '-', 1), 2) AS UNSIGNED)) as max_series 
                                          FROM asset_items 
                                          WHERE property_no LIKE ?");
            $pattern = $current_year . '-%';
            $series_stmt->bind_param('s', $pattern);
            $series_stmt->execute();
            $series_result = $series_stmt->get_result();
            $max_series = 0;
            
            if ($series_row = $series_result->fetch_assoc()) {
                $max_series = $series_row['max_series'] ?? 0;
            }
            
            $next_series = str_pad($max_series + 1, 2, '0', STR_PAD_LEFT);
            
            // Generate property number format: YYYY-PROP-GEN-001-01
            $property_number = $current_year . '-PROP-GEN-001-' . $next_series;
            
            // Update the asset item
            $update_stmt = $conn->prepare("UPDATE asset_items SET property_number = ?, property_no = ? WHERE id = ?");
            $update_stmt->bind_param("ssi", $property_number, $property_number, $row['id']);
            $update_stmt->execute();
            $update_stmt->close();
            
            $updated_count++;
        }
        
        $stmt->close();
        $conn->commit();
        
        $message = "Successfully generated property numbers for {$updated_count} serviceable assets.";
        logSystemAction($_SESSION['user_id'], 'generate_property_numbers', 'asset_management', "Generated {$updated_count} property numbers");
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error generating property numbers: " . $e->getMessage();
        logSystemAction($_SESSION['user_id'], 'generate_property_numbers_failed', 'asset_management', "Error: " . $e->getMessage());
    }
}

// Get current statistics
$serviceable_without_pn = 0;
$serviceable_with_pn = 0;

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM asset_items WHERE status = 'serviceable' AND (property_number IS NULL OR property_number = '')");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $serviceable_without_pn = $row['count'];
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM asset_items WHERE status = 'serviceable' AND property_number IS NOT NULL AND property_number != ''");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $serviceable_with_pn = $row['count'];
    $stmt->close();
    
} catch (Exception $e) {
    $error = "Error getting statistics: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Property Numbers - PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
<?php require_once 'includes/dark-mode-init.php'; ?>
</head>
<body>
    <?php 
    $page_title = 'Generate Property Numbers';
    ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
        <div class="main-content">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="bi bi-hash"></i> Generate Property Numbers
                        </h1>
                        <p class="text-muted mb-0">Generate property numbers for serviceable assets that don't have them</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="borrowing.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Borrowing
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Property Number Generation</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title text-primary"><?php echo $serviceable_without_pn; ?></h5>
                                    <p class="card-text">Serviceable Assets Without Property Numbers</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title text-success"><?php echo $serviceable_with_pn; ?></h5>
                                    <p class="card-text">Serviceable Assets With Property Numbers</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($serviceable_without_pn > 0): ?>
                        <form method="POST">
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Notice:</strong> This will generate property numbers for <?php echo $serviceable_without_pn; ?> serviceable assets that currently don't have property numbers. This action cannot be undone.
                            </div>
                            
                            <button type="submit" name="generate" class="btn btn-primary">
                                <i class="bi bi-hash"></i> Generate Property Numbers
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i>
                            <strong>All serviceable assets already have property numbers!</strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php require_once 'includes/logout-modal.php'; ?>
        <?php require_once 'includes/change-password-modal.php'; ?>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <?php include 'includes/sidebar-scripts.php'; ?>
    </div>
</body>
</html>
