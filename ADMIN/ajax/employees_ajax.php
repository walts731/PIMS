<?php
session_start();
require_once '../../config.php';
require_once '../../includes/system_functions.php';
require_once '../../includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// GET employee data for editing
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_employee') {
    $id = intval($_GET['id'] ?? 0);
    
    try {
        $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($employee = $result->fetch_assoc()) {
            $response = ['success' => true, 'data' => $employee];
        } else {
            $response = ['success' => false, 'message' => 'Employee not found'];
        }
        $stmt->close();
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

// UPDATE - Edit employee
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $office_id = intval($_POST['office_id'] ?? 0);
    $position = trim($_POST['position'] ?? '');
    $employment_status = trim($_POST['employment_status'] ?? 'permanent');
    $clearance_status = trim($_POST['clearance_status'] ?? 'uncleared');
    
    // Validation
    if (empty($firstname)) {
        $response['message'] = "First name is required.";
    } elseif (empty($lastname)) {
        $response['message'] = "Last name is required.";
    } elseif (empty($email)) {
        $response['message'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Please enter a valid email address.";
    } elseif ($office_id <= 0) {
        $response['message'] = "Please select an office.";
    } else {
        try {
            // Check if employee exists
            $check_stmt = $conn->prepare("SELECT id FROM employees WHERE id = ?");
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows == 0) {
                $response['message'] = "Employee not found.";
            } else {
                // Update employee
                $update_sql = "UPDATE employees SET firstname = ?, lastname = ?, email = ?, phone = ?, office_id = ?, position = ?, employment_status = ?, clearance_status = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sssissssi", $firstname, $lastname, $email, $phone, $office_id, $position, $employment_status, $clearance_status, $id);
                
                if ($update_stmt->execute()) {
                    logSystemAction($_SESSION['user_id'], 'update', 'employees', "Updated employee: $firstname $lastname");
                    $response = ['success' => true, 'message' => "Employee updated successfully!"];
                } else {
                    $response['message'] = "Error updating employee.";
                }
                $update_stmt->close();
            }
            $check_stmt->close();
        } catch (Exception $e) {
            error_log("Error updating employee: " . $e->getMessage());
            $response['message'] = "Database error occurred.";
        }
    }
}

// ADD - Create new employee
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $office_id = intval($_POST['office_id'] ?? 0);
    $position = trim($_POST['position'] ?? '');
    $employment_status = trim($_POST['employment_status'] ?? 'permanent');
    $clearance_status = trim($_POST['clearance_status'] ?? 'uncleared');
    
    // Validation
    if (empty($firstname)) {
        $response['message'] = "First name is required.";
    } elseif (empty($lastname)) {
        $response['message'] = "Last name is required.";
    } elseif (empty($email)) {
        $response['message'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Please enter a valid email address.";
    } elseif ($office_id <= 0) {
        $response['message'] = "Please select an office.";
    } else {
        try {
            // Generate employee number
            $year = date('Y');
            $prefix = 'EMP';
            
            // Get the last employee number for this year
            $last_stmt = $conn->prepare("SELECT employee_no FROM employees WHERE employee_no LIKE ? ORDER BY employee_no DESC LIMIT 1");
            $last_pattern = $prefix . $year . '%';
            $last_stmt->bind_param("s", $last_pattern);
            $last_stmt->execute();
            $last_result = $last_stmt->get_result();
            
            if ($last_row = $last_result->fetch_assoc()) {
                $last_number = intval(substr($last_row['employee_no'], -4));
                $new_number = $last_number + 1;
            } else {
                $new_number = 1;
            }
            $last_stmt->close();
            
            $employee_no = $prefix . $year . str_pad($new_number, 4, '0', STR_PAD_LEFT);
            
            // Insert employee
            $insert_sql = "INSERT INTO employees (employee_no, firstname, lastname, email, phone, office_id, position, employment_status, clearance_status, created_at, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssssissssi", $employee_no, $firstname, $lastname, $email, $phone, $office_id, $position, $employment_status, $clearance_status, $_SESSION['user_id']);
            
            if ($insert_stmt->execute()) {
                logSystemAction($_SESSION['user_id'], 'create', 'employees', "Added new employee: $firstname $lastname ($employee_no)");
                $response = ['success' => true, 'message' => "Employee added successfully!", 'employee_no' => $employee_no];
            } else {
                $response['message'] = "Error adding employee.";
            }
            $insert_stmt->close();
        } catch (Exception $e) {
            error_log("Error adding employee: " . $e->getMessage());
            $response['message'] = "Database error occurred.";
        }
    }
}

// DELETE - Delete employee
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        $response['message'] = "Invalid employee ID.";
    } else {
        try {
            // Get employee info before deletion
            $info_stmt = $conn->prepare("SELECT firstname, lastname, employee_no FROM employees WHERE id = ?");
            $info_stmt->bind_param("i", $id);
            $info_stmt->execute();
            $info_result = $info_stmt->get_result();
            
            if ($employee = $info_result->fetch_assoc()) {
                // Delete employee
                $delete_stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
                $delete_stmt->bind_param("i", $id);
                
                if ($delete_stmt->execute()) {
                    logSystemAction($_SESSION['user_id'], 'delete', 'employees', "Deleted employee: {$employee['firstname']} {$employee['lastname']} ({$employee['employee_no']})");
                    $response = ['success' => true, 'message' => "Employee deleted successfully!"];
                } else {
                    $response['message'] = "Error deleting employee.";
                }
                $delete_stmt->close();
            } else {
                $response['message'] = "Employee not found.";
            }
            $info_stmt->close();
        } catch (Exception $e) {
            error_log("Error deleting employee: " . $e->getMessage());
            $response['message'] = "Database error occurred.";
        }
    }
}

else {
    $response['message'] = "Invalid action.";
}

echo json_encode($response);
?>
