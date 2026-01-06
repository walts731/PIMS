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
require_once '../includes/logger.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Require PHPMailer autoloader
require_once 'PHPMailer/PHPMailer-7.0.0/src/Exception.php';
require_once 'PHPMailer/PHPMailer-7.0.0/src/PHPMailer.php';
require_once 'PHPMailer/PHPMailer-7.0.0/src/SMTP.php';

// Log user management page access
logSystemAction($_SESSION['user_id'], 'access', 'user_management', 'System admin accessed user management page');

// Handle form submissions
$message = '';
$message_type = '';

// Function to send welcome email
function sendWelcomeEmail($to_email, $first_name, $last_name, $username, $password) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->SMTPDebug = 0;                      // Disable verbose debug output
        $mail->isSMTP();                           // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';      // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                  // Enable SMTP authentication
        $mail->Username   = 'waltielappy@gmail.com'; // SMTP username - replace with your email
        $mail->Password   = 'swmd zjes fubb ffxt';    // SMTP password - replace with your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable implicit TLS encryption
        $mail->Port       = 465;                   // TCP port to connect to
        
        // Recipients
        $mail->setFrom('waltielappy@gmail.com', 'PIMS System Admin');
        $mail->addAddress($to_email, $first_name . ' ' . $last_name);
        
        // Content
        $mail->isHTML(true);  // Set email format to HTML
        $mail->Subject = 'Welcome to PIMS - Your Account Credentials';
        
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .credential-box { background: white; padding: 20px; border-left: 4px solid #191BA9; margin: 20px 0; border-radius: 5px; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                .btn { display: inline-block; padding: 12px 24px; background: #191BA9; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to PIMS</h1>
                    <p>Pilar Inventory Management System</p>
                </div>
                <div class='content'>
                    <p>Dear <strong>" . htmlspecialchars($first_name) . " " . htmlspecialchars($last_name) . "</strong>,</p>
                    <p>Your account has been successfully created in the Pilar Inventory Management System. Below are your login credentials:</p>
                    
                    <div class='credential-box'>
                        <h3>Your Login Credentials</h3>
                        <p><strong>Email:</strong> " . htmlspecialchars($username) . "</p>
                        <p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>
                        <p><strong>Login URL:</strong> <a href='http://localhost/pims'>http://localhost/pims</a></p>
                    </div>
                    
                    <p><strong>Important Security Notes:</strong></p>
                    <ul>
                        <li>Please change your password after your first login</li>
                        <li>Never share your credentials with anyone</li>
                        <li>Keep your password secure and confidential</li>
                        <li>Log out after each session, especially on shared computers</li>
                    </ul>
                    
                    <p>If you have any questions or issues, please contact the system administrator.</p>
                    
                    <a href='http://localhost/pims' class='btn'>Login to PIMS</a>
                    
                    <p>Best regards,<br>PIMS System Administrator</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p>&copy; " . date('Y') . " Pilar Inventory Management System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->AltBody = "
        Welcome to PIMS - Pilar Inventory Management System
        
        Dear " . $first_name . " " . $last_name . ",
        
        Your account has been successfully created. Below are your login credentials:
        
        Email: " . $username . "
        Password: " . $password . "
        Login URL: http://localhost/pims
        
        Important Security Notes:
        - Please change your password after your first login
        - Never share your credentials with anyone
        - Keep your password secure and confidential
        - Log out after each session, especially on shared computers
        
        If you have any questions or issues, please contact the system administrator.
        
        Best regards,
        PIMS System Administrator
        ";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Function to send user update notification email
function sendUserUpdateEmail($to_email, $first_name, $last_name, $username, $role) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->SMTPDebug = 0;                      // Disable verbose debug output
        $mail->isSMTP();                           // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';      // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                  // Enable SMTP authentication
        $mail->Username   = 'waltielappy@gmail.com'; // SMTP username - replace with your email
        $mail->Password   = 'swmd zjes fubb ffxt';    // SMTP password - replace with your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable implicit TLS encryption
        $mail->Port       = 465;                   // TCP port to connect to
        
        // Recipients
        $mail->setFrom('waltielappy@gmail.com', 'PIMS System Admin');
        $mail->addAddress($to_email, $first_name . ' ' . $last_name);
        
        // Content
        $mail->isHTML(true);  // Set email format to HTML
        $mail->Subject = 'Your PIMS Account Has Been Updated';
        
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .update-box { background: white; padding: 20px; border-left: 4px solid #ffc107; margin: 20px 0; border-radius: 5px; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                .btn { display: inline-block; padding: 12px 24px; background: #191BA9; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Account Updated</h1>
                    <p>Pilar Inventory Management System</p>
                </div>
                <div class='content'>
                    <p>Dear <strong>" . htmlspecialchars($first_name) . " " . htmlspecialchars($last_name) . "</strong>,</p>
                    <p>Your account information has been updated in the Pilar Inventory Management System. Below are your current account details:</p>
                    
                    <div class='update-box'>
                        <h3>Your Updated Account Information</h3>
                        <p><strong>Email:</strong> " . htmlspecialchars($username) . "</p>
                        <p><strong>Role:</strong> " . htmlspecialchars(str_replace('_', ' ', $role)) . "</p>
                        <p><strong>Login URL:</strong> <a href='http://localhost/pims'>http://localhost/pims</a></p>
                    </div>
                    
                    <p><strong>Important Notes:</strong></p>
                    <ul>
                        <li>Your login credentials may have been updated</li>
                        <li>Please use your current email address for login</li>
                        <li>If you didn't request these changes, please contact the system administrator immediately</li>
                        <li>Keep your login information secure and confidential</li>
                    </ul>
                    
                    <p>If you have any questions or concerns about these changes, please contact the system administrator.</p>
                    
                    <a href='http://localhost/pims' class='btn'>Login to PIMS</a>
                    
                    <p>Best regards,<br>PIMS System Administrator</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p>&copy; " . date('Y') . " Pilar Inventory Management System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->AltBody = "
        Your PIMS Account Has Been Updated
        
        Dear " . $first_name . " " . $last_name . ",
        
        Your account information has been updated in the Pilar Inventory Management System. Below are your current account details:
        
        Email: " . $username . "
        Role: " . str_replace('_', ' ', $role) . "
        Login URL: http://localhost/pims
        
        Important Notes:
        - Your login credentials may have been updated
        - Please use your current email address for login
        - If you didn't request these changes, please contact the system administrator immediately
        - Keep your login information secure and confidential
        
        If you have any questions or concerns about these changes, please contact the system administrator.
        
        Best regards,
        PIMS System Administrator
        ";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Update email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Add new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $email = trim($_POST['email']);
    $username = $email; // Use email as username
    $password = 'Pilar@2024'; // Default password
    $role = $_POST['role'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    
    // Validation
    if (empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $message = 'All fields are required';
        $message_type = 'danger';
    } else {
        try {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $message = 'Email already exists';
                $message_type = 'danger';
            } else {
                // Insert new user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role, first_name, last_name) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $username, $email, $password_hash, $role, $first_name, $last_name);
                
                if ($stmt->execute()) {
                    // Log user creation
                    logSystemAction($_SESSION['user_id'], 'create_user', 'users', "Created user: {$first_name} {$last_name} ({$email}) with role: {$role}");
                    
                    // Send welcome email with credentials
                    $email_sent = sendWelcomeEmail($email, $first_name, $last_name, $username, $password);
                    
                    if ($email_sent) {
                        $message = 'User added successfully. Welcome email sent with login credentials.';
                    } else {
                        $message = 'User added successfully, but failed to send welcome email.';
                    }
                    $message_type = 'success';
                } else {
                    $message = 'Error adding user';
                    $message_type = 'danger';
                }
            }
            $stmt->close();
        } catch (Exception $e) {
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Toggle user status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $user_id = $_POST['user_id'];
    $new_status = $_POST['new_status'];
    
    try {
        // Get user role before updating
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Prevent deactivation of system admin users
        if ($user['role'] === 'system_admin' && $new_status == 0) {
            $message = 'System admin users cannot be deactivated';
            $message_type = 'danger';
        } else {
            $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_status, $user_id);
            
            if ($stmt->execute()) {
                // Log status change
                $status_text = $new_status ? 'activated' : 'deactivated';
                logSystemAction($_SESSION['user_id'], 'toggle_user_status', 'users', "User {$user_id} {$status_text}");
                
                $message = 'User status updated successfully';
                $message_type = 'success';
            } else {
                $message = 'Error updating user status';
                $message_type = 'danger';
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        $message = 'Database error: ' . $e->getMessage();
        $message_type = 'danger';
    }
}


// Edit user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    $user_id = $_POST['user_id'];
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    
    // Validation
    if (empty($email) || empty($first_name) || empty($last_name)) {
        $message = 'All fields are required';
        $message_type = 'danger';
    } else {
        try {
            // Check if email already exists for another user
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $message = 'Email already exists for another user';
                $message_type = 'danger';
            } else {
                // Get current user data
                $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $current_user = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                // Prevent changing role of system admin to non-system admin
                if ($current_user['role'] === 'system_admin' && $role !== 'system_admin') {
                    $message = 'Cannot change system admin role';
                    $message_type = 'danger';
                } else {
                    // Update user
                    $username = $email; // Update username to match email
                    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ?, first_name = ?, last_name = ? WHERE id = ?");
                    $stmt->bind_param("sssssi", $username, $email, $role, $first_name, $last_name, $user_id);
                    
                    if ($stmt->execute()) {
                    // Log user update
                    logSystemAction($_SESSION['user_id'], 'update_user', 'users', "Updated user {$user_id}: {$first_name} {$last_name} ({$email}), role: {$role}");
                    
                    // Send update notification email
                    $email_sent = sendUserUpdateEmail($email, $first_name, $last_name, $username, $role);
                    
                    if ($email_sent) {
                        $message = 'User updated successfully. Notification email sent.';
                    } else {
                        $message = 'User updated successfully, but failed to send notification email.';
                    }
                    $message_type = 'success';
                } else {
                    $message = 'Error updating user';
                    $message_type = 'danger';
                }
                    $stmt->close();
                }
            }
            $stmt->close();
        } catch (Exception $e) {
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Get user data for editing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_user') {
    $user_id = $_GET['user_id'];
    
    try {
        $stmt = $conn->prepare("SELECT id, username, email, role, first_name, last_name FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            header('Content-Type: application/json');
            echo json_encode($user);
            exit();
        } else {
            echo json_encode(['error' => 'User not found']);
            exit();
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}

// Get role permissions for management
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_role_permissions') {
    $role = $_GET['role'];
    
    try {
        $stmt = $conn->prepare("
            SELECT p.name, p.description, p.module, rp.can_create, rp.can_read, rp.can_update, rp.can_delete 
            FROM permissions p 
            LEFT JOIN role_permissions rp ON p.id = rp.permission_id AND rp.role = ?
            ORDER BY p.module, p.name
        ");
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $permissions = [];
        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row;
        }
        
        header('Content-Type: application/json');
        echo json_encode($permissions);
        exit();
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}

// Update role permissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role_permissions') {
    $role = $_POST['role'];
    $permissions = $_POST['permissions'];
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        foreach ($permissions as $permission_id => $perms) {
            $can_create = isset($perms['can_create']) ? 1 : 0;
            $can_read = isset($perms['can_read']) ? 1 : 0;
            $can_update = isset($perms['can_update']) ? 1 : 0;
            $can_delete = isset($perms['can_delete']) ? 1 : 0;
            
            // Update or insert role permission
            $stmt = $conn->prepare("
                INSERT INTO role_permissions (role, permission_id, can_create, can_read, can_update, can_delete)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                can_create = VALUES(can_create),
                can_read = VALUES(can_read),
                can_update = VALUES(can_update),
                can_delete = VALUES(can_delete)
            ");
            $stmt->bind_param("siiiii", $role, $permission_id, $can_create, $can_read, $can_update, $can_delete);
            $stmt->execute();
            $stmt->close();
        }
        
        $conn->commit();
        $message = 'Role permissions updated successfully';
        $message_type = 'success';
        
        // Log role permissions update
        logSystemAction($_SESSION['user_id'], 'update_role_permissions', 'permissions', "Updated permissions for role: {$role}");
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = 'Error updating role permissions: ' . $e->getMessage();
        $message_type = 'danger';
    }
}
$users = [];
try {
    $stmt = $conn->prepare("SELECT id, username, email, role, first_name, last_name, is_active, created_at FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching users: " . $e->getMessage());
}

// Get user statistics
$stats = [];
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total_users, SUM(is_active) as active_users FROM users");
    $stmt->execute();
    $result = $stmt->get_result();
    $user_stats = $result->fetch_assoc();
    $stats['total_users'] = $user_stats['total_users'];
    $stats['active_users'] = $user_stats['active_users'];
    $stats['inactive_users'] = $stats['total_users'] - $stats['active_users'];
    $stmt->close();
    
    // Role distribution
    $stmt = $conn->prepare("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['roles'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['roles'][$row['role']] = $row['count'];
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching user stats: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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
        
        .user-card {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid rgba(25, 27, 169, 0.1);
            margin-bottom: 1rem;
        }
        
        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .role-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .role-system_admin {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .role-admin {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
        }
        
        .role-office_admin {
            background: linear-gradient(135deg, #5CC2F2 0%, #C1EAF2 100%);
            color: var(--dark-color);
        }
        
        .role-user {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
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
        
        .action-btn {
            padding: 0.375rem 0.75rem;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            transition: var(--transition);
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
        }
        
        .stats-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .search-box {
            background: white;
            border: 2px solid var(--accent-color);
            border-radius: var(--border-radius-lg);
            padding: 0.75rem 1rem;
            transition: var(--transition);
        }
        
        .search-box:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(25, 27, 169, 0.25);
            outline: none;
        }
        
        .form-control {
            background: var(--light-color);
            border: 2px solid var(--accent-color);
            border-radius: var(--border-radius-lg);
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(25, 27, 169, 0.25);
            outline: none;
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: var(--border-radius-lg);
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(25, 27, 169, 0.3);
        }
        
        /* Custom scrollbar for webkit browsers */
        .main-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .main-content::-webkit-scrollbar-track {
            background: rgba(25, 27, 169, 0.1);
            border-radius: 4px;
        }
        
        .main-content::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            border-radius: 4px;
        }
        
        .main-content::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5CC2F2 0%, #191BA9 100%);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
                max-height: calc(100vh - 60px);
            }
            
            .navbar-brand {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <?php
// Set page title for topbar
$page_title = 'User Management';
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
                        <i class="bi bi-people"></i> User Management
                    </h1>
                    <p class="text-muted mb-0">Manage system users, roles, and permissions</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="btn-group" role="group">
                        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#rolePermissionsModal">
                            <i class="bi bi-shield-check"></i> Role Permissions
                        </button>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="bi bi-plus-circle"></i> Add User
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Message Display -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- User Statistics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo $stats['total_users'] ?? 0; ?></div>
                            <div class="text-muted">Total Users</div>
                            <small class="text-success">
                                <i class="bi bi-arrow-up"></i> 
                                <?php echo $stats['active_users'] ?? 0; ?> active
                            </small>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-people fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo $stats['roles']['system_admin'] ?? 0; ?></div>
                            <div class="text-muted">System Admins</div>
                            <small class="text-warning">High Privilege</small>
                        </div>
                        <div class="text-danger">
                            <i class="bi bi-shield-check fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo ($stats['roles']['admin'] ?? 0) + ($stats['roles']['office_admin'] ?? 0); ?></div>
                            <div class="text-muted">Admin Users</div>
                            <small class="text-info">Management Level</small>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-person-badge fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo $stats['roles']['user'] ?? 0; ?></div>
                            <div class="text-muted">Regular Users</div>
                            <small class="text-success">Standard Access</small>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-person fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Search and Filter -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-header bg-primary text-white rounded-top-4">
                        <h6 class="mb-0"><i class="bi bi-people"></i> System Users</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="usersTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5">
                                                <i class="bi bi-people fs-1 text-muted"></i>
                                                <p class="text-muted mt-3">No users found in the system</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                            <i class="bi bi-person fs-5"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-semibold"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                                        <?php echo str_replace('_', ' ', $user['role']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <i class="bi bi-calendar"></i>
                                                        <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <?php if ($user['role'] === 'system_admin'): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary action-btn" disabled title="System admin cannot be deactivated">
                                                                <i class="bi bi-shield-check"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-outline-primary action-btn" onclick="toggleUserStatus(<?php echo $user['id']; ?>, <?php echo $user['is_active'] ? 0 : 1; ?>)" title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                                <i class="bi bi-<?php echo $user['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <button type="button" class="btn btn-sm btn-outline-warning action-btn" onclick="editUser(<?php echo $user['id']; ?>)" title="Edit User">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-person-plus"></i> Add New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_user">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                    <small class="text-muted">Email will be used as username for login</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="text" class="form-control" id="password" name="password" value="Pilar@2024" readonly>
                                    <small class="text-muted">Default password: Pilar@2024</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-control" id="role" name="role" required>
                                        <option value="">Select Role</option>
                                        <option value="system_admin">System Admin</option>
                                        <option value="admin">Admin</option>
                                        <option value="office_admin">Office Admin</option>
                                        <option value="user">User</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="editUserForm">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" id="editUserId">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="edit_email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="edit_email" name="email" required>
                                    <small class="text-muted">Email will be used as username for login</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="edit_role" class="form-label">Role</label>
                                    <select class="form-control" id="edit_role" name="role" required>
                                        <option value="">Select Role</option>
                                        <option value="system_admin">System Admin</option>
                                        <option value="admin">Admin</option>
                                        <option value="office_admin">Office Admin</option>
                                        <option value="user">User</option>
                                    </select>
                                    <small class="text-muted" id="roleWarning" style="display: none;">Note: System admin role cannot be changed</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Role Permissions Modal -->
    <div class="modal fade" id="rolePermissionsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="bi bi-shield-check"></i> Role Permissions Management</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="roleSelect" class="form-label">Select Role</label>
                            <select class="form-control" id="roleSelect">
                                <option value="">Choose a role...</option>
                                <option value="system_admin">System Admin</option>
                                <option value="admin">Admin</option>
                                <option value="office_admin">Office Admin</option>
                                <option value="user">User</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <div class="d-flex align-items-end h-100">
                                <button class="btn btn-primary me-2" onclick="loadRolePermissions()">
                                    <i class="bi bi-arrow-clockwise"></i> Load Permissions
                                </button>
                                <button class="btn btn-success" onclick="saveRolePermissions()" id="savePermissionsBtn" disabled>
                                    <i class="bi bi-save"></i> Save Changes
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div id="permissionsContainer" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Module</th>
                                        <th>Permission</th>
                                        <th>Description</th>
                                        <th>Create</th>
                                        <th>Read</th>
                                        <th>Update</th>
                                        <th>Delete</th>
                                    </tr>
                                </thead>
                                <tbody id="permissionsTableBody">
                                    <!-- Permissions will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        <?php require_once 'includes/sidebar-scripts.php'; ?>
        
        // Initialize DataTable
        $(document).ready(function() {
            $('#usersTable').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                order: [[3, 'desc']], // Sort by created date descending
                language: {
                    search: "Search users:",
                    lengthMenu: "Show _MENU_ users",
                    info: "Showing _START_ to _END_ of _TOTAL_ users",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        });
        
        // Toggle user status
        function toggleUserStatus(userId, newStatus) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'toggle_status';
            
            const userIdInput = document.createElement('input');
            userIdInput.type = 'hidden';
            userIdInput.name = 'user_id';
            userIdInput.value = userId;
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'new_status';
            statusInput.value = newStatus;
            
            form.appendChild(actionInput);
            form.appendChild(userIdInput);
            form.appendChild(statusInput);
            
            document.body.appendChild(form);
            form.submit();
        }
        
        // Edit user function
        function editUser(userId) {
            // Fetch user data
            $.ajax({
                url: 'user_management.php?action=get_user&user_id=' + userId,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        alert('Error: ' + response.error);
                    } else {
                        // Populate form fields
                        $('#editUserId').val(response.id);
                        $('#edit_first_name').val(response.first_name);
                        $('#edit_last_name').val(response.last_name);
                        $('#edit_email').val(response.email);
                        $('#edit_role').val(response.role);
                        
                        // Show warning if user is system admin
                        if (response.role === 'system_admin') {
                            $('#roleWarning').show();
                            $('#edit_role').prop('disabled', true);
                        } else {
                            $('#roleWarning').hide();
                            $('#edit_role').prop('disabled', false);
                        }
                        
                        // Show modal
                        $('#editUserModal').modal('show');
                    }
                },
                error: function() {
                    alert('Error fetching user data');
                }
            });
        }
        
        // Clear form on edit modal close
        document.getElementById('addUserModal').addEventListener('hidden.bs.modal', function () {
            this.querySelector('form').reset();
        });
        
        // Clear form on edit modal close
        document.getElementById('editUserModal').addEventListener('hidden.bs.modal', function () {
            this.querySelector('form').reset();
            $('#roleWarning').hide();
            $('#edit_role').prop('disabled', false);
        });
        
        // Handle edit form submission
        document.getElementById('editUserForm').addEventListener('submit', function (e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('user_management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // Reload page to show updated data
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating user');
            });
        });
        
        // Role Permissions Management
        function loadRolePermissions() {
            const role = document.getElementById('roleSelect').value;
            
            if (!role) {
                alert('Please select a role first');
                return;
            }
            
            $.ajax({
                url: 'user_management.php?action=get_role_permissions&role=' + role,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        alert('Error: ' + response.error);
                    } else {
                        displayPermissions(response);
                        document.getElementById('permissionsContainer').style.display = 'block';
                        document.getElementById('savePermissionsBtn').disabled = false;
                    }
                },
                error: function() {
                    alert('Error loading permissions');
                }
            });
        }
        
        function displayPermissions(permissions) {
            const tbody = document.getElementById('permissionsTableBody');
            tbody.innerHTML = '';
            
            let currentModule = '';
            permissions.forEach(permission => {
                if (permission.module !== currentModule) {
                    currentModule = permission.module;
                    // Add module header row
                    const moduleRow = document.createElement('tr');
                    moduleRow.innerHTML = `
                        <td colspan="8" class="table-secondary fw-bold">
                            <i class="bi bi-folder"></i> ${currentModule.charAt(0).toUpperCase() + currentModule.slice(1)}
                        </td>
                    `;
                    tbody.appendChild(moduleRow);
                }
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td></td>
                    <td>${permission.name}</td>
                    <td><small>${permission.description}</small></td>
                    <td class="text-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" 
                                   id="create_${permission.name}" 
                                   data-permission-id="${permission.name}" 
                                   data-action="can_create" 
                                   ${permission.can_create ? 'checked' : ''}>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" 
                                   id="read_${permission.name}" 
                                   data-permission-id="${permission.name}" 
                                   data-action="can_read" 
                                   ${permission.can_read ? 'checked' : ''}>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" 
                                   id="update_${permission.name}" 
                                   data-permission-id="${permission.name}" 
                                   data-action="can_update" 
                                   ${permission.can_update ? 'checked' : ''}>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" 
                                   id="delete_${permission.name}" 
                                   data-permission-id="${permission.name}" 
                                   data-action="can_delete" 
                                   ${permission.can_delete ? 'checked' : ''}>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }
        
        function saveRolePermissions() {
            const role = document.getElementById('roleSelect').value;
            const permissions = {};
            
            // Collect all permission data
            const checkboxes = document.querySelectorAll('#permissionsTableBody input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                const permissionName = checkbox.dataset.permissionId;
                const action = checkbox.dataset.action;
                
                if (!permissions[permissionName]) {
                    permissions[permissionName] = {};
                }
                permissions[permissionName][action] = checkbox.checked;
            });
            
            // Send data to server
            const formData = new FormData();
            formData.append('action', 'update_role_permissions');
            formData.append('role', role);
            formData.append('permissions', JSON.stringify(permissions));
            
            fetch('user_management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // Reload page to show updated data
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving permissions');
            });
        }
    </script>
</body>
</html>
