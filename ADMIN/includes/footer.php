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
    <div class="footer-top">
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
                            <li><a href="employees.php"><i class="bi bi-people"></i> Employees</a></li>
                            <li><a href="reports.php"><i class="bi bi-file-earmark-text"></i> Reports</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer Bottom -->
    <div class="footer-bottom">
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
                    <p class="text-muted">Professional Inventory Management System</p>
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
                <button type="button" class="btn btn-success" onclick="window.open('https://pilar.gov.ph')">Official Website</button>
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
</body>
</html>
