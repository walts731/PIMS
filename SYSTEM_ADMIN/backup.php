<?php
date_default_timezone_set('Asia/Manila');
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';
require_once 'includes/cloud_api.php';
require_once 'includes/backup_functions.php';

// Passive Cron: Trigger cron_handler if 15 mins have passed since last check
$last_cron_check = getSystemSetting('last_cron_check', 0);
if (time() - $last_cron_check > 900) { // 15 minutes
    updateSystemSetting('last_cron_check', time());
    // Run cron handler logic directly for this request context
    include 'cron_handler.php';
}

// Check session timeout
checkSessionTimeout();

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

// Log backup page access
logSystemAction($_SESSION['user_id'], 'access', 'backup', 'System admin accessed backup page');



// Handle scheduled backup creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_scheduled_backup'])) {
    $schedule_name = trim($_POST['schedule_name']);
    $backup_type = $_POST['backup_type'];
    $schedule_type = $_POST['schedule_type'];
    $schedule_day = $_POST['schedule_day'] ?? null;
    $schedule_time = $_POST['schedule_time'];
    
    // Set include flags based on backup_type if not explicitly provided via checkboxes
    if ($backup_type === 'full') {
        $include_database = 1;
        $include_files = 1;
    } elseif ($backup_type === 'database') {
        $include_database = 1;
        $include_files = 0;
    } elseif ($backup_type === 'files') {
        $include_database = 0;
        $include_files = 1;
    } else {
        $include_database = isset($_POST['include_database']) ? 1 : 0;
        $include_files = isset($_POST['include_files']) ? 1 : 0;
    }
    
    $online_backup = isset($_POST['online_backup']) ? 1 : 0;
    $cloud_provider = $online_backup ? $_POST['cloud_provider'] : null;
    
    $errors = [];
    
    if (empty($schedule_name)) {
        $errors[] = 'Schedule name is required';
    }
    
    if (empty($errors)) {
        try {
            $next_run = calculateNextRun($schedule_type, $schedule_day, $schedule_time);
            
            $stmt = $conn->prepare("
                INSERT INTO scheduled_backups 
                (name, backup_type, schedule_type, schedule_day, schedule_time, include_files, include_database, online_backup, cloud_provider, next_run, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssissiiisi", $schedule_name, $backup_type, $schedule_type, $schedule_day, $schedule_time, $include_files, $include_database, $online_backup, $cloud_provider, $next_run, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
            
            logSystemAction($_SESSION['user_id'], 'scheduled_backup_created', 'backup_system', "Schedule: $schedule_name");
            $success_message = 'Scheduled backup created successfully!';
        } catch (Exception $e) {
            $errors[] = 'Failed to create scheduled backup: ' . $e->getMessage();
        }
    }
}

// Handle scheduled backup update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_scheduled_backup'])) {
    $schedule_id = $_POST['schedule_id'];
    $schedule_name = trim($_POST['schedule_name']);
    $backup_type = $_POST['backup_type'];
    $schedule_type = $_POST['schedule_type'];
    $schedule_day = $_POST['schedule_day'] ?? null;
    $schedule_time = $_POST['schedule_time'];
    
    // Set include flags based on backup_type if not explicitly provided via checkboxes
    if ($backup_type === 'full') {
        $include_database = 1;
        $include_files = 1;
    } elseif ($backup_type === 'database') {
        $include_database = 1;
        $include_files = 0;
    } elseif ($backup_type === 'files') {
        $include_database = 0;
        $include_files = 1;
    } else {
        $include_database = isset($_POST['include_database']) ? 1 : 0;
        $include_files = isset($_POST['include_files']) ? 1 : 0;
    }
    
    $online_backup = isset($_POST['online_backup']) ? 1 : 0;
    $cloud_provider = $online_backup ? $_POST['cloud_provider'] : null;
    
    $errors = [];
    if (empty($errors)) {
        try {
            $next_run = calculateNextRun($schedule_type, $schedule_day, $schedule_time);
            $stmt = $conn->prepare("
                UPDATE scheduled_backups 
                SET name = ?, backup_type = ?, schedule_type = ?, schedule_day = ?, schedule_time = ?, 
                    include_files = ?, include_database = ?, online_backup = ?, cloud_provider = ?, 
                    next_run = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->bind_param("sssissiiisi", $schedule_name, $backup_type, $schedule_type, $schedule_day, $schedule_time, $include_files, $include_database, $online_backup, $cloud_provider, $next_run, $schedule_id);
            $stmt->execute();
            $stmt->close();
            
            $success_message = 'Scheduled backup updated successfully!';
        } catch (Exception $e) {
            $errors[] = 'Failed to update scheduled backup: ' . $e->getMessage();
        }
    }
}

// Handle scheduled backup deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_scheduled_backup'])) {
    $schedule_id = $_POST['schedule_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM scheduled_backups WHERE id = ?");
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $stmt->close();
        $success_message = 'Scheduled backup deleted successfully!';
    } catch (Exception $e) {
        $error_message = 'Failed to delete scheduled backup';
    }
}

