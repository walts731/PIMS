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

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

// Simple XLSX reader without vendor autoload
class SimpleXLSXReader {
    private $filename;
    private $zip;
    private $sharedStrings = [];
    
    public function __construct($filename) {
        $this->filename = $filename;
    }
    
    public function open() {
        if (!file_exists($this->filename)) {
            throw new Exception("File not found: " . $this->filename);
        }
        
        // XLSX is a ZIP file containing XML files
        $this->zip = new ZipArchive();
        $result = $this->zip->open($this->filename);
        
        if ($result !== true) {
            throw new Exception("Cannot open XLSX file. Error code: " . $result);
        }
        
        // Load shared strings
        $this->loadSharedStrings();
        
        return true;
    }
    
    private function loadSharedStrings() {
        $sharedStringsFile = 'xl/sharedStrings.xml';
        if ($this->zip->locateName($sharedStringsFile) !== false) {
            $sharedStringsXml = $this->zip->getFromName($sharedStringsFile);
            $xml = simplexml_load_string($sharedStringsXml);
            
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    $this->sharedStrings[] = (string) $si->t;
                }
            }
        }
    }
    
    public function getSheetData($sheetIndex = 0) {
        $sheetFile = "xl/worksheets/sheet" . ($sheetIndex + 1) . ".xml";
        
        if ($this->zip->locateName($sheetFile) === false) {
            throw new Exception("Sheet not found: " . $sheetFile);
        }
        
        $sheetXml = $this->zip->getFromName($sheetFile);
        $xml = simplexml_load_string($sheetXml);
        
        $data = [];
        
        if ($xml && isset($xml->sheetData->row)) {
            foreach ($xml->sheetData->row as $row) {
                $rowData = [];
                $rowIndex = (int) $row['r'] - 1; // Convert to 0-based index
                
                foreach ($row->c as $cell) {
                    $cellReference = (string) $cell['r'];
                    $columnIndex = $this->getColumnIndex($cellReference);
                    
                    $cellValue = '';
                    if (isset($cell->v)) {
                        $cellValue = (string) $cell->v;
                        
                        // If cell type is shared string, get the actual string
                        if (isset($cell['t']) && $cell['t'] == 's') {
                            $stringIndex = (int) $cellValue;
                            if (isset($this->sharedStrings[$stringIndex])) {
                                $cellValue = $this->sharedStrings[$stringIndex];
                            }
                        }
                    }
                    
                    // Ensure array has enough columns
                    while (count($rowData) <= $columnIndex) {
                        $rowData[] = '';
                    }
                    $rowData[$columnIndex] = $cellValue;
                }
                
                $data[$rowIndex] = $rowData;
            }
        }
        
        // Re-index array to ensure sequential keys
        return array_values($data);
    }
    
    private function getColumnIndex($cellReference) {
        // Extract column letters (e.g., 'A' from 'A1', 'AB' from 'AB12')
        $letters = preg_replace('/[^A-Z]/', '', $cellReference);
        $index = 0;
        
        for ($i = 0; $i < strlen($letters); $i++) {
            $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
        }
        
        return $index - 1; // Convert to 0-based index
    }
    
    public function close() {
        if ($this->zip) {
            $this->zip->close();
        }
    }
}

