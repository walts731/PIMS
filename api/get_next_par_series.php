<?php
// API endpoint to get next available PAR series number
require_once '../config.php';

header('Content-Type: application/json');

try {
    // Get next series number for PAR forms
    $next_par_series = '0001';
    
    // Query to get maximum series number from PAR forms
    // Pattern: OfficeP-Year-Month-Series (e.g., OMMP-2026-02-0002)
    $query = "SELECT MAX(CAST(SUBSTRING(par_no, -4, 4) AS UNSIGNED)) as max_series 
              FROM par_forms 
              WHERE par_no LIKE '%P-%' 
              AND par_no REGEXP 'P-[0-9]{4}-[0-9]{2}-[0-9]{4}$'";
    
    $result = $conn->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        $max_series = $row['max_series'];
        if ($max_series) {
            $next_par_series = str_pad($max_series + 1, 4, '0', STR_PAD_LEFT);
        }
    }
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'next_par_series' => $next_par_series,
        'current_year' => date('Y'),
        'current_month' => date('m')
    ]);
    
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'next_par_series' => '0001'
    ]);
}
?>
