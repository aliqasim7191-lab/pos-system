<?php
include "C:/xampp/htdocs/point of sale/includes/db.php";
$conn->query("ALTER TABLE product_variations ADD COLUMN purchase_price DECIMAL(10,2) DEFAULT 0 AFTER price");
echo "Added purchase_price to product_variations\n";
?>
