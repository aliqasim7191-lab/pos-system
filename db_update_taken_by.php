<?php
include 'includes/db.php';
$conn->query("ALTER TABLE sales ADD COLUMN taken_by VARCHAR(100) NULL AFTER customer_address;");
echo "DB Updated";
?>
