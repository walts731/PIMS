<?php
// Get current year for copyright
$current_year = date('Y');
// Get system settings for footer
$system_settings = getSystemSettings();
$system_name = $system_settings['system_name'] ?? 'PIMS';
$system_version = $system_settings['system_version'] ?? '1.0.0';
?>
<!-- Professional Footer -->
<footer class="admin-footer">
    <!-- Animated Ship -->
    <div class="ship-container">
        <div class="ship">
            <div class="ship-mast"></div>
            <div class="ship-sail-main"></div>
            <div class="ship-sail-secondary"></div>
            <div class="ship-flag"></div>
            <div class="ship-hull"></div>
            <div class="ship-deck"></div>
        </div>
    </div>
    <div class="wave"></div>
    
    <div class="footer-top">
        <!-- Night Sky -->
        <div class="night-sky">
            <div class="moon"></div>
            <div class="stars">
                <div class="star star1"></div>
                <div class="star star2"></div>
                <div class="star star3"></div>
                <div class="star star4"></div>
                <div class="star star5"></div>
                <div class="star star6"></div>
                <div class="star star7"></div>
                <div class="star star8"></div>
            </div>
            <!-- Floating Clouds -->
            <div class="clouds">
                <div class="cloud cloud1"></div>
                <div class="cloud cloud2"></div>
                <div class="cloud cloud3"></div>
                <div class="cloud cloud4"></div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row">
                <!-- System Information -->
                <div class="col-lg-6 col-md-6 mb-4 mb-lg-0">
                    <div class="footer-section">
                        <div class="footer-logo">
                            <img src="<?php echo $logo_path ?? '../assets/images/logo.png'; ?>" alt="<?php echo htmlspecialchars($system_name); ?>" class="img-fluid" style="max-height: 40px;">
                            <h5 class="footer-title"><?php echo htmlspecialchars($system_name); ?></h5>
                        </div>
                        <p class="footer-description">
                            Professional Inventory Management System designed for efficient asset tracking and management.
                        </p>
                        <div class="footer-version">
                            <span class="badge bg-primary">Version <?php echo htmlspecialchars($system_version); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="col-lg-6 col-md-6 mb-4 mb-lg-0">
                    <div class="footer-section">
                        <h6 class="footer-heading">Quick Links</h6>
                        <ul class="footer-links">
                            <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                            <li><a href="asset_items.php"><i class="bi bi-box"></i> Assets</a></li>
                            <li><a href="consumables.php"><i class="bi bi-water"></i> Consumables</a></li>
                            <li><a href="reports.php"><i class="bi bi-file-earmark-text"></i> Reports</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <!-- Sea Weeds -->
        <div class="sea-weeds">
            <div class="seaweed seaweed1"></div>
            <div class="seaweed seaweed2"></div>
            <div class="seaweed seaweed3"></div>
            <div class="seaweed seaweed4"></div>
            <div class="seaweed seaweed5"></div>
        </div>
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="copyright">
                        <p class="mb-0">
                            &copy; <?php echo $current_year; ?> <?php echo htmlspecialchars($system_name); ?>. 
                            All rights reserved. | Developed by BUP Interns
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="footer-bottom-links">
                        <a href="#" onclick="showSystemInfo(); return false;"><i class="bi bi-info-circle"></i> System Info</a>
                        <a href="#" onclick="showHelpModal(); return false;"><i class="bi bi-question-circle"></i> Help</a>
                        <a href="#" onclick="showAboutModal(); return false;"><i class="bi bi-building"></i> About</a>
                        <a href="#" onclick="showLogoutModal(); return false;"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- System Info Modal -->
