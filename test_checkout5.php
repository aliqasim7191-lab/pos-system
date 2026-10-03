<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/checkout.php");
$s = strpos($f, "\$costQ = \$conn->query(\"SELECT purchase_price");
echo substr($f, $s - 100, 400);
?>
