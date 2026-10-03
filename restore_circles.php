<?php
$idx = file_get_contents("C:/xampp/htdocs/point of sale/index.php");

$idx = str_replace('<i class="fa fa-exclamation-triangle"></i>', "\u{1F534}", $idx);
$idx = str_replace('<i class="fa fa-cube"></i>', "\u{1F7E2}", $idx);

file_put_contents("C:/xampp/htdocs/point of sale/index.php", $idx);
echo "Restored circles!";
?>