<div class="modal fade" id="systemInfoModal" tabindex="-1" aria-labelledby="systemInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="systemInfoModalLabel">
                    <i class="bi bi-info-circle me-2"></i>System Information
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary">System Details</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>System Name:</strong></td>
                                <td><?php echo htmlspecialchars($system_name); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Version:</strong></td>
                                <td><?php echo htmlspecialchars($system_version); ?></td>
                            </tr>
                            <tr>
                                <td><strong>PHP Version:</strong></td>
                                <td><?php echo PHP_VERSION; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Server:</strong></td>
                                <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary">Session Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Username:</strong></td>
                                <td><?php echo htmlspecialchars($_SESSION['username'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Role:</strong></td>
                                <td><?php echo ucfirst(htmlspecialchars($_SESSION['role'] ?? 'N/A')); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Session Start:</strong></td>
                                <td><?php echo date('Y-m-d H:i:s', $_SESSION['login_time'] ?? time()); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="exportSystemInfo()">Export Info</button>
            </div>
        </div>
    </div>
</div>

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="helpModalLabel">
                    <i class="bi bi-question-circle me-2"></i>Help & Support
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="help-section">
                    <h6 class="text-info">Quick Help</h6>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-arrow-right text-primary"></i> Use the sidebar to navigate between modules</li>
                        <li><i class="bi bi-arrow-right text-primary"></i> Click on any asset to view detailed information</li>
                        <li><i class="bi bi-arrow-right text-primary"></i> Use the search bar to find assets quickly</li>
                        <li><i class="bi bi-arrow-right text-primary"></i> Scan QR codes for instant asset lookup</li>
                        <li><i class="bi bi-arrow-right text-primary"></i> Generate reports from the Reports section</li>
                    </ul>
                </div>
                <div class="help-section mt-3">
                    <h6 class="text-info">Contact Support</h6>
                    <p><strong>Email:</strong> support@pims.gov.ph</p>
                    <p><strong>Phone:</strong> (047) 123-4567</p>
                    <p><strong>Office Hours:</strong> Monday - Friday, 8:00 AM - 5:00 PM</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-info" onclick="window.open('user_manual.pdf')">User Manual</button>
            </div>
        </div>
    </div>
</div>

<!-- About Modal -->
<div class="modal fade" id="aboutModal" tabindex="-1" aria-labelledby="aboutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="aboutModalLabel">
                    <i class="bi bi-building me-2"></i>About PIMS
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <img src="<?php echo $logo_path ?? '../assets/images/logo.png'; ?>" alt="<?php echo htmlspecialchars($system_name); ?>" class="img-fluid mb-3" style="max-height: 80px;">
                    <h4 class="text-success"><?php echo htmlspecialchars($system_name); ?></h4>
                    <p class="text-muted">Pilar Inventory Management System</p>
                </div>
                <div class="about-content">
                    <h6 class="text-success">About the System</h6>
                    <p>PIMS is a comprehensive inventory management solution designed to streamline asset tracking, maintenance scheduling, and reporting for government organizations.</p>
                    
                    <h6 class="text-success mt-3">Key Features</h6>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-check-circle text-success"></i> Real-time asset tracking</li>
                        <li><i class="bi bi-check-circle text-success"></i> QR code generation and scanning</li>
                        <li><i class="bi bi-check-circle text-success"></i> Automated reporting</li>
                        <li><i class="bi bi-check-circle text-success"></i> User role management</li>
                        <li><i class="bi bi-check-circle text-success"></i> Mobile-responsive design</li>
                    </ul>
                    
                    <div class="text-center mt-4">
                        <p class="text-muted small">
                            Republic of the Philippines<br>
                            Municipality of Pilar, Province of Sorsogon<br>
                            &copy; <?php echo $current_year; ?> All Rights Reserved
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" onclick="window.open('https://pims.lgu-pilarsor.ph/')">Official Website</button>
            </div>
        </div>
    </div>
</div>

<script>
// Footer JavaScript functions
function showSystemInfo() {
    const modal = new bootstrap.Modal(document.getElementById('systemInfoModal'));
    modal.show();
}

function showHelpModal() {
    const modal = new bootstrap.Modal(document.getElementById('helpModal'));
    modal.show();
}

function showAboutModal() {
    const modal = new bootstrap.Modal(document.getElementById('aboutModal'));
    modal.show();
}

function showLogoutModal() {
    const modal = new bootstrap.Modal(document.getElementById('logoutModal'));
    modal.show();
}

function exportSystemInfo() {
    const systemInfo = {
        systemName: '<?php echo htmlspecialchars($system_name); ?>',
        version: '<?php echo htmlspecialchars($system_version); ?>',
        phpVersion: '<?php echo PHP_VERSION; ?>',
        server: '<?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>',
        username: '<?php echo htmlspecialchars($_SESSION['username'] ?? 'N/A'); ?>',
        role: '<?php echo ucfirst(htmlspecialchars($_SESSION['role'] ?? 'N/A')); ?>',
        sessionStart: '<?php echo date('Y-m-d H:i:s', $_SESSION['login_time'] ?? time()); ?>',
        exportDate: new Date().toISOString()
    };
    
    const dataStr = JSON.stringify(systemInfo, null, 2);
    const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
    
    const exportFileDefaultName = 'system_info_' + new Date().toISOString().slice(0,10) + '.json';
    
    const linkElement = document.createElement('a');
    linkElement.setAttribute('href', dataUri);
    linkElement.setAttribute('download', exportFileDefaultName);
    linkElement.click();
}

// Auto-update time in footer
setInterval(function() {
    const timeElements = document.querySelectorAll('.footer-info .info-item:nth-child(2) span');
    if (timeElements.length > 0) {
        timeElements[0].textContent = 'Last Updated: ' + new Date().toLocaleString();
    }
}, 60000); // Update every minute
</script>

<?php
// Helper function to get system settings
function getSystemSettings() {
    static $settings = null;
    if ($settings === null) {
        // In a real implementation, this would fetch from database
        $settings = [
            'system_name' => 'PIMS',
            'system_version' => '1.0.0',
            'organization' => 'Municipality of Pilar',
            'department' => 'IT Department'
        ];
    }
    return $settings;
}
?>
<style>
/* Animated Ship for Admin Footer */
.admin-footer {
    position: relative;
    overflow: hidden;
}

.ship-container {
    position: absolute;
    bottom: 45px;
    left: -100px;
    width: 100%;
    height: 60px;
    pointer-events: none;
    z-index: 10;
}

.ship {
    position: absolute;
    width: 80px;
    height: 60px;
    animation: sailAcross 20s linear infinite;
    transition: transform 0.3s ease;
}

.ship:hover {
    transform: scale(1.2);
}

/* Ship Mast */
.ship-mast {
    position: absolute;
    bottom: 18px;
    left: 38px;
    width: 4px;
    height: 35px;
    background: linear-gradient(to right, #4a4a4a, #2a2a2a);
    border-radius: 1px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

/* Main Sail */
.ship-sail-main {
    position: absolute;
    bottom: 35px;
    left: 15px;
    width: 0;
    height: 0;
    border-right: 28px solid #f8f8f8;
    border-top: 12px solid transparent;
    border-bottom: 12px solid transparent;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
    transform: skewY(-2deg);
}

/* Secondary Sail */
.ship-sail-secondary {
    position: absolute;
    bottom: 40px;
    left: 42px;
    width: 0;
    height: 0;
    border-left: 20px solid #e8e8e8;
    border-top: 8px solid transparent;
    border-bottom: 8px solid transparent;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
    transform: skewY(1deg);
}

/* Ship Flag */
.ship-flag {
    position: absolute;
    bottom: 50px;
    left: 40px;
    width: 0;
    height: 0;
    border-left: 10px solid #FF6B6B;
    border-top: 5px solid transparent;
    border-bottom: 5px solid transparent;
    animation: flagWave 2s ease-in-out infinite;
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
}

/* Ship Hull */
.ship-hull {
    position: absolute;
    bottom: 0;
    width: 80px;
    height: 22px;
    background: linear-gradient(to bottom, #8B4513, #654321, #4a3018);
    border-radius: 0 0 40% 40%;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.4);
}

/* Ship Deck */
.ship-deck {
    position: absolute;
    bottom: 18px;
    left: 10px;
    width: 60px;
    height: 4px;
    background: linear-gradient(to right, #a0522d, #8B4513, #a0522d);
    border-radius: 2px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

/* Flag waving animation */
@keyframes flagWave {
    0%, 100% { transform: skewY(0deg); }
    50% { transform: skewY(5deg); }
}

@keyframes sailAcross {
    0% {
        left: -60px;
        transform: translateY(0px) rotate(-3deg);
    }
    20% {
        transform: translateY(-5px) rotate(-1deg);
    }
    40% {
        transform: translateY(3px) rotate(1deg);
    }
    60% {
        transform: translateY(-3px) rotate(-2deg);
    }
    80% {
        transform: translateY(2px) rotate(1deg);
    }
    100% {
        left: calc(100% + 60px);
        transform: translateY(0px) rotate(-3deg);
    }
}

/* Sea Weeds */
.sea-weeds {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 40px;
    pointer-events: none;
    z-index: 5;
}

.seaweed {
    position: absolute;
    bottom: 0;
    width: 6px;
    background: linear-gradient(to top, #2d5016, #4a7c28);
    border-radius: 2px 2px 0 0;
    transform-origin: bottom center;
}

.seaweed1 {
    left: 10%;
    height: 25px;
    animation: swaySeaweed 3s ease-in-out infinite;
}

.seaweed2 {
    left: 25%;
    height: 35px;
    animation: swaySeaweed 3.5s ease-in-out infinite 0.5s;
}

.seaweed3 {
    left: 45%;
    height: 30px;
    animation: swaySeaweed 4s ease-in-out infinite 1s;
}

.seaweed4 {
    left: 70%;
    height: 28px;
    animation: swaySeaweed 3.2s ease-in-out infinite 0.3s;
}

.seaweed5 {
    left: 85%;
    height: 32px;
    animation: swaySeaweed 3.8s ease-in-out infinite 0.7s;
}

@keyframes swaySeaweed {
    0%, 100% {
        transform: rotate(-3deg);
    }
    50% {
        transform: rotate(3deg);
    }
}

/* Wave effect */
.wave {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 40px;
    background: linear-gradient(90deg, 
        rgba(30, 86, 160, 0.3) 0%, 
        rgba(30, 86, 160, 0.5) 25%,
        rgba(30, 86, 160, 0.3) 50%,
        rgba(30, 86, 160, 0.5) 75%,
        rgba(30, 86, 160, 0.3) 100%);
    animation: wave 4s ease-in-out infinite;
    z-index: 1;
}

@keyframes wave {
    0%, 100% {
        transform: translateY(0px) scaleY(1);
    }
    50% {
        transform: translateY(-10px) scaleY(1.2);
    }
}

/* Night Sky */
.night-sky {
    position: absolute;
    top: 0;
    right: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

/* Moon */
.moon {
    position: absolute;
    top: 20px;
    right: 40px;
    width: 40px;
    height: 40px;
    background: radial-gradient(circle, #fffacd, #f0e68c);
    border-radius: 50%;
    box-shadow: 0 0 20px rgba(255, 250, 205, 0.8),
                0 0 40px rgba(255, 250, 205, 0.4),
                inset -5px -5px 10px rgba(240, 230, 140, 0.3);
    animation: moonGlow 4s ease-in-out infinite;
}

/* Stars */
.stars {
    position: absolute;
    top: 0;
    right: 0;
    width: 100%;
    height: 100%;
}

.star {
    position: absolute;
    background: white;
    border-radius: 50%;
    animation: twinkle 2s ease-in-out infinite;
}

.star1 {
    top: 15px;
    right: 100px;
    width: 3px;
    height: 3px;
    animation-delay: 0s;
}

.star2 {
    top: 35px;
    right: 150px;
    width: 2px;
    height: 2px;
    animation-delay: 0.3s;
}

.star3 {
    top: 25px;
    right: 200px;
    width: 4px;
    height: 4px;
    animation-delay: 0.6s;
}

.star4 {
    top: 45px;
    right: 120px;
    width: 2px;
    height: 2px;
    animation-delay: 0.9s;
}

.star5 {
    top: 10px;
    right: 180px;
    width: 3px;
    height: 3px;
    animation-delay: 1.2s;
}

.star6 {
    top: 40px;
    right: 250px;
    width: 2px;
    height: 2px;
    animation-delay: 1.5s;
}

.star7 {
    top: 30px;
    right: 80px;
    width: 3px;
    height: 3px;
    animation-delay: 1.8s;
}

.star8 {
    top: 50px;
    right: 220px;
    width: 2px;
    height: 2px;
    animation-delay: 2.1s;
}

/* Moon glow animation */
@keyframes moonGlow {
    0%, 100% {
        box-shadow: 0 0 20px rgba(255, 250, 205, 0.8),
                    0 0 40px rgba(255, 250, 205, 0.4),
                    inset -5px -5px 10px rgba(240, 230, 140, 0.3);
    }
    50% {
        box-shadow: 0 0 30px rgba(255, 250, 205, 1),
                    0 0 60px rgba(255, 250, 205, 0.6),
                    inset -5px -5px 10px rgba(240, 230, 140, 0.5);
    }
}

/* Star twinkle animation */
@keyframes twinkle {
    0%, 100% {
        opacity: 0.3;
        transform: scale(1);
    }
    50% {
        opacity: 1;
        transform: scale(1.2);
    }
}

/* Floating Clouds */
.clouds {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 2;
}

.cloud {
    position: absolute;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 100px;
    opacity: 0.6;
}

.cloud::before,
.cloud::after {
    content: '';
    position: absolute;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 100px;
}

.cloud1 {
    top: 15px;
    left: -60px;
    width: 35px;
    height: 15px;
    animation: floatCloud1 30s linear infinite;
}

.cloud1::before {
    top: -8px;
    left: 10px;
    width: 20px;
    height: 20px;
}

.cloud1::after {
    top: -5px;
    right: 8px;
    width: 15px;
    height: 15px;
}

.cloud2 {
    top: 40px;
    left: -80px;
    width: 45px;
    height: 18px;
    animation: floatCloud2 35s linear infinite 5s;
}

.cloud2::before {
    top: -10px;
    left: 15px;
    width: 25px;
    height: 25px;
}

.cloud2::after {
    top: -6px;
    right: 10px;
    width: 18px;
    height: 18px;
}

.cloud3 {
    top: 25px;
    left: -70px;
    width: 40px;
    height: 16px;
    animation: floatCloud3 40s linear infinite 10s;
}

.cloud3::before {
    top: -9px;
    left: 12px;
    width: 22px;
    height: 22px;
}

.cloud3::after {
    top: -5px;
    right: 9px;
    width: 16px;
    height: 16px;
}

.cloud4 {
    top: 50px;
    left: -90px;
    width: 50px;
    height: 20px;
    animation: floatCloud4 45s linear infinite 15s;
}

.cloud4::before {
    top: -12px;
    left: 18px;
    width: 28px;
    height: 28px;
}

.cloud4::after {
    top: -7px;
    right: 12px;
    width: 20px;
    height: 20px;
}

/* Cloud floating animations */
@keyframes floatCloud1 {
    0% {
        left: -60px;
        transform: translateY(0px);
    }
    25% {
        transform: translateY(-3px);
    }
    50% {
        transform: translateY(2px);
    }
    75% {
        transform: translateY(-2px);
    }
    100% {
        left: calc(100% + 60px);
        transform: translateY(0px);
    }
}

@keyframes floatCloud2 {
    0% {
        left: -80px;
        transform: translateY(0px);
    }
    25% {
        transform: translateY(2px);
    }
    50% {
        transform: translateY(-3px);
    }
    75% {
        transform: translateY(1px);
    }
    100% {
        left: calc(100% + 80px);
        transform: translateY(0px);
    }
}

@keyframes floatCloud3 {
    0% {
        left: -70px;
        transform: translateY(0px);
    }
    25% {
        transform: translateY(-2px);
    }
    50% {
        transform: translateY(3px);
    }
    75% {
        transform: translateY(-1px);
    }
    100% {
        left: calc(100% + 70px);
        transform: translateY(0px);
    }
}

@keyframes floatCloud4 {
    0% {
        left: -90px;
        transform: translateY(0px);
    }
    25% {
        transform: translateY(1px);
    }
    50% {
        transform: translateY(-2px);
    }
    75% {
        transform: translateY(2px);
    }
    100% {
        left: calc(100% + 90px);
        transform: translateY(0px);
    }
}
</style>
</body>
</html>
