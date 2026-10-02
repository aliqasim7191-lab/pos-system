<?php
require 'includes/db.php';
$res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE tenant_id = 1");
while($row = $res->fetch_assoc()) {
    echo $row['setting_key'] . "\n";
}
?>
