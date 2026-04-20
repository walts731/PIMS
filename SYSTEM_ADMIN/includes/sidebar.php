<?php
// Get current page name for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Get system settings for logo and dark mode
require_once '../config.php';
$system_settings = [];
try {
    $stmt = $conn->prepare("SELECT setting_name, setting_value FROM system_settings");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $system_settings[$row['setting_name']] = $row['setting_value'];
    }
    $stmt->close();
} catch (Exception $e) {
    // Fallback to default if database fails
    $system_settings['system_logo'] = '';
    $system_settings['system_name'] = 'PIMS';
    $system_settings['dark_mode'] = '0';
}

// Set dark mode class for body
$dark_mode_class = ($system_settings['dark_mode'] ?? '0') === '1' ? 'dark-mode' : '';

// Add dark mode class to body via JavaScript (runs before DOM loads to prevent flash)
if (!empty($dark_mode_class)) {
    echo "<script>document.body.classList.add('$dark_mode_class');</script>";
}
?>
<style>
<?php if (($system_settings['theme_preset'] ?? 'default') === 'legacy'): ?>
:root {
    --primary-color: #191BA9 !important;
    --primary-hover: #151689 !important;
    --primary-gradient: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%) !important;
    --light-accent: #C1EAF2 !important;
    --secondary-color: #5CC2F2 !important;
    --light-color: #F7F3F3 !important;
    --primary-rgb: 25, 27, 169 !important;
}
<?php else: ?>
:root {
    --primary-rgb: 30, 86, 160 !important;
}
<?php endif; ?>
/* Sidebar Styles */
.sidebar {
    position: fixed;
    top: 0;
    left: -280px;
    width: 280px;
    height: 100vh;
    background: var(--primary-gradient);
    box-shadow: 2px 0 10px rgba(30, 86, 160, 0.1);
    transition: left 0.3s ease-in-out;
    z-index: 1040;
    overflow-y: auto;
}

.sidebar.active {
    left: 0;
}

.sidebar-header {
    padding: 2rem 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: white;
}

.sidebar-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sidebar-nav {
    padding: 1rem 0;
}

.sidebar-nav-item {
    display: block;
    padding: 0.875rem 1.5rem;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
    font-weight: 500;
}

.sidebar-nav-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-left-color: white;
}

.sidebar-nav-item.active {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    border-left-color: white;
}

/* Sidebar Logo */
.sidebar-logo {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    margin-right: 1rem;
}

.sidebar-logo img {
    max-width: 100%;
    max-height: 100%;
    border-radius: 8px;
}

.sidebar-title h6 {
    font-weight: 700;
    font-size: 1rem;
    margin: 0;
}

.sidebar-title small {
    font-size: 0.75rem;
    font-weight: 500;
}

/* Dropdown Styles */
.sidebar-dropdown {
    position: relative;
}

.sidebar-dropdown-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 0.875rem 1.5rem;
    color: rgba(255, 255, 255, 0.8);
    background: none;
    border: none;
    text-align: left;
    font-weight: 500;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.sidebar-dropdown-toggle:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-left-color: white;
}

.sidebar-dropdown-toggle.active {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    border-left-color: white;
}

.sidebar-dropdown-menu {
    list-style: none;
    padding: 0;
    margin: 0;
    background: rgba(0, 0, 0, 0.1);
}

.sidebar-dropdown-item {
    display: block;
    padding: 0.75rem 1.5rem 0.75rem 3rem;
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
    font-size: 0.9rem;
}

.sidebar-dropdown-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-left-color: white;
}

.sidebar-dropdown-item.active {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    border-left-color: white;
}

.sidebar-dropdown i {
    width: 20px;
    margin-right: 0.75rem;
}

.sidebar-dropdown-toggle i:last-child {
    margin-right: 0;
    transition: transform 0.3s ease;
}

