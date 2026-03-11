<?php
/**
 * Notification Batching System Setup Script
 * This script sets up the database tables and initial configuration for the notification batching system
 */

// Include required files
require_once 'config.php';
require_once 'includes/logger.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is system admin
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'system_admin') {
    die("Access denied. System administrator privileges required.");
}

$message = '';
$message_type = '';

if ($_POST['action'] === 'setup') {
    try {
        // Read and execute the SQL setup file
        $sql_file = 'database/notification_batching_tables.sql';
        
        if (!file_exists($sql_file)) {
            throw new Exception("SQL setup file not found: {$sql_file}");
        }
        
        $sql_content = file_get_contents($sql_file);
        
        // Split SQL content into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql_content)));
        
        $executed_statements = 0;
        $errors = [];
        
        foreach ($statements as $statement) {
            if (empty($statement) || preg_match('/^--/', $statement)) {
                continue;
            }
            
            try {
                if ($conn->query($statement)) {
                    $executed_statements++;
                } else {
                    $errors[] = "Error executing statement: " . $conn->error;
                }
            } catch (Exception $e) {
                // Check if it's a "table already exists" error (which is okay)
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    // Table already exists, skip
                    continue;
                } else {
                    $errors[] = "Exception: " . $e->getMessage();
                }
            }
        }
        
        if (empty($errors)) {
            $message = "Notification batching system setup completed successfully! Executed {$executed_statements} SQL statements.";
            $message_type = 'success';
            
            // Log the setup
            logSystemAction($_SESSION['user_id'], 'system_setup', 'notification_batching', 'Notification batching system setup completed');
        } else {
            $message = "Setup completed with " . count($errors) . " errors. Executed {$executed_statements} statements successfully.";
            $message_type = 'warning';
            
            // Log the errors
            foreach ($errors as $error) {
                error_log("Notification batching setup error: " . $error);
            }
        }
        
    } catch (Exception $e) {
        $message = "Setup failed: " . $e->getMessage();
        $message_type = 'danger';
        error_log("Notification batching setup failed: " . $e->getMessage());
    }
}

// Check if batching system is already set up
function checkSetupStatus($conn) {
    $tables = [
        'notification_batches',
        'notification_queue',
        'notification_batch_rules',
        'notification_batch_logs',
        'notification_batch_metrics'
    ];
    
    $existing_tables = 0;
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '{$table}'");
        if ($result->num_rows > 0) {
            $existing_tables++;
        }
    }
    
    return [
        'total_tables' => count($tables),
        'existing_tables' => $existing_tables,
        'is_complete' => $existing_tables === count($tables)
    ];
}

$setup_status = checkSetupStatus($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Batching Setup - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .setup-container {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            max-width: 800px;
            width: 100%;
            margin: 2rem;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }
        
        .status-complete { background: #28a745; }
        .status-partial { background: #ffc107; }
        .status-missing { background: #dc3545; }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-list li i {
            color: #28a745;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="text-center mb-4">
            <h1 class="mb-3">
                <i class="bi bi-stack" style="color: var(--primary-color);"></i>
                Notification Batching Setup
            </h1>
            <p class="text-muted">Set up the notification batching system for PIMS Office Admin</p>
        </div>
        
        <!-- Message Alert -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Setup Status -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle"></i> Setup Status
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <span class="status-indicator status-<?php 
                        echo $setup_status['is_complete'] ? 'complete' : 
                             ($setup_status['existing_tables'] > 0 ? 'partial' : 'missing'); 
                    ?>"></span>
                    <strong>
                        <?php 
                        if ($setup_status['is_complete']) {
                            echo "Setup Complete";
                        } elseif ($setup_status['existing_tables'] > 0) {
                            echo "Partial Setup";
                        } else {
                            echo "Not Set Up";
                        }
                        ?>
                    </strong>
                </div>
                <p class="text-muted mb-0">
                    <?php echo $setup_status['existing_tables']; ?> of <?php echo $setup_status['total_tables']; ?> database tables are installed.
                </p>
            </div>
        </div>
        
        <!-- Features Overview -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-star"></i> Features
                </h5>
            </div>
            <div class="card-body">
                <ul class="feature-list">
                    <li><i class="bi bi-check-circle-fill"></i> Intelligent notification batching by type and priority</li>
                    <li><i class="bi bi-check-circle-fill"></i> Scheduled and immediate batch processing</li>
                    <li><i class="bi bi-check-circle-fill"></i> Configurable batch rules and thresholds</li>
                    <li><i class="bi bi-check-circle-fill"></i> Comprehensive logging and monitoring</li>
                    <li><i class="bi bi-check-circle-fill"></i> Performance metrics and analytics</li>
                    <li><i class="bi bi-check-circle-fill"></i> Automatic retry for failed batches</li>
                    <li><i class="bi bi-check-circle-fill"></i> Admin interface for batch management</li>
                    <li><i class="bi bi-check-circle-fill"></i> Cron job integration for automated processing</li>
                </ul>
            </div>
        </div>
        
        <!-- Setup Form -->
        <?php if (!$setup_status['is_complete']): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-gear"></i> Setup Actions
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="setup">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0">Click the button below to set up the notification batching system. This will create the necessary database tables and insert default configuration.</p>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-play-circle"></i> Setup Batching System
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Next Steps -->
        <?php if ($setup_status['is_complete']): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-arrow-right-circle"></i> Next Steps
                    </h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li class="mb-2">
                            <strong>Configure Cron Job:</strong><br>
                            Set up a cron job to run the batch processor every 5 minutes:<br>
                            <code class="bg-light p-2 d-block mt-1">*/5 * * * * php /path/to/pims/cron_notification_batch_processor.php</code>
                        </li>
                        <li class="mb-2">
                            <strong>Access Admin Interface:</strong><br>
                            Navigate to <code>OFFICE_ADMIN/notification_batch_management.php</code> to manage batches and rules.
                        </li>
                        <li class="mb-2">
                            <strong>Configure Rules:</strong><br>
                            Adjust batch processing rules based on your office's notification volume and requirements.
                        </li>
                        <li class="mb-2">
                            <strong>Monitor Performance:</strong><br>
                            Check the metrics dashboard to monitor batch processing performance and identify any issues.
                        </li>
                    </ol>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Technical Details -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-code-slash"></i> Technical Details
                </h5>
            </div>
            <div class="card-body">
                <h6>Database Tables Created:</h6>
                <ul class="list-unstyled">
                    <li><code>notification_batches</code> - Batch information and status</li>
                    <li><code>notification_queue</code> - Individual notification queue items</li>
                    <li><code>notification_batch_rules</code> - Processing rules and configuration</li>
                    <li><code>notification_batch_logs</code> - System logs and debugging info</li>
                    <li><code>notification_batch_metrics</code> - Performance metrics and analytics</li>
                </ul>
                
                <h6 class="mt-3">Key Classes:</h6>
                <ul class="list-unstyled">
                    <li><code>NotificationBatcher</code> - Core batching functionality</li>
                    <li><code>NotificationBatchProcessors</code> - Type-specific processors</li>
                    <li><code>NotificationBatchScheduler</code> - Scheduling and automation</li>
                </ul>
                
                <h6 class="mt-3">Integration Points:</h6>
                <ul class="list-unstyled">
                    <li>Updated <code>notification_functions.php</code> with batching support</li>
                    <li>Admin interface at <code>notification_batch_management.php</code></li>
                    <li>Cron job script: <code>cron_notification_batch_processor.php</code></li>
                </ul>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="OFFICE_ADMIN/notification_batch_management.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-right"></i> Go to Batch Management
            </a>
            <a href="index.php" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-house"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
