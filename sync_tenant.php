<?php
$conn = new mysqli("localhost", "root", "", "pos_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "INSERT IGNORE INTO tenants (id, company_name, domain, subscription_plan, subscription_status, subscription_ends_at) VALUES (1, 'Qasim Store (Main)', 'main.store', 'enterprise', 'active', DATE_ADD(CURRENT_DATE, INTERVAL 1 YEAR))";

if ($conn->query($sql) === TRUE) {
    echo "Tenant successfully added/synced!";
} else {
    echo "Error: " . $conn->error;
}
?>
