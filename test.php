<?php
echo "Test page is working!";
echo "<br>Current directory: " . __DIR__;
echo "<br>File exists: " . (file_exists(__FILE__) ? "Yes" : "No");
phpinfo();
?>
