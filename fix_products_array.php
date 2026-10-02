<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");
$f = str_replace('$products[] = $row;', '$products[$row[\'id\']] = $row;', $f);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "PRODUCTS_DATA associative array fixed!";
?>
