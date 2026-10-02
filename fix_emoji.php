<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");
$f = str_replace('placeholder="dY"? Scan Barcode (F4)"', 'placeholder="&#128269; Scan Barcode (F4)"', $f);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Emoji fixed!";
?>
