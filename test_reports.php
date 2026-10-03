<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/reports.php");
$s = strpos($f, "<th>Subtotal</th>");
echo substr($f, $s - 200, 800);
?>
