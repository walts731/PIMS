// Sidebar Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mainWrapper = document.getElementById('mainWrapper');
    const mainNavbar = document.getElementById('mainNavbar');

    // Check if elements exist before adding event listeners
    if (sidebarToggle && sidebar && sidebarOverlay && mainWrapper) {
        function toggleSidebar() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
            mainWrapper.classList.toggle('sidebar-active');
            sidebarToggle.classList.toggle('sidebar-active');
        }

        function closeSidebar() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            mainWrapper.classList.remove('sidebar-active');
            sidebarToggle.classList.remove('sidebar-active');
        }

        sidebarToggle.addEventListener('click', function() {
            console.log('Sidebar toggle clicked!');
            toggleSidebar();
        });
        sidebarOverlay.addEventListener('click', closeSidebar);

        // Close sidebar on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('active')) {
                closeSidebar();
            }
        });
    }
});

// Logout confirmation
function confirmLogout() {
    const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'));
    logoutModal.show();
}

// Update all logout links to use confirmation
document.addEventListener('DOMContentLoaded', function() {
    const logoutLinks = document.querySelectorAll('a[href="../logout.php"]');
    logoutLinks.forEach(link => {
        // Don't intercept the logout button inside the modal
        if (!link.closest('#logoutModal')) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                confirmLogout();
            });
        }
    });
});
