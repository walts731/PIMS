<?php
require_once '../config.php';

/**
 * LGU Compliance Functions
 * Implements COA & GPPB compliance features for office reports
 */

class LGUCompliance {
    private $conn;
    private $office_id;
    private $user_id;
    
    public function __construct($office_id, $user_id) {
        global $conn;
        $this->conn = $conn;
        $this->office_id = $office_id;
        $this->user_id = $user_id;
    }
    
    /**
     * Log report activity to audit trail
     */
    public function logReportActivity($report_id, $report_type, $action, $parameters = null, $file_path = null) {
        $audit_id = 'AUDIT_' . uniqid();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $query = "INSERT INTO report_audit_trail 
                  (report_id, report_type, action, user_id, office_id, action_date, ip_address, user_agent, parameters, file_path)
                  VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $params_json = $parameters ? json_encode($parameters) : null;
        $stmt->bind_param("sssiissss", $report_id, $report_type, $action, $this->user_id, $this->office_id, $ip_address, $user_agent, $params_json, $file_path);
        
        return $stmt->execute();
    }
    
    /**
     * Get document references for reports
     */
    public function getDocumentReferences($document_type = null, $date_from = null, $date_to = null) {
        $query = "SELECT dr.*, CONCAT(e.firstname, ' ', e.lastname) as created_by_name
                  FROM document_references dr
                  LEFT JOIN employees e ON dr.created_by = e.id
                  WHERE dr.office_id = ?";
        
        $params = [$this->office_id];
        $types = "i";
        
        if ($document_type) {
            $query .= " AND dr.document_type = ?";
            $params[] = $document_type;
            $types .= "s";
        }
        
        if ($date_from) {
            $query .= " AND dr.document_date >= ?";
            $params[] = $date_from;
            $types .= "s";
        }
        
        if ($date_to) {
            $query .= " AND dr.document_date <= ?";
            $params[] = $date_to;
            $types .= "s";
        }
        
        $query .= " ORDER BY dr.document_date DESC, dr.document_number";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Add document reference
     */
    public function addDocumentReference($document_type, $document_number, $document_date, $reference_amount = null, $supplier_name = null) {
        $query = "INSERT INTO document_references 
                  (document_type, document_number, document_date, reference_amount, supplier_name, office_id, created_by)
                  VALUES (?, ?, ?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE 
                  reference_amount = VALUES(reference_amount),
                  supplier_name = VALUES(supplier_name),
                  updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssdsii", $document_type, $document_number, $document_date, $reference_amount, $supplier_name, $this->office_id, $this->user_id);
        
        return $stmt->execute();
    }
    
    /**
     * Get authorized signatories
     */
    public function getSignatories($signatory_type = null) {
        $query = "SELECT sa.*, CONCAT(e.firstname, ' ', e.lastname, ' ', e.middle_name) as full_name, e.position
                  FROM signatory_authorities sa
                  LEFT JOIN employees e ON sa.employee_id = e.id
                  WHERE sa.office_id = ? AND sa.is_active = 1
                  AND (sa.expiry_date IS NULL OR sa.expiry_date >= CURDATE())
                  AND sa.effective_date <= CURDATE()";
        
        $params = [$this->office_id];
        $types = "i";
        
        if ($signatory_type) {
            $query .= " AND sa.signatory_type = ?";
            $params[] = $signatory_type;
            $types .= "s";
        }
        
        $query .= " ORDER BY sa.signatory_type, sa.effective_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Check data integrity issues
     */
    public function checkDataIntegrity() {
        $issues = [];
        
        // Check for quantity discrepancies in consumables
        $query = "SELECT c.id, c.description, c.quantity, c.reorder_level,
                         CASE WHEN c.quantity < 0 THEN 'Negative quantity'
                              WHEN c.quantity < c.reorder_level THEN 'Below reorder level'
                              ELSE 'OK' END as issue_type,
                         c.quantity as actual_value,
                         c.reorder_level as expected_value
                  FROM consumables c
                  WHERE c.office_id = ? 
                  AND (c.quantity < 0 OR c.quantity < c.reorder_level)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->office_id);
        $stmt->execute();
        $consumable_issues = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($consumable_issues as $issue) {
            $issues[] = [
                'type' => 'quantity_mismatch',
                'table' => 'consumables',
                'record_id' => $issue['id'],
                'description' => $issue['description'],
                'issue_type' => $issue['issue_type'],
                'severity' => $issue['quantity'] < 0 ? 'critical' : 'medium',
                'actual' => $issue['actual_value'],
                'expected' => $issue['expected_value']
            ];
        }
        
        // Check for asset value inconsistencies
        $query = "SELECT ai.id, ai.description, ai.value, ai.acquisition_date,
                         CASE WHEN ai.value <= 0 THEN 'Invalid value'
                              WHEN ai.acquisition_date IS NULL THEN 'Missing acquisition date'
                              ELSE 'OK' END as issue_type
                  FROM asset_items ai
                  WHERE ai.office_id = ? 
                  AND (ai.value <= 0 OR ai.acquisition_date IS NULL)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->office_id);
        $stmt->execute();
        $asset_issues = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($asset_issues as $issue) {
            $issues[] = [
                'type' => 'value_discrepancy',
                'table' => 'asset_items',
                'record_id' => $issue['id'],
                'description' => $issue['description'],
                'issue_type' => $issue['issue_type'],
                'severity' => 'high',
                'actual' => $issue['value'] ?? 'NULL',
                'expected' => '> 0'
            ];
        }
        
        return $issues;
    }
    
    /**
     * Log data integrity issue
     */
    public function logDataIntegrityIssue($check_type, $table_name, $record_id, $field_name, $expected_value, $actual_value, $severity, $discrepancy_amount = null) {
        // Check if issue already exists and is open
        $check_query = "SELECT id FROM data_integrity_checks 
                       WHERE table_name = ? AND record_id = ? AND field_name = ? AND status = 'open'";
        $stmt = $this->conn->prepare($check_query);
        $stmt->bind_param("sis", $table_name, $record_id, $field_name);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            return true; // Issue already logged
        }
        
        $query = "INSERT INTO data_integrity_checks 
                  (check_type, table_name, record_id, field_name, expected_value, actual_value, discrepancy_amount, severity, office_id, detected_by)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssisssdsii", $check_type, $table_name, $record_id, $field_name, $expected_value, $actual_value, $discrepancy_amount, $severity, $this->office_id, $this->user_id);
        
        return $stmt->execute();
    }
    
