<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dark Mode Test - PIMS</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    <?php require_once 'includes/dark-mode-init.php'; ?>
</head>
<body>
    <div class="main-wrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
        <div class="main-content">
            <div class="page-header">
                <h1><i class="bi bi-moon-stars"></i> Dark Mode Test</h1>
                <p class="text-muted">Test dark mode functionality across different components</p>
            </div>
            
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-value">Test</div>
                        <div class="stat-label">Statistics Card</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-value">Dark</div>
                        <div class="stat-label">Mode Card</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-value">Mode</div>
                        <div class="stat-label">Testing Card</div>
                    </div>
                </div>
            </div>
            
            <div class="section-card">
                <h5 class="mb-3">Form Elements Test</h5>
                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Text Input</label>
                            <input type="text" class="form-control" placeholder="Test input">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Select Dropdown</label>
                            <select class="form-select">
                                <option>Option 1</option>
                                <option>Option 2</option>
                                <option>Option 3</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="testCheck">
                                <label class="form-check-label" for="testCheck">
                                    Test checkbox
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-primary me-2">Primary Button</button>
                            <button type="button" class="btn btn-secondary me-2">Secondary Button</button>
                            <button type="button" class="btn btn-success me-2">Success Button</button>
                            <button type="button" class="btn btn-outline-primary">Outline Primary</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="section-card">
                <h5 class="mb-3">Status Badges Test</h5>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge status-active">Active</span>
                    <span class="badge status-inactive">Inactive</span>
                    <span class="badge status-pending">Pending</span>
                    <span class="badge status-serviceable">Serviceable</span>
                    <span class="badge status-unserviceable">Unserviceable</span>
                    <span class="badge status-borrowed">Borrowed</span>
                </div>
            </div>
            
            <div class="section-card">
                <h5 class="mb-3">Alerts Test</h5>
                <div class="alert alert-success">Success alert message</div>
                <div class="alert alert-danger">Danger alert message</div>
                <div class="alert alert-warning">Warning alert message</div>
                <div class="alert alert-info">Info alert message</div>
            </div>
            
            <div class="section-card">
                <h5 class="mb-3">Comprehensive Table Test</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Asset Name</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Value</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="active">
                                <td><strong>001</strong></td>
                                <td>Laptop Computer</td>
                                <td><span class="badge bg-primary">IT Equipment</span></td>
                                <td><span class="badge status-serviceable">Serviceable</span></td>
                                <td class="text-success">₱45,000</td>
                                <td class="table-actions">
                                    <button class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-info"><i class="bi bi-eye"></i></button>
                                    <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>002</strong></td>
                                <td>Office Desk</td>
                                <td><span class="badge bg-secondary">Furniture</span></td>
                                <td><span class="badge status-borrowed">Borrowed</span></td>
                                <td class="text-warning">₱12,500</td>
                                <td class="table-actions">
                                    <button class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-info"><i class="bi bi-eye"></i></button>
                                    <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                            <tr class="table-warning">
                                <td><strong>003</strong></td>
                                <td>Printer</td>
                                <td><span class="badge bg-primary">IT Equipment</span></td>
                                <td><span class="badge status-unserviceable">Unserviceable</span></td>
                                <td class="text-danger">₱8,200</td>
                                <td class="table-actions">
                                    <button class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-warning"><i class="bi bi-tools"></i></button>
                                    <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>004</strong></td>
                                <td>Conference Table</td>
                                <td><span class="badge bg-secondary">Furniture</span></td>
                                <td><span class="badge status-active">Serviceable</span></td>
                                <td class="text-muted">₱25,000</td>
                                <td class="table-actions">
                                    <button class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-info"><i class="bi bi-eye"></i></button>
                                    <button class="btn btn-sm btn-success"><i class="bi bi-check-circle"></i></button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4">Total Value</th>
                                <th class="text-success">₱90,700</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="mt-4">
                    <h6 class="mb-3">Table Variations</h6>
                    
                    <!-- Condensed Table -->
                    <div class="mb-4">
                        <h6 class="text-muted">Condensed Table</h6>
                        <table class="table table-sm table-dark">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>A001</td>
                                    <td>Item A</td>
                                    <td><span class="badge bg-success">Active</span></td>
                                </tr>
                                <tr>
                                    <td>A002</td>
                                    <td>Item B</td>
                                    <td><span class="badge bg-warning">Pending</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Borderless Table -->
                    <div class="mb-4">
                        <h6 class="text-muted">Borderless Table</h6>
                        <table class="table table-borderless">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Paper A4</td>
                                    <td>500 sheets</td>
                                    <td class="text-success">In Stock</td>
                                </tr>
                                <tr>
                                    <td>Pens</td>
                                    <td>120 pieces</td>
                                    <td class="text-warning">Low Stock</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="section-card">
                <h5 class="mb-3">Dark Mode Controls</h5>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" class="btn btn-primary" onclick="darkMode.toggle()">
                        <i class="bi bi-moon"></i> Toggle Dark Mode
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="darkMode.set(true)">
                        <i class="bi bi-moon-fill"></i> Enable Dark Mode
                    </button>
                    <button type="button" class="btn btn-light" onclick="darkMode.set(false)">
                        <i class="bi bi-sun"></i> Disable Dark Mode
                    </button>
                    <small class="text-muted">Current mode: <span id="currentMode">Checking...</span></small>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        <strong>Keyboard Shortcut:</strong> Ctrl/Cmd + Shift + D to toggle dark mode
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update current mode display
        function updateCurrentMode() {
            const modeElement = document.getElementById('currentMode');
            if (modeElement) {
                modeElement.textContent = darkMode.isEnabled() ? 'Dark Mode' : 'Light Mode';
                modeElement.className = darkMode.isEnabled() ? 'text-warning' : 'text-info';
            }
        }
        
        // Update mode display when page loads
        document.addEventListener('DOMContentLoaded', updateCurrentMode);
        
        // Update mode display when dark mode changes
        const originalToggle = window.darkMode.toggle;
        window.darkMode.toggle = function() {
            originalToggle.call(this);
            updateCurrentMode();
        };
        
        const originalSet = window.darkMode.set;
        window.darkMode.set = function(enabled) {
            originalSet.call(this, enabled);
            updateCurrentMode();
        };
    </script>
</body>
</html>