.sidebar-dropdown-toggle[aria-expanded="true"] i:last-child {
    transform: rotate(180deg);
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
    
    .sidebar {
        width: 100%;
        left: -100%;
    }
    
    .main-wrapper.sidebar-active {
        margin-left: 0;
    }
    
    .navbar.sidebar-active {
        padding-left: 80px; /* Keep space for toggle button on mobile */
    }
    
    .sidebar-toggle.sidebar-active {
        left: 20px; /* Keep toggle button in same position on mobile */
    }
}
</style>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="d-flex align-items-center">
            <div class="sidebar-logo">
                <?php 
                $logo_path = !empty($system_settings['system_logo']) ? '../' . htmlspecialchars($system_settings['system_logo']) : '../img/trans_logo.png';
                $system_name = htmlspecialchars($system_settings['system_name'] ?? 'PIMS');
                ?>
                <img src="<?php echo $logo_path; ?>" alt="<?php echo $system_name; ?> Logo" class="img-fluid" style="max-height: 40px; border-radius: 8px;">
            </div>
            <div class="sidebar-title">
                <h6 class="mb-0 text-white"><?php echo $system_name; ?></h6>
                <small class="text-white-50">System Admin</small>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="sidebar-nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            Dashboard
        </a>
        
        <div class="sidebar-dropdown">
            <button class="sidebar-dropdown-toggle <?php echo (in_array($current_page, ['categories.php', 'sub_categories.php', 'units.php', 'offices.php', 'funds.php'])) ? 'active' : ''; ?>" 
                    type="button" data-bs-toggle="collapse" data-bs-target="#managementDropdown" 
                    aria-expanded="<?php echo (in_array($current_page, ['categories.php', 'sub_categories.php', 'units.php', 'offices.php', 'funds.php'])) ? 'true' : 'false'; ?>">
                <div>
                    <i class="bi bi-gear"></i>
                    System Management
                </div>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="collapse <?php echo (in_array($current_page, ['categories.php', 'sub_categories.php', 'units.php', 'offices.php', 'funds.php'])) ? 'show' : ''; ?>" id="managementDropdown">
                <ul class="sidebar-dropdown-menu">
                    <li>
                        <a href="categories.php" class="sidebar-dropdown-item <?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">
                            <i class="bi bi-tags"></i>
                            Categories
                        </a>
                    </li>
                    <li>
                        <a href="sub_categories.php" class="sidebar-dropdown-item <?php echo $current_page == 'sub_categories.php' ? 'active' : ''; ?>">
                            <i class="bi bi-tag"></i>
                            Sub Categories
                        </a>
                    </li>
                    <li>
                        <a href="units.php" class="sidebar-dropdown-item <?php echo $current_page == 'units.php' ? 'active' : ''; ?>">
                            <i class="bi bi-rulers"></i>
                            Units
                        </a>
                    </li>
                    <li>
                        <a href="offices.php" class="sidebar-dropdown-item <?php echo $current_page == 'offices.php' ? 'active' : ''; ?>">
                            <i class="bi bi-building"></i>
                            Offices
                        </a>
                    </li>
                    
                </ul>
            </div>
        </div>
        
        <div class="sidebar-dropdown">
            <button class="sidebar-dropdown-toggle <?php echo (in_array($current_page, ['user_management.php', 'backup.php', 'logs.php', 'security_audit.php'])) ? 'active' : ''; ?>" 
                    type="button" data-bs-toggle="collapse" data-bs-target="#adminDropdown" 
                    aria-expanded="<?php echo (in_array($current_page, ['user_management.php', 'backup.php', 'logs.php', 'security_audit.php'])) ? 'true' : 'false'; ?>">
                <div>
                    <i class="bi bi-shield-check"></i>
                    Administration
                </div>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="collapse <?php echo (in_array($current_page, ['user_management.php', 'backup.php', 'logs.php', 'security_audit.php'])) ? 'show' : ''; ?>" id="adminDropdown">
                <ul class="sidebar-dropdown-menu">
                    <li>
                        <a href="user_management.php" class="sidebar-dropdown-item <?php echo $current_page == 'user_management.php' ? 'active' : ''; ?>">
                            <i class="bi bi-people"></i>
                            User Management
                        </a>
                    </li>
                    <li>
                        <a href="backup.php" class="sidebar-dropdown-item <?php echo $current_page == 'backup.php' ? 'active' : ''; ?>">
                            <i class="bi bi-cloud-arrow-up"></i>
                            Backup System
                        </a>
                    </li>
                    <li>
                        <a href="logs.php" class="sidebar-dropdown-item <?php echo $current_page == 'logs.php' ? 'active' : ''; ?>">
                            <i class="bi bi-file-text"></i>
                            System Logs
                        </a>
                    </li>
                    <li>
                        <a href="security_audit.php" class="sidebar-dropdown-item <?php echo $current_page == 'security_audit.php' ? 'active' : ''; ?>">
                            <i class="bi bi-shield-lock"></i>
                            Security Audit
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="sidebar-dropdown">
            <button class="sidebar-dropdown-toggle <?php echo (in_array($current_page, ['forms.php', 'form_details.php', 'tags.php'])) ? 'active' : ''; ?>" 
                    type="button" data-bs-toggle="collapse" data-bs-target="#formsDropdown" 
                    aria-expanded="<?php echo (in_array($current_page, ['forms.php', 'form_details.php', 'tags.php'])) ? 'true' : 'false'; ?>">
                <div>
                    <i class="bi bi-file-earmark-text"></i>
                    Forms & Tags
                </div>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="collapse <?php echo (in_array($current_page, ['forms.php', 'form_details.php', 'tags.php'])) ? 'show' : ''; ?>" id="formsDropdown">
                <ul class="sidebar-dropdown-menu">
                    <li>
                        <a href="forms.php" class="sidebar-dropdown-item <?php echo $current_page == 'forms.php' ? 'active' : ''; ?>">
                            <i class="bi bi-file-earmark-text"></i>
                            Forms Management
                        </a>
                    </li>
                   
                    <li>
                        <a href="tags.php" class="sidebar-dropdown-item <?php echo $current_page == 'tags.php' ? 'active' : ''; ?>">
                            <i class="bi bi-tags"></i>
                            Tags Management
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="sidebar-dropdown">
            <button class="sidebar-dropdown-toggle <?php echo (in_array($current_page, ['cloud_config.php', 'cloud_callback.php'])) ? 'active' : ''; ?>" 
                    type="button" data-bs-toggle="collapse" data-bs-target="#cloudDropdown" 
                    aria-expanded="<?php echo (in_array($current_page, ['cloud_config.php', 'cloud_callback.php'])) ? 'true' : 'false'; ?>">
                <div>
                    <i class="bi bi-cloud"></i>
                    Cloud Services
                </div>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="collapse <?php echo (in_array($current_page, ['cloud_config.php', 'cloud_callback.php'])) ? 'show' : ''; ?>" id="cloudDropdown">
                <ul class="sidebar-dropdown-menu">
                    <li>
                        <a href="cloud_config.php" class="sidebar-dropdown-item <?php echo $current_page == 'cloud_config.php' ? 'active' : ''; ?>">
                            <i class="bi bi-gear"></i>
                            Cloud Configuration
                        </a>
                    </li>
                  
                </ul>
            </div>
        </div>
        
        <a href="system_settings.php" class="sidebar-nav-item <?php echo $current_page == 'system_settings.php' ? 'active' : ''; ?>">
            <i class="bi bi-gear"></i>
            System Settings
        </a>
        
        <a href="profile.php" class="sidebar-nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
            <i class="bi bi-person-circle"></i>
            My Profile
        </a>
        
        <div class="sidebar-nav-item" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 2rem;">
            <i class="bi bi-box-arrow-right"></i>
            <a href="#" data-bs-toggle="modal" data-bs-target="#logoutModal" style="color: inherit; text-decoration: none;">Logout</a>
        </div>
    </nav>
</aside>
