<?php
include 'includes/db.php';

header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="pos_system_export.sql"');

$tables = [];
$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_array()) {
    $tables[] = $row[0];
}

$sqlScript = "-- POS System Database Backup\n-- Exported on " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($tables as $table) {
    $createRes = $conn->query("SHOW CREATE TABLE `$table`")->fetch_row();
    $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n" . $createRes[1] . ";\n\n";
    
    $rowsRes = $conn->query("SELECT * FROM `$table`");
    while ($r = $rowsRes->fetch_assoc()) {
        $vals = array_map(function($v) use ($conn) {
            if ($v === null) return 'NULL';
            return "'" . $conn->real_escape_string($v) . "'";
        }, array_values($r));
        $sqlScript .= "INSERT INTO `$table` VALUES (" . implode(', ', $vals) . ");\n";
    }
    $sqlScript .= "\n";
}

$sqlScript .= "SET FOREIGN_KEY_CHECKS=1;\n";
echo $sqlScript;
exit();
?>
