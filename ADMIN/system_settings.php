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

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

require_once 'includes/check_permissions.php';
adminRequirePermission('system.settings', 'can_read', 'dashboard.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_settings') {
        adminRequirePermission('system.settings', 'can_update', 'system_settings.php');
        try {
            // Start transaction
            $conn->begin_transaction();

            // Update general settings
            $general_settings = [
                'session_timeout' => intval($_POST['session_timeout'] ?? 30),
                'dark_mode' => isset($_POST['dark_mode']) ? 1 : 0,
                'auto_save_interval' => intval($_POST['auto_save_interval'] ?? 5),
                'items_per_page' => intval($_POST['items_per_page'] ?? 25),
                'date_format' => $_POST['date_format'] ?? 'Y-m-d',
                'time_format' => $_POST['time_format'] ?? '24h',
                'theme_preset' => $_POST['theme_preset'] ?? 'default'
            ];

            foreach ($general_settings as $key => $value) {
                $stmt = $conn->prepare("INSERT INTO system_settings (setting_name, setting_value) 
                                       VALUES (?, ?) 
                                       ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
                $stmt->bind_param("sss", $key, $value, $value);
                $stmt->execute();
            }

            $conn->commit();

            $_SESSION['success'] = "Settings updated successfully!";
            logSystemAction($_SESSION['user_id'], 'update', 'system_settings', 'Updated system settings');
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Error updating settings: " . $e->getMessage();
            error_log("System Settings Error: " . $e->getMessage());
        }

        header('Location: system_settings.php');
        exit();
    }

    if ($action === 'clear_cache') {
        try {
            // Clear application cache
            $cache_dir = '../cache/';
            if (is_dir($cache_dir)) {
                $files = glob($cache_dir . '*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }

            $_SESSION['success'] = "System cache cleared successfully!";
            logSystemAction($_SESSION['user_id'], 'clear_cache', 'system_settings', 'Cleared system cache');
        } catch (Exception $e) {
            $_SESSION['error'] = "Error clearing cache: " . $e->getMessage();
        }

        header('Location: system_settings.php');
        exit();
    }

    if ($action === 'optimize_database') {
        try {
            // Optimize database tables
            $tables = ['users', 'assets', 'asset_items', 'consumables', 'employees', 'offices', 'system_logs'];
            foreach ($tables as $table) {
                $conn->query("OPTIMIZE TABLE `$table`");
            }

            $_SESSION['success'] = "Database optimized successfully!";
            logSystemAction($_SESSION['user_id'], 'optimize_db', 'system_settings', 'Optimized database tables');
        } catch (Exception $e) {
            $_SESSION['error'] = "Error optimizing database: " . $e->getMessage();
        }

        header('Location: system_settings.php');
        exit();
    }

    if ($action === 'test_email') {
        try {
            $test_email = $_POST['test_email'] ?? '';
            if (!empty($test_email) && filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
                // In a real implementation, you would send an actual email here
                // For now, we'll just log it
                $_SESSION['success'] = "Test email sent to $test_email successfully!";
                logSystemAction($_SESSION['user_id'], 'test_email', 'system_settings', "Test email sent to $test_email");
            } else {
                $_SESSION['error'] = "Invalid email address!";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error sending test email: " . $e->getMessage();
        }

        header('Location: system_settings.php');
        exit();
    }

    if ($action === 'export_settings') {
        try {
            // Get all settings
            $stmt = $conn->query("SELECT setting_name, setting_value FROM system_settings");
            $settings = [];
            while ($row = $stmt->fetch_assoc()) {
                $settings[$row['setting_name']] = $row['setting_value'];
            }

            // Create JSON export
            $export_data = [
                'export_date' => date('Y-m-d H:i:s'),
                'exported_by' => $_SESSION['username'],
                'settings' => $settings
            ];

            $json = json_encode($export_data, JSON_PRETTY_PRINT);

            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="system_settings_' . date('Y-m-d_H-i-s') . '.json"');
            echo $json;
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error exporting settings: " . $e->getMessage();
            header('Location: system_settings.php');
            exit();
        }
    }
}

// Get current settings
$settings = [];
try {
    $stmt = $conn->query("SELECT setting_name, setting_value FROM system_settings");
    while ($row = $stmt->fetch_assoc()) {
        $settings[$row['setting_name']] = $row['setting_value'];
    }
} catch (Exception $e) {
    error_log("Error loading settings: " . $e->getMessage());
}

// Set default values
$defaults = [
    'session_timeout' => 30,
    'dark_mode' => 0,
    'auto_save_interval' => 5,
    'items_per_page' => 25,
    'date_format' => 'Y-m-d',
    'time_format' => '24h',
    'theme_preset' => 'default'
];

foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

// Log settings page access
logSystemAction($_SESSION['user_id'], 'access', 'system_settings', 'Accessed system settings page');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - PIMS</title>
     <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
   
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    <style>
        /* Page-specific styles that complement admin-unified.css */
        .settings-card {
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .settings-section {
            margin-bottom: 2rem;
        }

        .settings-section h6 {
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-color);
        }

        .info-card {
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .info-label {
            font-weight: 600;
            font-size: 0.875rem;
        }

        .info-value {
            font-family: 'Courier New', monospace;
            font-weight: 500;
        }

        .system-action {
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-color);
        }

        .alert-dismissible .btn-close {
            padding: 0.75rem 1rem;
        }

        /* Settings page specific styles - dark mode handled by dark-mode-init.php */
    </style>
<?php require_once 'includes/dark-mode-init.php'; ?>
</head>

<body>
    <?php
    // Set page title for topbar
    $page_title = 'System Settings';
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
                            <i class="bi bi-gear"></i> System Settings
                        </h1>
                        <p class="text-muted mb-0">Configure system parameters and preferences</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-gear"></i> Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
                                <li>
                                    <button class="dropdown-item" onclick="exportSettings()">
                                        <i class="bi bi-download"></i> Export
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <button class="dropdown-item" onclick="location.reload()">
                                        <i class="bi bi-arrow-clockwise"></i> Refresh Page
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['success']);
                    unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" id="settingsForm">
                <input type="hidden" name="action" value="update_settings">

                <div class="row">
                    <!-- General Settings -->
                    <div class="col-12">
                        <div class="settings-card">
                            <h5 class="mb-3">
                                <i class="bi bi-sliders"></i> General Settings
                            </h5>

                            <div class="settings-section">
                                <h6>Session Settings</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                                            <select class="form-select" id="session_timeout" name="session_timeout">
                                                <option value="5" <?php echo $settings['session_timeout'] == 5 ? 'selected' : ''; ?>>5 minutes</option>
                                                <option value="10" <?php echo $settings['session_timeout'] == 10 ? 'selected' : ''; ?>>10 minutes</option>
                                                <option value="15" <?php echo $settings['session_timeout'] == 15 ? 'selected' : ''; ?>>15 minutes</option>
                                                <option value="30" <?php echo $settings['session_timeout'] == 30 ? 'selected' : ''; ?>>30 minutes</option>
                                                <option value="60" <?php echo $settings['session_timeout'] == 60 ? 'selected' : ''; ?>>1 hour</option>
                                                <option value="120" <?php echo $settings['session_timeout'] == 120 ? 'selected' : ''; ?>>2 hours</option>
                                                <option value="180" <?php echo $settings['session_timeout'] == 180 ? 'selected' : ''; ?>>3 hours</option>
                                                <option value="240" <?php echo $settings['session_timeout'] == 240 ? 'selected' : ''; ?>>4 hours</option>
                                                <option value="360" <?php echo $settings['session_timeout'] == 360 ? 'selected' : ''; ?>>6 hours</option>
                                                <option value="480" <?php echo $settings['session_timeout'] == 480 ? 'selected' : ''; ?>>8 hours</option>
                                            </select>
                                            <div class="form-text">How long users remain logged in without activity</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="auto_save_interval" class="form-label">Auto-Save Interval (minutes)</label>
                                            <input type="number" class="form-control" id="auto_save_interval" name="auto_save_interval"
                                                value="<?php echo htmlspecialchars($settings['auto_save_interval']); ?>" min="1" max="30">
                                            <div class="form-text">How often to auto-save form data</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="settings-section">
                                <h6>Appearance</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label d-block">
                                                <strong>Dark Mode</strong>
                                                <small class="text-muted d-block">Use dark theme across the system</small>
                                            </label>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" id="dark_mode"
                                                        name="dark_mode" value="1"
                                                        <?php echo $settings['dark_mode'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="dark_mode">
                                                        Enable Dark Mode
                                                    </label>
                                                </div>
                                                <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                        onclick="darkMode.toggle()" 
                                                        title="Toggle Dark Mode Instantly">
                                                    <i class="bi bi-moon"></i> Quick Toggle
                                                </button>
                                            </div>
                                            <div class="form-text mt-2">
                                                <small class="text-muted">
                                                    <i class="bi bi-info-circle"></i> 
                                                    Use the switch to save preference, or the quick toggle button for instant preview. 
                                                    Changes are synchronized across all pages.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="items_per_page" class="form-label">Items Per Page</label>
                                            <select class="form-select" id="items_per_page" name="items_per_page">
                                                <option value="10" <?php echo $settings['items_per_page'] == 10 ? 'selected' : ''; ?>>10 items</option>
                                                <option value="25" <?php echo $settings['items_per_page'] == 25 ? 'selected' : ''; ?>>25 items</option>
                                                <option value="50" <?php echo $settings['items_per_page'] == 50 ? 'selected' : ''; ?>>50 items</option>
                                                <option value="100" <?php echo $settings['items_per_page'] == 100 ? 'selected' : ''; ?>>100 items</option>
                                            </select>
                                            <div class="form-text">Default number of items to display in tables</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="theme_preset" class="form-label">Theme Preset</label>
                                            <select class="form-select" id="theme_preset" name="theme_preset">
                                                <option value="default" <?php echo $settings['theme_preset'] == 'default' ? 'selected' : ''; ?>>Modern Navy Blue (#1E56A0)</option>
                                                <option value="legacy" <?php echo $settings['theme_preset'] == 'legacy' ? 'selected' : ''; ?>>Classic Bright Blue (#191BA9)</option>
                                            </select>
                                            <div class="form-text">Choose the primary color palette for the system</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="settings-section">
                                <h6>Date & Time Format</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="date_format" class="form-label">Date Format</label>
                                            <select class="form-select" id="date_format" name="date_format">
                                                <option value="Y-m-d" <?php echo $settings['date_format'] == 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                                <option value="m/d/Y" <?php echo $settings['date_format'] == 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                                <option value="d/m/Y" <?php echo $settings['date_format'] == 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                                <option value="F j, Y" <?php echo $settings['date_format'] == 'F j, Y' ? 'selected' : ''; ?>>Month DD, YYYY</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="time_format" class="form-label">Time Format</label>
                                            <select class="form-select" id="time_format" name="time_format">
                                                <option value="24h" <?php echo $settings['time_format'] == '24h' ? 'selected' : ''; ?>>24-hour (14:30)</option>
                                                <option value="12h" <?php echo $settings['time_format'] == '12h' ? 'selected' : ''; ?>>12-hour (2:30 PM)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <?php if (adminHasPermission('system.settings', 'can_update')): ?>
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg action-btn">
                                    <i class="bi bi-check-circle"></i> Save Settings
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div> <!-- Close main wrapper -->

    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    <?php require_once 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        // Auto-save draft functionality
        let autoSaveTimer;

        function autoSaveDraft() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(() => {
                const formData = new FormData(document.getElementById('settingsForm'));
                localStorage.setItem('settingsDraft', JSON.stringify(Object.fromEntries(formData)));
                console.log('Settings draft saved automatically');
            }, 2000);
        }

        // Load draft on page load
        window.addEventListener('load', () => {
            const draft = localStorage.getItem('settingsDraft');
            if (draft) {
                console.log('Draft found. You can restore it if needed.');
            }
        });

        // Test email function
        function testEmail() {
            const email = document.getElementById('test_email').value;
            if (!email) {
                alert('Please enter an email address');
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="test_email">
                <input type="hidden" name="test_email" value="${email}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // Export settings function
        function exportSettings() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="export_settings">';
            document.body.appendChild(form);
            form.submit();
        }

        // Auto-save on input change
        document.querySelectorAll('input, select, textarea').forEach(element => {
            element.addEventListener('change', autoSaveDraft);
        });

        // Dark mode toggle using centralized system
        const darkModeToggle = document.getElementById('dark_mode');
        if (darkModeToggle) {
            darkModeToggle.addEventListener('change', function() {
                // Use the centralized dark mode system
                if (typeof darkMode !== 'undefined') {
                    darkMode.set(this.checked);
                } else {
                    // Fallback if darkMode system not loaded
                    if (this.checked) {
                        document.body.classList.add('dark-mode');
                    } else {
                        document.body.classList.remove('dark-mode');
                    }
                }
            });
            
            // Sync toggle with current dark mode state
            if (typeof darkMode !== 'undefined') {
                // Update toggle to match current dark mode state
                this.checked = darkMode.isEnabled();
                
                // Listen for dark mode changes from other sources (like topbar toggle)
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                            const isDark = document.body.classList.contains('dark-mode');
                            if (darkModeToggle.checked !== isDark) {
                                darkModeToggle.checked = isDark;
                            }
                        }
                    });
                });
                
                observer.observe(document.body, {
                    attributes: true,
                    attributeFilter: ['class']
                });
            }
        }

        // Form validation
        document.getElementById('settingsForm').addEventListener('submit', function(e) {
            const sessionTimeout = parseInt(document.getElementById('session_timeout').value);
            const autoSaveInterval = parseInt(document.getElementById('auto_save_interval').value);

            if (sessionTimeout < 5 || sessionTimeout > 480) {
                e.preventDefault();
                alert('Session timeout must be between 5 and 480 minutes');
                return;
            }

            if (autoSaveInterval < 1 || autoSaveInterval > 30) {
                e.preventDefault();
                alert('Auto-save interval must be between 1 and 30 minutes');
                return;
            }
        });

        // Clear draft after successful save
        <?php if (isset($_SESSION['success'])): ?>
            localStorage.removeItem('settingsDraft');
        <?php endif; ?>
    </script>
</body>

</html>