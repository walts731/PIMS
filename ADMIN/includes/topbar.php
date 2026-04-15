<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark" id="mainNavbar">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <img src="<?php echo $logo_path; ?>" alt="<?php echo $system_name; ?> Logo" class="img-fluid" style="max-height: 35px; border-radius: 6px;">
            <span class="fw-bold d-none d-sm-inline"><?php echo $system_name; ?></span>
            <span class="fw-bold d-none d-md-block">PIMS</span>
        </a>
        
        <!-- Mobile Icons for Very Small Screens -->
        <div class="d-md-none d-lg-none d-flex align-items-center">
            <!-- Search Toggle -->
            <button class="navbar-toggler border-0 text-white me-1" type="button" id="mobileSearchToggle" title="Toggle Search">
                <i class="bi bi-search"></i>
            </button>
            
            <!-- QR Scanner -->
            <div class="nav-item me-1">
                <a href="scan_qr.php" class="nav-link text-white" title="QR Scanner">
                    <i class="bi bi-qr-code-scan"></i>
                </a>
            </div>
            
            <!-- Notification Bell -->
            <div class="nav-item me-1">
                <div class="dropdown">
                    <a class="nav-link text-white position-relative notification-bell" href="#" role="button" data-bs-toggle="dropdown" title="Notifications">
                        <i class="bi bi-bell"></i>
                        <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end notification-dropdown" id="notificationDropdown">
                        <div class="notification-header">
                            <h6 class="mb-0">Notifications</h6>
                            <div class="notification-actions">
                                <a href="#" class="mark-all-read" title="Mark all as read">
                                    <i class="bi bi-check2-all"></i>
                                </a>
                            </div>
                        </div>
                        <div class="notification-list" id="notificationList">
                            <div class="notification-loading">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                        <div class="notification-footer">
                            <a href="../ADMIN/notifications.php" class="view-all-notifications">View All Notifications</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Profile Dropdown -->
            <div class="nav-item dropdown">
                <a class="nav-link text-white" href="#" role="button" data-bs-toggle="dropdown" title="Profile">
                    <i class="bi bi-person-circle"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><i class="bi bi-key"></i> Change Password</a></li>
                    <li><a class="dropdown-item" href="system_settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Mobile Menu Toggle -->
        <button class="navbar-toggler d-md-none d-lg-none border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#mobileNavbarContent">
            <i class="bi bi-list"></i>
        </button>
        
        <!-- Mobile Search Container -->
        <div class="w-100 d-md-none d-lg-none" id="mobileSearchContainer" style="display: none;">
            <div class="search-container position-relative mt-2">
                <form class="d-flex" action="search_handler.php" method="GET" id="mobileSearchForm">
                    <div class="input-group">
                        <input type="text" class="form-control" name="q" id="mobileSearchInput" 
                               placeholder="Search..." autocomplete="off"
                               value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                        <button class="btn btn-outline-light" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
                <!-- Mobile Search Suggestions Dropdown -->
                <div class="search-suggestions" id="mobileSearchSuggestions"></div>
            </div>
        </div>

        <div class="collapse navbar-collapse" id="mobileNavbarContent">
            <div class="navbar-nav ms-auto">
                <!-- Mobile Only: Notification Bell -->
                <div class="nav-item me-2 d-lg-none d-md-block">
                    <div class="dropdown">
                        <a class="nav-link text-white position-relative notification-bell" href="#" role="button" data-bs-toggle="dropdown" title="Notifications">
                            <i class="bi bi-bell"></i>
                            <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end notification-dropdown" id="notificationDropdown">
                            <div class="notification-header">
                                <h6 class="mb-0">Notifications</h6>
                                <div class="notification-actions">
                                    <a href="#" class="mark-all-read" title="Mark all as read">
                                        <i class="bi bi-check2-all"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="notification-list" id="notificationList">
                                <div class="notification-loading">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="notification-footer">
                                <a href="../ADMIN/notifications.php" class="view-all-notifications">View All Notifications</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Desktop Only Elements -->
        <div class="d-none d-lg-block">
            <div class="navbar-nav ms-auto">
                <!-- Search Bar - Desktop only -->
                <div class="nav-item me-3">
                    <div class="search-container position-relative">
                        <form class="d-flex" action="search_handler.php" method="GET" id="searchForm">
                            <div class="input-group">
                                <input type="text" class="form-control" name="q" id="searchInput" 
                                       placeholder="Search..." autocomplete="off"
                                       value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                                <button class="btn btn-outline-light" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                        <!-- Search Suggestions Dropdown -->
                        <div class="search-suggestions" id="searchSuggestions"></div>
                    </div>
                </div>
                
                <!-- Desktop Right Side Actions -->
                <div class="d-flex align-items-center">
                    <!-- QR Scanner - Desktop only -->
                    <div class="nav-item me-2">
                        <a href="scan_qr.php" class="nav-link text-white" title="QR Scanner">
                            <i class="bi bi-qr-code-scan"></i>
                        </a>
                    </div>
                    
                    <!-- Desktop Notifications -->
                    <div class="nav-item me-2">
                        <div class="dropdown">
                            <a class="nav-link text-white position-relative notification-bell" href="#" role="button" data-bs-toggle="dropdown" title="Notifications">
                                <i class="bi bi-bell"></i>
                                <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end notification-dropdown" id="notificationDropdown">
                                <div class="notification-header">
                                    <h6 class="mb-0">Notifications</h6>
                                    <div class="notification-actions">
                                        <a href="#" class="mark-all-read" title="Mark all as read">
                                            <i class="bi bi-check2-all"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="notification-list" id="notificationList">
                                    <div class="notification-loading">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="notification-footer">
                                    <a href="../ADMIN/notifications.php" class="view-all-notifications">View All Notifications</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Profile Dropdown - Desktop only -->
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
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
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><i class="bi bi-key"></i> Change Password</a></li>
                            <li><a class="dropdown-item" href="system_settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="logoutModalLabel">
                    <i class="bi bi-box-arrow-right text-danger me-2"></i>
                    Confirm Logout
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="bi bi-person-circle text-muted" style="font-size: 3rem;"></i>
                </div>
                <h6 class="mb-2">Are you sure you want to logout?</h6>
                <p class="text-muted mb-0">You will be redirected to the login page.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </button>
                <a href="../logout.php" class="btn btn-danger px-4">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </div>
        </div>
    </div>