    /**
     * Get fiscal year dates
     */
    public function getFiscalYearDates($year = null) {
        $year = $year ?: date('Y');
        
        $query = "SELECT start_date, end_date FROM fiscal_year_settings 
                  WHERE office_id = ? AND fiscal_year = ? AND is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $this->office_id, $year);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result) {
            return $result;
        }
        
        // Default to January-December if not set
        return [
            'start_date' => "$year-01-01",
            'end_date' => "$year-12-31"
        ];
    }
    
    /**
     * Generate report ID with audit trail
     */
    public function generateReportId($report_type) {
        $prefix = strtoupper(substr($report_type, 0, 3));
        $timestamp = date('YmdHis');
        $office_code = str_pad($this->office_id, 3, '0', STR_PAD_LEFT);
        $user_code = str_pad($this->user_id, 4, '0', STR_PAD_LEFT);
        
        return "{$prefix}_{$office_code}_{$user_code}_{$timestamp}";
    }
    
    /**
     * Get report generation history
     */
    public function getReportHistory($report_type = null, $limit = 50) {
        $query = "SELECT rgh.*, CONCAT(e.firstname, ' ', e.lastname) as generated_by_name
                  FROM report_generation_history rgh
                  LEFT JOIN employees e ON rgh.generated_by = e.id
                  WHERE rgh.office_id = ?";
        
        $params = [$this->office_id];
        $types = "i";
        
        if ($report_type) {
            $query .= " AND rgh.report_type = ?";
            $params[] = $report_type;
            $types .= "s";
        }
        
        $query .= " ORDER BY rgh.created_at DESC LIMIT ?";
        $params[] = $limit;
        $types .= "i";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Log report generation
     */
    public function logReportGeneration($report_id, $report_type, $generation_method, $parameters = null) {
        $query = "INSERT INTO report_generation_history 
                  (report_id, report_type, generation_method, office_id, generated_by, parameters, status)
                  VALUES (?, ?, ?, ?, ?, ?, 'generating')";
        
        $stmt = $this->conn->prepare($query);
        $params_json = $parameters ? json_encode($parameters) : null;
        $stmt->bind_param("sssiiis", $report_id, $report_type, $generation_method, $this->office_id, $this->user_id, $params_json);
        
        return $stmt->execute();
    }
    
    /**
     * Update report generation status
     */
    public function updateReportGenerationStatus($report_id, $status, $file_path = null, $file_size = null, $record_count = null, $generation_time = null, $error_message = null) {
        $query = "UPDATE report_generation_history 
                  SET status = ?, completed_at = NOW()";
        
        $params = [$status];
        $types = "s";
        
        if ($file_path) {
            $query .= ", file_path = ?";
            $params[] = $file_path;
            $types .= "s";
        }
        
        if ($file_size) {
            $query .= ", file_size = ?";
            $params[] = $file_size;
            $types .= "i";
        }
        
        if ($record_count) {
            $query .= ", record_count = ?";
            $params[] = $record_count;
            $types .= "i";
        }
        
        if ($generation_time) {
            $query .= ", generation_time = ?";
            $params[] = $generation_time;
            $types .= "d";
        }
        
        if ($error_message) {
            $query .= ", error_message = ?";
            $params[] = $error_message;
            $types .= "s";
        }
        
        $query .= " WHERE report_id = ? AND office_id = ?";
        $params[] = $report_id;
        $params[] = $this->office_id;
        $types .= "si";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        
        return $stmt->execute();
    }
    
    /**
     * Get report template
     */
    public function getReportTemplate($report_type, $template_name = null) {
        $query = "SELECT * FROM report_templates 
                  WHERE report_type = ? AND is_active = 1
                  AND (office_id = ? OR office_id IS NULL)";
        
        $params = [$report_type, $this->office_id];
        $types = "si";
        
        if ($template_name) {
            $query .= " AND template_name = ?";
            $params[] = $template_name;
            $types .= "s";
        }
        
        $query .= " ORDER BY office_id DESC, is_default DESC LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Check user permissions for report operations
     */
    public function checkReportPermissions($action) {
        // Role-based access control for reports
        $allowed_actions = [
            'office_admin' => ['view', 'export', 'print', 'schedule'],
            'system_admin' => ['view', 'export', 'print', 'schedule', 'approve', 'delete'],
            'user' => ['view']
        ];
        
        $user_role = $_SESSION['role'] ?? 'user';
        
        return in_array($action, $allowed_actions[$user_role] ?? []);
    }
    
    /**
     * Get scheduled reports
     */
    public function getScheduledReports() {
        $query = "SELECT rs.*, CONCAT(e.firstname, ' ', e.lastname) as created_by_name
                  FROM report_schedules rs
                  LEFT JOIN employees e ON rs.created_by = e.id
                  WHERE rs.office_id = ? AND rs.is_active = 1
                  ORDER BY rs.next_run ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->office_id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Schedule report
     */
    public function scheduleReport($schedule_name, $report_type, $frequency, $schedule_day, $schedule_time, $recipients) {
        // Calculate next run date
        $next_run = $this->calculateNextRun($frequency, $schedule_day, $schedule_time);
        
        $query = "INSERT INTO report_schedules 
                  (schedule_name, report_type, frequency, schedule_day, schedule_time, recipients, office_id, next_run, created_by)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $recipients_json = json_encode($recipients);
        $stmt->bind_param("sssiisiss", $schedule_name, $report_type, $frequency, $schedule_day, $schedule_time, $recipients_json, $this->office_id, $next_run, $this->user_id);
        
        return $stmt->execute();
    }
    
    /**
     * Calculate next run date for scheduled reports
     */
    private function calculateNextRun($frequency, $schedule_day, $schedule_time) {
        $now = new DateTime();
        $time_parts = explode(':', $schedule_time);
        $next_run = clone $now;
        $next_run->setTime($time_parts[0], $time_parts[1], 0);
        
        switch ($frequency) {
            case 'daily':
                if ($next_run <= $now) {
                    $next_run->add(new DateInterval('P1D'));
                }
                break;
                
            case 'weekly':
                $next_run->modify('next ' . $this->getDayName($schedule_day));
                if ($next_run <= $now) {
                    $next_run->add(new DateInterval('P7D'));
                }
                break;
                
            case 'monthly':
                $next_run->setDate($next_run->format('Y'), $next_run->format('m'), $schedule_day);
                if ($next_run <= $now) {
                    $next_run->add(new DateInterval('P1M'));
                }
                break;
                
            case 'quarterly':
                $quarter = ceil($next_run->format('n') / 3);
                $next_run->setDate($next_run->format('Y'), ($quarter * 3), $schedule_day);
                if ($next_run <= $now) {
                    $next_run->add(new DateInterval('P3M'));
                }
                break;
                
            case 'annually':
                $next_run->setDate($next_run->format('Y'), 12, 31); // End of fiscal year
                if ($next_run <= $now) {
                    $next_run->add(new DateInterval('P1Y'));
                }
                break;
        }
        
        return $next_run->format('Y-m-d H:i:s');
    }
    
    /**
     * Convert day number to day name
     */
    private function getDayName($day_number) {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return $days[$day_number] ?? 'Monday';
    }
}

/**
 * Utility function to format report headers with LGU compliance
 */
function formatLGUReportHeader($office_name, $report_title, $report_date, $report_id = null) {
    $header = "
    <div style='text-align: center; margin-bottom: 30px; font-family: Arial, sans-serif;'>
        <h2 style='margin: 0; color: #191BA9;'>REPUBLIC OF THE PHILIPPINES</h2>
        <h3 style='margin: 5px 0; color: #191BA9;'>PROVINCE OF ALBAY</h3>
        <h4 style='margin: 5px 0; font-weight: bold;'>{$office_name}</h4>
        <hr style='border: 2px solid #191BA9; margin: 10px 0;'>
        <h3 style='margin: 10px 0; text-decoration: underline;'>{$report_title}</h3>
        <p style='margin: 5px 0;'>As of: " . date('F j, Y', strtotime($report_date)) . "</p>";
    
    if ($report_id) {
        $header .= "<p style='margin: 5px 0; font-size: 12px;'>Report ID: {$report_id}</p>";
    }
    
    $header .= "</div>";
    
    return $header;
}

/**
 * Utility function to format LGU signatory section
 */
function formatLGUSignatorySection($signatories) {
    $section = "
    <div style='margin-top: 50px; font-family: Arial, sans-serif;'>
        <table style='width: 100%; border-collapse: collapse;'>";
    
    $signatory_types = ['prepared' => 'Prepared by:', 'noted' => 'Noted by:', 'approved' => 'Approved by:', 'certified' => 'Certified by:'];
    
    $count = 0;
    foreach ($signatory_types as $type => $label) {
        $signatory = $signatories[$type] ?? null;
        
        if ($count % 2 == 0) {
            $section .= "<tr>";
        }
        
        $section .= "
            <td style='width: 50%; padding: 20px; text-align: center; vertical-align: top;'>
                <div style='margin-bottom: 30px;'>{$label}</div>";
        
        if ($signatory) {
            $section .= "
                <div style='border-bottom: 1px solid #000; width: 200px; margin: 0 auto; height: 40px;'></div>
                <div style='font-weight: bold;'>{$signatory['full_name']}</div>
                <div style='font-size: 12px;'>{$signatory['designation']}</div>";
        } else {
            $section .= "
                <div style='border-bottom: 1px solid #000; width: 200px; margin: 0 auto; height: 40px;'></div>
                <div style='font-weight: bold;'>_____________________</div>
                <div style='font-size: 12px;'>Signature Over Printed Name</div>";
        }
        
        $section .= "</td>";
        
        if ($count % 2 == 1) {
            $section .= "</tr>";
        }
        
        $count++;
    }
    
    if ($count % 2 == 1) {
        $section .= "<td style='width: 50%;'></td></tr>";
    }
    
    $section .= "
        </table>
    </div>";
    
    return $section;
}
?>
