<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

try {
    // Search employees by name, employee number, or position
    $stmt = $conn->prepare("SELECT e.id, e.employee_no, e.firstname, e.lastname, e.position, 
                                   o.office_name
                            FROM employees e 
                            LEFT JOIN offices o ON e.office_id = o.id 
                            WHERE (e.firstname LIKE ? OR e.lastname LIKE ? OR e.employee_no LIKE ? OR e.position LIKE ?)
                            AND e.employment_status NOT IN ('resigned', 'retired')
                            ORDER BY e.firstname, e.lastname
                            LIMIT 10");
    
    $searchParam = "%{$query}%";
    $stmt->bind_param("ssss", $searchParam, $searchParam, $searchParam, $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = [
            'id' => $row['id'],
            'employee_no' => $row['employee_no'],
            'firstname' => $row['firstname'],
            'lastname' => $row['lastname'],
            'position' => $row['position'],
            'office_name' => $row['office_name']
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($employees);
    
} catch (Exception $e) {
    error_log("Error searching employees: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