</div>

<style>
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

/* Notification Dropdown Styles */
.notification-dropdown {
    width: 350px;
    max-height: 400px;
    border: none;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.notification-header {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
}

.notification-header h6 {
    color: #495057;
    font-weight: 600;
}

.notification-actions a {
    color: #6c757d;
    text-decoration: none;
    padding: 0.25rem;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.notification-actions a:hover {
    background: #e9ecef;
    color: #495057;
}

.notification-list {
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f8f9fa;
    transition: background-color 0.2s ease;
    cursor: pointer;
    position: relative;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #e3f2fd;
    border-left: 3px solid var(--primary-color);
}

.notification-item.unread::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0.5rem;
    width: 8px;
    height: 8px;
    background: var(--primary-color);
    border-radius: 50%;
    transform: translateY(-50%);
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.notification-message {
    color: #6c757d;
    font-size: 0.85rem;
    line-height: 1.4;
    margin-bottom: 0.25rem;
}

.notification-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.75rem;
    color: #adb5bd;
}

.notification-type {
    padding: 0.125rem 0.5rem;
    border-radius: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.notification-type.info { background: #d1ecf1; color: #0c5460; }
.notification-type.success { background: #d4edda; color: #155724; }
.notification-type.warning { background: #fff3cd; color: #856404; }
.notification-type.error { background: #f8d7da; color: #721c24; }
.notification-type.system { background: #e2e3e5; color: #383d41; }

.notification-actions-item {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.notification-actions-item button {
    padding: 0.25rem 0.5rem;
    border: none;
    border-radius: 4px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.notification-actions-item .btn-mark-read {
    background: #28a745;
    color: white;
}

.notification-actions-item .btn-delete {
    background: #dc3545;
    color: white;
}

.notification-actions-item button:hover {
    opacity: 0.8;
}

.notification-loading {
    padding: 2rem;
    text-align: center;
}

.notification-empty {
    padding: 2rem;
    text-align: center;
    color: #6c757d;
}

.notification-footer {
    padding: 0.75rem 1rem;
    border-top: 1px solid #e9ecef;
    text-align: center;
    background: #f8f9fa;
}

.notification-footer a {
    color: var(--primary-color);
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
}

.notification-footer a:hover {
    text-decoration: underline;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .notification-dropdown {
        width: 300px;
    }
    
    .notification-title {
        font-size: 0.85rem;
    }
    
    .notification-message {
        font-size: 0.8rem;
    }
}

.search-container .form-control {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    border-radius: var(--border-radius);
    width: 250px;
    transition: width 0.3s ease;
}

.search-container .form-control:focus {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.3);
    box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
    color: white;
    width: 300px;
}

.search-container .form-control::placeholder {
    color: rgba(255, 255, 255, 0.7);
}

.search-container .btn-outline-light {
    border-color: rgba(255, 255, 255, 0.2);
    color: rgba(255, 255, 255, 0.8);
}

.search-container .btn-outline-light:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.3);
    color: white;
}

/* Search Suggestions Dropdown */
.search-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0 0 var(--border-radius) var(--border-radius);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    max-height: 300px;
    overflow-y: auto;
    z-index: 1050;
    display: none;
}

.search-suggestion-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f8f9fa;
    cursor: pointer;
    transition: background-color 0.2s ease;
    color: #333;
}

.search-suggestion-item:hover {
    background-color: #f8f9fa;
}

.search-suggestion-item:last-child {
    border-bottom: none;
}

.suggestion-type {
    font-size: 0.75rem;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-weight: 600;
    margin-left: 0.5rem;
}

.suggestion-asset {
    background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
    color: white;
}

.suggestion-employee {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.suggestion-text {
    font-weight: 500;
}

.suggestion-meta {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.no-suggestions {
    padding: 1rem;
    text-align: center;
    color: #6c757d;
    font-size: 0.9rem;
}

/* Mobile Responsive Styles */
@media (max-width: 768px) {
    .search-container .form-control {
        width: 180px;
    }
    
    .search-container .form-control:focus {
        width: 200px;
    }
    
    .navbar-brand {
        font-size: 1rem;
    }
    
        
    /* Mobile notification dropdown */
    .notification-dropdown {
        width: 280px;
        max-height: 350px;
    }
    
    /* Compact user avatar on mobile */
    .user-avatar {
        font-size: 1.5rem;
    }
    
    .user-avatar i {
        font-size: 1.5rem;
    }
}

@media (max-width: 576px) {
    /* Compact navbar */
    #mainNavbar {
        padding: 0.25rem 0.5rem !important;
        min-height: auto !important;
    }
    
    .container-fluid {
        padding: 0 0.25rem !important;
    }
    
    .search-container .form-control {
        width: 150px;
    }
    
    .search-container .form-control:focus {
        width: 170px;
    }
    
    .search-container .form-control::placeholder {
        font-size: 0.8rem;
    }
    
    /* Mobile menu toggle - smaller */
    .navbar-toggler {
        padding: 0.15rem 0.25rem !important;
        font-size: 1rem !important;
    }
    
    /* Mobile notification dropdown - more compact */
    .notification-dropdown {
        width: 250px;
        max-height: 300px;
        right: -5px;
    }
    
    .notification-title {
        font-size: 0.8rem;
    }
    
    .notification-message {
        font-size: 0.75rem;
    }
    
    /* Compact user info on mobile */
    .user-info .user-name {
        font-size: 0.75rem;
        max-width: 60px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    /* Mobile dropdown menu - more compact */
    .dropdown-menu {
        font-size: 0.85rem;
        min-width: 180px;
    }
    
    .dropdown-item {
        padding: 0.375rem 0.75rem;
    }
    
    /* Mobile brand adjustments - smaller */
    .navbar-brand {
        padding: 0.125rem 0.25rem !important;
    }
    
    .navbar-brand img {
        max-height: 24px !important;
    }
    
    .navbar-brand span {
        font-size: 0.85rem;
    }
    
    /* Compact nav items */
    .nav-item.me-2 {
        margin-right: 0.125rem !important;
    }
    
    /* Smaller notification badge */
    .notification-badge {
        width: 14px;
        height: 14px;
        font-size: 0.55rem;
        top: -2px;
        right: -2px;
        border-width: 1px;
    }
    
    /* Smaller user avatar */
    .user-avatar {
        margin-right: 0.25rem !important;
        font-size: 1.25rem;
    }
    
    .user-avatar i {
        font-size: 1.25rem;
    }
}

/* Portrait mode specific styles */
@media (max-width: 576px) and (orientation: portrait) {
    /* Ultra compact navbar */
    #mainNavbar {
        padding: 0.125rem 0.25rem !important;
    }
    
    .container-fluid {
        padding: 0 0.125rem !important;
    }
    
    /* Tighter spacing between elements */
    .navbar-nav .d-flex {
        gap: 0.25rem;
    }
    
    /* Even smaller notification badge */
    .notification-badge {
        width: 12px;
        height: 12px;
        font-size: 0.5rem;
        top: -2px;
        right: -2px;
        border-width: 1px;
    }
    
    /* Minimal spacing for nav items */
    .nav-item.me-2 {
        margin-right: 0.125rem !important;
    }
    
    /* Smaller brand logo */
    .navbar-brand img {
        max-height: 20px !important;
    }
    
    .navbar-brand span {
        font-size: 0.75rem;
    }
    
        
    /* Smaller user avatar */
    .user-avatar {
        margin-right: 0.125rem !important;
        font-size: 1.1rem;
    }
    
    .user-avatar i {
        font-size: 1.1rem;
    }
    
    /* Compact user name */
    .user-info .user-name {
        font-size: 0.7rem;
        max-width: 50px;
    }
}

/* Very Small Screens (< 425px) - Icon bar mode */
@media (max-width: 425px) {
    /* Hide all text completely */
    .navbar-brand span {
        display: none !important;
    }
    
    /* Hide mobile menu toggle */
    .navbar-toggler:not(#mobileSearchToggle) {
        display: none !important;
    }
    
    /* Hide mobile navbar content */
    #mobileNavbarContent {
        display: none !important;
    }
    
    /* Compact logo */
    .navbar-brand img {
        max-height: 24px !important;
        margin-right: 0 !important;
    }
    
    /* Icon bar styling */
    .d-md-none.d-lg-none.d-flex {
        gap: 0.25rem;
    }
    
    .d-md-none .nav-link {
        padding: 0.25rem !important;
        font-size: 1rem;
    }
    
    .d-md-none .navbar-toggler {
        padding: 0.25rem !important;
        font-size: 1rem;
    }
    
    /* Mobile search container */
    #mobileSearchContainer {
        padding: 0.25rem 0.5rem;
    }
    
    #mobileSearchContainer .form-control {
        font-size: 0.85rem;
        padding: 0.375rem;
        height: 2rem;
    }
    
    #mobileSearchContainer .btn {
        padding: 0.375rem;
        height: 2rem;
    }
    
    /* Hide notification badge text, keep only dot */
    .notification-badge {
        width: 8px;
        height: 8px;
        font-size: 0;
        top: -1px;
        right: -1px;
        border-width: 1px;
    }
}

/* iPhone SE specific (375x667) - Ultra compact */
@media (max-width: 375px) {
    /* Ultra compact navbar for iPhone SE */
    #mainNavbar {
        padding: 0.125rem 0.25rem !important;
        min-height: auto !important;
    }
    
    .container-fluid {
        padding: 0 0.125rem !important;
    }
    
    /* Even smaller logo */
    .navbar-brand img {
        max-height: 20px !important;
    }
    
    /* Minimal icons */
    .d-md-none .nav-link {
        padding: 0.125rem !important;
        font-size: 0.9rem;
    }
    
    .d-md-none .navbar-toggler {
        padding: 0.125rem !important;
        font-size: 0.9rem;
    }
    
    /* Ultra small notification badge */
    .notification-badge {
        width: 6px;
        height: 6px;
        top: 0;
        right: 0;
    }
}

