<?php
session_start();
require_once 'config.php';

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Rate limiting - prevent brute force attacks
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

// Lockout after 5 failed attempts for 15 minutes
if ($_SESSION['login_attempts'] >= 5) {
    $time_diff = time() - $_SESSION['last_attempt_time'];
    if ($time_diff < 900) { // 15 minutes
        $error = "Account locked. Please try again in " . ceil((900 - $time_diff) / 60) . " minutes.";
    } else {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt_time'] = time();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request. Please try again.";
    } else {
        // Input validation and sanitization
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        
        // Validate email format
        if (!$email) {
            $error = "Invalid email format.";
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
        } elseif (empty($password)) {
            $error = "Password is required.";
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
        } else {
            try {
                // Prepare and execute query with parameterized statements
                $stmt = $conn->prepare("SELECT id, username, email, password_hash, role, first_name, last_name, is_active FROM users WHERE email = ? LIMIT 1");
                
                if ($stmt === false) {
                    throw new Exception("Database error. Please try again later.");
                }
                
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    // Check if account is active
                    if (!$user['is_active']) {
                        $error = "Account is deactivated. Please contact administrator.";
                        $_SESSION['login_attempts']++;
                        $_SESSION['last_attempt_time'] = time();
                    } elseif (!password_verify($password, $user['password_hash'])) {
                        // Invalid password - use generic error message for security
                        $error = "Invalid email or password.";
                        $_SESSION['login_attempts']++;
                        $_SESSION['last_attempt_time'] = time();
                        
                        // Log failed login attempt
                        error_log("Failed login attempt for email: " . $email . " from IP: " . $_SERVER['REMOTE_ADDR']);
                    } else {
                        // Successful login - reset attempts
                        $_SESSION['login_attempts'] = 0;
                        $_SESSION['last_attempt_time'] = time();
                        
                        // Create secure session
                        session_regenerate_id(true);
                        
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');
                        $_SESSION['email'] = htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8');
                        $_SESSION['role'] = htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8');
                        $_SESSION['first_name'] = htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8');
                        $_SESSION['last_name'] = htmlspecialchars($user['last_name'], ENT_QUOTES, 'UTF-8');
                        $_SESSION['logged_in'] = true;
                        $_SESSION['login_time'] = time();
                        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                        
                        // Log successful login
                        error_log("Successful login for user ID: " . $user['id'] . " from IP: " . $_SERVER['REMOTE_ADDR']);
                        
                        // Redirect based on role
                        $allowed_roles = ['system_admin', 'admin', 'office_admin', 'user'];
                        if (in_array($user['role'], $allowed_roles)) {
                            switch ($user['role']) {
                                case 'system_admin':
                                    header('Location: SYSTEM_ADMIN/dashboard.php');
                                    break;
                                case 'admin':
                                    header('Location: ADMIN/dashboard.php');
                                    break;
                                case 'office_admin':
                                    header('Location: OFFICE_ADMIN/dashboard.php');
                                    break;
                                case 'user':
                                    header('Location: USER/dashboard.php');
                                    break;
                            }
                            exit();
                        } else {
                            $error = "Invalid user role. Please contact administrator.";
                        }
                    }
                } else {
                    // User not found - use generic error message for security
                    $error = "Invalid email or password.";
                    $_SESSION['login_attempts']++;
                    $_SESSION['last_attempt_time'] = time();
                    
                    // Log failed attempt
                    error_log("Failed login attempt for unknown email: " . $email . " from IP: " . $_SERVER['REMOTE_ADDR']);
                }
                
                $stmt->close();
            } catch (Exception $e) {
                error_log("Login error: " . $e->getMessage());
                $error = "System error. Please try again later.";
            }
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PIMS - Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/index.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        
        .split-screen {
            display: flex;
            height: 100vh;
            width: 100vw;
        }
        
        .carousel-section {
            flex: 1;
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
            height: 100vh;
        }
        
        .login-section {
            flex: 1;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            height: 100vh;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .split-screen {
                flex-direction: column;
                height: 100vh;
            }
            
            .carousel-section {
                height: 35vh;
                flex: none;
            }
            
            .login-section {
                height: 65vh;
                flex: none;
            }
        }
        
        @media (max-width: 576px) {
            .carousel-section {
                height: 30vh;
                padding: 0.5rem;
            }
            
            .login-section {
                height: 70vh;
                padding: 0.5rem;
            }
        }
        
        @media (max-width: 400px) {
            .carousel-section {
                height: 20vh;
            }
            
            .login-section {
                height: 80vh;
            }
        }
    </style>
</head>
<body>
    <div class="split-screen">
        <!-- Carousel Section -->
        <div class="carousel-section">
            <div class="carousel-content">
                <div id="featureCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-indicators">
                        <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="0" class="active"></button>
                        <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="1"></button>
                        <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="2"></button>
                        <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="3"></button>
                    </div>
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <div class="text-center text-white p-4">
                                <div class="display-1 mb-3">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                                <h3 class="carousel-title">PIMS</h3>
                                <p class="lead">
                                    Pilar Inventory Management System - Streamline your inventory operations with our comprehensive management solution.
                                </p>
                            </div>
                        </div>
                        <div class="carousel-item">
                            <div class="text-center text-white p-4">
                                <div class="display-1 mb-3">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <h3 class="carousel-title">Secure & Reliable</h3>
                                <p class="lead">
                                    Enterprise-grade security with role-based access control ensuring your data is protected and accessible only to authorized users.
                                </p>
                            </div>
                        </div>
                        <div class="carousel-item">
                            <div class="text-center text-white p-4">
                                <div class="display-1 mb-3">
                                    <i class="bi bi-graph-up"></i>
                                </div>
                                <h3 class="carousel-title">Real-time Analytics</h3>
                                <p class="lead">
                                    Track inventory levels, monitor trends, and make data-driven decisions with our advanced reporting and analytics tools.
                                </p>
                            </div>
                        </div>
                        <div class="carousel-item">
                            <div class="text-center text-white p-4">
                                <div class="display-1 mb-3">
                                    <i class="bi bi-people"></i>
                                </div>
                                <h3 class="carousel-title">Team Collaboration</h3>
                                <p class="lead">
                                    Work seamlessly with your team across different roles and departments with our collaborative platform.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Login Section -->
        <div class="login-section">
            <div class="row w-100">
                <div class="col-12 col-md-8 col-lg-6 mx-auto">
                    <div class="card shadow-lg border-0 rounded-4">
                        <div class="card-header bg-primary text-white text-center rounded-top-4">
                            <div class="mb-3">
                                <div class="logo-circle">
                                    <img src="img/trans_logo.png" alt="PIMS Logo" class="img-fluid" style="max-height: 60px; border-radius: 8px;">
                                </div>
                            </div>
                            <h6 class="mb-0">PILAR INVENTORY MANAGEMENT SYSTEM</h6>
                        </div>
                        <div class="card-body">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="" id="loginForm">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                                    <label for="email"><i class="bi bi-envelope"></i> Email Address</label>
                                </div>
                                
                                <div class="form-floating mb-3 position-relative">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                    <label for="password"><i class="bi bi-lock"></i> Password</label>
                                    <i class="bi bi-eye position-absolute" style="right: 1rem; top: 50%; transform: translateY(-50%); cursor: pointer;" id="passwordToggle"></i>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">
                                        Remember me
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100" id="loginBtn">
                                    <i class="bi bi-box-arrow-in-right"></i> Sign In
                                </button>
                            </form>
                            
                            <div class="text-center mt-3">
                                <hr>
                                <a href="forgot_password.php" class="text-decoration-none">
                                    <i class="bi bi-key"></i> Forgot Password?
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password visibility toggle
        const passwordToggle = document.getElementById('passwordToggle');
        const passwordInput = document.getElementById('password');
        
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle icon
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });
        
        // Form submission loading state
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        
        loginForm.addEventListener('submit', function() {
            loginBtn.classList.add('loading');
            loginBtn.innerHTML = '<span style="opacity: 0;">Signing In...</span>';
        });
        
        // Input focus effects
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.classList.remove('focused');
                }
            });
        });
        
        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });
    </script>
</body>
</html>