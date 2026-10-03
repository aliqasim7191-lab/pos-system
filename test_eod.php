<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/end_of_day.php");
$s = strpos($f, "Total Sales (Inc. Udhaar):");
echo substr($f, $s - 100, 500);
?>
