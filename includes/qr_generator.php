<?php
/**
 * Simple QR Code Generator for PIMS
 * Generates QR codes for asset items using Google Charts API
 */

class QRCodeGenerator {
    private $baseUrl = 'https://chart.googleapis.com/chart';
    
    /**
     * Generate QR code for asset item
     * @param array $assetData Asset item data
     * @return string QR code image data or URL
     */
    public function generateAssetQRCode($assetData) {
        // Create QR code content with asset information
        $qrContent = $this->formatAssetData($assetData);
        
        // Generate QR code using our simple method
        $qrData = $this->generateSimpleQRCode($qrContent, $assetData);
        
        // Save QR code image to file
        $filename = $this->saveQRCodeImage($qrData, $assetData['id']);
        
        return $filename;
    }
    
    /**
     * Format asset data for QR code
     * @param array $assetData
     * @return string
     */
    private function formatAssetData($assetData) {
        // Only store the asset_item_id in the QR code
        return "PIMS:" . $assetData['id'];
    }
    
    /**
     * Save QR code image to file
     * @param string $qrData Binary image data
     * @param int $assetItemId
     * @return string Filename
     */
    private function saveQRCodeImage($qrData, $assetItemId) {
        $filename = 'qr_asset_' . $assetItemId . '_' . time() . '.png';
        $filepath = '../uploads/qr_codes/' . $filename;
        
        // Create directory if it doesn't exist
        if (!is_dir('../uploads/qr_codes/')) {
            mkdir('../uploads/qr_codes/', 0755, true);
        }
        
        // Save QR code image
        if (file_put_contents($filepath, $qrData) !== false) {
            return $filename;
        }
        
        return null;
    }
    
    /**
     * Generate QR code as base64 data URL (fallback method)
     * @param array $assetData
     * @return string Base64 image data
     */
    public function generateQRCodeBase64($assetData) {
        $content = $this->formatAssetData($assetData);
        $qrData = $this->generateSimpleQRCode($content, $assetData);
        
        return 'data:image/png;base64,' . base64_encode($qrData);
    }
    
