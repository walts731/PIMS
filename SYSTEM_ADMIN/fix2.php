<?php
$dir = __DIR__;
$files = glob($dir . '/*.php');

$count = 0;
foreach ($files as $file) {
    if (is_file($file)) {
        $content = file_get_contents($file);
        
        $pattern = '~<script>\s*<\?php\s+(require_once|include)\s+[\'"]includes/sidebar-scripts\.php[\'"]\s*;\s*\?>~i';
        $replacement = "<?php require_once 'includes/sidebar-scripts.php'; ?>\n    <script>";
        
        $new_content = preg_replace($pattern, $replacement, $content);
        
        if ($new_content !== $content) {
            file_put_contents($file, $new_content);
            echo "Updated: " . basename($file) . "\n";
            $count++;
        }
    }
}
echo "Total files updated: $count\n";