// Handle backup creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_backup'])) {
    $backup_name = trim($_POST['backup_name']);
    $backup_type = $_POST['backup_type'];
    $include_files = isset($_POST['include_files']) ? 1 : 0;
    $include_database = isset($_POST['include_database']) ? 1 : 0;
    $online_backup = isset($_POST['online_backup']) ? 1 : 0;
    $cloud_provider = $online_backup ? $_POST['cloud_provider'] : null;
    
    $errors = [];
    
    if (empty($backup_name)) {
        $errors[] = 'Backup name is required';
    }
    
    if (empty($errors)) {
        $backupConfig = [
            'name' => $backup_name,
            'type' => $backup_type,
            'include_database' => $include_database,
            'include_files' => $include_files,
            'created_by' => $_SESSION['user_id'],
            'online_backup' => $online_backup,
            'cloud_provider' => $cloud_provider
        ];

        // We don't use performPIMSBackup directly because backup.php handles 
        // online backup asynchronously via session.
        // We replicate parts of performPIMSBackup here but with session handling.
        
        try {
            $backup_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'backups';
            if (!is_dir($backup_dir)) mkdir($backup_dir, 0755, true);
            
            $timestamp = date('Y-m-d_H-i-s');
            $safe_backup_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $backup_name);
            $backup_filename = $safe_backup_name . '_' . $timestamp;
            $backup_path = $backup_dir . DIRECTORY_SEPARATOR . $backup_filename;
            
            $backup_success = true;
            
            if ($include_database) {
                $db_backup_file = $backup_path . '_database.sql';
                $db_success = false;
                $mysqldump_paths = ['C:\\xampp\\mysql\\bin\\mysqldump.exe', 'C:\\xampp\\mysql\\bin\\mysqldump', 'mysqldump'];
                
                foreach ($mysqldump_paths as $mysqldump) {
                    $cmd = escapeshellarg($mysqldump) . " --user=$username --password=$password --host=$host --single-transaction --routines --force --result-file=" . escapeshellarg($db_backup_file) . " $database";
                    $output = [];
                    exec($cmd . ' 2>&1', $output, $return_var);
                    if (file_exists($db_backup_file) && filesize($db_backup_file) > 100) {
                        $db_success = true;
                        break;
                    }
                }
                if (!$db_success) $db_success = backupDatabasePHP($db_backup_file, $conn, $database);
                if (!$db_success) {
                    $errors[] = "Database backup failed.";
                    $backup_success = false;
                }
            }
            
            if ($include_files && $backup_success) {
                $zip_file = $backup_path . '_files.zip';
                $zip = new ZipArchive();
                if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                    $pims_root = dirname(__DIR__);
                    $directories = ['ADMIN', 'OFFICE_ADMIN', 'SYSTEM_ADMIN', 'USER', 'assets'];
                    foreach ($directories as $dir) {
                        $dir_abs = $pims_root . DIRECTORY_SEPARATOR . $dir;
                        if (is_dir($dir_abs)) {
                            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir_abs), RecursiveIteratorIterator::LEAVES_ONLY);
                            foreach ($files as $file) {
                                if (!$file->isDir()) {
                                    $zip->addFile($file->getRealPath(), substr($file->getRealPath(), strlen($pims_root) + 1));
                                }
                            }
                        }
                    }
                    $zip->close();
                } else {
                    $errors[] = "Files backup failed.";
                    $backup_success = false;
                }
            }
            
            if ($backup_success) {
                $stmt = $conn->prepare("INSERT INTO backups (name, type, include_files, include_database, file_path, created_by, created_at, online_backup, cloud_provider) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
                $stmt->bind_param("ssiisiis", $backup_name, $backup_type, $include_files, $include_database, $backup_path, $_SESSION['user_id'], $online_backup, $cloud_provider);
                $stmt->execute();
                $backup_id = $stmt->insert_id;
                $stmt->close();
                
                logSystemAction($_SESSION['user_id'], 'backup_created', 'backup_system', "Backup: $backup_name");
                $success_message = 'Backup created successfully!';
                
                if ($online_backup && !empty($cloud_provider)) {
                    $_SESSION['pending_online_backup'] = [
                        'backup_id' => $backup_id,
                        'cloud_provider' => $cloud_provider,
                        'backup_path' => $backup_path,
                        'include_database' => $include_database,
                        'include_files' => $include_files
                    ];
                }
            }
        } catch (Exception $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

// Handle online backup upload
if (isset($_SESSION['pending_online_backup'])) {
    $pending_backup = $_SESSION['pending_online_backup'];
    try {
        $cloudAPI = new CloudStorageAPI($pending_backup['cloud_provider']);
        $upload_success = false;
        $cloud_url = '';
        
        if ($pending_backup['include_database']) {
            $db_file = $pending_backup['backup_path'] . '_database.sql';
            if (file_exists($db_file)) {
                $result = $cloudAPI->uploadFile($db_file, basename($db_file));
                if ($result['success']) {
                    $upload_success = true;
                    $cloud_url = $result['url'];
                }
            }
        }
        
        if ($upload_success) {
            $stmt = $conn->prepare("UPDATE backups SET cloud_backup_status = 'completed', cloud_backup_url = ?, cloud_backup_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $cloud_url, $pending_backup['backup_id']);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("UPDATE backups SET cloud_backup_status = 'failed' WHERE id = ?");
            $stmt->bind_param("i", $pending_backup['backup_id']);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Online backup upload error: " . $e->getMessage());
    }
    unset($_SESSION['pending_online_backup']);
}

// Handle backup deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_backup'])) {
    $backup_id = $_POST['backup_id'];
    try {
        $stmt = $conn->prepare("SELECT * FROM backups WHERE id = ?");
        $stmt->bind_param("i", $backup_id);
        $stmt->execute();
        $backup = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($backup) {
            if ($backup['include_database'] && file_exists($backup['file_path'] . '_database.sql')) unlink($backup['file_path'] . '_database.sql');
            if ($backup['include_files'] && file_exists($backup['file_path'] . '_files.zip')) unlink($backup['file_path'] . '_files.zip');
            
            $stmt = $conn->prepare("DELETE FROM backups WHERE id = ?");
            $stmt->bind_param("i", $backup_id);
            $stmt->execute();
            $stmt->close();
            $success_message = 'Backup deleted successfully!';
        }
    } catch (Exception $e) {
        $error_message = 'Failed to delete backup';
    }
}

// Get existing backups
$backups = [];
try {
    $stmt = $conn->prepare("SELECT b.*, u.first_name, u.last_name, u.username FROM backups b LEFT JOIN users u ON b.created_by = u.id ORDER BY b.created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $backups[] = $row;
    $stmt->close();
} catch (Exception $e) {}

// Get scheduled backups
$scheduled_backups = [];
try {
    $stmt = $conn->prepare("SELECT s.*, u.first_name, u.last_name, u.username FROM scheduled_backups s LEFT JOIN users u ON s.created_by = u.id WHERE s.enabled = 1 ORDER BY s.next_run ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $scheduled_backups[] = $row;
    $stmt->close();
} catch (Exception $e) {}

// Get cloud providers
$cloud_providers = [];
try {
    $stmt = $conn->prepare("SELECT provider FROM online_backup_configs WHERE is_active = TRUE");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $cloud_providers[] = $row;
    $stmt->close();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup System - PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    <?php require_once 'includes/dark-mode-init.php'; ?>
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%); min-height: 100vh; }
        .page-header { background: white; border-radius: 15px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-left: 4px solid #191BA9; }
        .backup-card { background: white; border-radius: 15px; padding: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 2rem; border-left: 4px solid #191BA9; }
        .backup-item { background: white; border-radius: 15px; padding: 1.5rem; margin-bottom: 1rem; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border-left: 4px solid #191BA9; transition: all 0.3s ease; border: 1px solid #e9ecef; }
        .stats-card { background: white; border-radius: 15px; padding: 1.5rem; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .stats-number { font-size: 2rem; font-weight: 700; color: #191BA9; }
        .btn-primary { background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%); border: none; border-radius: 10px; font-weight: 600; }
        .backup-type-badge { font-size: 0.8rem; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; text-transform: uppercase; }
        .backup-type-full { background: #191BA9; color: white; }
        .backup-type-database { background: #28a745; color: white; }
        .backup-type-files { background: #ffc107; color: white; }
        
        /* Sidebar Toggle Specific Fix */
        .sidebar-toggle {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1050;
            background: #191BA9;
            color: white;
            border: none;
            border-radius: 8px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        .sidebar-toggle:hover {
            background: #5CC2F2;
            transform: scale(1.1);
        }
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1030;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .sidebar-overlay.active {
            display: block;
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
        <div class="main-content">
            <div class="page-header row align-items-center">
                <div class="col-md-8">
                    <h1><i class="bi bi-cloud-download"></i> Backup System</h1>
                    <p class="text-muted">Create and manage system backups</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBackupModal"><i class="bi bi-plus-circle"></i> Create Backup</button>
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <strong>Error:</strong>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error) echo "<li>" . htmlspecialchars($error) . "</li>"; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($success_message)) echo "<div class='alert alert-success'>".htmlspecialchars($success_message)."</div>"; ?>

            <div class="row mb-4 text-center">
                <div class="col-md-3"><div class="stats-card"><div class="stats-number"><?php echo count($backups); ?></div><div class="text-muted">Total Backups</div></div></div>
                <div class="col-md-3"><div class="stats-card">
                    <div class="stats-number">
                        <?php 
                        $total_size = 0;
                        foreach ($backups as $b) {
                            if ($b['include_database'] && file_exists($b['file_path'] . '_database.sql')) $total_size += filesize($b['file_path'] . '_database.sql');
                            if ($b['include_files'] && file_exists($b['file_path'] . '_files.zip')) $total_size += filesize($b['file_path'] . '_files.zip');
                        }
                        echo round($total_size / 1024 / 1024, 2) . ' MB';
                        ?>
                    </div>
                    <div class="text-muted">Total Size</div>
                </div></div>
                <div class="col-md-3"><div class="stats-card"><div class="stats-number"><?php echo count($scheduled_backups); ?></div><div class="text-muted">Schedules</div></div></div>
                <div class="col-md-3"><div class="stats-card"><div class="stats-number"><?php echo !empty($backups) ? date('M j', strtotime($backups[0]['created_at'])) : 'Never'; ?></div><div class="text-muted">Last Backup</div></div></div>
            </div>

            <div class="backup-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0"><i class="bi bi-clock"></i> Scheduled Backups</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createScheduledBackupModal">
                        <i class="bi bi-plus-circle"></i> Schedule Backup
                    </button>
                </div>
                
                <?php if (empty($scheduled_backups)): ?>
                    <div class="text-center py-5">
                        <p class="text-muted">No scheduled backups found.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($scheduled_backups as $schedule): ?>
                        <div class="backup-item">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5><?php echo htmlspecialchars($schedule['name']); ?></h5>
                                    <small class="text-muted">Next Run: <?php echo date('M j, Y H:i', strtotime($schedule['next_run'])); ?></small>
                                </div>
                                <div class="col-md-3 text-center">
                                    <span class="badge bg-secondary mb-1"><?php echo ucwords($schedule['schedule_type']); ?></span>
                                    <div>
                                        <?php if ($schedule['include_database']): ?><span class="badge bg-success-subtle text-success border border-success" style="font-size: 0.65rem;">DB</span><?php endif; ?>
                                        <?php if ($schedule['include_files']): ?><span class="badge bg-info-subtle text-info border border-info" style="font-size: 0.65rem;">Files</span><?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-3 text-end">
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteSchedule(<?php echo $schedule['id']; ?>, '<?php echo htmlspecialchars($schedule['name']); ?>')"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="backup-card">
                <h4 class="mb-4">History</h4>
                <?php foreach ($backups as $backup): ?>
                    <div class="backup-item row align-items-center">
                        <div class="col-md-6">
                            <h5><?php echo htmlspecialchars($backup['name']); ?></h5>
                            <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($backup['created_at'])); ?></small>
                            <div class="mt-2">
                                <span class="backup-type-badge backup-type-<?php echo $backup['type']; ?>"><?php echo $backup['type']; ?></span>
                                <?php if ($backup['include_database']): ?><span class="badge bg-success">DB</span><?php endif; ?>
                                <?php if ($backup['include_files']): ?><span class="badge bg-info">Files</span><?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <?php if ($backup['include_database'] && file_exists($backup['file_path'] . '_database.sql')): ?>
                                <a href="download_backup.php?id=<?php echo $backup['id']; ?>&type=database" class="btn btn-sm btn-success">DB</a>
                            <?php endif; ?>
                            <?php if ($backup['include_files'] && file_exists($backup['file_path'] . '_files.zip')): ?>
                                <a href="download_backup.php?id=<?php echo $backup['id']; ?>&type=files" class="btn btn-sm btn-info">Files</a>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteBackup(<?php echo $backup['id']; ?>, '<?php echo htmlspecialchars($backup['name']); ?>')"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Create Backup Modal -->
    <div class="modal fade" id="createBackupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white"><h5>Create Backup</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label">Name</label><input type="text" name="backup_name" class="form-control" required placeholder="e.g. Manual Backup"></div>
                        <div class="mb-3"><label class="form-label">Type</label><select name="backup_type" class="form-select"><option value="full">Full</option><option value="database">Database Only</option><option value="files">Files Only</option></select></div>
                        <div class="mb-3">
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="include_database" checked id="inc_db"><label class="form-check-label" for="inc_db">Include Database</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="include_files" checked id="inc_files"><label class="form-check-label" for="inc_files">Include Files</label></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="submit" name="create_backup" class="btn btn-primary">Create</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteBackupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="backup_id" id="del_id">
                    <div class="modal-header bg-danger text-white"><h5>Delete Backup</h5></div>
                    <div class="modal-body">Are you sure you want to delete "<span id="del_name"></span>"?</div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button><button type="submit" name="delete_backup" class="btn btn-danger">Yes, Delete</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Scheduled Backup Modal -->
    <div class="modal fade" id="createScheduledBackupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white"><h5>Schedule Backup</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label">Schedule Name</label><input type="text" name="schedule_name" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Type</label><select name="backup_type" class="form-select"><option value="full">Full</option><option value="database">Database</option><option value="files">Files</option></select></div>
                        <div class="mb-3"><label class="form-label">Frequency</label><select name="schedule_type" id="sched_type" class="form-select" onchange="toggleSchedDays()"><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option></select></div>
                        <div class="mb-3" id="sched_day_container" style="display:none;"><label class="form-label" id="sched_day_label">Day</label><input type="number" name="schedule_day" class="form-control" min="1" max="31"></div>
                        <div class="mb-3"><label class="form-label">Time</label><input type="time" name="schedule_time" class="form-control" value="02:00" required></div>
                    </div>
                    <div class="modal-footer"><button type="submit" name="create_scheduled_backup" class="btn btn-primary">Schedule</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Schedule Modal -->
    <div class="modal fade" id="deleteScheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="schedule_id" id="del_sched_id">
                    <div class="modal-header bg-danger text-white"><h5>Delete Schedule</h5></div>
                    <div class="modal-body">Delete schedule "<span id="del_sched_name"></span>"?</div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button><button type="submit" name="delete_scheduled_backup" class="btn btn-danger">Yes, Delete</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Sidebar Toggle Functionality
        $(document).ready(function() {
            const $sidebar = $('#sidebar');
            const $mainWrapper = $('#mainWrapper');
            const $overlay = $('#sidebarOverlay');
            const $toggle = $('#sidebarToggle');

            function toggleSidebar() {
                $sidebar.toggleClass('active');
                $mainWrapper.toggleClass('sidebar-active');
                $overlay.toggleClass('active');
                $toggle.toggleClass('active');
                
                if ($sidebar.hasClass('active')) {
                    $('body').css('overflow', 'hidden');
                } else {
                    $('body').css('overflow', '');
                }
            }

            $toggle.on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleSidebar();
            });

            $overlay.on('click', function() {
                if ($sidebar.hasClass('active')) {
                    toggleSidebar();
                }
            });

            // Close on Escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $sidebar.hasClass('active')) {
                    toggleSidebar();
                }
            });
        });
    </script>
    
    <script>
        function confirmDeleteBackup(id, name) {
            document.getElementById('del_id').value = id;
            document.getElementById('del_name').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteBackupModal')).show();
        }
        function confirmDeleteSchedule(id, name) {
            document.getElementById('del_sched_id').value = id;
            document.getElementById('del_sched_name').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteScheduleModal')).show();
        }
        function toggleSchedDays() {
            const type = document.getElementById('sched_type').value;
            const container = document.getElementById('sched_day_container');
            const label = document.getElementById('sched_day_label');
            if (type === 'daily') {
                container.style.display = 'none';
            } else {
                container.style.display = 'block';
                label.textContent = type === 'weekly' ? 'Day of Week (1-7)' : 'Day of Month (1-31)';
            }
        }
    </script>
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>
