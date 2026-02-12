<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <img src="../img/system_logo.png" alt="PIMS Logo" class="sidebar-logo">
            <span class="sidebar-title">PIMS</span>
        </div>
        <!-- Sidebar Toggle Button -->
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
    </div>
    
    <ul class="sidebar-menu">
        <li class="menu-item active">
            <a href="dashboard.php" class="menu-link">
                <i class="bi bi-speedometer2"></i>
                <span class="menu-text">Dashboard</span>
            </a>
        </li>
        
        <li class="menu-header">Office Management</li>
        <li class="menu-item">
            <a href="office_assets.php" class="menu-link">
                <i class="bi bi-box-seam"></i>
                <span class="menu-text">Office Assets</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="office_consumables.php" class="menu-link">
                <i class="bi bi-archive"></i>
                <span class="menu-text">Consumables</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="requests.php" class="menu-link">
                <i class="bi bi-send"></i>
                <span class="menu-text">Requests</span>
            </a>
        </li>
        
        <li class="menu-header">Forms & Documents</li>
        <li class="menu-item">
            <a href="par_forms.php" class="menu-link">
                <i class="bi bi-file-earmark-text"></i>
                <span class="menu-text">PAR Forms</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="ics_forms.php" class="menu-link">
                <i class="bi bi-file-earmark-text"></i>
                <span class="menu-text">ICS Forms</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="ris_forms.php" class="menu-link">
                <i class="bi bi-file-earmark-text"></i>
                <span class="menu-text">RIS Forms</span>
            </a>
        </li>
        
        <li class="menu-header">Reports</li>
        <li class="menu-item">
            <a href="office_reports.php" class="menu-link">
                <i class="bi bi-graph-up"></i>
                <span class="menu-text">Office Reports</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="inventory_reports.php" class="menu-link">
                <i class="bi bi-clipboard-data"></i>
                <span class="menu-text">Inventory Reports</span>
            </a>
        </li>
        
        <li class="menu-header">Account</li>
        <li class="menu-item">
            <a href="profile.php" class="menu-link">
                <i class="bi bi-person"></i>
                <span class="menu-text">Profile</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="#" class="menu-link" data-bs-toggle="modal" data-bs-target="#logoutModal">
                <i class="bi bi-box-arrow-right"></i>
                <span class="menu-text">Logout</span>
            </a>
        </li>
    </ul>
</nav>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to logout?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="../logout.php" class="btn btn-primary">Logout</a>
            </div>
        </div>
    </div>
</div>
