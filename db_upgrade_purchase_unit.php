<?php
include 'includes/db.php';
$conn->query("ALTER TABLE purchase_items ADD COLUMN unit VARCHAR(20) DEFAULT 'pcs'");
echo "Added unit to purchase_items";
?>
