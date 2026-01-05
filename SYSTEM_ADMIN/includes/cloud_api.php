<?php
// Cloud Storage API Integration Library
// Supports Google Drive, Dropbox, and OneDrive

class CloudStorageAPI {
    private $config;
    private $provider;
    
    public function __construct($provider) {
        global $conn;
        $this->provider = $provider;
        
        // Load configuration from database
        $stmt = $conn->prepare("SELECT * FROM online_backup_configs WHERE provider = ? AND is_active = TRUE");
        $stmt->bind_param("s", $provider);
        $stmt->execute();
        $result = $stmt->get_result();
        $this->config = $result->fetch_assoc();
        $stmt->close();
        
        if (!$this->config) {
            throw new Exception("No active configuration found for {$provider}");
        }
    }
    
    /**
     * Upload file to cloud storage
     */
    public function uploadFile($localFilePath, $remoteFileName = null) {
        if (!file_exists($localFilePath)) {
            throw new Exception("Local file does not exist: {$localFilePath}");
        }
        
        switch ($this->provider) {
            case 'google_drive':
                return $this->uploadToGoogleDrive($localFilePath, $remoteFileName);
            case 'dropbox':
                return $this->uploadToDropbox($localFilePath, $remoteFileName);
            case 'onedrive':
                return $this->uploadToOneDrive($localFilePath, $remoteFileName);
            default:
                throw new Exception("Unsupported provider: {$this->provider}");
        }
    }
    
    /**
     * Google Drive Upload
     */
    private function uploadToGoogleDrive($localFilePath, $remoteFileName = null) {
        if (!$remoteFileName) {
            $remoteFileName = basename($localFilePath);
        }
        
        // Check if we have access token
        if (empty($this->config['access_token'])) {
            // Need to get OAuth token
            return $this->getGoogleDriveOAuthToken($localFilePath, $remoteFileName);
        }
        
        $folderPath = $this->config['folder_path'] ?? '';
        $remotePath = $folderPath ? trim($folderPath, '/') . '/' . $remoteFileName : $remoteFileName;
        
        // Google Drive API endpoint
        $url = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart';
        
        // Prepare metadata
        $metadata = [
            'name' => $remoteFileName,
            'parents' => $this->getGoogleDriveFolderId($folderPath)
        ];
        
        // Create multipart request
        $boundary = uniqid();
        $postdata = "--{$boundary}\r\n";
        $postdata .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $postdata .= json_encode($metadata) . "\r\n";
        $postdata .= "--{$boundary}\r\n";
        $postdata .= "Content-Type: " . mime_content_type($localFilePath) . "\r\n\r\n";
        $postdata .= file_get_contents($localFilePath) . "\r\n";
        $postdata .= "--{$boundary}--";
        
        $headers = [
            'Authorization: Bearer ' . $this->config['access_token'],
            'Content-Type: multipart/related; boundary=' . $boundary,
            'Content-Length: ' . strlen($postdata)
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            $fileId = $data['id'];
            $webViewLink = $data['webViewLink'] ?? "https://drive.google.com/file/d/{$fileId}/view";
            
            return [
                'success' => true,
                'file_id' => $fileId,
                'url' => $webViewLink,
                'provider' => 'google_drive'
            ];
        } else {
            throw new Exception("Google Drive upload failed: HTTP {$httpCode}");
        }
    }
    
