<?php
include "C:/xampp/htdocs/point of sale/includes/db.php";
$conn->query("ALTER TABLE z_reports_history ADD COLUMN total_cogs DECIMAL(10,2) DEFAULT 0 AFTER total_supplier_payments");
echo "Added total_cogs column\n";
?>