$message = '';
$message_type = '';
$import_results = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['import_file'])) {
    $skip_duplicates = isset($_POST['skip_duplicates']) && $_POST['skip_duplicates'] === 'on';
    
    // Validate file upload
    if ($_FILES['import_file']['error'] != 0) {
        $message = "Error uploading file. Please try again.";
        $message_type = "danger";
    } else {
        $file_name = $_FILES['import_file']['name'];
        $file_tmp = $_FILES['import_file']['tmp_name'];
        $file_size = $_FILES['import_file']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validate file type and size
        if (!in_array($file_ext, ['xlsx', 'xls'])) {
            $message = "Invalid file format. Please upload an Excel file (.xlsx or .xls).";
            $message_type = "danger";
        } elseif ($file_size > 10 * 1024 * 1024) { // 10MB limit
            $message = "File size too large. Maximum size is 10MB.";
            $message_type = "danger";
        } else {
            try {
                // Move uploaded file
                $upload_dir = '../uploads/temp/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $uploaded_file = $upload_dir . 'import_' . time() . '.' . $file_ext;
                move_uploaded_file($file_tmp, $uploaded_file);
                
                // Read Excel file
                $reader = new SimpleXLSXReader($uploaded_file);
                $reader->open();
                
                $data = $reader->getSheetData(0);
                $reader->close();
                
                // Clean up uploaded file
                unlink($uploaded_file);
                
                if (empty($data)) {
                    $message = "No data found in the Excel file.";
                    $message_type = "danger";
                } else {
                    // Get a default office ID (use Head Office or first available office)
                    $default_office_id = null;
                    $office_result = $conn->query("SELECT id FROM offices WHERE status = 'active' LIMIT 1");
                    if ($row = $office_result->fetch_assoc()) {
                        $default_office_id = $row['id'];
                    }
                    
                    $import_results = [
                        'total' => 0,
                        'imported' => 0,
                        'skipped' => 0,
                        'errors' => 0,
                        'details' => []
                    ];
                    
                    // Skip header row (row 0)
                    for ($i = 1; $i < count($data); $i++) {
                        $row = $data[$i];
                        $import_results['total']++;
                        
                        // Extract data from columns
                        $full_name = trim($row[0] ?? '');
                        $position = trim($row[1] ?? '');
                        $office_assignment = trim($row[2] ?? '');
                        $email = trim($row[3] ?? '');
                        $employment_status = trim($row[4] ?? '');
                        $employee_id = trim($row[5] ?? '');
                        
                        // Skip empty rows
                        if (empty($full_name) && empty($employee_id)) {
                            $import_results['skipped']++;
                            $import_results['details'][] = [
                                'row' => $i + 1,
                                'status' => 'skipped',
                                'message' => 'Empty row'
                            ];
                            continue;
                        }
                        
                        // Parse full name
                        $name_parts = explode(',', $full_name);
                        $lastname = trim($name_parts[0] ?? '');
                        $firstname = '';
                        $middle_name = '';
                        
                        if (isset($name_parts[1])) {
                            $first_middle = trim($name_parts[1]);
                            $first_middle_parts = explode(' ', $first_middle);
                            if (count($first_middle_parts) >= 1) {
                                $firstname = trim($first_middle_parts[0]);
                            }
                            if (count($first_middle_parts) >= 2) {
                                $middle_name = trim(implode(' ', array_slice($first_middle_parts, 1)));
                            }
                        }
                        
                        // Validate required fields
                        if (empty($firstname) || empty($lastname)) {
                            $import_results['errors']++;
                            $import_results['details'][] = [
                                'row' => $i + 1,
                                'status' => 'error',
                                'message' => 'Missing first name or last name',
                                'data' => $full_name
                            ];
                            continue;
                        }
                        
                        if (empty($employee_id)) {
                            $import_results['errors']++;
                            $import_results['details'][] = [
                                'row' => $i + 1,
                                'status' => 'error',
                                'message' => 'Missing employee ID',
                                'data' => $full_name
                            ];
                            continue;
                        }
                        
                        // Simple office assignment - use default office or null
                        $office_id = $default_office_id; // Use default office for all imports
                        
                        // Normalize employment status
                        $employment_status = strtolower($employment_status);
                        if (strpos($employment_status, 'permanent') !== false) {
                            $employment_status = 'permanent';
                        } elseif (strpos($employment_status, 'contract') !== false) {
                            $employment_status = 'contractual';
                        } elseif (strpos($employment_status, 'job') !== false) {
                            $employment_status = 'job_order';
                        } elseif (strpos($employment_status, 'resign') !== false) {
                            $employment_status = 'resigned';
                        } elseif (strpos($employment_status, 'retire') !== false) {
                            $employment_status = 'retired';
                        } else {
                            $employment_status = 'permanent'; // default
                        }
                        
                        // Check for duplicate employee ID
                        if ($skip_duplicates) {
                            $check_stmt = $conn->prepare("SELECT id FROM employees WHERE employee_no = ?");
                            $check_stmt->bind_param("s", $employee_id);
                            $check_stmt->execute();
                            if ($check_stmt->get_result()->num_rows > 0) {
                                $import_results['skipped']++;
                                $import_results['details'][] = [
                                    'row' => $i + 1,
                                    'status' => 'skipped',
                                    'message' => 'Duplicate employee ID',
                                    'data' => $employee_id
                                ];
                                $check_stmt->close();
                                continue;
                            }
                            $check_stmt->close();
                        }
                        
                        // Handle empty email to avoid unique constraint violation
                        if (empty($email)) {
                            $email = null; // Use NULL instead of empty string
                        }
                        
                        // Insert employee
                        $insert_sql = "INSERT INTO employees (employee_no, firstname, middle_name, lastname, email, office_id, position, employment_status, clearance_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'uncleared', NOW())";
                        $insert_stmt = $conn->prepare($insert_sql);
                        $insert_stmt->bind_param("ssssssss", $employee_id, $firstname, $middle_name, $lastname, $email, $office_id, $position, $employment_status);
                        
                        if ($insert_stmt->execute()) {
                            $import_results['imported']++;
                            $office_info = $office_id ? "Office ID: $office_id" : "No office assigned";
                            $import_results['details'][] = [
                                'row' => $i + 1,
                                'status' => 'success',
                                'message' => "Imported successfully. $office_info",
                                'data' => $firstname . ' ' . $lastname . ' (' . $employee_id . ')'
                            ];
                            
                            logSystemAction($_SESSION['user_id'], 'create', 'employees', "Imported employee: $firstname $lastname ($employee_id)");
                        } else {
                            $import_results['errors']++;
                            $import_results['details'][] = [
                                'row' => $i + 1,
                                'status' => 'error',
                                'message' => 'Database error: ' . $insert_stmt->error,
                                'data' => $firstname . ' ' . $lastname
                            ];
                        }
                        $insert_stmt->close();
                    }
                    
                    if ($import_results['imported'] > 0) {
                        $message = "Successfully imported {$import_results['imported']} employees.";
                        if ($import_results['skipped'] > 0) {
                            $message .= " Skipped {$import_results['skipped']} duplicates.";
                        }
                        if ($import_results['errors'] > 0) {
                            $message .= " {$import_results['errors']} errors occurred.";
                        }
                        $message_type = "success";
                    } else {
                        $message = "No employees were imported.";
                        $message_type = "warning";
                    }
                    
                    // Store results in session for display
                    $_SESSION['import_results'] = $import_results;
                    
                }
                
            } catch (Exception $e) {
                $message = "Error processing Excel file: " . $e->getMessage();
                $message_type = "danger";
                error_log("Import error: " . $e->getMessage());
            }
        }
    }
    
    // Redirect back to employees page with message
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $message_type;
    header("Location: employees.php");
    exit();
} else {
    // If not POST request, redirect back
    header("Location: employees.php");
    exit();
}
?>
