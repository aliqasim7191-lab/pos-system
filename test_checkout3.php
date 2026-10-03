<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/checkout.php");
$s = strpos($f, "INSERT INTO sale_items");
echo substr($f, $s - 100, 800);
?>
