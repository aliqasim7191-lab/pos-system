<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");
$f = str_replace('<html lang="en">', '<html>', $f);
file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);
echo "Fixed HTML tag";
?>
