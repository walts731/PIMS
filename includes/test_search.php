<?php
session_start();
require_once '../config.php';

// Simple test to check if database connection works
echo "<h1>Testing Employee Search</h1>";

try {
    // Test database connection
    echo "<p>Database connection: " . ($conn ? "OK" : "FAILED") . "</p>";
    
    // Test query with a simple search
    $stmt = $conn->prepare("SELECT e.id, e.employee_no, e.firstname, e.lastname, e.position, 
                                   o.office_name
                            FROM employees e 
                            LEFT JOIN offices o ON e.office_id = o.id 
                            WHERE (e.firstname LIKE ? OR e.lastname LIKE ? OR e.employee_no LIKE ? OR e.position LIKE ?)
                            AND e.employment_status NOT IN ('resigned', 'retired')
                            ORDER BY e.firstname, e.lastname
                            LIMIT 10");
    
    $searchParam = "%a%"; // Search for anything with 'a'
    $stmt->bind_param("ssss", $searchParam, $searchParam, $searchParam, $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    
    echo "<p>Found " . count($employees) . " employees:</p>";
    echo "<pre>";
    print_r($employees);
    echo "</pre>";
    
    // Test JSON output
    header('Content-Type: application/json');
    echo "<h2>JSON Output:</h2>";
    echo "<pre>";
    echo json_encode($employees, JSON_PRETTY_PRINT);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
