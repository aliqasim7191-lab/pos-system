<?php
require 'includes/db.php';
$res = $conn->query("SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE REFERENCED_TABLE_NAME = 'suppliers' AND TABLE_SCHEMA = 'pos_system';");
while($row = $res->fetch_assoc()){ print_r($row); }
?>
