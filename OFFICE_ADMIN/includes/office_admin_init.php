<?php
/**
 * OFFICE_ADMIN Initialization Class
 * 
 * Object-oriented approach to OFFICE_ADMIN page initialization
 * Provides better organization and dependency management
 */

class OfficeAdminInit {
    private static $instance = null;
    private $conn;
    private $office_id;
    private $user_id;
    private $config;
    
    private function __construct() {
        $this->initialize();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initialize() {
        // Set timezone
        date_default_timezone_set('Asia/Manila');
        
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Load dependencies
        $this->loadDependencies();
        
        // Authenticate
        $this->authenticate();
        
        // Initialize properties
        $this->setProperties();
        
        // Set security headers
        $this->setSecurityHeaders();
    }
    
    private function loadDependencies() {
        require_once dirname(__DIR__) . '/config.php';
        require_once dirname(__DIR__) . '/includes/system_functions.php';
        require_once dirname(__DIR__) . '/includes/logger.php';
        require_once __DIR__ . '/notification_functions.php';
        
        $this->conn = $GLOBALS['conn'];
    }
    
    private function authenticate() {
        checkSessionTimeout();
        
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->redirect('../index.php');
        }
        
        if ($_SESSION['role'] !== 'office_admin') {
            $this->redirect('../index.php');
        }
    }
    
    private function setProperties() {
        $this->office_id = $_SESSION['office_id'] ?? null;
        $this->user_id = $_SESSION['user_id'] ?? null;
        $this->config = [
            'timezone' => 'Asia/Manila',
            'session_timeout' => 3600, // 1 hour
            'max_upload_size' => 5242880, // 5MB
        ];
    }
    
    private function setSecurityHeaders() {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
    
    private function redirect($location) {
        header("Location: $location");
        exit();
    }
    
    // Public getters
    public function getConnection() {
        return $this->conn;
    }
    
    public function getOfficeId() {
        return $this->office_id;
    }
    
    public function getUserId() {
        return $this->user_id;
    }
    
    public function getConfig($key = null) {
        if ($key === null) {
            return $this->config;
        }
        return $this->config[$key] ?? null;
    }
    
    // Utility methods
    public function logActivity($action, $details = []) {
        if (function_exists('logActivity')) {
            logActivity($_SESSION['user_id'], $action, 'office_admin', $details);
        }
    }
    
    public function validateCSRF($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public function generateCSRF() {
        if (function_exists('generateCSRFToken')) {
            return generateCSRFToken();
        }
        return bin2hex(random_bytes(32));
    }
}
