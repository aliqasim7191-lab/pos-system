<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/checkout.php");
$s = strpos($f, "SELECT purchase_price FROM products WHERE id");
echo substr($f, $s - 100, 500);
?>