    /**
     * Dropbox Upload
     */
    private function uploadToDropbox($localFilePath, $remoteFileName = null) {
        if (!$remoteFileName) {
            $remoteFileName = basename($localFilePath);
        }
        
        // Check if we have access token
        if (empty($this->config['access_token'])) {
            // Need to get OAuth token
            return $this->getDropboxOAuthToken($localFilePath, $remoteFileName);
        }
        
        $folderPath = $this->config['folder_path'] ?? '';
        $remotePath = $folderPath ? trim($folderPath, '/') . '/' . $remoteFileName : $remoteFileName;
        
        $url = 'https://content.dropboxapi.com/2/files/upload';
        
        $headers = [
            'Authorization: Bearer ' . $this->config['access_token'],
            'Content-Type: application/octet-stream',
            'Dropbox-API-Arg: ' . json_encode([
                'path' => '/' . $remotePath,
                'mode' => 'add',
                'autorename' => true
            ])
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($localFilePath));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            $shareUrl = $this->createDropboxShareLink($data['path_lower']);
            
            return [
                'success' => true,
                'file_id' => $data['id'],
                'url' => $shareUrl,
                'provider' => 'dropbox'
            ];
        } else {
            throw new Exception("Dropbox upload failed: HTTP {$httpCode}");
        }
    }
    
    /**
     * OneDrive Upload
     */
    private function uploadToOneDrive($localFilePath, $remoteFileName = null) {
        if (!$remoteFileName) {
            $remoteFileName = basename($localFilePath);
        }
        
        // Check if we have access token
        if (empty($this->config['access_token'])) {
            // Need to get OAuth token
            return $this->getOneDriveOAuthToken($localFilePath, $remoteFileName);
        }
        
        $folderPath = $this->config['folder_path'] ?? '';
        $remotePath = $folderPath ? trim($folderPath, '/') . '/' . $remoteFileName : $remoteFileName;
        
        // OneDrive API endpoint
        $url = "https://graph.microsoft.com/v1.0/me/drive/root:/" . urlencode($remotePath) . ":/content";
        
        $headers = [
            'Authorization: Bearer ' . $this->config['access_token'],
            'Content-Type: ' . mime_content_type($localFilePath)
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($localFilePath));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 || $httpCode === 201) {
            $data = json_decode($response, true);
            $webViewLink = $data['webUrl'];
            
            return [
                'success' => true,
                'file_id' => $data['id'],
                'url' => $webViewLink,
                'provider' => 'onedrive'
            ];
        } else {
            throw new Exception("OneDrive upload failed: HTTP {$httpCode}");
        }
    }
    
    /**
     * Get Dropbox OAuth Token
     */
    private function getDropboxOAuthToken($localFilePath, $remoteFileName) {
        $redirectUri = self::getCallbackUrl();
        $state = base64_encode(json_encode([
            'provider' => 'dropbox',
            'local_file' => $localFilePath,
            'remote_file' => $remoteFileName
        ]));
        
        $authUrl = 'https://www.dropbox.com/oauth2/authorize?' . http_build_query([
            'client_id' => $this->config['api_key'],
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'state' => $state
        ]);
        
        return [
            'success' => false,
            'auth_required' => true,
            'auth_url' => $authUrl,
            'provider' => 'dropbox'
        ];
    }
    
    /**
     * Get OneDrive OAuth Token
     */
    private function getOneDriveOAuthToken($localFilePath, $remoteFileName) {
        $redirectUri = self::getCallbackUrl();
        $state = base64_encode(json_encode([
            'provider' => 'onedrive',
            'local_file' => $localFilePath,
            'remote_file' => $remoteFileName
        ]));
        
        $authUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?' . http_build_query([
            'client_id' => $this->config['api_key'],
            'redirect_uri' => $redirectUri,
            'scope' => 'Files.ReadWrite offline_access',
            'response_type' => 'code',
            'state' => $state,
            'response_mode' => 'query'
        ]);
        
        return [
            'success' => false,
            'auth_required' => true,
            'auth_url' => $authUrl,
            'provider' => 'onedrive'
        ];
    }
    
    /**
     * Get Google Drive OAuth Token
     */
    private function getGoogleDriveOAuthToken($localFilePath, $remoteFileName) {
        $redirectUri = self::getCallbackUrl();
        $state = base64_encode(json_encode([
            'provider' => 'google_drive',
            'local_file' => $localFilePath,
            'remote_file' => $remoteFileName
        ]));
        
        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $this->config['api_key'],
            'redirect_uri' => $redirectUri,
            'scope' => 'https://www.googleapis.com/auth/drive.file',
            'response_type' => 'code',
            'state' => $state,
            'access_type' => 'offline'
        ]);
        
        return [
            'success' => false,
            'auth_required' => true,
            'auth_url' => $authUrl,
            'provider' => 'google_drive'
        ];
    }
    
    /**
     * Get Google Drive Folder ID
     */
    private function getGoogleDriveFolderId($folderPath) {
        if (empty($folderPath)) {
            return []; // Root folder
        }
        
        // This is a simplified version - in production, you'd want to cache folder IDs
        $url = 'https://www.googleapis.com/drive/v3/files?q=' . urlencode("name='{$folderPath}' and mimeType='application/vnd.google-apps.folder'");
        
        $headers = [
            'Authorization: Bearer ' . $this->config['access_token']
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (!empty($data['files'])) {
            return [$data['files'][0]['id']];
        }
        
        return []; // Folder not found, use root
    }
    
    /**
     * Create Dropbox Share Link
     */
    private function createDropboxShareLink($path) {
        $url = 'https://api.dropboxapi.com/2/sharing/create_shared_link_with_settings';
        
        $headers = [
            'Authorization: Bearer ' . $this->config['access_token'],
            'Content-Type: application/json'
        ];
        
        $data = json_encode(['path' => $path]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return $result['url'];
        }
        
        return ''; // Share link creation failed
    }
    
    /**
     * Get callback URL
     */
    public static function getCallbackUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // For production, use the full path
        if (strpos($host, 'localhost') === false) {
            return "{$protocol}://{$host}/PIMS/SYSTEM_ADMIN/cloud_callback.php";
        } else {
            // For local development
            return "{$protocol}://{$host}/PIMS/SYSTEM_ADMIN/cloud_callback.php";
        }
    }
    
    /**
     * Test connection to cloud provider
     */
    public function testConnection() {
        switch ($this->provider) {
            case 'google_drive':
                return $this->testGoogleDriveConnection();
            case 'dropbox':
                return $this->testDropboxConnection();
            case 'onedrive':
                return $this->testOneDriveConnection();
            default:
                throw new Exception("Unsupported provider: {$this->provider}");
        }
    }
    
    private function testGoogleDriveConnection() {
        $url = 'https://www.googleapis.com/drive/v3/about';
        $headers = ['Authorization: Bearer ' . $this->config['access_token']];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
    
    private function testDropboxConnection() {
        $url = 'https://api.dropboxapi.com/2/users/get_current_account';
        $headers = ['Authorization: Bearer ' . $this->config['access_token']];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
    
    private function testOneDriveConnection() {
        $url = 'https://graph.microsoft.com/v1.0/me';
        $headers = ['Authorization: Bearer ' . $this->config['access_token']];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
}

// OAuth callback handler
class CloudOAuthHandler {
    public static function handleCallback($provider, $code, $state = null) {
        global $conn;
        
        // Load configuration
        $stmt = $conn->prepare("SELECT * FROM online_backup_configs WHERE provider = ? AND is_active = TRUE");
        $stmt->bind_param("s", $provider);
        $stmt->execute();
        $result = $stmt->get_result();
        $config = $result->fetch_assoc();
        $stmt->close();
        
        if (!$config) {
            throw new Exception("No active configuration found for {$provider}");
        }
        
        switch ($provider) {
            case 'google_drive':
                return self::handleGoogleDriveCallback($config, $code);
            case 'dropbox':
                return self::handleDropboxCallback($config, $code);
            case 'onedrive':
                return self::handleOneDriveCallback($config, $code);
            default:
                throw new Exception("Unsupported provider: {$provider}");
        }
    }
    
    private static function handleGoogleDriveCallback($config, $code) {
        $redirectUri = (new CloudStorageAPI('google_drive'))->getCallbackUrl();
        
        $url = 'https://oauth2.googleapis.com/token';
        $data = [
            'client_id' => $config['api_key'],
            'client_secret' => $config['api_secret'],
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $tokenData = json_decode($response, true);
        
        if (isset($tokenData['access_token'])) {
            // Update tokens in database
            global $conn;
            $stmt = $conn->prepare("
                UPDATE online_backup_configs 
                SET access_token = ?, refresh_token = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE provider = ?
            ");
            $stmt->bind_param("sss", $tokenData['access_token'], $tokenData['refresh_token'], $config['provider']);
            $stmt->execute();
            $stmt->close();
            
            return [
                'success' => true,
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token']
            ];
        } else {
            throw new Exception("Failed to obtain access token");
        }
    }
    
    private static function handleDropboxCallback($config, $code) {
        $redirectUri = (new CloudStorageAPI('dropbox'))->getCallbackUrl();
        
        $url = 'https://api.dropboxapi.com/oauth2/token';
        $data = [
            'client_id' => $config['api_key'],
            'client_secret' => $config['api_secret'],
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $tokenData = json_decode($response, true);
        
        if (isset($tokenData['access_token'])) {
            // Update token in database
            global $conn;
            $stmt = $conn->prepare("
                UPDATE online_backup_configs 
                SET access_token = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE provider = ?
            ");
            $stmt->bind_param("ss", $tokenData['access_token'], $config['provider']);
            $stmt->execute();
            $stmt->close();
            
            return [
                'success' => true,
                'access_token' => $tokenData['access_token']
            ];
        } else {
            throw new Exception("Failed to obtain access token");
        }
    }
    
    private static function handleOneDriveCallback($config, $code) {
        $redirectUri = (new CloudStorageAPI('onedrive'))->getCallbackUrl();
        
        $url = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
        $data = [
            'client_id' => $config['api_key'],
            'client_secret' => $config['api_secret'],
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
            'scope' => 'Files.ReadWrite offline_access'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $tokenData = json_decode($response, true);
        
        if (isset($tokenData['access_token'])) {
            // Update tokens in database
            global $conn;
            $stmt = $conn->prepare("
                UPDATE online_backup_configs 
                SET access_token = ?, refresh_token = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE provider = ?
            ");
            $stmt->bind_param("sss", $tokenData['access_token'], $tokenData['refresh_token'], $config['provider']);
            $stmt->execute();
            $stmt->close();
            
            return [
                'success' => true,
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token']
            ];
        } else {
            throw new Exception("Failed to obtain access token");
        }
    }
}
?>
