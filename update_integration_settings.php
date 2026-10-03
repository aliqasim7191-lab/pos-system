<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "pos_system";
$conn = new mysqli($host, $user, $password, $database);
if($conn->query("ALTER TABLE integration_settings ADD COLUMN tenant_id INT NOT NULL DEFAULT 1")) echo "Added tenant_id to integration_settings!";
?>