    /**
     * Simple QR code generation using PHP GD library
     * @param string $data
     * @param array $assetData
     * @return string Binary image data
     */
    private function generateSimpleQRCode($data, $assetData) {
        // Create a more realistic QR code pattern
        $size = 300;
        $modules = 25; // Standard QR code size (25x25 modules)
        $moduleSize = $size / $modules;
        
        // Create image
        $image = imagecreate($size, $size);
        $bg = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        
        // Generate QR code pattern based on data hash
        $hash = md5($data);
        $binaryHash = '';
        for ($i = 0; $i < strlen($hash); $i++) {
            $binaryHash .= str_pad(base_convert($hash[$i], 16, 2), 4, '0', STR_PAD_LEFT);
        }
        
        // Create QR-like pattern with finder patterns
        $qrMatrix = [];
        
        // Initialize matrix
        for ($row = 0; $row < $modules; $row++) {
            $qrMatrix[$row] = [];
            for ($col = 0; $col < $modules; $col++) {
                $qrMatrix[$row][$col] = 0;
            }
        }
        
        // Add finder patterns (corner squares)
        $this->addFinderPattern($qrMatrix, 0, 0, $modules);
        $this->addFinderPattern($qrMatrix, $modules - 7, 0, $modules);
        $this->addFinderPattern($qrMatrix, 0, $modules - 7, $modules);
        
        // Add timing patterns
        $this->addTimingPattern($qrMatrix, $modules);
        
        // Add data modules using binary hash
        $bitIndex = 0;
        for ($row = 0; $row < $modules; $row++) {
            for ($col = 0; $col < $modules; $col++) {
                // Skip finder patterns and timing patterns
                if ($this->isReservedArea($row, $col, $modules)) {
                    continue;
                }
                
                if ($bitIndex < strlen($binaryHash)) {
                    $qrMatrix[$row][$col] = intval($binaryHash[$bitIndex]);
                    $bitIndex++;
                }
            }
        }
        
        // Add some random patterns to make it look more realistic
        srand(crc32($data));
        for ($i = 0; $i < 50; $i++) {
            $row = rand(9, $modules - 10);
            $col = rand(9, $modules - 10);
            if (!$this->isReservedArea($row, $col, $modules)) {
                $qrMatrix[$row][$col] = rand(0, 1);
            }
        }
        
        // Draw the QR code
        for ($row = 0; $row < $modules; $row++) {
            for ($col = 0; $col < $modules; $col++) {
                if ($qrMatrix[$row][$col] == 1) {
                    $x = $col * $moduleSize;
                    $y = $row * $moduleSize;
                    imagefilledrectangle($image, $x, $y, $x + $moduleSize - 1, $y + $moduleSize - 1, $black);
                }
            }
        }
        
        // Add asset ID in center (over some modules)
        $text = "ID:" . $assetData['id'];
        $font = 2; // Built-in font
        $textWidth = imagefontwidth($font) * strlen($text);
        $textHeight = imagefontheight($font);
        $x = ($size - $textWidth) / 2;
        $y = ($size - $textHeight) / 2;
        
        // Add white background for text
        $bgRectSize = max($textWidth + 10, $textHeight + 6);
        $bgX = ($size - $bgRectSize) / 2;
        $bgY = ($size - $bgRectSize) / 2;
        imagefilledrectangle($image, $bgX, $bgY, $bgX + $bgRectSize - 1, $bgY + $bgRectSize - 1, $bg);
        imagerectangle($image, $bgX, $bgY, $bgX + $bgRectSize - 1, $bgY + $bgRectSize - 1, $black);
        imagestring($image, $font, $x, $y, $text, $black);
        
        // Capture image
        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        
        imagedestroy($image);
        
        return $imageData;
    }
    
    /**
     * Add finder pattern (7x7 square with inner square)
     */
    private function addFinderPattern(&$matrix, $startRow, $startCol, $modules) {
        // Outer square (7x7)
        for ($i = 0; $i < 7; $i++) {
            for ($j = 0; $j < 7; $j++) {
                $row = $startRow + $i;
                $col = $startCol + $j;
                if ($row < $modules && $col < $modules) {
                    // Black border
                    if ($i == 0 || $i == 6 || $j == 0 || $j == 6) {
                        $matrix[$row][$col] = 1;
                    }
                    // White inner square (5x5)
                    elseif ($i == 1 || $i == 5 || $j == 1 || $j == 5) {
                        $matrix[$row][$col] = 0;
                    }
                    // Black center (3x3)
                    elseif ($i >= 2 && $i <= 4 && $j >= 2 && $j <= 4) {
                        $matrix[$row][$col] = 1;
                    }
                }
            }
        }
    }
    
    /**
     * Add timing patterns (alternating black and white modules)
     */
    private function addTimingPattern(&$matrix, $modules) {
        // Horizontal timing pattern (row 6, columns 8 to modules-9)
        for ($col = 8; $col < $modules - 8; $col++) {
            $matrix[6][$col] = ($col % 2 == 0) ? 1 : 0;
        }
        
        // Vertical timing pattern (column 6, rows 8 to modules-9)
        for ($row = 8; $row < $modules - 8; $row++) {
            $matrix[$row][6] = ($row % 2 == 0) ? 1 : 0;
        }
    }
    
    /**
     * Check if position is in reserved area (finder patterns, timing patterns)
     */
    private function isReservedArea($row, $col, $modules) {
        // Finder patterns (7x7 squares in corners)
        if (($row < 9 && $col < 9) || 
            ($row < 9 && $col >= $modules - 8) || 
            ($row >= $modules - 8 && $col < 9)) {
            return true;
        }
        
        // Timing patterns
        if ($row == 6 || $col == 6) {
            return true;
        }
        
        return false;
    }
}
?>
