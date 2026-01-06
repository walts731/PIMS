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
            $stmt = $conn->prepare("SELECT id FROM online_backup_configs WHERE provider = ?");
            $stmt->bind_param("s", $provider);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing = $result->fetch_assoc();
            $stmt->close();
            
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
            
            <!-- Configuration Guide -->
            <div class="config-card">
                <h4 class="mb-4"><i class="bi bi-info-circle"></i> Setup Guide</h4>
                <div class="accordion" id="setupGuide">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#googleDriveSetup">
                                <i class="bi bi-google me-2"></i> Google Drive Setup
                            </button>
                        </h2>
                        <div id="googleDriveSetup" class="accordion-collapse collapse show" data-bs-parent="#setupGuide">
                            <div class="accordion-body">
                                <ol>
                                    <li>Go to <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                                    <li>Create a new project or select existing one</li>
                                    <li>Enable Google Drive API</li>
                                    <li>Create OAuth 2.0 credentials</li>
                                    <li>Add authorized redirect URI: <code><?php echo "http://{$_SERVER['HTTP_HOST']}/PIMS/SYSTEM_ADMIN/cloud_callback.php"; ?></code></li>
                                    <li>Copy Client ID and Client Secret to the configuration form</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#dropboxSetup">
                                <i class="bi bi-dropbox me-2"></i> Dropbox Setup
                            </button>
                        </h2>
                        <div id="dropboxSetup" class="accordion-collapse collapse" data-bs-parent="#setupGuide">
                            <div class="accordion-body">
                                <ol>
                                    <li>Go to <a href="https://www.dropbox.com/developers/apps" target="_blank">Dropbox App Console</a></li>
                                    <li>Create a new app</li>
                                    <li>Select "Scoped access" and "Full Dropbox"</li>
                                    <li>Enable app folder or full Dropbox access</li>
                                    <li>Generate access token</li>
                                    <li>Copy App Key and App Secret to the configuration form</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#onedriveSetup">
                                <i class="bi bi-microsoft me-2"></i> OneDrive Setup
                            </button>
                        </h2>
                        <div id="onedriveSetup" class="accordion-collapse collapse" data-bs-parent="#setupGuide">
                            <div class="accordion-body">
                                <ol>
                                    <li>Go to <a href="https://portal.azure.com/" target="_blank">Azure Portal</a></li>
                                    <li>Register a new application</li>
                                    <li>Add Microsoft Graph permissions (Files.ReadWrite)</li>
                                    <li>Create client secret</li>
                                    <li>Add redirect URI: <code><?php echo "http://{$_SERVER['HTTP_HOST']}/PIMS/SYSTEM_ADMIN/cloud_callback.php"; ?></code></li>
                                    <li>Copy Application ID and Client Secret to the configuration form</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Existing Configurations -->
            <div class="config-card">
                <h4 class="mb-4"><i class="bi bi-gear"></i> Existing Configurations</h4>
                
                <?php if (empty($configs)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-cloud fs-1 text-muted"></i>
                        <p class="text-muted mt-3">No cloud storage configurations found. Set up your first configuration below.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($configs as $config): ?>
                        <div class="provider-card">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-1">
                                        <?php
                                        $icon = [
                                            'google_drive' => 'bi-google',
                                            'dropbox' => 'bi-dropbox',
                                            'onedrive' => 'bi-microsoft'
                                        ][$config['provider']] ?? 'bi-cloud';
                                        ?>
                                        <i class="bi <?php echo $icon; ?>"></i>
                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $config['provider']))); ?>
                                    </h5>
                                    <p class="text-muted mb-2">
                                        <small>
                                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($config['first_name'] . ' ' . $config['last_name']); ?> •
                                            <i class="bi bi-calendar"></i> <?php echo date('M j, Y H:i', strtotime($config['created_at'])); ?>
                                        </small>
                                    </p>
                                    <div>
                                        <span class="status-badge <?php echo $config['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                            <i class="bi bi-<?php echo $config['is_active'] ? 'check-circle' : 'x-circle'; ?>"></i>
                                            <?php echo $config['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                        <?php if (!empty($config['bucket_name'])): ?>
                                            <span class="badge bg-info ms-2">
                                                <i class="bi bi-folder"></i> <?php echo htmlspecialchars($config['bucket_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <button class="btn btn-sm btn-primary" 
                                            onclick="editConfig('<?php echo $config['provider']; ?>')">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="deleteConfig('<?php echo $config['provider']; ?>')">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Configuration Form -->
            <div class="config-card">
                <h4 class="mb-4"><i class="bi bi-plus-circle"></i> Add/Edit Configuration</h4>
                
                <form method="POST" action="cloud_config.php" id="configForm">
                    <input type="hidden" name="provider" id="provider" value="">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="config_name" class="form-label">Configuration Name</label>
                                <input type="text" class="form-control" id="config_name" name="config_name" 
                                       placeholder="e.g., My Google Drive" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="folder_path" class="form-label">Folder Path</label>
                                <input type="text" class="form-control" id="folder_path" name="folder_path" 
                                       placeholder="e.g., /PIMS_Backups">
                                <div class="form-text">Folder path in cloud storage (optional)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="api_key" class="form-label">API Key / Client ID</label>
                                <input type="text" class="form-control" id="api_key" name="api_key" 
                                       placeholder="Enter API key or client ID">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="api_secret" class="form-label">API Secret / Client Secret</label>
                                <input type="password" class="form-control" id="api_secret" name="api_secret" 
                                       placeholder="Enter API secret or client secret">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="access_token" class="form-label">Access Token</label>
                                <input type="text" class="form-control" id="access_token" name="access_token" 
                                       placeholder="Enter access token (if available)">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="refresh_token" class="form-label">Refresh Token</label>
                                <input type="text" class="form-control" id="refresh_token" name="refresh_token" 
                                       placeholder="Enter refresh token (if available)">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bucket_name" class="form-label">Bucket/Container Name</label>
                                <input type="text" class="form-control" id="bucket_name" name="bucket_name" 
                                       placeholder="Enter bucket or container name">
                                <div class="form-text">For services that use buckets/containers</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">
                                        <i class="bi bi-check-circle"></i> Enable this configuration
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-shield-check"></i>
                        <strong>Security Note:</strong> All credentials are encrypted and stored securely. Never share your API credentials with others.
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" onclick="clearForm()">
                            <i class="bi bi-x-circle"></i> Clear
                        </button>
                        <button type="submit" name="update_config" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Configuration
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
        
        // Configuration management
        function editConfig(provider) {
            // Load existing configuration data
            const configs = <?php echo json_encode($configs); ?>;
            const config = configs.find(c => c.provider === provider);
            
            if (config) {
                document.getElementById('provider').value = config.provider;
                document.getElementById('config_name').value = config.config_name;
                document.getElementById('api_key').value = config.api_key || '';
                document.getElementById('api_secret').value = config.api_secret || '';
                document.getElementById('access_token').value = config.access_token || '';
                document.getElementById('refresh_token').value = config.refresh_token || '';
                document.getElementById('bucket_name').value = config.bucket_name || '';
                document.getElementById('folder_path').value = config.folder_path || '';
                document.getElementById('is_active').checked = config.is_active;
                
                // Scroll to form
                document.getElementById('configForm').scrollIntoView({ behavior: 'smooth' });
            }
        }
        
        function deleteConfig(provider) {
            if (confirm('Are you sure you want to delete this configuration?')) {
                // Implement delete functionality
                window.location.href = `cloud_config.php?delete=${provider}`;
            }
        }
        
        function clearForm() {
            document.getElementById('configForm').reset();
            document.getElementById('provider').value = '';
        }
        
        // Quick setup buttons
        document.addEventListener('DOMContentLoaded', function() {
            // Add quick setup buttons for each provider
            const providers = ['google_drive', 'dropbox', 'onedrive'];
            const providerNames = {
                'google_drive': 'Google Drive',
                'dropbox': 'Dropbox',
                'onedrive': 'OneDrive'
            };
            
            // Check if there's a success message and redirect to backup page after 3 seconds
            const successAlert = document.querySelector('.alert-success');
            if (successAlert && successAlert.textContent.includes('Successfully connected')) {
                setTimeout(function() {
                    window.location.href = 'backup.php';
                }, 3000);
            }
        });
        
        // Add quick setup buttons for each provider
        providers.forEach(provider => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-outline-primary btn-sm me-2 mb-2';
            button.innerHTML = `<i class="bi bi-plus"></i> Add ${providerNames[provider]}`;
            button.onclick = function() {
                document.getElementById('provider').value = provider;
                document.getElementById('config_name').value = `${providerNames[provider]} Backup`;
                document.getElementById('config_name').focus();
            };
            
            document.querySelector('.config-card h4').parentNode.appendChild(button);
        });
    </script>
</body>
</html>
