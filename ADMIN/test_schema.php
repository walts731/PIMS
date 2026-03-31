<?php
require '../config.php';
$tables = ['assets', 'asset_items', 'ics_items', 'ics_forms', 'asset_item_history'];
foreach($tables as $t) {
    echo $t . ": ";
    $res = $conn->query("DESCRIBE " . $t);
    while($r = $res->fetch_assoc()) {
        if($r['Field'] == 'ics_id') echo 'YES ';
    }
    echo "\n";
}
?>
