<?php
$idx = file_get_contents("C:/xampp/htdocs/point of sale/index.php");
$idx = preg_replace('/placeholder=".*?Scan Barcode/', 'placeholder="Scan Barcode', $idx);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $idx);
?>
