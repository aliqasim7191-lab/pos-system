<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/product_variations.php");
$f = str_replace('AND branch_id = $current_branch_id', 'AND tenant_id = ' . "\$_SESSION['tenant_id']", $f);
file_put_contents("C:/xampp/htdocs/point of sale/product_variations.php", $f);
echo "product_variations.php tenant_id fixed!";
?>
