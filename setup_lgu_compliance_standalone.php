<?php
// Standalone LGU Compliance Setup - No authentication required
// Use this for initial installation

$message = '';
$error = '';

// Check if config file exists
if (!file_exists('config.php')) {
    $error = "config.php not found. Please ensure PIMS is properly installed.";
} else {
    require_once 'config.php';
    
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
                $errors_list = [];
                
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        if ($conn->query($statement)) {
                            $success_count++;
                        } else {
                            $error_count++;
                            $errors_list[] = $conn->error;
                            error_log("SQL Error: " . $conn->error);
                        }
                    }
                }
                
                if ($error_count === 0) {
                    $message = "LGU Compliance tables created successfully! ($success_count statements executed)<br><br>
                               <strong>Next Steps:</strong><br>
                               1. Log in to PIMS as System Admin<br>
                               2. Access OFFICE_ADMIN/office_reports.php<br>
                               3. Configure signatories and document references";
                    
                    // Create basic fiscal year settings if not exists
                    $fiscal_setup = "INSERT IGNORE INTO fiscal_year_settings 
                                    (office_id, fiscal_year, start_date, end_date, created_by) 
                                    SELECT o.id, YEAR(CURRENT_DATE), CONCAT(YEAR(CURRENT_DATE), '-01-01'), 
                                           CONCAT(YEAR(CURRENT_DATE), '-12-31'), 1
                                    FROM offices o WHERE o.id IS NOT NULL";
                    $conn->query($fiscal_setup);
                    
                } else {
                    $error = "Some SQL statements failed. Success: $success_count, Errors: $error_count<br>
                             <strong>Errors:</strong><br>" . implode("<br>", array_unique($errors_list));
                }
            } else {
                $error = "LGU compliance SQL file not found: $sql_file";
            }
        } catch (Exception $e) {
            $error = "Error setting up LGU compliance: " . $e->getMessage();
            error_log("LGU Compliance Setup Error: " . $e->getMessage());
        }
    }
    
    // Check if tables already exist
    $tables_check = [];
    $required_tables = [
        'document_references', 'report_audit_trail', 'report_schedules',
        'signatory_authorities', 'data_integrity_checks', 'fiscal_year_settings',
        'report_templates', 'report_generation_history'
    ];
    
    foreach ($required_tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        $tables_check[$table] = $result->num_rows > 0;
    }
    
    $all_tables_exist = array_sum($tables_check) === count($required_tables);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LGU Compliance Setup - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
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
            padding: 20px;
        }
        
        .setup-container {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 700px;
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
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
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
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .table-status {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-exists {
            background: #d4edda;
            color: #155724;
        }
        
        .status-missing {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <i class="bi bi-shield-check"></i>
            <h1>LGU Compliance Setup</h1>
            <p class="text-muted">COA & GPPB Compliance Features for PIMS</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-setup">
                <i class="bi bi-check-circle"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-setup alert-error">
                <i class="bi bi-exclamation-triangle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($all_tables_exist) && $all_tables_exist): ?>
            <div class="alert alert-setup">
                <i class="bi bi-check-circle"></i>
                <strong>LGU Compliance is already installed!</strong><br>
                All required tables exist in the database.
            </div>
        <?php endif; ?>
        
        <?php if (isset($tables_check)): ?>
            <div class="table-status">
                <h6 class="mb-3">Database Tables Status:</h6>
                <div class="row">
                    <?php foreach ($tables_check as $table => $exists): ?>
                        <div class="col-md-6 mb-2">
                            <span class="status-badge <?php echo $exists ? 'status-exists' : 'status-missing'; ?>">
                                <i class="bi bi-<?php echo $exists ? 'check' : 'x'; ?>"></i>
                                <?php echo $table; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
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
            
            <?php if (!isset($all_tables_exist) || !$all_tables_exist): ?>
                <form method="POST">
                    <div class="text-center">
                        <button type="submit" class="btn btn-setup">
                            <i class="bi bi-gear"></i> Install LGU Compliance Features
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="index.php" class="text-muted me-3">
                <i class="bi bi-arrow-left"></i> Back to PIMS
            </a>
            <?php if (isset($all_tables_exist) && $all_tables_exist): ?>
                <a href="OFFICE_ADMIN/office_reports.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-graph-up"></i> Go to Office Reports
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
