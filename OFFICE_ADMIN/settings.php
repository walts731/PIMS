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
if ($_SESSION['role'] !== 'office_admin') {
    header('Location: ../index.php');
    exit();
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $theme = $_POST['theme'] ?? 'light';
    $language = $_POST['language'] ?? 'en';
    $timezone = $_POST['timezone'] ?? 'UTC';
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $desktop_notifications = isset($_POST['desktop_notifications']) ? 1 : 0;
    $auto_refresh = isset($_POST['auto_refresh']) ? 1 : 0;
    $refresh_interval = $_POST['refresh_interval'] ?? 30;
    
    // Build dynamic UPDATE query based on existing columns
    $update_fields = [];
    $update_values = [];
    $update_types = '';
    
    // Check each column and add to update if it exists
    $columns_and_values = [
        'theme' => $theme,
        'language' => $language,
        'timezone' => $timezone,
        'email_notifications' => $email_notifications,
        'desktop_notifications' => $desktop_notifications,
        'auto_refresh' => $auto_refresh,
        'refresh_interval' => $refresh_interval
    ];
    
    foreach ($columns_and_values as $column => $value) {
        $check_column = "SHOW COLUMNS FROM users LIKE '$column'";
        $result = $conn->query($check_column);
        
        if ($result->num_rows > 0) {
            $update_fields[] = "$column = ?";
            $update_values[] = $value;
            $update_types .= 's'; // All are strings except the integers
        }
    }
    
    if (!empty($update_fields)) {
        // Convert integer values
        if (in_array('email_notifications', array_keys($columns_and_values))) {
            $key = array_search('email_notifications', array_keys($update_fields));
            $update_values[$key] = $email_notifications;
            $update_types = substr_replace('s', 'i', $key, 1);
        }
        if (in_array('desktop_notifications', array_keys($columns_and_values))) {
            $key = array_search('desktop_notifications', array_keys($update_fields));
            $update_values[$key] = $desktop_notifications;
            $update_types = substr_replace('s', 'i', $key, 1);
        }
        if (in_array('auto_refresh', array_keys($columns_and_values))) {
            $key = array_search('auto_refresh', array_keys($update_fields));
            $update_values[$key] = $auto_refresh;
            $update_types = substr_replace('s', 'i', $key, 1);
        }
        if (in_array('refresh_interval', array_keys($columns_and_values))) {
            $key = array_search('refresh_interval', array_keys($update_fields));
            $update_values[$key] = $refresh_interval;
            $update_types = substr_replace('s', 'i', $key, 1);
        }
        
        $update_fields[] = "id = ?";
        $update_values[] = $_SESSION['user_id'];
        $update_types .= 'i';
        
        $update_query = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param($update_types, ...$update_values);
        
        if ($stmt->execute()) {
            // Update session variables for columns that exist
            $session_updates = [
                'theme' => $theme,
                'language' => $language,
                'timezone' => $timezone,
                'email_notifications' => $email_notifications,
                'desktop_notifications' => $desktop_notifications,
                'auto_refresh' => $auto_refresh,
                'refresh_interval' => $refresh_interval
            ];
            
            foreach ($session_updates as $key => $value) {
                $check_column = "SHOW COLUMNS FROM users LIKE '$key'";
                $result = $conn->query($check_column);
                if ($result->num_rows > 0) {
                    $_SESSION[$key] = $value;
                }
            }
            
            $success_message = "Settings updated successfully!";
            logSystemAction($_SESSION['user_id'], 'update', 'settings', 'User updated their settings');
        } else {
            $error_message = "Error updating settings. Please try again.";
        }
        $stmt->close();
    } else {
        $error_message = "No settings columns available to update.";
    }
}

// Get current user settings (with fallback for missing columns)
$user_settings = [];
$columns_to_check = ['theme', 'language', 'timezone', 'email_notifications', 'desktop_notifications', 'auto_refresh', 'refresh_interval'];

