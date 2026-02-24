<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mainWrapper = document.getElementById('mainWrapper');
    const navbar = document.querySelector('.navbar');

    if (!sidebarToggle || !sidebar || !sidebarOverlay || !mainWrapper) {
        return;
    }

    sidebarToggle.addEventListener('click', function() {
        toggleSidebar();
    });

    sidebarOverlay.addEventListener('click', function() {
        closeSidebar();
    });

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
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        sidebarToggle.classList.remove('sidebar-active');
        mainWrapper.classList.remove('sidebar-active');
        if (navbar) {
            navbar.classList.remove('sidebar-active');
        }
        document.body.style.overflow = '';
    }
});
</script>
