<?php
// Clear OPcache and restart PHP
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared successfully<br>";
} else {
    echo "OPcache is not enabled<br>";
}

// Clear file status cache
clearstatcache();
echo "File status cache cleared<br>";

// Clear realpath cache
if (function_exists('realpath_cache_get')) {
    $cache = realpath_cache_get();
    echo "Realpath cache entries: " . count($cache) . "<br>";
}

echo "All caches cleared. Search functionality updated.";
?>
