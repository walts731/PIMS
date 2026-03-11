<?php
// fix_zero_dates.php
// Converts 0000-00-00 dates to NULL for MySQL 8.0 compatibility
// DELETE THIS FILE after running it successfully

require_once 'config.php';

if (!isset($conn) || !$conn) {
    die("<pre>Could not find conn. Check config.php path.</pre>");
}

echo "<pre style='font-family:monospace; font-size:14px; padding:20px;'>";
echo "==========================================\n";
echo " Zero Date Fix Script - PIMS\n";
echo "==========================================\n\n";

// Safety check - only run if user confirms
$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

if (!$confirmed) {
    echo "This script will convert all 0000-00-00 dates to NULL.\n\n";
    echo "STEP 1: First run a preview to see what will be changed:\n";
    echo "  http://localhost/PIMS/fix_zero_dates.php?confirm=preview\n\n";
    echo "STEP 2: If preview looks correct, run the actual fix:\n";
    echo "  http://localhost/PIMS/fix_zero_dates.php?confirm=yes\n";
    echo "</pre>";
    exit;
}

$preview = isset($_GET['confirm']) && $_GET['confirm'] === 'preview';

if ($preview) {
    echo "PREVIEW MODE - no changes will be made\n\n";
} else {
    echo "FIX MODE - applying changes now\n\n";
}

// All zero-date columns found by compatibility check
$targets = array(
    'ics_forms'   => array('received_from_date', 'received_by_date'),
    'itr_forms'   => array('requested_date', 'approved_date', 'released_date', 'received_date'),
    'par_forms'   => array('received_by_date', 'issued_by_date'),
    'ris_forms'   => array('date', 'date_2', 'requested_date', 'approved_date', 'issued_date', 'received_date'),
    'ris_summary' => array('date'),
);

$totalFixed  = 0;
$totalErrors = 0;

foreach ($targets as $table => $columns) {
    echo "Table: " . $table . "\n";
    echo str_repeat("-", 40) . "\n";

    foreach ($columns as $col) {

        // Count affected rows first
        $countResult = mysqli_query($conn,
            "SELECT COUNT(*) as cnt FROM " . $table . " " .
            "WHERE " . $col . " = '0000-00-00' " .
            "OR " . $col . " = '0000-00-00 00:00:00'"
        );
        $countRow = mysqli_fetch_assoc($countResult);
        $count    = $countRow['cnt'];

        if ($count === 0) {
            echo "  " . $col . ": no zero dates found - skipping\n";
            continue;
        }

        if ($preview) {
            echo "  " . $col . ": " . $count . " row(s) would be set to NULL\n";
            continue;
        }

        // Check if column allows NULL before updating
        $nullCheckResult = mysqli_query($conn,
            "SELECT IS_NULLABLE FROM information_schema.COLUMNS " .
            "WHERE TABLE_SCHEMA = DATABASE() " .
            "AND TABLE_NAME = '" . $table . "' " .
            "AND COLUMN_NAME = '" . $col . "'"
        );
        $nullCheckRow = mysqli_fetch_assoc($nullCheckResult);
        $isNullable   = $nullCheckRow['IS_NULLABLE'];

        if ($isNullable === 'NO') {
            // Column does not allow NULL - alter it first
            echo "  " . $col . ": column is NOT NULL - making it nullable first...\n";

            // Get column type to preserve it
            $typeResult = mysqli_query($conn,
                "SELECT COLUMN_TYPE FROM information_schema.COLUMNS " .
                "WHERE TABLE_SCHEMA = DATABASE() " .
                "AND TABLE_NAME = '" . $table . "' " .
                "AND COLUMN_NAME = '" . $col . "'"
            );
            $typeRow    = mysqli_fetch_assoc($typeResult);
            $colType    = $typeRow['COLUMN_TYPE'];

            $alterResult = mysqli_query($conn,
                "ALTER TABLE " . $table . " MODIFY " . $col . " " . $colType . " NULL"
            );

            if (!$alterResult) {
                echo "  ERROR altering " . $col . ": " . mysqli_error($conn) . "\n";
                $totalErrors++;
                continue;
            }
            echo "  " . $col . ": column altered to allow NULL\n";
        }

        // Now update zero dates to NULL
        $updateResult = mysqli_query($conn,
            "UPDATE " . $table . " SET " . $col . " = NULL " .
            "WHERE " . $col . " = '0000-00-00' " .
            "OR " . $col . " = '0000-00-00 00:00:00'"
        );

        if ($updateResult) {
            $affected = mysqli_affected_rows($conn);
            echo "  " . $col . ": " . $affected . " row(s) fixed - set to NULL\n";
            $totalFixed += $affected;
        } else {
            echo "  ERROR on " . $col . ": " . mysqli_error($conn) . "\n";
            $totalErrors++;
        }
    }
    echo "\n";
}

echo "==========================================\n";
if ($preview) {
    echo " PREVIEW COMPLETE\n";
    echo " Run with ?confirm=yes to apply fixes\n";
} else {
    echo " Total rows fixed : " . $totalFixed . "\n";
    echo " Total errors     : " . $totalErrors . "\n";
    if ($totalErrors === 0) {
        echo " ALL DONE. Run check_compatibility.php to verify.\n";
    } else {
        echo " Some errors occurred. Review above and fix manually.\n";
    }
}
echo "==========================================\n";
echo "\nREMINDER: Delete this file after use.\n";
echo "</pre>";
