<?php
/**
 * QR Code Generator for PIMS
 * Generates QR codes for asset items using Public QR Code API
 */

class QRCodeGenerator {
    private $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/';
    
    /**
     * Generate QR code for asset item
     * @param array $assetData Asset item data
     * @return string QR code filename
     */
    public function generateAssetQRCode($assetData) {
        // Create QR code content with asset information
        $qrContent = $this->formatAssetData($assetData);
        
        // Generate QR code using public API
        $qrImageData = $this->generateQRCodeFromAPI($qrContent);
        
        if ($qrImageData) {
            // Save QR code image to file
            $filename = $this->saveQRCodeImage($qrImageData, $assetData['id']);
            return $filename;
        }
        
        return null;
    }
    
    /**
     * Format asset data for QR code
     * @param array $assetData
     * @return string
     */
    private function formatAssetData($assetData) {
        // Only store the asset_item_id in the QR code for simplicity
        return $assetData['id'];
    }
    
    /**
     * Generate QR code using Public QR Code API
     * @param string $data Data to encode in QR code
     * @return string|null Binary image data or null on failure
     */
    private function generateQRCodeFromAPI($data) {
        try {
            // Build API URL with parameters
            $url = $this->qrApiUrl . '?' . http_build_query([
                'size' => '300x300',
                'data' => $data,
                'format' => 'png',
                'margin' => '0',
                'ecc' => 'M' // Error correction level
            ]);
            
            // Initialize cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_USERAGENT, 'PIMS-QR-Generator/1.0');
            
            // Execute request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            // Check for errors
            if ($error) {
                error_log("QR Code API cURL error: " . $error);
                return null;
            }
            
            if ($httpCode !== 200) {
                error_log("QR Code API HTTP error: " . $httpCode);
                return null;
            }
            
            // Validate response is image data
            $imageInfo = @getimagesizefromstring($response);
            if ($imageInfo === false) {
                error_log("QR Code API response is not a valid image");
                return null;
            }
            
            return $response;
            
        } catch (Exception $e) {
            error_log("QR Code API exception: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Save QR code image to file
     * @param string $qrData Binary image data
     * @param int $assetItemId
     * @return string|null Filename or null on failure
     */
    private function saveQRCodeImage($qrData, $assetItemId) {
        try {
            $filename = 'qr_asset_' . $assetItemId . '_' . time() . '.png';
            $filepath = '../uploads/qr_codes/' . $filename;
            
            // Create directory if it doesn't exist
            if (!is_dir('../uploads/qr_codes/')) {
                if (!mkdir('../uploads/qr_codes/', 0755, true)) {
                    error_log("Failed to create QR codes directory");
                    return null;
                }
            }
            
            // Save QR code image
            if (file_put_contents($filepath, $qrData) !== false) {
                return $filename;
            } else {
                error_log("Failed to save QR code image: " . $filepath);
                return null;
            }
            
        } catch (Exception $e) {
            error_log("Exception saving QR code: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Generate QR code as base64 data URL
     * @param array $assetData
     * @return string|null Base64 image data or null on failure
     */
    public function generateQRCodeBase64($assetData) {
        $content = $this->formatAssetData($assetData);
        $qrImageData = $this->generateQRCodeFromAPI($content);
        
        if ($qrImageData) {
            return 'data:image/png;base64,' . base64_encode($qrImageData);
        }
        
        return null;
    }
    
    /**
     * Generate QR code URL directly (for display without saving)
     * @param array $assetData
     * @return string QR code URL
     */
    public function generateQRCodeURL($assetData) {
        $content = $this->formatAssetData($assetData);
        
        return $this->qrApiUrl . '?' . http_build_query([
            'size' => '300x300',
            'data' => $content,
            'format' => 'png',
            'margin' => '0',
            'ecc' => 'M'
        ]);
    }
    
    /**
     * Validate QR code data
     * @param string $data
     * @return bool
     */
    public function validateQRData($data) {
        // Check if data is numeric (asset item ID)
        return is_numeric($data) && intval($data) > 0;
    }
    
    /**
     * Get QR code API status
     * @return array Status information
     */
    public function getAPIStatus() {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->qrApiUrl . '?data=test&size=100x100');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            return [
                'status' => $error ? 'error' : ($httpCode === 200 ? 'online' : 'offline'),
                'http_code' => $httpCode,
                'error' => $error,
                'api_url' => $this->qrApiUrl
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'api_url' => $this->qrApiUrl
            ];
        }
    }
}
?>
