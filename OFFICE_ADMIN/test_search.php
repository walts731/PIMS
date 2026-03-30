<?php
session_start();
require_once '../config.php';

// Simple session check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role'] !== 'office_admin') {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Test - Office Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #191ba9;
            --secondary-color: #5cc2f2;
            --primary-gradient: linear-gradient(135deg, #191ba9 0%, #5cc2f2 100%);
            --border-radius: 8px;
        }
        
        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .test-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 2rem auto;
            max-width: 800px;
            padding: 2rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/topbar.php'; ?>
    
    <div class="container">
        <div class="test-container">
            <h1 class="mb-4"><i class="bi bi-search"></i> Search Functionality Test</h1>
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Test Instructions:</strong>
                <ul class="mb-0 mt-2">
                    <li>Use the search bar in the top navigation</li>
                    <li>Try searching for: "laptop", "ASUS", "request", or user names</li>
                    <li>Press Ctrl+K to quickly focus the search bar</li>
                    <li>Click on results to navigate to relevant pages</li>
                </ul>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-laptop"></i> Asset Search</h5>
                        </div>
                        <div class="card-body">
                            <p>Search for assets by:</p>
                            <ul>
                                <li>Description (e.g., "laptop", "printer")</li>
                                <li>Model (e.g., "ASUS", "HP")</li>
                                <li>Serial number</li>
                                <li>Property number</li>
                                <li>End user name</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-arrow-left-right"></i> Request Search</h5>
                        </div>
                        <div class="card-body">
                            <p>Search for requests by:</p>
                            <ul>
                                <li>Purpose/description</li>
                                <li>Asset description</li>
                                <li>Requester name</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-person"></i> User Search</h5>
                        </div>
                        <div class="card-body">
                            <p>Search for users by:</p>
                            <ul>
                                <li>First name</li>
                                <li>Last name</li>
                                <li>Email</li>
                                <li>Position</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-keyboard"></i> Keyboard Shortcuts</h5>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li><kbd>Ctrl</kbd> + <kbd>K</kbd> - Focus search</li>
                                <li><kbd>Esc</kbd> - Close search results</li>
                                <li><kbd>Enter</kbd> - Perform search</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <h5><i class="bi bi-gear"></i> Search Features</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="badge bg-primary mb-2">Real-time Search</div>
                        <p class="small">Results appear as you type (after 2 characters)</p>
                    </div>
                    <div class="col-md-4">
                        <div class="badge bg-success mb-2">Multi-source</div>
                        <p class="small">Search across assets, requests, and users</p>
                    </div>
                    <div class="col-md-4">
                        <div class="badge bg-info mb-2">Relevance Scoring</div>
                        <p class="small">Results ranked by relevance</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
