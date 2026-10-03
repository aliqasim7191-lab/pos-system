<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/checkout.php");
$s = strpos($f, "foreach(\$cart");
echo substr($f, $s, 400);
?>
