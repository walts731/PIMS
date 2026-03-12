<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark" id="mainNavbar" style="background: var(--primary-gradient);">
    <div class="container-fluid">
        <!-- Logo and Brand -->
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <?php 
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
                $system_settings['system_logo'] = '';
                $system_settings['system_name'] = 'PIMS';
            }
            
            $logo_path = !empty($system_settings['system_logo']) ? '../img/trans_logo.png' : htmlspecialchars($system_settings['system_logo']);
            $system_name = htmlspecialchars($system_settings['system_name'] ?? 'PIMS');
            ?>
            <img src="<?php echo $logo_path; ?>" alt="<?php echo $system_name; ?> Logo" 
                 style="height: 35px; margin-right: 10px; border-radius: 6px;">
            <span>
                <i class="bi bi-speedometer2"></i>
                <?php echo ucfirst($page_title ?? 'Dashboard'); ?>
            </span>
        </a>
        
        <div class="navbar-nav ms-auto align-items-center">
            <!-- Date and Time Display -->
            <div class="nav-item me-3">
                <div class="datetime-display">
                    <span class="date" id="currentDate"></span>
                    <span class="time-separator"> • </span>
                    <span class="time" id="currentTime"></span>
                </div>
            </div>
            
            <!-- User Menu -->
            <div class="nav-item dropdown">
                <a class="nav-link text-white dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header">Admin Panel</h6></li>
                    <li><a class="dropdown-item" href="#">
                        <i class="bi bi-person"></i> My Profile
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<style>
/* Topbar Styles */
#mainNavbar {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 0.5rem 0;
}

.navbar-brand {
    font-weight: 600;
    color: white !important;
}

.navbar-brand:hover {
    color: rgba(255,255,255,0.8) !important;
}

.datetime-display {
    color: white;
    font-size: 0.875rem;
    font-weight: 500;
}

.dropdown-menu {
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
    border-radius: 0.5rem;
}

.dropdown-item {
    color: #333;
    padding: 0.5rem 1rem;
}

.dropdown-item:hover {
    background: #f8f9fa;
    color: var(--primary-color);
}

.dropdown-header {
    color: #6c757d;
    font-size: 0.75rem;
    text-transform: uppercase;
    font-weight: 600;
}
</style>

<script>
// Update date and time
function updateDateTime() {
    const now = new Date();
    
    // Format date
    const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
    const dateString = now.toLocaleDateString('en-US', options);
    
    // Format time
    const timeString = now.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: true 
    });
    
    // Update elements if they exist
    const dateElement = document.getElementById('currentDate');
    const timeElement = document.getElementById('currentTime');
    
    if (dateElement) dateElement.textContent = dateString;
    if (timeElement) timeElement.textContent = timeString;
}

// Initialize and update every second
document.addEventListener('DOMContentLoaded', function() {
    updateDateTime();
    setInterval(updateDateTime, 1000);
});
</script>
