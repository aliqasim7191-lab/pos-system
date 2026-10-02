<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");
$f = preg_replace('/placeholder="[^"]*?Scan Barcode \(F4\)"/', 'placeholder="Scan Barcode (F4)"', $f);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Emoji fixed with regex!";
?>
