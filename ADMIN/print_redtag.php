<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

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

// Get control number from URL
$control_no = trim($_GET['control_no'] ?? '');
$error_message = '';
$show_error = false;

if (empty($control_no)) {
    $error_message = 'Control number is required';
    $show_error = true;
} else {
    // Get red tag data from database
    $red_tag = [];
    try {
        $sql = "SELECT * FROM red_tags WHERE control_no = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $control_no);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            $red_tag = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching red tag: " . $e->getMessage());
        $error_message = 'Error fetching red tag data';
        $show_error = true;
    }

    if (empty($red_tag) && !$show_error) {
        $error_message = 'Red tag not found';
        $show_error = true;
    }
}

// If there's an error, show error page with modal
if ($show_error) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Red Tag Not Found</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F6F6F6 0%, #D6E4F0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .error-container {
            text-align: center;
            max-width: 500px;
            padding: 2rem;
        }
        
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }
        
        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
        }
        
        .error-message {
            color: #666;
            margin-bottom: 2rem;
        }
        
        .control-no-display {
            background: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-family: monospace;
            font-weight: 600;
            color: #dc3545;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <h1 class="error-title">Red Tag Not Found</h1>
        <p class="error-message">
            <?php echo htmlspecialchars($error_message); ?>
            <?php if (!empty($control_no)): ?>
                <div class="control-no-display">
                    Control No: <?php echo htmlspecialchars($control_no); ?>
                </div>
            <?php endif; ?>
        </p>
        <div class="d-flex gap-2 justify-content-center">
            <button type="button" class="btn btn-primary" onclick="window.close()">
                <i class="bi bi-x-circle"></i> Close Window
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                <i class="bi bi-arrow-left"></i> Go Back
            </button>
            <a href="create_redtag.php" class="btn btn-outline-info">
                <i class="bi bi-plus-circle"></i> Create New Red Tag
            </a>
        </div>
    </div>

    <!-- Auto-close modal after delay -->
    <script>
        // Show modal and auto-close after 5 seconds
        setTimeout(function() {
            if (confirm('Red tag not found. This window will close automatically.\n\nClick OK to close now or Cancel to stay on this page.')) {
                window.close();
            }
        }, 3000);
        
        // Close window if user clicks outside
        window.addEventListener('blur', function() {
            setTimeout(function() {
                window.close();
            }, 1000);
        });
    </script>
</body>
</html>
<?php
    exit();
}

