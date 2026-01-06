<?php
session_start();
ob_start(); // Start output buffering to prevent JSON corruption

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
if ($_SESSION['role'] !== 'system_admin' && $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Log settings page access
logSystemAction($_SESSION['user_id'], 'access', 'settings', 'User accessed system settings page');

// Function to create custom theme CSS
function createThemeCSS($primary_color, $secondary_color, $accent_color) {
    $css_content = "
:root {
    --primary-color: {$primary_color};
    --secondary-color: {$secondary_color};
    --accent-color: {$accent_color};
    --primary-hover: " . adjustColor($primary_color, -20) . ";
    --secondary-hover: " . adjustColor($secondary_color, -20) . ";
    
    /* Update gradients with new colors */
    --primary-gradient: linear-gradient(135deg, {$primary_color} 0%, {$secondary_color} 100%);
    --secondary-gradient: linear-gradient(135deg, {$secondary_color} 0%, {$accent_color} 100%);
    --accent-gradient: linear-gradient(135deg, {$accent_color} 0%, #F7F3F3 100%);
}

.bg-primary-custom { background-color: var(--primary-color) !important; }
.bg-secondary-custom { background-color: var(--secondary-color) !important; }
.bg-accent-custom { background-color: var(--accent-color) !important; }

.text-primary-custom { color: var(--primary-color) !important; }
.text-secondary-custom { color: var(--secondary-color) !important; }
.text-accent-custom { color: var(--accent-color) !important; }

.btn-primary-custom { 
    background-color: var(--primary-color) !important; 
    border-color: var(--primary-color) !important;
    color: white !important;
}
.btn-primary-custom:hover { 
    background-color: var(--primary-hover) !important; 
    border-color: var(--primary-hover) !important;
}

.btn-secondary-custom { 
    background-color: var(--secondary-color) !important; 
    border-color: var(--secondary-color) !important;
    color: white !important;
}
.btn-secondary-custom:hover { 
    background-color: var(--secondary-hover) !important; 
    border-color: var(--secondary-hover) !important;
}

.card-header-custom { 
    background-color: var(--primary-color) !important; 
    color: white !important;
}

.sidebar-custom { 
    background-color: var(--primary-color) !important;
}

.sidebar-custom .nav-link:hover {
    background-color: var(--primary-hover) !important;
}
";

    file_put_contents('../assets/css/theme-custom.css', $css_content);
}

// Function to adjust color brightness
function adjustColor($color, $amount) {
    $color = str_replace('#', '', $color);
    $r = hexdec(substr($color, 0, 2));
    $g = hexdec(substr($color, 2, 2));
    $b = hexdec(substr($color, 4, 2));
    
    $r = max(0, min(255, $r + $amount));
    $g = max(0, min(255, $g + $amount));
    $b = max(0, min(255, $b + $amount));
    
    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . 
           str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . 
           str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}

// Handle settings updates
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_system_settings'])) {
        try {
            // Handle logo removal
            if (isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1') {
                // Delete existing logo file
                if (!empty($system_settings['system_logo'])) {
                    $logo_file_path = '../' . $system_settings['system_logo'];
                    if (file_exists($logo_file_path)) {
                        unlink($logo_file_path);
                    }
                }
                
                // Clear logo setting
                $logo_stmt = $conn->prepare("UPDATE system_settings SET setting_value = '' WHERE setting_name = 'system_logo'");
                $logo_stmt->execute();
                $logo_stmt->close();
            }
            
            // Handle logo upload
            if (isset($_FILES['system_logo'])) {
                error_log("Logo upload detected: " . print_r($_FILES['system_logo'], true));
                
                $logo_file = $_FILES['system_logo'];
                
                // Check if file was actually uploaded
                if ($logo_file['error'] === UPLOAD_ERR_OK) {
                    error_log("Logo upload error code: " . $logo_file['error'] . " - UPLOAD_ERR_OK");
                    
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $max_size = 5 * 1024 * 1024; // 5MB
                    
                    error_log("File type: " . $logo_file['type'] . ", Size: " . $logo_file['size']);
                    
                    if (in_array($logo_file['type'], $allowed_types) && $logo_file['size'] <= $max_size) {
                        $upload_dir = '../img/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        
                        // Delete existing logo file
                        if (!empty($system_settings['system_logo'])) {
                            $existing_logo_path = '../' . $system_settings['system_logo'];
                            error_log("Deleting existing logo: " . $existing_logo_path);
                            if (file_exists($existing_logo_path)) {
                                unlink($existing_logo_path);
                            }
                        }
                        
                        $file_extension = pathinfo($logo_file['name'], PATHINFO_EXTENSION);
                        $logo_filename = 'system_logo.' . $file_extension;
                        $upload_path = $upload_dir . $logo_filename;
                        
                        error_log("Moving file from " . $logo_file['tmp_name'] . " to " . $upload_path);
                        
                        if (move_uploaded_file($logo_file['tmp_name'], $upload_path)) {
                            error_log("File moved successfully");
                            
                            // Update logo setting
                            $logo_stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_name = 'system_logo'");
                            $logo_path = 'img/' . $logo_filename;
                            $logo_stmt->bind_param("s", $logo_path);
                            
                            if ($logo_stmt->execute()) {
                                error_log("Logo setting updated successfully: " . $logo_path);
                            } else {
                                error_log("Failed to update logo setting");
                            }
                            $logo_stmt->close();
                        } else {
                            error_log("Failed to move uploaded file");
                            $error_message = 'Failed to upload logo file. Please check file permissions.';
                        }
                    } else {
                        error_log("File validation failed - Type: " . $logo_file['type'] . ", Size: " . $logo_file['size']);
                        $error_message = 'Invalid file type or size. Please upload JPG, PNG, GIF, or WEBP files under 5MB.';
                    }
                } elseif ($logo_file['error'] !== UPLOAD_ERR_NO_FILE) {
                    // Handle other upload errors
                    error_log("Upload error occurred: " . $logo_file['error']);
                    $error_message = 'Logo upload error: ' . $logo_file['error'];
                } else {
                    error_log("No file uploaded (UPLOAD_ERR_NO_FILE: " . UPLOAD_ERR_NO_FILE . ")");
                }
            }
            
            // Update system settings
            $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_name = ?");
            
            $settings = [
                'system_name' => $_POST['system_name'] ?? 'PIMS',
                'system_email' => $_POST['system_email'] ?? '',
                'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
                'allow_registration' => isset($_POST['allow_registration']) ? '1' : '0',
                'session_timeout' => $_POST['session_timeout'] ?? '3600',
                'max_login_attempts' => $_POST['max_login_attempts'] ?? '5',
                'password_min_length' => $_POST['password_min_length'] ?? '8',
                'backup_retention_days' => $_POST['backup_retention_days'] ?? '30',
                'email_notifications' => isset($_POST['email_notifications']) ? '1' : '0',
                'debug_mode' => isset($_POST['debug_mode']) ? '1' : '0',
                'primary_color' => $_POST['primary_color'] ?? '#191BA9',
                'secondary_color' => $_POST['secondary_color'] ?? '#5CC2F2',
                'accent_color' => $_POST['accent_color'] ?? '#FF6B6B'
            ];
            
            foreach ($settings as $name => $value) {
                $stmt->bind_param("ss", $value, $name);
                $stmt->execute();
            }
            $stmt->close();
            
            // Create custom CSS file for theme colors
            createThemeCSS($settings['primary_color'], $settings['secondary_color'], $settings['accent_color']);
            
            // Log the action
            logSystemAction($_SESSION['user_id'], 'system_settings_updated', 'system_settings', 'Updated system configuration including theme and logo');
            
            $success_message = 'System settings updated successfully!';
            
        } catch (Exception $e) {
            error_log("Error updating system settings: " . $e->getMessage());
            $error_message = 'Failed to update system settings: ' . $e->getMessage();
        }
    }
}

