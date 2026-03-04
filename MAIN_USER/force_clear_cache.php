<?php
// Force clear all PHP caches
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache reset<br>";
}
if (function_exists('opcache_invalidate')) {
    opcache_invalidate(__FILE__, true);
    echo "Current file invalidated<br>";
}
clearstatcache(true);
echo "File status cache cleared<br>";

// Clear all session data
if (session_status() === PHP_SESSION_ACTIVE) {
    session_unset();
    session_destroy();
    echo "Session cleared<br>";
}

echo "All caches forcefully cleared!";
?>