// Log the print action
logSystemAction($_SESSION['user_id'], 'print', 'red_tag', "Printed red tag: {$control_no}");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Red Tag - <?php echo htmlspecialchars($red_tag['control_no']); ?></title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: white;
            margin: 0;
            padding: 0;
        }
        
        .print-container {
            width: 100%;
            max-width: 4in;
            margin: 0 auto;
            padding: 0;
            position: relative;
        }
        
        .tag-container {
            width: 4in;
            height: 4in;
            border: 2px solid #dc3545;
            padding: 15px;
            background: white;
            page-break-inside: avoid;
            display: flex;
            flex-direction: column;
        }
        
        .tag-header {
            text-align: center;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .tag-main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ddd;
        }
        
        .tag-logo {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 6px;
            text-align: center;
            font-weight: bold;
        }
        
        .tag-logo .header-logo {
            max-width: 36px;
            max-height: 36px;
            object-fit: contain;
        }
        
        .tag-government {
            text-align: center;
            flex: 1;
            margin: 0 10px;
        }
        
        .tag-government .republic {
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        
        .tag-government .province {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        
        .tag-government .municipality {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .tag-number {
            text-align: right;
            font-size: 12px;
            font-weight: bold;
            color: #dc3545;
        }
        
        .tag-title {
            font-size: 16px;
            font-weight: bold;
            color: #dc3545;
            margin: 0;
        }
        
        .tag-subtitle {
            font-size: 10px;
            color: #666;
            margin: 5px 0 0 0;
        }
        
        .tag-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 8px;
        }
        
        .tag-section {
            margin-bottom: 15px;
        }
        
        .tag-row {
            display: flex;
            align-items: flex-start;
            gap: 5px;
        }
        
        .tag-label {
            width: 70px;
            font-weight: bold;
            flex-shrink: 0;
            font-size: 7px;
        }
        
        .tag-value {
            flex: 1;
            border-bottom: 1px solid #000;
            min-height: 12px;
            padding: 1px 2px;
            font-size: 7px;
        }
        
        .tag-checkboxes {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        
        .tag-checkbox {
            display: flex;
            align-items: center;
            gap: 3px;
        }
        
        .tag-checkbox input[type="checkbox"] {
            width: 10px;
            height: 10px;
        }
        
        .tag-checkbox label {
            font-size: 7px;
        }
        
        .tag-footer {
            margin-top: auto;
            text-align: center;
            font-size: 7px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .print-container {
                padding: 0;
            }
            
            @page {
                size: Letter;
                margin: 0.5in;
            }
            
            html {
                overflow: hidden;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="tag-container">
            <div class="tag-main-header">
                <div class="tag-logo">
                    <?php 
                    $logo_path = '../img/trans_logo.png'; // default
                    if (!empty($system_settings['system_logo'])) {
                        if (file_exists('../' . $system_settings['system_logo'])) {
                            $logo_path = '../' . $system_settings['system_logo'];
                        } elseif (file_exists($system_settings['system_logo'])) {
                            $logo_path = $system_settings['system_logo'];
                        }
                    }
                    ?>
                    <img src="<?php echo $logo_path; ?>" alt="LGU Logo" class="header-logo">
                </div>
                <div class="tag-government">
                    <div class="republic">Republic of the Philippines</div>
                    <div class="province">Province of Sorsogon</div>
                    <div class="municipality">Municipality of Pilar</div>
                </div>
                <div class="tag-number">
                    Red Tag No:<br>
                    <?php echo htmlspecialchars($red_tag['red_tag_no']); ?>
                </div>
            </div>
            
            <div class="tag-header">
                <div class="tag-title">5S RED TAG</div>
            </div>
            
            <div class="tag-section">
                <div class="tag-row">
                    <div class="tag-label">Control No.:</div>
                    <div class="tag-value"><?php echo htmlspecialchars($red_tag['control_no']); ?></div>
                </div>
                <div class="tag-row">
                    <div class="tag-label">Date Received:</div>
                    <div class="tag-value"><?php echo date('F j, Y', strtotime($red_tag['date_received'])); ?></div>
                </div>
                <div class="tag-row">
                    <div class="tag-label">Tagged by:</div>
                    <div class="tag-value"><?php echo htmlspecialchars($red_tag['tagged_by']); ?></div>
                </div>
            </div>
            
            <div class="tag-section">
                <div class="tag-row">
                    <div class="tag-label">Item Location:</div>
                    <div class="tag-value"><?php echo htmlspecialchars($red_tag['item_location']); ?></div>
                </div>
                <div class="tag-row">
                    <div class="tag-label">Description:</div>
                    <div class="tag-value"><?php echo htmlspecialchars($red_tag['item_description']); ?></div>
                </div>
                <div class="tag-row">
                    <div class="tag-label">Reason for Removal:</div>
                    <div class="tag-value"><?php echo htmlspecialchars($red_tag['removal_reason']); ?></div>
                </div>
            </div>
            
            <div class="tag-section">
                <div class="tag-label">Action:</div>
                <div class="tag-checkboxes">
                    <div class="tag-checkbox">
                        <input type="checkbox" <?php echo ($red_tag['action'] === 'repair') ? 'checked' : ''; ?>>
                        <label>Repair</label>
                    </div>
                    <div class="tag-checkbox">
                        <input type="checkbox" <?php echo ($red_tag['action'] === 'recondition') ? 'checked' : ''; ?>>
                        <label>Recondition</label>
                    </div>
                    <div class="tag-checkbox">
                        <input type="checkbox" <?php echo ($red_tag['action'] === 'dispose') ? 'checked' : ''; ?>>
                        <label>Dispose</label>
                    </div>
                    <div class="tag-checkbox">
                        <input type="checkbox" <?php echo ($red_tag['action'] === 'relocate') ? 'checked' : ''; ?>>
                        <label>Relocate</label>
                    </div>
                    <?php if (!in_array($red_tag['action'], ['repair', 'recondition', 'dispose', 'relocate'])): ?>
                        <div class="tag-checkbox">
                            <input type="checkbox" checked>
                            <label><?php echo htmlspecialchars($red_tag['action']); ?></label>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        
        // Close window after printing
        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>
</html>
