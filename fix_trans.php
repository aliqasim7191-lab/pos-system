<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");

$bad = <<<PHP
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
PHP;

$good = <<<PHP
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(2px);
PHP;

$f = str_replace($bad, $good, $f);

file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Overlay transparency fixed!\n";
?>
