<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");
$f = str_replace(
    'background:rgba(15, 23, 42, 0.9); backdrop-filter: blur(8px);', 
    'background:rgba(0, 0, 0, 0.7);', 
    $f
);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Opacity fixed!";
?>