/* Landscape mode adjustments */
@media (max-width: 768px) and (orientation: landscape) {
    #mobileSearchContainer {
        margin-bottom: 0.125rem;
    }
    
    .navbar {
        padding-top: 0.125rem;
        padding-bottom: 0.125rem;
    }
}
</style>

<script>
// Notification System
let notificationDropdown;
let notificationList;
let notificationBadge;
let notificationTimeout;

document.addEventListener('DOMContentLoaded', function() {
    notificationDropdown = document.getElementById('notificationDropdown');
    notificationList = document.getElementById('notificationList');
    notificationBadge = document.getElementById('notificationBadge');
    
    // Initialize notification system
    updateNotificationBadge();
    
    // Setup dropdown events
    const notificationBell = document.querySelector('.notification-bell');
    if (notificationBell) {
        notificationBell.addEventListener('click', function() {
            loadNotifications();
        });
    }
    
    // Setup mark all as read
    const markAllReadBtn = document.querySelector('.mark-all-read');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            markAllNotificationsAsRead();
        });
    }
    
    // Auto-refresh notification count every 30 seconds
    setInterval(updateNotificationBadge, 30000);
});

function updateNotificationBadge() {
    fetch('notifications_handler.php?action=get_count', {
        credentials: 'include'  // Include cookies for session
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Notification count response:', data);
            const count = data.unread_count || 0;
            if (count > 0) {
                notificationBadge.textContent = count > 99 ? '99+' : count;
                notificationBadge.style.display = 'flex';
            } else {
                notificationBadge.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error fetching notification count:', error);
            // Hide badge on error to prevent confusion
            notificationBadge.style.display = 'none';
        });
}

function loadNotifications() {
    // Show loading state
    notificationList.innerHTML = `
        <div class="notification-loading">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    fetch('notifications_handler.php?action=get_notifications&limit=10', {
        credentials: 'include'  // Include cookies for session
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Notifications response:', data);
            if (data.error) {
                throw new Error(data.error);
            }
            displayNotifications(data.notifications);
        })
        .catch(error => {
            console.error('Error fetching notifications:', error);
            notificationList.innerHTML = `
                <div class="notification-empty">
                    <i class="bi bi-exclamation-triangle"></i>
                    <p>Error loading notifications</p>
                    <small>${error.message}</small>
                </div>
            `;
        });
}

function displayNotifications(notifications) {
    if (notifications.length === 0) {
        notificationList.innerHTML = `
            <div class="notification-empty">
                <i class="bi bi-bell-slash"></i>
                <p>No notifications</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    notifications.forEach(notification => {
        const unreadClass = notification.is_read ? '' : 'unread';
        const typeClass = `notification-type ${notification.type}`;
        
        html += `
            <div class="notification-item ${unreadClass}" data-id="${notification.id}">
                <div class="notification-content">
                    <div class="notification-title">${notification.title}</div>
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-meta">
                        <span class="notification-time">${notification.time_ago}</span>
                        <span class="${typeClass}">${notification.type}</span>
                    </div>
                    ${!notification.is_read ? `
                        <div class="notification-actions-item">
                            <button class="btn-mark-read" onclick="markNotificationAsRead(${notification.id})">
                                <i class="bi bi-check"></i> Mark as read
                            </button>
                            <button class="btn-delete" onclick="deleteNotificationItem(${notification.id})">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    });
    
    notificationList.innerHTML = html;
    
    // Add click handlers for notification items
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function(e) {
            // Don't trigger if clicking on action buttons
            if (e.target.closest('.notification-actions-item')) {
                return;
            }
            
            const notificationId = this.dataset.id;
            const isUnread = this.classList.contains('unread');
            
            if (isUnread) {
                markNotificationAsRead(notificationId);
            }
            
            // Navigate to related URL if available
            // This would need to be implemented based on the notification's action_url
        });
    });
}

