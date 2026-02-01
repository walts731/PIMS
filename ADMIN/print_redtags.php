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

// Get tag IDs from URL
$tag_ids = trim($_GET['ids'] ?? '');
if (empty($tag_ids)) {
    echo 'No red tags selected';
    exit();
}

// Convert comma-separated IDs to array
$tag_id_array = explode(',', $tag_ids);

// Get multiple red tags from database
$red_tags = [];
try {
    // Create placeholders for IN clause
    $placeholders = str_repeat('?,', count($tag_id_array));
    $placeholders = rtrim($placeholders, ',');
    
    $sql = "SELECT * FROM red_tags WHERE id IN ($placeholders) ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    $types = str_repeat('i', count($tag_id_array));
    $stmt->bind_param($types, ...$tag_id_array);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $red_tags[] = $row;
        }
    }
    $stmt->close();
    
    // Debug logging
    error_log("Print Red Tags Debug:");
    error_log("Tag IDs: " . implode(',', $tag_id_array));
    error_log("SQL: " . $sql);
    error_log("Types: " . $types);
    error_log("Found tags: " . count($red_tags));
    
} catch (Exception $e) {
    error_log("Error fetching red tags: " . $e->getMessage());
    echo 'Error fetching red tags: ' . $e->getMessage();
    exit();
}

if (empty($red_tags)) {
    echo 'Red tags not found';
    exit();
}

// Log the print action
logSystemAction($_SESSION['user_id'], 'print', 'red_tags', "Printed multiple red tags: " . count($red_tags) . " tags");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Red Tags - Multiple</title>
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
            max-width: 8.5in;
            margin: 0 auto;
            padding: 8px;
            position: relative;
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr 1fr;
            gap: 12px;
            min-height: 100vh;
        }
        
        .tag-container {
            width: 100%;
            height: 3.2in;
            border: 2px solid #dc3545;
            padding: 10px;
            background: white;
            page-break-inside: avoid;
            display: flex;
            flex-direction: column;
        }
        
        .tag-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        
        .tag-main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .tag-logo {
            width: 35px;
            height: 35px;
            border: 2px solid #000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5px;
            text-align: center;
            font-weight: bold;
        }
        
        .tag-logo .header-logo {
            max-width: 32px;
            max-height: 32px;
            border-radius: 50%;
            object-fit: contain;
        }
        
        .tag-government {
            text-align: center;
            flex: 1;
            margin: 0 8px;
        }
        
        .tag-government .republic {
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            margin-bottom: 1px;
        }
        
        .tag-government .province {
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 1px;
        }
        
        .tag-government .municipality {
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .tag-number {
            text-align: right;
            font-size: 11px;
            font-weight: bold;
            color: #dc3545;
        }
        
        .tag-title {
            font-size: 14px;
            font-weight: bold;
            color: #dc3545;
            margin: 0;
        }
        
        .tag-subtitle {
            font-size: 9px;
            color: #666;
            margin: 4px 0 0 0;
        }
        
        .tag-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 7px;
        }
        
        .tag-section {
            margin-bottom: 12px;
        }
        
        .tag-row {
            display: flex;
            align-items: flex-start;
            gap: 4px;
        }
        
        .tag-label {
            width: 60px;
            font-weight: bold;
            flex-shrink: 0;
            font-size: 6px;
        }
        
        .tag-value {
            flex: 1;
            border-bottom: 1px solid #000;
            min-height: 10px;
            padding: 1px 2px;
            font-size: 6px;
        }
        
        .tag-checkboxes {
            display: flex;
            gap: 8px;
            margin-top: 8px;
            flex-wrap: wrap;
        }
        
        .tag-checkbox {
            display: flex;
            align-items: center;
            gap: 2px;
        }
        
        .tag-checkbox input[type="checkbox"] {
            width: 9px;
            height: 9px;
        }
        
        .tag-checkbox label {
            font-size: 6px;
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
                margin: 0.15in 0.25in 0.15in 0.25in; /* top right bottom left */
            }
            
            html {
                overflow: hidden;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <?php 
        // Group red tags into pages of 6
        $chunks = array_chunk($red_tags, 6);
        
        foreach ($chunks as $page_index => $page_tags): 
        ?>
            <div class="tags-grid">
                <?php 
                // Fill remaining slots with empty tags to maintain grid
                $page_tags_with_empty = array_pad($page_tags, 6, null);
                
                foreach ($page_tags_with_empty as $red_tag): 
                    if ($red_tag):
                ?>
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
                        
                        <div class="tag-body">
                            <div class="tag-section">
                                <div class="tag-row">
                                    <div class="tag-label">Control No.:</div>
                                    <div class="tag-value"><?php echo htmlspecialchars($red_tag['control_no']); ?></div>
                                </div>
                                <div class="tag-row">
                                    <div class="tag-label">Date Received:</div>
                                    <div class="tag-value"><?php echo date('M j, Y', strtotime($red_tag['date_received'])); ?></div>
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
                                    <div class="tag-label">Reason:</div>
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
                <?php else: ?>
                    <!-- Empty tag container to maintain grid -->
                    <div class="tag-container" style="border: 2px dashed #ccc;">
                        <!-- Empty space -->
                    </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
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
