<?php
session_start();
require_once 'config.php';
require_once 'includes/system_functions.php';
require_once 'includes/logger.php';

// Check if user is logged in and is system admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

if ($_SESSION['role'] !== 'system_admin') {
    header('Location: index.php');
    exit();
}

$message = '';
$error = '';

// Handle setup request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Read and execute LGU compliance tables SQL
        $sql_file = 'database/lgu_compliance_tables.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            
            // Split SQL into individual statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            $success_count = 0;
            $error_count = 0;
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    if ($conn->query($statement)) {
                        $success_count++;
                    } else {
                        $error_count++;
                        error_log("SQL Error: " . $conn->error);
                    }
                }
            }
            
            if ($error_count === 0) {
                $message = "LGU Compliance tables created successfully! ($success_count statements executed)";
                
                // Log setup completion
                logSystemAction($_SESSION['user_id'], 'setup', 'lgu_compliance', 'LGU compliance tables created');
                
                // Redirect to office admin dashboard
                header('refresh: 3; url=OFFICE_ADMIN/dashboard.php');
            } else {
                $error = "Some SQL statements failed. Success: $success_count, Errors: $error_count";
            }
        } else {
            $error = "LGU compliance SQL file not found: $sql_file";
        }
    } catch (Exception $e) {
        $error = "Error setting up LGU compliance: " . $e->getMessage();
        error_log("LGU Compliance Setup Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup LGU Compliance - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/index.css" rel="stylesheet">
    <link href="assets/css/theme-custom.css" rel="stylesheet">
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
            border-radius: var(--border-radius-xl);
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
            border-left: 5px solid #191BA9;
        }
        
        .setup-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .setup-header i {
            font-size: 4rem;
            color: #191BA9;
            margin-bottom: 1rem;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 2rem 0;
        }
        
        .feature-list li {
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-list i {
            color: #28a745;
            margin-right: 1rem;
            font-size: 1.2rem;
        }
        
        .btn-setup {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: var(--border-radius-lg);
            font-weight: 600;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(25, 27, 169, 0.3);
        }
        
        .btn-setup:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(25, 27, 169, 0.4);
            color: white;
        }
        
        .alert-setup {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: 1px solid #c3e6cb;
            color: #155724;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <i class="bi bi-shield-check"></i>
            <h1>LGU Compliance Setup</h1>
            <p class="text-muted">Configure COA & GPPB Compliance Features</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-setup">
                <i class="bi bi-check-circle"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-setup alert-error">
                <i class="bi bi-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="setup-content">
            <h5 class="mb-3">Features to be Installed:</h5>
            <ul class="feature-list">
                <li><i class="bi bi-file-earmark-text"></i> Document Reference Numbers (RIS, PO, PAR, ICS, JEV, DV, OR)</li>
                <li><i class="bi bi-clock-history"></i> Comprehensive Audit Trail System</li>
                <li><i class="bi bi-calendar-check"></i> Report Scheduling System</li>
                <li><i class="bi bi-person-check"></i> Authorized Signatory Management</li>
                <li><i class="bi bi-shield-exclamation"></i> Data Integrity Monitoring</li>
                <li><i class="bi bi-calendar-range"></i> Fiscal Year Configuration (January-December)</li>
                <li><i class="bi bi-file-earmark-code"></i> Custom Report Templates</li>
                <li><i class="bi bi-graph-up"></i> Report Generation History</li>
            </ul>
            
            <form method="POST">
                <div class="text-center">
                    <button type="submit" class="btn btn-setup">
                        <i class="bi bi-gear"></i> Install LGU Compliance Features
                    </button>
                </div>
            </form>
        </div>
        
        <div class="text-center mt-4">
            <a href="index.php" class="text-muted">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