function markNotificationAsRead(notificationId) {
    fetch('notifications_handler.php?action=mark_read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `notification_id=${notificationId}`,
        credentials: 'include'  // Include cookies for session
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove unread class and actions
            const item = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
            if (item) {
                item.classList.remove('unread');
                const actions = item.querySelector('.notification-actions-item');
                if (actions) {
                    actions.remove();
                }
            }
            updateNotificationBadge();
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

function markAllNotificationsAsRead() {
    fetch('notifications_handler.php?action=mark_all_read', {
        method: 'POST',
        credentials: 'include'  // Include cookies for session
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload notifications to show updated state
            loadNotifications();
            updateNotificationBadge();
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
    });
}

function deleteNotificationItem(notificationId) {
    fetch('notifications_handler.php?action=delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `notification_id=${notificationId}`,
        credentials: 'include'  // Include cookies for session
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove notification item
            const item = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
            if (item) {
                item.remove();
                
                // Check if there are no more notifications
                const remainingItems = document.querySelectorAll('.notification-item');
                if (remainingItems.length === 0) {
                    notificationList.innerHTML = `
                        <div class="notification-empty">
                            <i class="bi bi-bell-slash"></i>
                            <p>No notifications</p>
                        </div>
                    `;
                }
            }
            updateNotificationBadge();
        }
    })
    .catch(error => {
        console.error('Error deleting notification:', error);
    });
}