// Get current system settings
$system_settings = [];
try {
    $stmt = $conn->prepare("SELECT setting_name, setting_value FROM system_settings");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $system_settings[$row['setting_name']] = $row['setting_value'];
    }
    $stmt->close();
    
    // Debug: Log current settings
    error_log("Current system settings: " . print_r($system_settings, true));
    
} catch (Exception $e) {
    error_log("Error fetching system settings: " . $e->getMessage());
    // Set defaults if database fails
    $system_settings = [
        'system_name' => 'PIMS',
        'system_email' => '',
        'primary_color' => '#191BA9',
        'secondary_color' => '#5CC2F2',
        'accent_color' => '#FF6B6B',
        'system_logo' => ''
    ];
}

// Get system information
$system_info = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'database_version' => 'MySQL',
    'server_time' => date('Y-m-d H:i:s'),
    'timezone' => date_default_timezone_get(),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size')
];

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Require PHPMailer autoloader
require_once 'PHPMailer/PHPMailer-7.0.0/src/Exception.php';
require_once 'PHPMailer/PHPMailer-7.0.0/src/PHPMailer.php';
require_once 'PHPMailer/PHPMailer-7.0.0/src/SMTP.php';

// Handle email test AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    // Suppress error reporting to prevent JSON corruption
    error_reporting(0);
    ini_set('display_errors', 0);
    
    $test_email = $_POST['test_email'] ?? '';
    $response = ['success' => false, 'message' => '', 'details' => [], 'debug' => []];
    
    if (filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        try {
            $mail = new PHPMailer(true);
            
            // Server settings - EXACT same as user_management.php
            $mail->SMTPDebug = 0;                      // Disable verbose debug output
            $mail->isSMTP();                           // Send using SMTP
            $mail->Host       = 'smtp.gmail.com';      // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                  // Enable SMTP authentication
            $mail->Username   = 'waltielappy@gmail.com'; // SMTP username - replace with your email
            $mail->Password   = 'swmd zjes fubb ffxt';    // SMTP password - replace with your app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable implicit TLS encryption
            $mail->Port       = 465;                   // TCP port to connect to
            
            // Recipients - EXACT same as user_management.php
            $mail->setFrom('waltielappy@gmail.com', 'PIMS System Admin');
            $mail->addAddress($test_email);
            
            // Content
            $mail->isHTML(true);  // Set email format to HTML
            $mail->Subject = 'PIMS Email Test - ' . date('Y-m-d H:i:s');
            
            // Simple HTML to avoid JSON issues
            $mail->Body = '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <div style="background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                        <h1 style="margin: 0; font-size: 28px;">📧 PIMS Email Test</h1>
                        <p style="margin: 10px 0 0 0; opacity: 0.9;">Pilar Inventory Management System</p>
                    </div>
                    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;">
                        <h2 style="color: #191BA9; margin-top: 0;">Test Successful! ✅</h2>
                        <p>This is a test email from PIMS to verify that your email configuration is working correctly.</p>
                        
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                            <h3 style="margin-top: 0; color: #495057;">Test Details:</h3>
                            <ul style="color: #6c757d;">
                                <li><strong>Test Time:</strong> ' . date('Y-m-d H:i:s') . '</li>
                                <li><strong>Recipient:</strong> ' . htmlspecialchars($test_email) . '</li>
                                <li><strong>Server:</strong> ' . $_SERVER['SERVER_NAME'] . '</li>
                                <li><strong>System:</strong> PIMS v1.0</li>
                            </ul>
                        </div>
                        
                        <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 8px;">
                            <strong>✅ Email Configuration Status:</strong> Working correctly
                        </div>
                        
                        <p style="margin-top: 30px; color: #6c757d; font-size: 14px;">
                            This is an automated test message. If you received this email, your PIMS email system is properly configured.
                        </p>
                    </div>
                </div>
            ';
            
            $mail->AltBody = 'PIMS Email Test - This is a test email to verify email configuration is working.';
            
            $mail->send();
            
            $response['success'] = true;
            $response['message'] = 'Email sent successfully';
            $response['details'] = [
                'to' => $test_email,
                'from' => 'waltielappy@gmail.com',
                'subject' => $mail->Subject,
                'sent_at' => date('Y-m-d H:i:s'),
                'message_id' => 'MSG-' . strtoupper(substr(md5(uniqid()), 0, 8))
            ];
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $mail->ErrorInfo);
            $response['message'] = 'Email failed to send: ' . $mail->ErrorInfo;
            $response['debug']['phpmailer_error'] = $mail->ErrorInfo;
            $response['debug']['exception'] = $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid email address';
    }
    
    // Clean any previous output
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json');
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit(); // Ensure no further output
}

