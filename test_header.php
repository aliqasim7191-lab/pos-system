<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");
$s = strpos($f, "<head>");
echo substr($f, $s, 300);
?>