// Create notification function (to be called from other parts of the system)
window.createNotification = function(userId, title, message, type = 'info', relatedId = null, relatedType = null) {
    // This would typically be called from server-side PHP
    // For client-side notifications, you could use a WebSocket or polling
    console.log('Notification created:', { userId, title, message, type, relatedId, relatedType });
};

let searchTimeout;
const searchInput = document.getElementById('searchInput');
const searchSuggestions = document.getElementById('searchSuggestions');
const searchForm = document.getElementById('searchForm');

// Mobile search elements
const mobileSearchToggle = document.getElementById('mobileSearchToggle');
const mobileSearchContainer = document.getElementById('mobileSearchContainer');
const mobileSearchInput = document.getElementById('mobileSearchInput');
const mobileSearchSuggestions = document.getElementById('mobileSearchSuggestions');
const mobileSearchForm = document.getElementById('mobileSearchForm');


// Search suggestions functionality
searchInput.addEventListener('input', function() {
    const query = this.value.trim();
    
    clearTimeout(searchTimeout);
    
    if (query.length < 2) {
        searchSuggestions.style.display = 'none';
        return;
    }
    
    // Debounce search requests
    searchTimeout = setTimeout(() => {
        fetchSuggestions(query);
    }, 300);
});

// Mobile search toggle functionality
if (mobileSearchToggle) {
    mobileSearchToggle.addEventListener('click', function() {
        const isVisible = mobileSearchContainer.style.display !== 'none';
        
        if (isVisible) {
            mobileSearchContainer.style.display = 'none';
            mobileSearchToggle.classList.remove('active');
        } else {
            mobileSearchContainer.style.display = 'block';
            mobileSearchToggle.classList.add('active');
            mobileSearchInput.focus();
        }
    });
}