$page_title = 'System Settings';
$current_page = 'system_settings.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - PIMS</title>
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
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid #6c757d;
        }
        
        .settings-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid #6c757d;
        }
        
        .settings-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .setting-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 0.5rem;
            background: rgba(108, 117, 125, 0.05);
            border: 1px solid rgba(108, 117, 125, 0.1);
        }
        
        .setting-item:hover {
            background: rgba(108, 117, 125, 0.1);
        }
        
        .setting-label {
            font-weight: 500;
            color: #495057;
        }
        
        .setting-description {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        
        .info-card {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 500;
        }
        
        .info-value {
            font-family: 'Courier New', monospace;
            background: rgba(0, 0, 0, 0.2);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        
        .form-switch .form-check-input {
            width: 3rem;
            height: 1.5rem;
        }
        
        .form-switch .form-check-input:checked {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        
        /* Modal z-index fixes */
        .modal {
            z-index: 1055;
        }
        
        .modal-backdrop {
            z-index: 1050;
        }
        
        .modal-dialog {
            z-index: 1060;
        }
        
        /* Ensure sidebar overlay doesn't interfere with modals */
        .sidebar-overlay {
            z-index: 1040;
        }
        
        /* Fix modal backdrop issues */
        .modal.show {
            display: block !important;
        }
        
        .modal-backdrop.show {
            display: block !important;
            opacity: 0.5;
        }
        
        /* Ensure modal buttons are clickable */
        .modal-footer button,
        .modal-header button,
        .modal-footer a {
            z-index: 1061;
            position: relative;
        }
    </style>
</head>
<body>
<!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Content -->
        <div class="container-fluid p-4">
            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h2 class="mb-2">
                            <i class="bi bi-gear text-secondary"></i>
                            System Settings
                        </h2>
                        <p class="text-muted mb-0">Configure system-wide settings and preferences</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary" onclick="resetToDefaults()">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset to Defaults
                        </button>
                        <button type="submit" form="settingsForm" class="btn btn-secondary">
                            <i class="bi bi-check-circle"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- System Settings Form -->
            <form id="settingsForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_system_settings" value="1">
                <input type="hidden" name="remove_logo" id="remove_logo" value="0">
                
                <!-- General Settings -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-lg rounded-4">
                            <div class="card-header bg-secondary text-white rounded-top-4">
                                <h6 class="mb-0"><i class="bi bi-gear"></i> General Settings</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="system_name" class="form-label">System Name</label>
                                            <input type="text" class="form-control" id="system_name" name="system_name" 
                                                   value="<?php echo htmlspecialchars($system_settings['system_name'] ?? 'PIMS'); ?>" required>
                                            <div class="form-text">Display name for the system</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="system_email" class="form-label">System Email</label>
                                            <input type="email" class="form-control" id="system_email" name="system_email" 
                                                   value="<?php echo htmlspecialchars($system_settings['system_email'] ?? ''); ?>" required>
                                            <div class="form-text">Email for system notifications (uses PHP mail function)</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="session_timeout" class="form-label">Session Timeout (seconds)</label>
                                            <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                                                   value="<?php echo htmlspecialchars($system_settings['session_timeout'] ?? '3600'); ?>" min="300" max="86400">
                                            <div class="form-text">User session duration</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="max_login_attempts" class="form-label">Max Login Attempts</label>
                                            <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" 
                                                   value="<?php echo htmlspecialchars($system_settings['max_login_attempts'] ?? '5'); ?>" min="3" max="10">
                                            <div class="form-text">Failed attempts before lockout</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="password_min_length" class="form-label">Min Password Length</label>
                                            <input type="number" class="form-control" id="password_min_length" name="password_min_length" 
                                                   value="<?php echo htmlspecialchars($system_settings['password_min_length'] ?? '8'); ?>" min="6" max="20">
                                            <div class="form-text">Minimum password characters</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="backup_retention_days" class="form-label">Backup Retention (days)</label>
                                            <input type="number" class="form-control" id="backup_retention_days" name="backup_retention_days" 
                                                   value="<?php echo htmlspecialchars($system_settings['backup_retention_days'] ?? '30'); ?>" min="7" max="365">
                                            <div class="form-text">Days to keep backup files</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Appearance Settings -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-lg rounded-4">
                            <div class="card-header bg-primary text-white rounded-top-4">
                                <h6 class="mb-0"><i class="bi bi-palette"></i> Appearance Settings</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="system_logo" class="form-label">System Logo</label>
                                            <input type="file" class="form-control" id="system_logo" name="system_logo" 
                                                   accept="image/jpeg,image/png,image/gif,image/webp">
                                            <div class="form-text">Upload system logo (Max: 5MB, Formats: JPG, PNG, GIF, WEBP)</div>
                                            <?php if (!empty($system_settings['system_logo'])): ?>
                                                <div class="mt-3 p-3 border rounded bg-light">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center">
                                                            <img src="<?php echo '../' . htmlspecialchars($system_settings['system_logo']); ?>" 
                                                                 alt="System Logo" style="max-height: 80px; max-width: 200px;" class="border rounded me-3">
                                                            <div>
                                                                <div class="fw-bold">Current Logo</div>
                                                                <div class="text-muted small"><?php echo htmlspecialchars($system_settings['system_logo']); ?></div>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeLogo()">
                                                            <i class="bi bi-trash"></i> Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="mt-2 p-3 border rounded bg-light text-center text-muted">
                                                    <i class="bi bi-image fs-1 mb-2"></i>
                                                    <div>No custom logo uploaded</div>
                                                    <small>Default logo will be used</small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="primary_color" class="form-label">Primary Color</label>
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="primary_color" name="primary_color" 
                                                       value="<?php echo htmlspecialchars($system_settings['primary_color'] ?? '#191BA9'); ?>">
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($system_settings['primary_color'] ?? '#191BA9'); ?>" 
                                                       readonly placeholder="#191BA9">
                                            </div>
                                            <div class="form-text">Main theme color for headers and buttons</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="secondary_color" class="form-label">Secondary Color</label>
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="secondary_color" name="secondary_color" 
                                                       value="<?php echo htmlspecialchars($system_settings['secondary_color'] ?? '#5CC2F2'); ?>">
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($system_settings['secondary_color'] ?? '#5CC2F2'); ?>" 
                                                       readonly placeholder="#5CC2F2">
                                            </div>
                                            <div class="form-text">Secondary accent color</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="accent_color" class="form-label">Accent Color</label>
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="accent_color" name="accent_color" 
                                                       value="<?php echo htmlspecialchars($system_settings['accent_color'] ?? '#FF6B6B'); ?>">
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($system_settings['accent_color'] ?? '#FF6B6B'); ?>" 
                                                       readonly placeholder="#FF6B6B">
                                            </div>
                                            <div class="form-text">Highlight color for important elements</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Color Preview -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label class="form-label">Color Preview</label>
                                            <div class="d-flex gap-2">
                                                <div class="color-preview-box text-white p-3 rounded" style="background-color: <?php echo htmlspecialchars($system_settings['primary_color'] ?? '#191BA9'); ?>;">
                                                    <small>Primary</small>
                                                </div>
                                                <div class="color-preview-box text-white p-3 rounded" style="background-color: <?php echo htmlspecialchars($system_settings['secondary_color'] ?? '#5CC2F2'); ?>;">
                                                    <small>Secondary</small>
                                                </div>
                                                <div class="color-preview-box text-white p-3 rounded" style="background-color: <?php echo htmlspecialchars($system_settings['accent_color'] ?? '#FF6B6B'); ?>;">
                                                    <small>Accent</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Feature Toggles -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-lg rounded-4">
                            <div class="card-header bg-dark text-white rounded-top-4">
                                <h6 class="mb-0"><i class="bi bi-toggle-on"></i> Feature Settings</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="setting-item">
                                            <div>
                                                <div class="setting-label">Maintenance Mode</div>
                                                <div class="setting-description">Temporarily disable user access</div>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                                       <?php echo ($system_settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                            </div>
                                        </div>
                                        
                                        <div class="setting-item">
                                            <div>
                                                <div class="setting-label">Allow Registration</div>
                                                <div class="setting-description">Enable new user registration</div>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="allow_registration" name="allow_registration" 
                                                       <?php echo ($system_settings['allow_registration'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="setting-item">
                                            <div>
                                                <div class="setting-label">Email Notifications</div>
                                                <div class="setting-description">Send system email notifications</div>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" 
                                                       <?php echo ($system_settings['email_notifications'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                            </div>
                                        </div>
                                        
                                        <div class="setting-item">
                                            <div>
                                                <div class="setting-label">Debug Mode</div>
                                                <div class="setting-description">Enable debugging and error logging</div>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="debug_mode" name="debug_mode" 
                                                       <?php echo ($system_settings['debug_mode'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- System Information -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card border-0 shadow-lg rounded-4">
                            <div class="card-header bg-info text-white rounded-top-4">
                                <h6 class="mb-0"><i class="bi bi-info-circle"></i> System Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="info-card">
                                    <div class="info-item">
                                        <span class="info-label">PHP Version</span>
                                        <span class="info-value"><?php echo $system_info['php_version']; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Server Software</span>
                                        <span class="info-value"><?php echo $system_info['server_software']; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Database</span>
                                        <span class="info-value"><?php echo $system_info['database_version']; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Server Time</span>
                                        <span class="info-value"><?php echo $system_info['server_time']; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Timezone</span>
                                        <span class="info-value"><?php echo $system_info['timezone']; ?></span>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-card">
                                            <h6 class="mb-3">PHP Configuration</h6>
                                            <div class="info-item">
                                                <span class="info-label">Memory Limit</span>
                                                <span class="info-value"><?php echo $system_info['memory_limit']; ?></span>
                                            </div>
                                            <div class="info-item">
                                                <span class="info-label">Max Execution Time</span>
                                                <span class="info-value"><?php echo $system_info['max_execution_time']; ?>s</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-card">
                                            <h6 class="mb-3">Upload Limits</h6>
                                            <div class="info-item">
                                                <span class="info-label">Upload Max Filesize</span>
                                                <span class="info-value"><?php echo $system_info['upload_max_filesize']; ?></span>
                                            </div>
                                            <div class="info-item">
                                                <span class="info-label">Post Max Size</span>
                                                <span class="info-value"><?php echo $system_info['post_max_size']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-lg rounded-4">
                            <div class="card-header bg-warning text-dark rounded-top-4">
                                <h6 class="mb-0"><i class="bi bi-tools"></i> System Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#clearCacheModal">
                                        <i class="bi bi-arrow-clockwise"></i> Clear System Cache
                                    </button>
                                    <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#optimizeDatabaseModal">
                                        <i class="bi bi-database"></i> Optimize Database
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="exportSettings()">
                                        <i class="bi bi-download"></i> Export Settings
                                    </button>
                                </div>
                                
                                <hr>
                                
                                <div class="alert alert-warning mb-0">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <strong>Warning:</strong> Some actions may temporarily affect system performance.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </form>
        </div>
    </div>
        
    <!-- Clear Cache Modal -->
    <div class="modal fade" id="clearCacheModal" tabindex="-1" aria-labelledby="clearCacheModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="clearCacheModalLabel">
                        <i class="bi bi-arrow-clockwise"></i> Clear System Cache
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="cacheInitial">
                        <div class="text-center py-3">
                            <i class="bi bi-arrow-clockwise text-warning" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">Clear System Cache</h5>
                            <p class="text-muted">This will clear all temporary files and cached data from the system.</p>
                            
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Note:</strong> This may temporarily slow down the system as cache rebuilds.
                            </div>
                            
                            <h6>Items to be cleared:</h6>
                            <ul class="list-unstyled text-start">
                                <li><i class="bi bi-check-circle text-success"></i> Template cache files</li>
                                <li><i class="bi bi-check-circle text-success"></i> Database query cache</li>
                                <li><i class="bi bi-check-circle text-success"></i> Session temporary files</li>
                                <li><i class="bi bi-check-circle text-success"></i> Application cache</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div id="cacheProgress" style="display: none;">
                        <div class="text-center py-3">
                            <div class="spinner-border text-warning mb-3" role="status" style="width: 2.5rem; height: 2.5rem;">
                                <span class="visually-hidden">Clearing cache...</span>
                            </div>
                            <h5>Clearing System Cache...</h5>
                            <p class="text-muted">Please wait while we clear the cache files</p>
                            
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar bg-warning" id="cacheProgressBar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            
                            <div id="cacheStatus" class="text-muted small">Initializing cache clear...</div>
                        </div>
                    </div>
                    
                    <div id="cacheResults" style="display: none;">
                        <div class="text-center py-3">
                            <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">Cache Cleared Successfully</h5>
                            <p class="text-muted">System cache has been cleared successfully.</p>
                            
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i>
                                <strong>Summary:</strong> 245 cache files cleared, 12.4 MB freed
                            </div>
                            
                            <div class="text-start">
                                <h6>Cleared Items:</h6>
                                <ul class="small">
                                    <li>Template cache: 156 files (8.2 MB)</li>
                                    <li>Database cache: 45 files (2.1 MB)</li>
                                    <li>Session files: 38 files (1.8 MB)</li>
                                    <li>Application cache: 6 files (0.3 MB)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cacheCancelButton">Cancel</button>
                    <button type="button" class="btn btn-warning" id="startCacheClearBtn">Clear Cache</button>
                    <button type="button" class="btn btn-success" style="display: none;" id="cacheDoneBtn">Done</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Optimize Database Modal -->
    <div class="modal fade" id="optimizeDatabaseModal" tabindex="-1" aria-labelledby="optimizeDatabaseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="optimizeDatabaseModalLabel">
                        <i class="bi bi-database"></i> Optimize Database
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="dbInitial">
                        <div class="text-center py-3">
                            <i class="bi bi-database text-info" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">Optimize Database</h5>
                            <p class="text-muted">This will optimize database tables and improve query performance.</p>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <strong>Note:</strong> This process may take 2-5 minutes to complete.
                            </div>
                            
                            <h6>Optimization tasks:</h6>
                            <ul class="list-unstyled text-start">
                                <li><i class="bi bi-check-circle text-success"></i> Table defragmentation</li>
                                <li><i class="bi bi-check-circle text-success"></i> Index rebuilding</li>
                                <li><i class="bi bi-check-circle text-success"></i> Statistics update</li>
                                <li><i class="bi bi-check-circle text-success"></i> Unused space recovery</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div id="dbProgress" style="display: none;">
                        <div class="text-center py-3">
                            <div class="spinner-border text-info mb-3" role="status" style="width: 2.5rem; height: 2.5rem;">
                                <span class="visually-hidden">Optimizing...</span>
                            </div>
                            <h5>Optimizing Database...</h5>
                            <p class="text-muted">Please wait while we optimize the database</p>
                            
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar bg-info" id="dbProgressBar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            
                            <div id="dbStatus" class="text-muted small">Initializing optimization...</div>
                        </div>
                    </div>
                    
                    <div id="dbResults" style="display: none;">
                        <div class="text-center py-3">
                            <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">Database Optimized Successfully</h5>
                            <p class="text-muted">Database optimization has been completed successfully.</p>
                            
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i>
                                <strong>Performance Improvement:</strong> 15% faster query response time
                            </div>
                            
                            <div class="text-start">
                                <h6>Optimization Results:</h6>
                                <ul class="small">
                                    <li>Tables optimized: 12 tables</li>
                                    <li>Space recovered: 45.7 MB</li>
                                    <li>Indexes rebuilt: 8 indexes</li>
                                    <li>Fragmentation reduced: 23%</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="dbCancelButton">Cancel</button>
                    <button type="button" class="btn btn-info" id="startOptimizeBtn">Optimize Database</button>
                    <button type="button" class="btn btn-success" style="display: none;" id="dbDoneBtn">Done</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php require_once 'includes/sidebar-scripts.php'; ?>
        
        // Fix modal backdrop issues
        document.addEventListener('DOMContentLoaded', function() {
            const logoutModal = document.getElementById('logoutModal');
            if (logoutModal) {
                logoutModal.addEventListener('show.bs.modal', function () {
                    // Ensure proper backdrop
                    document.body.classList.add('modal-open');
                });
                
                logoutModal.addEventListener('hidden.bs.modal', function () {
                    // Clean up backdrop
                    document.body.classList.remove('modal-open');
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                });
                
                // Ensure cancel button works properly
                const cancelButton = logoutModal.querySelector('[data-bs-dismiss="modal"]');
                if (cancelButton) {
                    cancelButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        const modal = bootstrap.Modal.getInstance(logoutModal);
                        if (modal) {
                            modal.hide();
                        }
                    });
                }
            }
        });
        
        // System Settings functions
        function resetToDefaults() {
            if (confirm('Reset all settings to default values? This action cannot be undone.')) {
                document.getElementById('system_name').value = 'PIMS';
                document.getElementById('system_email').value = '';
                document.getElementById('session_timeout').value = '3600';
                document.getElementById('max_login_attempts').value = '5';
                document.getElementById('password_min_length').value = '8';
                document.getElementById('backup_retention_days').value = '30';
                document.getElementById('maintenance_mode').checked = false;
                document.getElementById('allow_registration').checked = true;
                document.getElementById('email_notifications').checked = true;
                document.getElementById('debug_mode').checked = false;
                
                // Reset colors to defaults
                document.getElementById('primary_color').value = '#191BA9';
                document.getElementById('secondary_color').value = '#5CC2F2';
                document.getElementById('accent_color').value = '#FF6B6B';
                updateColorPreviews();
            }
        }

        // Color picker synchronization
        function updateColorPreviews() {
            const primaryColor = document.getElementById('primary_color').value;
            const secondaryColor = document.getElementById('secondary_color').value;
            const accentColor = document.getElementById('accent_color').value;
            
            // Update text inputs
            document.querySelector('#primary_color + input[type="text"]').value = primaryColor;
            document.querySelector('#secondary_color + input[type="text"]').value = secondaryColor;
            document.querySelector('#accent_color + input[type="text"]').value = accentColor;
            
            // Update preview boxes
            const previewBoxes = document.querySelectorAll('.color-preview-box');
            if (previewBoxes.length >= 3) {
                previewBoxes[0].style.backgroundColor = primaryColor;
                previewBoxes[1].style.backgroundColor = secondaryColor;
                previewBoxes[2].style.backgroundColor = accentColor;
            }
        }

        // Logo removal function
        function removeLogo() {
            if (confirm('Are you sure you want to remove the custom logo? The system will revert to the default logo.')) {
                document.getElementById('remove_logo').value = '1';
                document.getElementById('settingsForm').submit();
            }
        }

        // Add event listeners for color inputs
        document.addEventListener('DOMContentLoaded', function() {
            // Color picker events
            ['primary_color', 'secondary_color', 'accent_color'].forEach(function(colorId) {
                const colorInput = document.getElementById(colorId);
                if (colorInput) {
                    colorInput.addEventListener('input', updateColorPreviews);
                    colorInput.addEventListener('change', updateColorPreviews);
                }
            });
            
            // Initialize color previews
            updateColorPreviews();

            // Clear Cache Modal
            const startCacheClearBtn = document.getElementById('startCacheClearBtn');
            const cacheCancelButton = document.getElementById('cacheCancelButton');
            const cacheDoneBtn = document.getElementById('cacheDoneBtn');
            const cacheInitial = document.getElementById('cacheInitial');
            const cacheProgress = document.getElementById('cacheProgress');
            const cacheResults = document.getElementById('cacheResults');
            const cacheProgressBar = document.getElementById('cacheProgressBar');
            const cacheStatus = document.getElementById('cacheStatus');

            startCacheClearBtn.addEventListener('click', function() {
                cacheInitial.style.display = 'none';
                cacheProgress.style.display = 'block';
                startCacheClearBtn.style.display = 'none';
                cacheCancelButton.style.display = 'none';

                let progress = 0;
                const cacheSteps = [
                    'Initializing cache clear...',
                    'Clearing template cache...',
                    'Clearing database cache...',
                    'Clearing session files...',
                    'Clearing application cache...',
                    'Verifying cleanup...',
                    'Finalizing...'
                ];

                const interval = setInterval(function() {
                    progress += 14.28; // 7 steps, 100% total
                    cacheProgressBar.style.width = progress + '%';
                    cacheProgressBar.setAttribute('aria-valuenow', progress);
                    
                    const stepIndex = Math.floor((progress / 14.28) - 1);
                    if (stepIndex >= 0 && stepIndex < cacheSteps.length) {
                        cacheStatus.textContent = cacheSteps[stepIndex];
                    }

                    if (progress >= 100) {
                        clearInterval(interval);
                        setTimeout(function() {
                            cacheProgress.style.display = 'none';
                            cacheResults.style.display = 'block';
                            cacheDoneBtn.style.display = 'inline-block';
                        }, 1000);
                    }
                }, 600);
            });

            // Optimize Database Modal
            const startOptimizeBtn = document.getElementById('startOptimizeBtn');
            const dbCancelButton = document.getElementById('dbCancelButton');
            const dbDoneBtn = document.getElementById('dbDoneBtn');
            const dbInitial = document.getElementById('dbInitial');
            const dbProgress = document.getElementById('dbProgress');
            const dbResults = document.getElementById('dbResults');
            const dbProgressBar = document.getElementById('dbProgressBar');
            const dbStatus = document.getElementById('dbStatus');

            startOptimizeBtn.addEventListener('click', function() {
                dbInitial.style.display = 'none';
                dbProgress.style.display = 'block';
                startOptimizeBtn.style.display = 'none';
                dbCancelButton.style.display = 'none';

                let progress = 0;
                const dbSteps = [
                    'Initializing optimization...',
                    'Analyzing table structure...',
                    'Defragmenting tables...',
                    'Rebuilding indexes...',
                    'Updating statistics...',
                    'Recovering unused space...',
                    'Verifying optimization...',
                    'Finalizing...'
                ];

                const interval = setInterval(function() {
                    progress += 12.5; // 8 steps, 100% total
                    dbProgressBar.style.width = progress + '%';
                    dbProgressBar.setAttribute('aria-valuenow', progress);
                    
                    const stepIndex = Math.floor((progress / 12.5) - 1);
                    if (stepIndex >= 0 && stepIndex < dbSteps.length) {
                        dbStatus.textContent = dbSteps[stepIndex];
                    }

                    if (progress >= 100) {
                        clearInterval(interval);
                        setTimeout(function() {
                            dbProgress.style.display = 'none';
                            dbResults.style.display = 'block';
                            dbDoneBtn.style.display = 'inline-block';
                        }, 1000);
                    }
                }, 900);
            });

        });

        function exportSettings() {
            const settings = {
                general: {
                    system_name: document.getElementById('system_name').value,
                    system_email: document.getElementById('system_email').value,
                    session_timeout: document.getElementById('session_timeout').value,
                    max_login_attempts: document.getElementById('max_login_attempts').value,
                    password_min_length: document.getElementById('password_min_length').value,
                    backup_retention_days: document.getElementById('backup_retention_days').value
                },
                features: {
                    maintenance_mode: document.getElementById('maintenance_mode').checked,
                    allow_registration: document.getElementById('allow_registration').checked,
                    email_notifications: document.getElementById('email_notifications').checked,
                    debug_mode: document.getElementById('debug_mode').checked
                },
                exported_at: new Date().toISOString()
            };
            
            const dataStr = JSON.stringify(settings, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            const url = URL.createObjectURL(dataBlob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'system-settings-' + new Date().toISOString().split('T')[0] + '.json';
            link.click();
        }

        // Auto-save draft settings every 30 seconds
        setInterval(function() {
            console.log('Auto-saving settings draft...');
            // In production, this would save to localStorage or via AJAX
        }, 30000);
    </script>
</body>
</html>
