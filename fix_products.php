<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/products.php");

$bad_insert = 'INSERT INTO products (name, purchase_price, price, wholesale_price, stock, image, unit, barcode, category, tax_class_id, branch_id, tenant_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, {$_SESSION['tenant_id']})';
$good_insert = 'INSERT INTO products (name, purchase_price, price, wholesale_price, stock, image, unit, barcode, category, tax_class_id, branch_id, tenant_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, {$_SESSION[\'tenant_id\']})';

$f = str_replace($bad_insert, $good_insert, $f);

file_put_contents("C:/xampp/htdocs/point of sale/products.php", $f);
echo "Products insert fixed!\n";
?>
