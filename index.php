<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Prepare and execute query
    $stmt = $conn->prepare("SELECT id, username, email, password_hash, role, first_name, last_name FROM users WHERE email = ? AND is_active = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password_hash'])) {
            // Password is correct, create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['logged_in'] = true;
            
            // Redirect based on role
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
                default:
                    header('Location: index.php');
            }
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found or inactive!";
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PIMS - Secure Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
            height: 100vh;
        }
        
        .login-section {
            flex: 1;
            background: rgba(255, 255, 255, 0.98);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            height: 100vh;
        }
        
        .carousel-container {
            max-width: 500px;
            width: 100%;
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
                height: 25vh;
            }
            
            .login-section {
                height: 75vh;
            }
        }
    </style>
</head>
<body>
    <div class="split-screen">
        <!-- Carousel Section -->
        <div class="carousel-section">
            <div class="carousel-container">
                <div id="featureCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-indicators">
                        <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="0" class="active"></button>
                        <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="1"></button>
                        <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="2"></button>
                        <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="3"></button>
                    </div>
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <div class="carousel-icon">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <h3 class="carousel-title">PIMS</h3>
                            <p class="carousel-description">
                                Pilar Inventory Management System - Streamline your inventory operations with our comprehensive management solution.
                            </p>
                        </div>
                        <div class="carousel-item">
                            <div class="carousel-icon">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <h3 class="carousel-title">Secure & Reliable</h3>
                            <p class="carousel-description">
                                Enterprise-grade security with role-based access control ensuring your data is protected and accessible only to authorized users.
                            </p>
                        </div>
                        <div class="carousel-item">
                            <div class="carousel-icon">
                                <i class="bi bi-graph-up"></i>
                            </div>
                            <h3 class="carousel-title">Real-time Analytics</h3>
                            <p class="carousel-description">
                                Track inventory levels, monitor trends, and make data-driven decisions with our advanced reporting and analytics tools.
                            </p>
                        </div>
                        <div class="carousel-item">
                            <div class="carousel-icon">
                                <i class="bi bi-people"></i>
                            </div>
                            <h3 class="carousel-title">Team Collaboration</h3>
                            <p class="carousel-description">
                                Work seamlessly with your team across different roles and departments with our collaborative platform.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Login Section -->
        <div class="login-section">
            <div class="col-md-6 col-lg-4 mx-auto">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h1 class="mb-0"><i class="bi bi-box-seam"></i> PIMS</h1>
                        <small>Pilar Inventory Management System</small>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" id="loginForm">
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