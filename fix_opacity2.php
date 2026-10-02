<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");
$f = str_replace(
    'background:rgba(0, 0, 0, 0.7);', 
    'background:rgba(0, 0, 0, 0.4);', 
    $f
);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Opacity adjusted to 0.4!";
?>
