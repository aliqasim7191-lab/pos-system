<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/product_variations.php");
$f = str_replace(
    'INSERT INTO product_variations (product_id, variation_name, barcode, stock, price, tenant_id) VALUES ($product_id, \'$v_name\', \'$v_sku\', $v_stock, $priceVal, {$_SESSION['tenant_id']})', 
    'INSERT INTO product_variations (product_id, variation_name, barcode, stock, price, tenant_id) VALUES ($product_id, \'$v_name\', \'$v_sku\', $v_stock, $priceVal, ' . "\$_SESSION['tenant_id']" . ')', 
    $f
);
file_put_contents("C:/xampp/htdocs/point of sale/product_variations.php", $f);
echo "product_variations.php insert fixed!";
?>