// Mobile search input event listener
if (mobileSearchInput) {
    let mobileSearchTimeout;
    
    mobileSearchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(mobileSearchTimeout);
        
        if (query.length < 2) {
            mobileSearchSuggestions.style.display = 'none';
            return;
        }
        
        // Debounce search requests
        mobileSearchTimeout = setTimeout(() => {
            fetchSuggestions(query);
        }, 300);
    });
}

// Hide suggestions when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-container')) {
        searchSuggestions.style.display = 'none';
        if (mobileSearchSuggestions) {
            mobileSearchSuggestions.style.display = 'none';
        }
    }
});

function fetchSuggestions(query) {
    fetch('search_suggestions.php?q=' + encodeURIComponent(query))
        .then(response => response.json())
        .then(data => {
            displaySuggestions(data);
            if (mobileSearchSuggestions) {
                displayMobileSuggestions(data);
            }
        })
        .catch(error => {
            console.error('Error fetching suggestions:', error);
            searchSuggestions.style.display = 'none';
            if (mobileSearchSuggestions) {
                mobileSearchSuggestions.style.display = 'none';
            }
        });
}

function displaySuggestions(suggestions) {
    if (suggestions.length === 0) {
        searchSuggestions.innerHTML = '<div class="no-suggestions">No results found</div>';
        searchSuggestions.style.display = 'block';
        return;
    }
    
    let html = '';
    suggestions.forEach(item => {
        const typeClass = item.type === 'asset' ? 'suggestion-asset' : 'suggestion-employee';
        const typeText = item.type === 'asset' ? 'Asset' : 'Employee';
        
        html += `
            <div class="search-suggestion-item" onclick="selectSuggestion('${item.url}')">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="suggestion-text">${highlightMatch(item.text, searchInput.value.trim())}</div>
                        ${item.meta ? `<div class="suggestion-meta">${item.meta}</div>` : ''}
                    </div>
                    <span class="suggestion-type ${typeClass}">${typeText}</span>
                </div>
            </div>
        `;
    });
    
    searchSuggestions.innerHTML = html;
    searchSuggestions.style.display = 'block';
}