foreach ($columns_to_check as $column) {
    // Check if column exists
    $check_column = "SHOW COLUMNS FROM users LIKE '$column'";
    $result = $conn->query($check_column);
    
    if ($result->num_rows > 0) {
        // Column exists, get the value
        $stmt = $conn->prepare("SELECT $column FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $column_result = $stmt->get_result();
        if ($row = $column_result->fetch_assoc()) {
            $user_settings[$column] = $row[$column];
        }
        $stmt->close();
    } else {
        // Column doesn't exist, use default
        $defaults = [
            'theme' => 'light',
            'language' => 'en', 
            'timezone' => 'UTC',
            'email_notifications' => 1,
            'desktop_notifications' => 0,
            'auto_refresh' => 1,
            'refresh_interval' => 30
        ];
        $user_settings[$column] = $defaults[$column];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings - PIMS</title>
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
        }
        
        .settings-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .settings-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid #5CC2F2;
        }
        
        .settings-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .settings-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .settings-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #191BA9;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #5CC2F2;
            box-shadow: 0 0 0 0.2rem rgba(92, 194, 242, 0.25);
        }
        
        .form-check-input:checked {
            background-color: #5CC2F2;
            border-color: #5CC2F2;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            border: none;
            border-radius: var(--border-radius);
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(92, 194, 242, 0.3);
        }
        
        .alert {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow);
        }
        
        .theme-preview {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        
        .theme-option {
            width: 60px;
            height: 40px;
            border-radius: var(--border-radius);
            border: 3px solid transparent;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .theme-option:hover {
            transform: scale(1.05);
        }
        
        .theme-option.selected {
            border-color: #191BA9;
        }
        
        .theme-light {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }
        
        .theme-dark {
            background: linear-gradient(135deg, #212529 0%, #343a40 100%);
        }
        
        .theme-blue {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'User Settings';
    ?>
    <!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
        <?php require_once 'includes/notification_js.php'; ?>
    
        <!-- Main Content -->
        <div class="main-content">
            <div class="settings-container">
                <!-- Page Header -->
                <div class="settings-card">
                    <div class="d-flex align-items-center mb-4">
                        <i class="bi bi-gear-fill fs-3 text-primary me-3"></i>
                        <div>
                            <h1 class="mb-1">User Settings</h1>
                            <p class="text-muted mb-0">Manage your account preferences and settings</p>
                        </div>
                    </div>
                    
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Settings Form -->
                <form method="POST" action="">
                    <input type="hidden" name="update_settings" value="1">
                    
                    <!-- Appearance Settings -->
                    <div class="settings-card">
                        <div class="settings-section">
                            <h3 class="settings-title">
                                <i class="bi bi-palette"></i>
                                Appearance
                            </h3>
                            
                            <div class="mb-3">
                                <label class="form-label">Theme</label>
                                <div class="theme-preview">
                                    <div class="theme-option theme-light <?php echo $user_settings['theme'] === 'light' ? 'selected' : ''; ?>" 
                                         data-theme="light" title="Light Theme"></div>
                                    <div class="theme-option theme-dark <?php echo $user_settings['theme'] === 'dark' ? 'selected' : ''; ?>" 
                                         data-theme="dark" title="Dark Theme"></div>
                                    <div class="theme-option theme-blue <?php echo $user_settings['theme'] === 'blue' ? 'selected' : ''; ?>" 
                                         data-theme="blue" title="Blue Theme"></div>
                                </div>
                                <input type="hidden" name="theme" id="selectedTheme" value="<?php echo htmlspecialchars($user_settings['theme']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="language" class="form-label">Language</label>
                                <select class="form-select" id="language" name="language">
                                    <option value="en" <?php echo $user_settings['language'] === 'en' ? 'selected' : ''; ?>>English</option>
                                    <option value="es" <?php echo $user_settings['language'] === 'es' ? 'selected' : ''; ?>>Spanish</option>
                                    <option value="fr" <?php echo $user_settings['language'] === 'fr' ? 'selected' : ''; ?>>French</option>
                                    <option value="de" <?php echo $user_settings['language'] === 'de' ? 'selected' : ''; ?>>German</option>
                                    <option value="zh" <?php echo $user_settings['language'] === 'zh' ? 'selected' : ''; ?>>Chinese</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="timezone" class="form-label">Timezone</label>
                                <select class="form-select" id="timezone" name="timezone">
                                    <option value="UTC" <?php echo $user_settings['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                    <option value="America/New_York" <?php echo $user_settings['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                                    <option value="America/Chicago" <?php echo $user_settings['timezone'] === 'America/Chicago' ? 'selected' : ''; ?>>Central Time</option>
                                    <option value="America/Denver" <?php echo $user_settings['timezone'] === 'America/Denver' ? 'selected' : ''; ?>>Mountain Time</option>
                                    <option value="America/Los_Angeles" <?php echo $user_settings['timezone'] === 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time</option>
                                    <option value="Europe/London" <?php echo $user_settings['timezone'] === 'Europe/London' ? 'selected' : ''; ?>>London</option>
                                    <option value="Europe/Paris" <?php echo $user_settings['timezone'] === 'Europe/Paris' ? 'selected' : ''; ?>>Paris</option>
                                    <option value="Asia/Tokyo" <?php echo $user_settings['timezone'] === 'Asia/Tokyo' ? 'selected' : ''; ?>>Tokyo</option>
                                    <option value="Asia/Shanghai" <?php echo $user_settings['timezone'] === 'Asia/Shanghai' ? 'selected' : ''; ?>>Shanghai</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notification Settings -->
                    <div class="settings-card">
                        <div class="settings-section">
                            <h3 class="settings-title">
                                <i class="bi bi-bell"></i>
                                Notifications
                            </h3>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="email_notifications" 
                                           name="email_notifications" <?php echo $user_settings['email_notifications'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="email_notifications">
                                        Email Notifications
                                        <div class="form-text">Receive notifications via email</div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="desktop_notifications" 
                                           name="desktop_notifications" <?php echo $user_settings['desktop_notifications'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="desktop_notifications">
                                        Desktop Notifications
                                        <div class="form-text">Show desktop notifications</div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="auto_refresh" 
                                           name="auto_refresh" <?php echo $user_settings['auto_refresh'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="auto_refresh">
                                        Auto-refresh Data
                                        <div class="form-text">Automatically refresh dashboard data</div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="refresh_interval" class="form-label">Refresh Interval (seconds)</label>
                                <select class="form-select" id="refresh_interval" name="refresh_interval">
                                    <option value="15" <?php echo $user_settings['refresh_interval'] == 15 ? 'selected' : ''; ?>>15 seconds</option>
                                    <option value="30" <?php echo $user_settings['refresh_interval'] == 30 ? 'selected' : ''; ?>>30 seconds</option>
                                    <option value="60" <?php echo $user_settings['refresh_interval'] == 60 ? 'selected' : ''; ?>>1 minute</option>
                                    <option value="300" <?php echo $user_settings['refresh_interval'] == 300 ? 'selected' : ''; ?>>5 minutes</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Privacy Settings -->
                    <div class="settings-card">
                        <div class="settings-section">
                            <h3 class="settings-title">
                                <i class="bi bi-shield-check"></i>
                                Privacy
                            </h3>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="show_online_status" checked>
                                    <label class="form-check-label" for="show_online_status">
                                        Show Online Status
                                        <div class="form-text">Let others see when you're online</div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="show_activity" checked>
                                    <label class="form-check-label" for="show_activity">
                                        Show Activity Status
                                        <div class="form-text">Display your recent activity to others</div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="data_collection" checked>
                                    <label class="form-check-label" for="data_collection">
                                        Allow Data Collection
                                        <div class="form-text">Help improve PIMS with usage analytics</div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Save Button -->
                    <div class="settings-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">
                                    <i class="bi bi-info-circle"></i>
                                    Your settings are saved automatically and applied across all your sessions.
                                </small>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-2"></i>
                                Save Settings
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Sidebar Scripts -->
    <script src="../assets/js/sidebar.js"></script>
    
    <script>
    // Theme selection
    document.querySelectorAll('.theme-option').forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all options
            document.querySelectorAll('.theme-option').forEach(opt => opt.classList.remove('selected'));
            
            // Add selected class to clicked option
            this.classList.add('selected');
            
            // Update hidden input
            document.getElementById('selectedTheme').value = this.dataset.theme;
        });
    });
    
    // Request desktop notification permission
    document.getElementById('desktop_notifications').addEventListener('change', function() {
        if (this.checked && 'Notification' in window) {
            Notification.requestPermission().then(permission => {
                if (permission !== 'granted') {
                    this.checked = false;
                    alert('Please allow desktop notifications in your browser settings.');
                }
            });
        }
    });
    
    // Auto-refresh toggle
    document.getElementById('auto_refresh').addEventListener('change', function() {
        const refreshInterval = document.getElementById('refresh_interval');
        refreshInterval.disabled = !this.checked;
    });
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        const autoRefresh = document.getElementById('auto_refresh');
        const refreshInterval = document.getElementById('refresh_interval');
        refreshInterval.disabled = !autoRefresh.checked;
    });
    </script>
</body>
</html>
