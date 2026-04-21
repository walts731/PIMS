<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

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

require_once '../config.php';

// Function to log system actions
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

// Handle cloud configuration updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_config'])) {
    $provider = $_POST['provider'];
    $config_name = trim($_POST['config_name']);
    $api_key = trim($_POST['api_key']);
    $api_secret = trim($_POST['api_secret']);
    $access_token = trim($_POST['access_token']);
    $refresh_token = trim($_POST['refresh_token']);
    $bucket_name = trim($_POST['bucket_name']);
    $folder_path = trim($_POST['folder_path']);
    $is_active = isset($_POST['is_active']);
    
    $errors = [];
    
    if (empty($config_name)) {
        $errors[] = 'Configuration name is required';
    }
    
    if (empty($errors)) {
        try {
            // Check if config exists
            $stmt = $conn->prepare("SELECT api_key, api_secret FROM online_backup_configs WHERE provider = ?");
            $stmt->bind_param("s", $provider);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing = $result->fetch_assoc();
            $stmt->close();
            
            // If simple mode, preserve existing keys if new ones are empty
            if (empty($api_key) && !empty($existing['api_key'])) {
                $api_key = $existing['api_key'];
            }
            if (empty($api_secret) && !empty($existing['api_secret'])) {
                $api_secret = $existing['api_secret'];
            }

            if ($existing) {
                // Update existing config
                $stmt = $conn->prepare("
                    UPDATE online_backup_configs 
                    SET config_name = ?, api_key = ?, api_secret = ?, access_token = ?, 
                        refresh_token = ?, bucket_name = ?, folder_path = ?, is_active = ?, 
                        updated_at = CURRENT_TIMESTAMP 
                    WHERE provider = ?
                ");
                $stmt->bind_param("sssssssis", $config_name, $api_key, $api_secret, $access_token, 
                    $refresh_token, $bucket_name, $folder_path, $is_active, $provider);
            } else {
                // Insert new config
                $stmt = $conn->prepare("
                    INSERT INTO online_backup_configs 
                    (provider, config_name, api_key, api_secret, access_token, refresh_token, 
                     bucket_name, folder_path, is_active, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("ssssssssii", $provider, $config_name, $api_key, $api_secret, 
                    $access_token, $refresh_token, $bucket_name, $folder_path, $is_active, $_SESSION['user_id']);
            }
            
            $stmt->execute();
            $stmt->close();
            
            // Trigger OAuth redirect if this is Google Drive and we have keys but no token
            if ($provider === 'google_drive' && !empty($api_key) && empty($access_token)) {
                require_once 'includes/cloud_api.php';
                try {
                    $cloud_api = new CloudStorageAPI('google_drive');
                    $auth_url = $cloud_api->getAuthorizationUrl();
                    header("Location: " . $auth_url);
                    exit();
                } catch (Exception $e) {
                    error_log("Failed to get auth URL: " . $e->getMessage());
                    // Fall through to success message if we can't redirect
                }
            }

            logSystemAction($_SESSION['user_id'], 'cloud_config_updated', 'cloud_storage', 
                "Updated {$provider} configuration");
            
            $success_message = 'Cloud storage configuration updated successfully!';
            
        } catch (Exception $e) {
            error_log("Cloud config update error: " . $e->getMessage());
            $errors[] = 'Failed to update configuration: ' . $e->getMessage();
        }
    }
}

// Get existing configurations
$configs = [];
try {
    $stmt = $conn->prepare("
        SELECT c.*, u.first_name, u.last_name 
        FROM online_backup_configs c 
        LEFT JOIN users u ON c.created_by = u.id 
        ORDER BY c.provider
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $configs[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching cloud configs: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloud Storage Configuration - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
      <link href="assets/css/admin-unified.css" rel="stylesheet">
<?php require_once 'includes/dark-mode-init.php'; ?>
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
        
        .config-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border-left: 4px solid #191BA9;
        }
        
        .provider-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #191BA9;
            transition: transform 0.3s ease;
        }
        
        .provider-card:hover {
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
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .status-active {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .status-inactive {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
        }
        
        .provider-icon {
            width: 40px;
            height: 40px;
            margin-right: 1rem;
        }
    </style>
</head>
<body>
    <?php
    $page_title = 'Cloud Storage Configuration';
    ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="bi bi-cloud"></i> Cloud Storage Configuration
                        </h1>
                        <p class="text-muted mb-0">Configure cloud storage providers for online backups</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="backup.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left"></i> Back to Backup
                        </a>
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
            
            <?php if (isset($_SESSION['cloud_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['cloud_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['cloud_success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['cloud_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($_SESSION['cloud_error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['cloud_error']); ?>
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
            
            <!-- Quick Google Drive Setup (Simple Mode) -->
            <div class="config-card" id="simpleGoogleDriveCard">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0 text-primary">
                        <i class="bi bi-google"></i> Simplified Google Drive Setup
                    </h4>
                    <span class="badge bg-success rounded-pill">Recommended</span>
                </div>
                
                <p class="text-muted mb-4">The easiest way to backup your data. Just enter your Google account email and click connect.</p>
                
                <form method="POST" action="cloud_config.php" class="row g-3 align-items-end">
                    <input type="hidden" name="provider" value="google_drive">
                    <input type="hidden" name="update_config" value="1">
                    <input type="hidden" name="config_name" value="Google Drive Backup">
                    
                    <div class="col-md-8">
                        <label for="gmail_address" class="form-label">Google Account Email (Gmail)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-envelope-at text-primary"></i>
                            </span>
                            <input type="email" class="form-control border-start-0" id="gmail_address" name="folder_path" 
                                   placeholder="yourname@gmail.com" 
                                   value="<?php 
                                        $gd_config = array_filter($configs, function($c) { return $c['provider'] === 'google_drive'; });
                                        $gd_config = reset($gd_config);
                                        echo htmlspecialchars($gd_config['folder_path'] ?? ''); 
                                   ?>">
                        </div>
                        <div class="form-text mt-2">This email will be used as the primary backup destination identifier.</div>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100 py-2 shadow-sm">
                            <i class="bi bi-link-45deg"></i> Connect & Authorize
                        </button>
                    </div>
                    
                    <div class="col-12 mt-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active_simple" name="is_active" checked>
                            <label class="form-check-label" for="is_active_simple">Enable Online Backup automatic syncing</label>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Existing Configurations (Simplified List) -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="config-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0"><i class="bi bi-list-task"></i> Active Providers</h4>
                            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#advancedConfigSection">
                                <i class="bi bi-gear-fill"></i> Advanced Settings
                            </button>
                        </div>
                        
                        <?php if (empty($configs)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-cloud-slash fs-2 text-muted"></i>
                                <p class="text-muted mt-2">No providers configured yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Provider</th>
                                            <th>Identifier / Gmail</th>
                                            <th>Status</th>
                                            <th>Last Sync</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($configs as $config): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php
                                                        $icon = [
                                                            'google_drive' => 'bi-google text-primary',
                                                            'dropbox' => 'bi-dropbox text-info',
                                                            'onedrive' => 'bi-microsoft text-primary'
                                                        ][$config['provider']] ?? 'bi-cloud';
                                                        ?>
                                                        <i class="bi <?php echo $icon; ?> fs-4 me-3"></i>
                                                        <strong><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $config['provider']))); ?></strong>
                                                    </div>
                                                </td>
                                                <td>
                                                    <code class="text-dark"><?php echo htmlspecialchars($config['folder_path'] ?: 'Not set'); ?></code>
                                                </td>
                                                <td>
                                                    <span class="badge rounded-pill <?php echo $config['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                        <?php echo $config['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($config['updated_at'])); ?></small>
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group">
                                                        <button class="btn btn-sm btn-outline-primary" onclick="editConfig('<?php echo $config['provider']; ?>')">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteConfig('<?php echo $config['provider']; ?>')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Advanced Configuration Section (Collapsed by default) -->
            <div class="collapse" id="advancedConfigSection">
                <div class="config-card">
                    <h4 class="mb-4 text-warning"><i class="bi bi-shield-lock"></i> Developer / Advanced Settings</h4>
                    <p class="small text-muted mb-4">Modify raw API credentials and technical endpoints. Only change these if you know what you are doing.</p>
                    
                    <form method="POST" action="cloud_config.php" id="configForm">
                        <input type="hidden" name="provider" id="provider" value="">
                        <input type="hidden" name="update_config" value="1">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Configuration Name</label>
                                <input type="text" class="form-control" id="config_name" name="config_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Backup Folder / Email Identifier</label>
                                <input type="text" class="form-control" id="folder_path" name="folder_path">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">API Client ID (Public Key)</label>
                                <input type="text" class="form-control font-monospace" id="api_key" name="api_key">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">API Client Secret (Private Key)</label>
                                <div class="input-group">
                                    <input type="password" class="form-control font-monospace" id="api_secret" name="api_secret">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('api_secret')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Access Token</label>
                                <textarea class="form-control font-monospace small" id="access_token" name="access_token" rows="2"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Refresh Token</label>
                                <input type="text" class="form-control font-monospace small" id="refresh_token" name="refresh_token">
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                <label class="form-check-label">Is Active</label>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-secondary" onclick="clearForm()">Reset</button>
                                <button type="submit" class="btn btn-warning px-4">Update Technical Specs</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Technical Setup Guides -->
                <div class="config-card">
                    <h5 class="mb-3"><i class="bi bi-book"></i> Reference: Manual API Setup</h5>
                    <div class="accordion accordion-flush" id="setupGuide">
                        <div class="accordion-item bg-transparent">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#googleDriveSetup">
                                    Google Drive API Details
                                </button>
                            </h2>
                            <div id="googleDriveSetup" class="accordion-collapse collapse" data-bs-parent="#setupGuide">
                                <div class="accordion-body small">
                                    Redirect URI: <code><?php echo "http://{$_SERVER['HTTP_HOST']}/PIMS/SYSTEM_ADMIN/cloud_callback.php"; ?></code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = event.currentTarget.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }
        
        function editConfig(provider) {
            const configs = <?php echo json_encode($configs); ?>;
            const config = configs.find(c => c.provider === provider);
            
            if (config) {
                document.getElementById('provider').value = config.provider;
                document.getElementById('config_name').value = config.config_name;
                document.getElementById('api_key').value = config.api_key || '';
                document.getElementById('api_secret').value = config.api_secret || '';
                document.getElementById('access_token').value = config.access_token || '';
                document.getElementById('refresh_token').value = config.refresh_token || '';
                document.getElementById('folder_path').value = config.folder_path || '';
                document.getElementById('is_active').checked = parseInt(config.is_active) === 1;
                
                const collapse = new bootstrap.Collapse(document.getElementById('advancedConfigSection'), { show: true });
                document.getElementById('configForm').scrollIntoView({ behavior: 'smooth' });
            }
        }
        
        function deleteConfig(provider) {
            if (confirm('Delete technical configuration for ' + provider + '?')) {
                window.location.href = `cloud_config.php?delete=${provider}`;
            }
        }
        
        function clearForm() {
            document.getElementById('configForm').reset();
            document.getElementById('provider').value = '';
        }
    </script>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>
