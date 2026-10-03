<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "pos_system";
$conn = new mysqli($host, $user, $password, $database);

$conn->query("ALTER TABLE tenants ADD COLUMN subscription_ends_at DATE NULL AFTER subscription_status");
$conn->query("UPDATE tenants SET subscription_ends_at = DATE_ADD(CURRENT_DATE, INTERVAL 1 MONTH) WHERE id > 0");
echo "DB Updated!";
?>
