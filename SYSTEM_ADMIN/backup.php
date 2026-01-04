<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role'] !== 'system_admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../config.php';

// Function to log system actions (if not already defined)
if (!function_exists('logSystemAction')) {
    function logSystemAction($user_id, $action, $module, $details = null) {
        global $conn;
        
        try {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            $stmt = $conn->prepare("
                INSERT INTO system_logs (user_id, action, module, details, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isssss", $user_id, $action, $module, $details, $ip_address, $user_agent);
            $stmt->execute();
            $stmt->close();
            
            return true;
        } catch (Exception $e) {
            error_log("Failed to log system action: " . $e->getMessage());
            return false;
        }
    }
}

// PHP-based database backup function
if (!function_exists('backupDatabasePHP')) {
    function backupDatabasePHP($filename, $conn, $database) {
        try {
            $tables = array();
            $result = $conn->query("SHOW TABLES");
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }
            
            $sql = "";
            foreach ($tables as $table) {
                $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                
                $createTable = $conn->query("SHOW CREATE TABLE `$table`");
                $row = $createTable->fetch_row();
                $sql .= $row[1] . ";\n\n";
                
                $result = $conn->query("SELECT * FROM `$table`");
                $numColumns = $result->field_count;
                
                while ($row = $result->fetch_row()) {
                    $sql .= "INSERT INTO `$table` VALUES(";
                    for ($i = 0; $i < $numColumns; $i++) {
                        $row[$i] = $row[$i];
                        $row[$i] = addslashes($row[$i]);
                        $row[$i] = str_replace("\n", "\\n", $row[$i]);
                        if (isset($row[$i])) {
                            $sql .= '"' . $row[$i] . '"';
                        } else {
                            $sql .= '""';
                        }
                        if ($i < $numColumns - 1) {
                            $sql .= ',';
                        }
                    }
                    $sql .= ");\n";
                }
                $sql .= "\n\n";
            }
            
            return file_put_contents($filename, $sql) !== false;
            
        } catch (Exception $e) {
            error_log("PHP database backup error: " . $e->getMessage());
            return false;
        }
    }
}

// Handle backup creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_backup'])) {
    $backup_name = trim($_POST['backup_name']);
    $backup_type = $_POST['backup_type'];
    $include_files = isset($_POST['include_files']);
    $include_database = isset($_POST['include_database']);
    
    $errors = [];
    
    if (empty($backup_name)) {
        $errors[] = 'Backup name is required';
    }
    
    if (empty($errors)) {
        try {
            // Check if backups table exists
            $table_check = $conn->prepare("DESCRIBE backups");
            $table_check->execute();
            $table_check->close();
            
            // Create backup directory if it doesn't exist
            $backup_dir = '../backups';
            if (!is_dir($backup_dir)) {
                if (!mkdir($backup_dir, 0755, true)) {
                    $errors[] = 'Failed to create backup directory';
                }
            }
            
            if (empty($errors)) {
                $timestamp = date('Y-m-d_H-i-s');
                $backup_filename = $backup_name . '_' . $timestamp;
                $backup_path = $backup_dir . '/' . $backup_filename;
                
                // Create backup log entry
                $backup_details = "Backup: $backup_name, Type: $backup_type";
                if ($include_files) $backup_details .= ", Files: Yes";
                if ($include_database) $backup_details .= ", Database: Yes";
                
                if ($include_database) {
                    // Database backup with better error handling
                    $db_backup_file = $backup_path . '_database.sql';
                    
                    // Try different mysqldump approaches
                    $commands = [
                        "mysqldump --user=" . escapeshellarg($username) . " --password=" . escapeshellarg($password) . " --host=" . escapeshellarg($host) . " " . escapeshellarg($database) . " > " . escapeshellarg($db_backup_file),
                        "mysqldump -u" . escapeshellarg($username) . " -p" . escapeshellarg($password) . " -h" . escapeshellarg($host) . " " . escapeshellarg($database) . " > " . escapeshellarg($db_backup_file),
                        "mysqldump -u" . escapeshellarg($username) . " -p" . escapeshellarg($password) . " " . escapeshellarg($database) . " > " . escapeshellarg($db_backup_file)
                    ];
                    
                    $backup_success = false;
                    foreach ($commands as $command) {
                        exec($command . ' 2>&1', $output, $return_var);
                        if ($return_var === 0 && file_exists($db_backup_file) && filesize($db_backup_file) > 0) {
                            $backup_success = true;
                            break;
                        }
                    }
                    
                    if (!$backup_success) {
                        // Fallback to PHP-based backup
                        $backup_success = backupDatabasePHP($db_backup_file, $conn, $database);
                    }
                    
                    if (!$backup_success) {
                        $errors[] = 'Database backup failed - mysqldump not available or permission denied';
                    }
                }
                
                if ($include_files && empty($errors)) {
                    // Check if ZipArchive is available
                    if (!class_exists('ZipArchive')) {
                        $errors[] = 'ZipArchive extension not available for file backup';
                    } else {
                        // Files backup (create zip)
                        $zip_file = $backup_path . '_files.zip';
                        $zip = new ZipArchive();
                        
                        if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                            // Add important directories
                            $directories_to_backup = ['ADMIN', 'OFFICE_ADMIN', 'SYSTEM_ADMIN', 'USER', 'assets'];
                            
                            foreach ($directories_to_backup as $dir) {
                                if (is_dir('../' . $dir)) {
                                    $files = new RecursiveIteratorIterator(
                                        new RecursiveDirectoryIterator('../' . $dir),
                                        RecursiveIteratorIterator::LEAVES_ONLY
                                    );
                                    
                                    foreach ($files as $name => $file) {
                                        if (!$file->isDir()) {
                                            $filePath = $file->getRealPath();
                                            $relativePath = substr($filePath, strlen(realpath('../')) + 1);
                                            $zip->addFile($filePath, $relativePath);
                                        }
                                    }
                                }
                            }
                            
                            // Add important files
                            $important_files = ['config.php', 'index.php', 'change_password.php', 'forgot_password.php', 'logout.php', 'reset_password.php'];
                            foreach ($important_files as $file) {
                                if (file_exists('../' . $file)) {
                                    $zip->addFile('../' . $file, $file);
                                }
                            }
                            
                            $zip->close();
                        } else {
                            $errors[] = 'Failed to create files backup archive';
                        }
                    }
                }
                
                if (empty($errors)) {
                    // Save backup record to database
                    $stmt = $conn->prepare("
                        INSERT INTO backups (name, type, include_files, include_database, file_path, created_by, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->bind_param("ssiisi", $backup_name, $backup_type, $include_files, $include_database, $backup_path, $_SESSION['user_id']);
                    $stmt->execute();
                    $stmt->close();
                    
                    logSystemAction($_SESSION['user_id'], 'backup_created', 'backup_system', $backup_details);
                    $success_message = 'Backup created successfully!';
                }
            }
            
        } catch (Exception $e) {
            error_log("Backup creation error: " . $e->getMessage());
            $errors[] = 'Backup creation failed: ' . $e->getMessage();
        }
    }
}

// Handle backup deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_backup'])) {
    $backup_id = $_POST['backup_id'];
    
    try {
        // Get backup info
        $stmt = $conn->prepare("SELECT * FROM backups WHERE id = ?");
        $stmt->bind_param("i", $backup_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $backup = $result->fetch_assoc();
        $stmt->close();
        
        if ($backup) {
            // Delete backup files
            if ($backup['include_database'] && file_exists($backup['file_path'] . '_database.sql')) {
                unlink($backup['file_path'] . '_database.sql');
            }
            if ($backup['include_files'] && file_exists($backup['file_path'] . '_files.zip')) {
                unlink($backup['file_path'] . '_files.zip');
            }
            
            // Delete database record
            $stmt = $conn->prepare("DELETE FROM backups WHERE id = ?");
            $stmt->bind_param("i", $backup_id);
            $stmt->execute();
            $stmt->close();
            
            logSystemAction($_SESSION['user_id'], 'backup_deleted', 'backup_system', "Deleted backup: {$backup['name']}");
            
            $success_message = 'Backup deleted successfully!';
        }
        
    } catch (Exception $e) {
        error_log("Backup deletion error: " . $e->getMessage());
        $error_message = 'Failed to delete backup';
    }
}

// Get existing backups
$backups = [];
try {
    $stmt = $conn->prepare("
        SELECT b.*, u.first_name, u.last_name, u.username 
        FROM backups b 
        LEFT JOIN users u ON b.created_by = u.id 
        ORDER BY b.created_at DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $backups[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching backups: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup System - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
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
        
        .backup-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border-left: 4px solid #191BA9;
        }
        
        .backup-item {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #191BA9;
            transition: transform 0.3s ease;
        }
        
        .backup-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #191BA9;
            box-shadow: 0 0 0 0.2rem rgba(25, 27, 169, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(25, 27, 169, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(220, 53, 69, 0.3);
        }
        
        .backup-type-badge {
            font-size: 0.8rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .backup-type-full {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
        }
        
        .backup-type-database {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .backup-type-files {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: #191BA9;
        }
        
        .form-check-input:checked {
            background-color: #191BA9;
            border-color: #191BA9;
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Backup System';
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
                            <i class="bi bi-cloud-download"></i> Backup System
                        </h1>
                        <p class="text-muted mb-0">Create and manage system backups</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBackupModal">
                            <i class="bi bi-plus-circle"></i> Create Backup
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Error:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo count($backups); ?></div>
                        <div class="text-muted">Total Backups</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number">
                            <?php 
                            $total_size = 0;
                            foreach ($backups as $backup) {
                                if ($backup['include_database'] && file_exists($backup['file_path'] . '_database.sql')) {
                                    $total_size += filesize($backup['file_path'] . '_database.sql');
                                }
                                if ($backup['include_files'] && file_exists($backup['file_path'] . '_files.zip')) {
                                    $total_size += filesize($backup['file_path'] . '_files.zip');
                                }
                            }
                            echo round($total_size / 1024 / 1024, 2) . ' MB';
                            ?>
                        </div>
                        <div class="text-muted">Total Size</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number">
                            <?php 
                            $recent_backups = array_filter($backups, function($backup) {
                                return strtotime($backup['created_at']) > strtotime('-7 days');
                            });
                            echo count($recent_backups);
                            ?>
                        </div>
                        <div class="text-muted">Last 7 Days</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number">
                            <?php 
                            $latest_backup = !empty($backups) ? $backups[0]['created_at'] : 'Never';
                            echo !empty($backups) ? date('M j', strtotime($latest_backup)) : 'Never';
                            ?>
                        </div>
                        <div class="text-muted">Last Backup</div>
                    </div>
                </div>
            </div>
            
            <!-- Backup List -->
            <div class="backup-card">
                <h4 class="mb-4"><i class="bi bi-clock-history"></i> Backup History</h4>
                
                <?php if (empty($backups)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-cloud-download fs-1 text-muted"></i>
                        <p class="text-muted mt-3">No backups found. Create your first backup to get started.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($backups as $backup): ?>
                        <div class="backup-item">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($backup['name']); ?></h5>
                                    <p class="text-muted mb-2">
                                        <small>
                                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($backup['first_name'] . ' ' . $backup['last_name']); ?> •
                                            <i class="bi bi-calendar"></i> <?php echo date('M j, Y H:i:s', strtotime($backup['created_at'])); ?>
                                        </small>
                                    </p>
                                    <div>
                                        <span class="backup-type-badge backup-type-<?php echo $backup['type']; ?>">
                                            <?php echo htmlspecialchars($backup['type']); ?>
                                        </span>
                                        <?php if ($backup['include_database']): ?>
                                            <span class="badge bg-success ms-2">
                                                <i class="bi bi-database"></i> Database
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($backup['include_files']): ?>
                                            <span class="badge bg-info ms-2">
                                                <i class="bi bi-folder"></i> Files
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-1">
                                        <strong>Size:</strong> 
                                        <?php 
                                        $backup_size = 0;
                                        if ($backup['include_database'] && file_exists($backup['file_path'] . '_database.sql')) {
                                            $backup_size += filesize($backup['file_path'] . '_database.sql');
                                        }
                                        if ($backup['include_files'] && file_exists($backup['file_path'] . '_files.zip')) {
                                            $backup_size += filesize($backup['file_path'] . '_files.zip');
                                        }
                                        echo $backup_size > 0 ? round($backup_size / 1024 / 1024, 2) . ' MB' : 'Unknown';
                                        ?>
                                    </p>
                                </div>
                                <div class="col-md-3 text-md-end">
                                    <?php if ($backup['include_database'] && file_exists($backup['file_path'] . '_database.sql')): ?>
                                        <a href="<?php echo htmlspecialchars($backup['file_path'] . '_database.sql'); ?>" 
                                           class="btn btn-sm btn-success me-2" download>
                                            <i class="bi bi-download"></i> DB
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($backup['include_files'] && file_exists($backup['file_path'] . '_files.zip')): ?>
                                        <a href="<?php echo htmlspecialchars($backup['file_path'] . '_files.zip'); ?>" 
                                           class="btn btn-sm btn-info me-2" download>
                                            <i class="bi bi-download"></i> Files
                                        </a>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="confirmDeleteBackup(<?php echo $backup['id']; ?>, '<?php echo htmlspecialchars($backup['name']); ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Create Backup Modal -->
    <div class="modal fade" id="createBackupModal" tabindex="-1" aria-labelledby="createBackupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="backup.php">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="createBackupModalLabel">
                            <i class="bi bi-cloud-download"></i> Create Backup
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="backup_name" class="form-label">Backup Name</label>
                            <input type="text" class="form-control" id="backup_name" name="backup_name" 
                                   placeholder="e.g., Daily Backup" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="backup_type" class="form-label">Backup Type</label>
                            <select class="form-select" id="backup_type" name="backup_type" required>
                                <option value="full">Full Backup</option>
                                <option value="database">Database Only</option>
                                <option value="files">Files Only</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Include in Backup</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="include_database" name="include_database" checked>
                                <label class="form-check-label" for="include_database">
                                    <i class="bi bi-database"></i> Database Backup
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="include_files" name="include_files" checked>
                                <label class="form-check-label" for="include_files">
                                    <i class="bi bi-folder"></i> System Files
                                </label>
                            </div>
                        </div>
                        
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle"></i>
                            <strong>Note:</strong> Full backups include both database and system files. Database backups contain all data, while file backups contain the application files.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_backup" class="btn btn-primary">
                            <i class="bi bi-cloud-download"></i> Create Backup
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteBackupModal" tabindex="-1" aria-labelledby="deleteBackupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="backup.php" id="deleteBackupForm">
                    <input type="hidden" name="backup_id" id="delete_backup_id">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deleteBackupModalLabel">
                            <i class="bi bi-exclamation-triangle"></i> Delete Backup
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning" role="alert">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Warning:</strong> This action cannot be undone!
                        </div>
                        <p>Are you sure you want to delete the backup "<span id="delete_backup_name"></span>"?</p>
                        <p class="text-muted">This will permanently remove all backup files and records.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_backup" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Delete Backup
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <script>
        // Sidebar functionality
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const mainWrapper = document.getElementById('mainWrapper');
        const navbar = document.querySelector('.navbar');

        if (sidebarToggle && sidebar && sidebarOverlay) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
                mainWrapper.classList.toggle('sidebar-active');
                if (navbar) navbar.classList.toggle('sidebar-active');
                sidebarToggle.classList.toggle('sidebar-active');
            });

            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                mainWrapper.classList.remove('sidebar-active');
                if (navbar) navbar.classList.remove('sidebar-active');
                sidebarToggle.classList.remove('sidebar-active');
            });
        }
        
        // Delete backup confirmation
        function confirmDeleteBackup(backupId, backupName) {
            document.getElementById('delete_backup_id').value = backupId;
            document.getElementById('delete_backup_name').textContent = backupName;
            new bootstrap.Modal(document.getElementById('deleteBackupModal')).show();
        }
        
        // Update backup type checkboxes
        document.getElementById('backup_type')?.addEventListener('change', function() {
            const includeDatabase = document.getElementById('include_database');
            const includeFiles = document.getElementById('include_files');
            
            if (this.value === 'database') {
                includeDatabase.checked = true;
                includeFiles.checked = false;
                includeFiles.disabled = true;
            } else if (this.value === 'files') {
                includeDatabase.checked = false;
                includeFiles.checked = true;
                includeDatabase.disabled = true;
            } else {
                includeDatabase.checked = true;
                includeFiles.checked = true;
                includeDatabase.disabled = false;
                includeFiles.disabled = false;
            }
        });
    </script>
</body>
</html>
