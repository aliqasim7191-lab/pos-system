<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/checkout.php");
$s = strpos($f, "foreach(\$cart", strpos($f, "foreach(\$cart")+10);
echo substr($f, $s, 600);
?>
