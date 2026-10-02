<?php
$conn = new mysqli("localhost", "root", "", "pos_system");
$conn->query("ALTER TABLE settings DROP PRIMARY KEY, ADD PRIMARY KEY (setting_key, tenant_id)");
echo $conn->error;
echo "PK updated!";
?>
