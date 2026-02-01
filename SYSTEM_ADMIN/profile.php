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
if ($_SESSION['role'] !== 'system_admin' && $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../config.php';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    $errors = [];
    
    // Validation
    if (empty($first_name)) $errors[] = 'First name is required';
    if (empty($last_name)) $errors[] = 'Last name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
    
    // Check if email is already taken by another user
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = 'Email is already taken by another user';
        }
        $stmt->close();
    }
    
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone, $address, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $success_message = 'Profile updated successfully!';
                // Update session variables
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['email'] = $email;
            } else {
                $error_message = 'Failed to update profile';
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            $error_message = 'Database error occurred';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validation
    if (empty($current_password)) $errors[] = 'Current password is required';
    if (empty($new_password)) $errors[] = 'New password is required';
    if (strlen($new_password) < 6) $errors[] = 'New password must be at least 6 characters';
    if ($new_password !== $confirm_password) $errors[] = 'Passwords do not match';
    
    // Verify current password
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if (!password_verify($current_password, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect';
        }
    }
    
    if (empty($errors)) {
        try {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("si", $new_password_hash, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $password_success = 'Password changed successfully!';
            } else {
                $password_error = 'Failed to change password';
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            $password_error = 'Database error occurred';
        }
    }
}

// Get user profile data
$user_data = null;
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
}

// Get user activity logs
$recent_logs = [];
try {
    $stmt = $conn->prepare("
        SELECT action, module, details, created_at, ip_address 
        FROM system_logs 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $recent_logs[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching user logs: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - PIMS</title>
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
        
        .profile-header {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 30px;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #191BA9;
            margin: 0 auto 1rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border-left: 4px solid #191BA9;
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
        
        .nav-tabs .nav-link {
            border-radius: 10px 10px 0 0;
            border: none;
            color: #6c757d;
            font-weight: 600;
        }
        
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
        }
        
        .activity-item {
            padding: 1rem;
            border-left: 3px solid #191BA9;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-radius: 0 10px 10px 0;
        }
        
        .role-badge {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
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
        
        /* Remove scrollbar from sidebar */
        .sidebar {
            overflow: hidden;
        }
        
        .sidebar * {
            scrollbar-width: none; /* Firefox */
        }
        
        .sidebar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Edge */
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
    <?php
    // Set page title for topbar
    $page_title = 'My Profile';
    ?>
    <!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
        <!-- Main Content -->
        <div class="main-content">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center">
                            <div class="profile-avatar">
                                <i class="bi bi-person-fill"></i>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h1 class="mb-2"><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></h1>
                            <p class="mb-2"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user_data['email']); ?></p>
                            <span class="role-badge"><?php echo htmlspecialchars($user_data['role']); ?></span>
                        </div>
                        <div class="col-md-3 text-md-end">
                            <div class="text-white">
                                <small>Member Since</small><br>
                                <strong><?php echo date('M j, Y', strtotime($user_data['created_at'])); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="container">
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
                
                <?php if (isset($password_success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i>
                        <?php echo htmlspecialchars($password_success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($password_error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($password_error); ?>
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
                
                <!-- Profile Tabs -->
                <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-content" type="button" role="tab">
                            <i class="bi bi-person"></i> Profile Information
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security-content" type="button" role="tab">
                            <i class="bi bi-shield-lock"></i> Security
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity-content" type="button" role="tab">
                            <i class="bi bi-clock-history"></i> Recent Activity
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="profileTabsContent">
                    <!-- Profile Information Tab -->
                    <div class="tab-pane fade show active" id="profile-content" role="tabpanel">
                        <div class="profile-card">
                            <h4 class="mb-4"><i class="bi bi-person-circle"></i> Personal Information</h4>
                            <form method="POST" action="profile.php">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="first_name" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                                   value="<?php echo htmlspecialchars($user_data['first_name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="last_name" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                                   value="<?php echo htmlspecialchars($user_data['last_name']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>"
                                           placeholder="+1 (555) 123-4567">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"
                                              placeholder="Enter your address"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['username']); ?>" readonly>
                                            <small class="text-muted">Username cannot be changed</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Role</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['role']); ?>" readonly>
                                            <small class="text-muted">Role assigned by administrator</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Security Tab -->
                    <div class="tab-pane fade" id="security-content" role="tabpanel">
                        <div class="profile-card">
                            <h4 class="mb-4"><i class="bi bi-shield-lock"></i> Change Password</h4>
                            <form method="POST" action="profile.php">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" 
                                           minlength="6" required>
                                    <small class="text-muted">Password must be at least 6 characters long</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           minlength="6" required>
                                </div>
                                
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="bi bi-shield-check"></i> Change Password
                                </button>
                            </form>
                        </div>
                        
                        <div class="profile-card">
                            <h4 class="mb-4"><i class="bi bi-info-circle"></i> Security Information</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Last Login:</strong><br>
                                    <span class="text-muted">
                                        <?php 
                                        // Get last login from logs if available
                                        $last_login = 'Unknown';
                                        try {
                                            $stmt = $conn->prepare("
                                                SELECT created_at FROM system_logs 
                                                WHERE user_id = ? AND action = 'login' 
                                                ORDER BY created_at DESC LIMIT 1
                                            ");
                                            $stmt->bind_param("i", $_SESSION['user_id']);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            if ($row = $result->fetch_assoc()) {
                                                $last_login = date('M j, Y H:i:s', strtotime($row['created_at']));
                                            }
                                            $stmt->close();
                                        } catch (Exception $e) {
                                            // Ignore errors
                                        }
                                        echo $last_login;
                                        ?>
                                    </span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Account Status:</strong><br>
                                    <span class="badge bg-<?php echo $user_data['is_active'] ? 'success' : 'danger'; ?>">
                                        <?php echo $user_data['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity Tab -->
                    <div class="tab-pane fade" id="activity-content" role="tabpanel">
                        <div class="profile-card">
                            <h4 class="mb-4"><i class="bi bi-clock-history"></i> Recent Activity</h4>
                            
                            <?php if (empty($recent_logs)): ?>
                                <p class="text-muted">No recent activity found.</p>
                            <?php else: ?>
                                <?php foreach ($recent_logs as $log): ?>
                                    <div class="activity-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?php echo htmlspecialchars(ucfirst($log['action'])); ?></strong>
                                                <?php if ($log['module']): ?>
                                                    <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($log['module']); ?></span>
                                                <?php endif; ?>
                                                <?php if ($log['details']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($log['details']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y H:i:s', strtotime($log['created_at'])); ?>
                                            </small>
                                        </div>
                                        <?php if ($log['ip_address']): ?>
                                            <small class="text-muted">
                                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($log['ip_address']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
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
        
        // Password confirmation validation
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Form validation feedback
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
        
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
    </script>
</body>
</html>
