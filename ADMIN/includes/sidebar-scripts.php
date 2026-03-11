<script>
// Sidebar functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mainWrapper = document.getElementById('mainWrapper');
    const navbar = document.querySelector('.navbar');

    // Toggle sidebar
    sidebarToggle.addEventListener('click', function() {
        toggleSidebar();
    });

    // Close sidebar when clicking overlay
    sidebarOverlay.addEventListener('click', function() {
        closeSidebar();
    });

    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSidebar();
        }
    });

    function toggleSidebar() {
        const isOpen = sidebar.classList.contains('active');
        
        if (isOpen) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    function openSidebar() {
        sidebar.classList.add('active');
        sidebarOverlay.classList.add('active');
        sidebarToggle.classList.add('sidebar-active');
        mainWrapper.classList.add('sidebar-active');
        if (navbar) {
            navbar.classList.add('sidebar-active');
        }
        document.body.style.overflow = 'hidden'; // Prevent background scroll
    }

    function closeSidebar() {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        sidebarToggle.classList.remove('sidebar-active');
        mainWrapper.classList.remove('sidebar-active');
        if (navbar) {
            navbar.classList.remove('sidebar-active');
        }
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