function displayMobileSuggestions(suggestions) {
    if (!mobileSearchSuggestions) return;
    
    if (suggestions.length === 0) {
        mobileSearchSuggestions.innerHTML = '<div class="no-suggestions">No results found</div>';
        mobileSearchSuggestions.style.display = 'block';
        return;
    }
    
    let html = '';
    suggestions.forEach(item => {
        const typeClass = item.type === 'asset' ? 'suggestion-asset' : 'suggestion-employee';
        const typeText = item.type === 'asset' ? 'Asset' : 'Employee';
        
        html += `
            <div class="search-suggestion-item" onclick="selectMobileSuggestion('${item.url}')">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="suggestion-text">${highlightMatch(item.text, mobileSearchInput.value.trim())}</div>
                        ${item.meta ? `<div class="suggestion-meta">${item.meta}</div>` : ''}
                    </div>
                    <span class="suggestion-type ${typeClass}">${typeText}</span>
                </div>
            </div>
        `;
    });
    
    mobileSearchSuggestions.innerHTML = html;
    mobileSearchSuggestions.style.display = 'block';
}

function highlightMatch(text, query) {
    const regex = new RegExp(`(${query})`, 'gi');
    return text.replace(regex, '<strong>$1</strong>');
}

function selectSuggestion(url) {
    window.location.href = url;
}

function selectMobileSuggestion(url) {
    window.location.href = url;
}

// Handle form submission
searchForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const query = searchInput.value.trim();
    if (query) {
        window.location.href = `search_handler.php?q=${encodeURIComponent(query)}`;
    }
});

// Handle mobile form submission
if (mobileSearchForm) {
    mobileSearchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const query = mobileSearchInput.value.trim();
        if (query) {
            window.location.href = `search_handler.php?q=${encodeURIComponent(query)}`;
        }
    });
}

// Keyboard navigation
let currentSuggestionIndex = -1;

searchInput.addEventListener('keydown', function(e) {
    const items = searchSuggestions.querySelectorAll('.search-suggestion-item');
    
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        currentSuggestionIndex = Math.min(currentSuggestionIndex + 1, items.length - 1);
        updateActiveSuggestion(items);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        currentSuggestionIndex = Math.max(currentSuggestionIndex - 1, -1);
        updateActiveSuggestion(items);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (currentSuggestionIndex >= 0 && items[currentSuggestionIndex]) {
            items[currentSuggestionIndex].click();
        } else {
            searchForm.dispatchEvent(new Event('submit'));
        }
    } else if (e.key === 'Escape') {
        searchSuggestions.style.display = 'none';
        currentSuggestionIndex = -1;
    }
});

function updateActiveSuggestion(items) {
    items.forEach((item, index) => {
        if (index === currentSuggestionIndex) {
            item.style.backgroundColor = '#e9ecef';
        } else {
            item.style.backgroundColor = '';
        }
    });
}

</script>
