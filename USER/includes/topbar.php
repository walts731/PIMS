<nav class="navbar navbar-expand-lg navbar-dark" id="mainNavbar">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-speedometer2"></i>
            <?php echo ucfirst($page_title ?? 'Dashboard'); ?>
        </a>

        <div class="navbar-nav ms-auto align-items-center">
            <div class="d-flex align-items-center">
                <div class="nav-item me-3">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" class="form-control" placeholder="Search assets..." id="topbarSearch">
                        <button class="btn btn-outline-light" type="button" onclick="performSearch()">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="nav-item me-2">
                    <a href="#" class="nav-link text-white" title="Notifications">
                        <i class="bi bi-bell"></i>
                    </a>
                </div>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <div class="user-avatar me-3">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')); ?></div>
                            <div class="user-role">
                                <span class="badge bg-success">User</span>
                            </div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><i class="bi bi-key"></i> Change Password</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
function performSearch() {
    const searchValue = document.getElementById('topbarSearch').value.trim();
    if (searchValue) {
        // Redirect to assets page with search parameter
        window.location.href = `assets.php?search=${encodeURIComponent(searchValue)}`;
    } else {
        // If empty, redirect to assets page without filters
        window.location.href = 'assets.php';
    }
}

// Add Enter key support for search input
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('topbarSearch');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
        
        // Set current search value if exists
        const urlParams = new URLSearchParams(window.location.search);
        const currentSearch = urlParams.get('search');
        if (currentSearch) {
            searchInput.value = currentSearch;
        }
    }
});
</script>
