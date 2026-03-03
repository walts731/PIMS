<!-- Topbar -->
<header class="topbar" id="topbar">
    <div class="topbar-left">
        <!-- Sidebar Toggle Button -->
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title">
            <h5 class="mb-0"><?php echo $page_title ?? 'Office Admin'; ?></h5>
        </div>
    </div>
    
    <div class="topbar-right">
        <!-- Search -->
        <div class="topbar-search">
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Search...">
                <button class="btn btn-outline-secondary" type="button">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>
        
        <!-- Notifications -->
        <div class="topbar-notifications dropdown">
            <button class="btn btn-link position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-bell"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    3
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                <li><h6 class="dropdown-header">Notifications</h6></li>
                <li><a class="dropdown-item" href="#">New consumable request pending</a></li>
                <li><a class="dropdown-item" href="#">Asset maintenance due</a></li>
                <li><a class="dropdown-item" href="#">Low stock alert</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-center" href="#">View all notifications</a></li>
            </ul>
        </div>
        
        <!-- User Profile Dropdown -->
        <div class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="user-avatar me-3">
                    <i class="bi bi-person-circle"></i>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></div>
                    <div class="user-role">
                        <?php 
                        $role = htmlspecialchars(ucfirst(str_replace('_', ' ', $_SESSION['role'])));
                        $badge_class = 'bg-secondary';
                        if ($_SESSION['role'] === 'system_admin') {
                            $badge_class = 'bg-danger';
                        } elseif ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'office_admin') {
                            $badge_class = 'bg-warning text-dark';
                        } elseif ($_SESSION['role'] === 'user') {
                            $badge_class = 'bg-success';
                        }
                        ?>
                        <span class="badge <?php echo $badge_class; ?>"><?php echo $role; ?></span>
                    </div>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Profile</a></li>
                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><i class="bi bi-key"></i> Change Password</a></li>
                <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../logout.php" onclick="event.preventDefault(); confirmLogout();"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</header>

<style>
/* User Dropdown Styles */
.user-avatar {
    font-size: 1.5rem;
    color: rgba(255, 255, 255, 0.8);
}

.user-info {
    text-align: left;
}

.user-name {
    font-weight: 600;
    color: white;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.user-role .badge {
    font-size: 0.7rem;
    font-weight: 500;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    text-transform: uppercase;
}

/* Dropdown container fixes */
.dropdown {
    position: relative !important;
}

.dropdown.show .dropdown-menu {
    display: block !important;
}

.navbar-dark .nav-link {
    color: rgba(255, 255, 255, 0.8) !important;
    transition: color 0.3s ease;
}

.navbar-dark .nav-link:hover {
    color: white !important;
}

.dropdown-menu {
    border: none;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
    border-radius: var(--border-radius);
    margin-top: 0.5rem;
    z-index: 1050 !important;
    position: absolute !important;
}

.dropdown-item {
    padding: 0.75rem 1rem;
    transition: background-color 0.2s ease;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

.dropdown-item i {
    width: 16px;
    margin-right: 0.5rem;
}

/* Notification Bell Styles */
.notification-bell {
    font-size: 1.2rem;
    transition: transform 0.2s ease;
}

.notification-bell:hover {
    transform: scale(1.1);
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.7rem;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(255, 255, 255, 0.9);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* Search Styles */
.topbar-search .form-control {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    border-radius: var(--border-radius);
    width: 200px;
    transition: width 0.3s ease;
}

.topbar-search .form-control:focus {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.3);
    box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
    color: white;
    width: 250px;
}

.topbar-search .form-control::placeholder {
    color: rgba(255, 255, 255, 0.7);
}

.topbar-search .btn-outline-secondary {
    border-color: rgba(255, 255, 255, 0.2);
    color: rgba(255, 255, 255, 0.8);
}

.topbar-search .btn-outline-secondary:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.3);
    color: white;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .topbar-search .form-control {
        width: 150px;
    }
    
    .topbar-search .form-control:focus {
        width: 180px;
    }
    
    .user-name {
        font-size: 0.8rem;
    }
    
    .user-avatar {
        font-size: 1.2rem;
    }
}
</style>

<script>
// Debug dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('Topbar loaded, checking dropdown functionality...');
    
    // Wait a bit for Bootstrap to fully initialize
    setTimeout(function() {
        // Check if Bootstrap is loaded
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap is not loaded!');
            return;
        }
        console.log('Bootstrap is loaded successfully');
        
        // Check dropdown elements
        const dropdowns = document.querySelectorAll('.dropdown');
        console.log('Found dropdowns:', dropdowns.length);
        
        // Initialize dropdowns manually if needed
        dropdowns.forEach(function(dropdown, index) {
            const toggle = dropdown.querySelector('.dropdown-toggle');
            if (toggle) {
                console.log('Initializing dropdown', index + 1, 'for:', toggle);
                try {
                    // Destroy any existing instance first
                    const existingInstance = bootstrap.Dropdown.getInstance(toggle);
                    if (existingInstance) {
                        existingInstance.dispose();
                    }
                    
                    // Create new instance
                    const dropdownInstance = new bootstrap.Dropdown(toggle);
                    console.log('Dropdown', index + 1, 'initialized successfully');
                    
                    // Add click event listener for debugging
                    toggle.addEventListener('click', function(e) {
                        console.log('Dropdown', index + 1, 'clicked');
                        e.preventDefault();
                    });
                    
                } catch (error) {
                    console.error('Error initializing dropdown', index + 1, ':', error);
                }
            }
        });
        
        // Test dropdown functionality
        console.log('Testing dropdown functionality...');
        const firstDropdown = document.querySelector('.dropdown-toggle');
        if (firstDropdown) {
            console.log('First dropdown element:', firstDropdown);
            console.log('Data attributes:', firstDropdown.dataset);
        }
    }, 100);
});

function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../logout.php';
    }
}
</script>
