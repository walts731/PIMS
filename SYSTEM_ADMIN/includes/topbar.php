<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark" id="mainNavbar">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-speedometer2"></i>
            <?php echo ucfirst($page_title ?? 'Dashboard'); ?>
        </a>
        
        <div class="navbar-nav ms-auto">
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
                    <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../logout.php" onclick="event.preventDefault(); confirmLogout();"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
