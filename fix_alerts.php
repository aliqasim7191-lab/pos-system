<?php
include 'includes/db.php';
$conn->query("UPDATE alerts SET action_link = 'dashboard.php#low-stock-list' WHERE type = 'Low Stock'");
echo "Fixed existing alerts.\n";
?>
