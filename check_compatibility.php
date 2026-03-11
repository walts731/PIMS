<?php
// check_compatibility.php
// DELETE THIS FILE before final production deployment

require_once 'config.php';

// ============================================================
// DO NOT EDIT BELOW THIS LINE
// ============================================================

$issues = 0;

if (!isset($conn) || !$conn) {
    die("<pre>Could not find conn. Check the require_once path at the top of this file.</pre>");
}

echo "<pre style='font-family:monospace; font-size:14px; padding:20px;'>";
echo "==========================================\n";
echo " MySQL 8.0 Compatibility Check - PIMS\n";
echo "==========================================\n\n";

// --- CHECK 1: Engine version ---
$versionResult = mysqli_query($conn, "SELECT VERSION() as v");
$versionRow    = mysqli_fetch_assoc($versionResult);
$version       = $versionRow['v'];
$isMariaDB     = stripos($version, 'mariadb') !== false;

echo "Engine : " . $version . "\n";
echo "Type   : " . ($isMariaDB ? "MariaDB - localhost" : "MySQL - production") . "\n\n";

// --- CHECK 2: Strict mode ---
echo "[1/4] Checking strict mode...\n";
$modeResult = mysqli_query($conn, "SELECT @@sql_mode as mode");
$modeRow    = mysqli_fetch_assoc($modeResult);
$sqlMode    = $modeRow['mode'];

if (strpos($sqlMode, 'STRICT_TRANS_TABLES') === false) {
    echo "      FAIL: Strict mode OFF - add SET SESSION sql_mode in your config.php\n";
    $issues++;
} else {
    echo "      PASS: Strict mode ON\n";
}
echo "      Current mode: " . $sqlMode . "\n";

// --- CHECK 3: Reserved keywords in column names ---
echo "\n[2/4] Checking column names for reserved keywords...\n";
$reserved      = array('rank','groups','percent','system','rows','cube','lateral','window');
$tablesResult  = mysqli_query($conn, "SHOW TABLES");
$tables        = array();
$foundKeywords = false;

while ($row = mysqli_fetch_array($tablesResult)) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    $colResult = mysqli_query($conn, "SHOW COLUMNS FROM " . $table);
    while ($col = mysqli_fetch_assoc($colResult)) {
        $colName = strtolower($col['Field']);
        if (in_array($colName, $reserved)) {
            echo "      FAIL: Reserved keyword found: " . $table . "." . $col['Field'] . " - rename this column\n";
            $issues++;
            $foundKeywords = true;
        }
    }
}
if (!$foundKeywords) {
    echo "      PASS: No reserved keywords found in any column names\n";
}

// --- CHECK 4: Zero dates ---
echo "\n[3/4] Checking for zero dates (0000-00-00)...\n";
$zeroDatesFound = false;

foreach ($tables as $table) {
    $dateColResult = mysqli_query($conn,
        "SELECT COLUMN_NAME " .
        "FROM information_schema.COLUMNS " .
        "WHERE TABLE_SCHEMA = DATABASE() " .
        "AND TABLE_NAME = '" . $table . "' " .
        "AND DATA_TYPE IN ('date','datetime','timestamp')"
    );

    while ($col = mysqli_fetch_assoc($dateColResult)) {
        $colName     = $col['COLUMN_NAME'];
        $countResult = mysqli_query($conn,
            "SELECT COUNT(*) as cnt FROM " . $table . " " .
            "WHERE " . $colName . " = '0000-00-00' " .
            "OR " . $colName . " = '0000-00-00 00:00:00'"
        );
        $countRow = mysqli_fetch_assoc($countResult);
        $count    = $countRow['cnt'];

        if ($count > 0) {
            echo "      FAIL: Zero dates - " . $count . " row(s) in " . $table . "." . $colName . "\n";
            $zeroDatesFound = true;
            $issues++;
        }
    }
}
if (!$zeroDatesFound) {
    echo "      PASS: No zero dates found\n";
}

// --- CHECK 5: Charset ---
echo "\n[4/4] Checking character set...\n";
$charsetResult = mysqli_query($conn,
    "SELECT DEFAULT_CHARACTER_SET_NAME as cs " .
    "FROM information_schema.SCHEMATA " .
    "WHERE SCHEMA_NAME = DATABASE()"
);
$charsetRow = mysqli_fetch_assoc($charsetResult);
$charset    = $charsetRow['cs'];

if ($charset !== 'utf8mb4') {
    echo "      WARN: Charset is '" . $charset . "' - recommend utf8mb4 for MySQL 8.0\n";
    $issues++;
} else {
    echo "      PASS: Charset is utf8mb4\n";
}

// --- CHECK 6: system_logs table (specific to PIMS) ---
echo "\n[Bonus] Checking system_logs table...\n";
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'system_logs'");
if (mysqli_num_rows($tableCheck) > 0) {
    $colResult = mysqli_query($conn, "SHOW COLUMNS FROM system_logs");
    $cols = array();
    while ($col = mysqli_fetch_assoc($colResult)) {
        $cols[] = $col['Field'];
    }
    echo "      PASS: system_logs exists. Columns: " . implode(', ', $cols) . "\n";
} else {
    echo "      INFO: system_logs not found - will be created on first login\n";
}

// --- Final result ---
echo "\n==========================================\n";
if ($issues === 0) {
    echo " ALL CHECKS PASSED. Safe to deploy.\n";
} else {
    echo " " . $issues . " ISSUE(S) FOUND. Fix before deploying.\n";
}
echo "==========================================\n";
echo "\nREMINDER: Delete this file before going live.\n";
echo "</pre>";
