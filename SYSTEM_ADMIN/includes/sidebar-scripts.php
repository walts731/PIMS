<script>
// Sidebar functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mainWrapper = document.getElementById('mainWrapper');
    const navbar = document.querySelector('.navbar');

    // Toggle sidebar
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            toggleSidebar();
        });
    }

    // Close sidebar when clicking overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            closeSidebar();
        });
    }

    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSidebar();
        }
    });

    function toggleSidebar() {
        const isOpen = sidebar && sidebar.classList ? sidebar.classList.contains('active') : false;
        
        if (isOpen) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    function openSidebar() {
        if (sidebar && sidebar.classList) sidebar.classList.add('active');
        if (sidebarOverlay && sidebarOverlay.classList) sidebarOverlay.classList.add('active');
        if (sidebarToggle && sidebarToggle.classList) sidebarToggle.classList.add('sidebar-active');
        if (mainWrapper && mainWrapper.classList) mainWrapper.classList.add('sidebar-active');
        if (navbar && navbar.classList) navbar.classList.add('sidebar-active');
        document.body.style.overflow = 'hidden'; // Prevent background scroll
    }

    function closeSidebar() {
        if (sidebar && sidebar.classList) sidebar.classList.remove('active');
        if (sidebarOverlay && sidebarOverlay.classList) sidebarOverlay.classList.remove('active');
        if (sidebarToggle && sidebarToggle.classList) sidebarToggle.classList.remove('sidebar-active');
        if (mainWrapper && mainWrapper.classList) mainWrapper.classList.remove('sidebar-active');
        if (navbar && navbar.classList) navbar.classList.remove('sidebar-active');
        document.body.style.overflow = ''; // Restore background scroll
    }

    // Auto-close sidebar on window resize if desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            // Optional: keep sidebar open on desktop if you want
            // closeSidebar();
        }
    });
});
</script>
