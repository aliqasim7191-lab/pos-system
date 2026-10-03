<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/reports.php");
$s = strpos($f, "SELECT s.id, s.total_amount");
if ($s === false) { $s = strpos($f, "SELECT * FROM sales"); }
if ($s === false) { $s = strpos($f, "FROM sales"); }
echo substr($f, $s - 100, 600);
?>
