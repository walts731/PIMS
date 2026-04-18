<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role'] !== 'system_admin') {
    header('Location: ../index.php');
    exit();
}

// Log export action
logSystemAction($_SESSION['user_id'], 'export', 'categories', 'System admin exported categories data');

try {
    // Get categories data
    $query = "SELECT 
                category_name, 
                category_code, 
                description, 
                depreciation_rate, 
                useful_life_years, 
                CASE 
                    WHEN status = 'active' THEN 'Active'
                    ELSE 'Inactive'
                END as status,
                created_at,
                updated_at
              FROM asset_categories 
              ORDER BY category_name ASC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Error fetching categories data");
    }
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="categories_export_' . date('Y-m-d_H-i-s') . '.csv"');
    
    // Prevent caching
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM to fix Excel encoding issues
    fwrite($output, "\xEF\xBB\xBF");
    
    // CSV headers
    $headers = [
        'Category Name',
        'Category Code', 
        'Description',
        'Depreciation Rate (%)',
        'Useful Life (Years)',
        'Status',
        'Created At',
        'Updated At'
    ];
    
    fputcsv($output, $headers);
    
    // Write data rows
    while ($row = $result->fetch_assoc()) {
        $csv_row = [
            $row['category_name'],
            $row['category_code'],
            $row['description'],
            $row['depreciation_rate'],
            $row['useful_life_years'],
            $row['status'],
            $row['created_at'],
            $row['updated_at']
        ];
        
        fputcsv($output, $csv_row);
    }
    
    // Close output stream
    fclose($output);
    
    // Log successful export
    logSystemAction($_SESSION['user_id'], 'export', 'categories', 'Categories exported successfully: ' . $result->num_rows . ' records');
    
} catch (Exception $e) {
    // Log error
    logSystemAction($_SESSION['user_id'], 'error', 'categories', 'Export failed: ' . $e->getMessage());
    
    // Show error message (optional - you could redirect back with error)
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Error exporting categories: ' . $e->getMessage();
}
?>
