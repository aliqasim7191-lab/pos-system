<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/assets/js/app.js");

$bad1 = "currentTotal = total;";
$good1 = "currentTotal = Math.round(total * 100) / 100;";
$f = str_replace($bad1, $good1, $f);

$bad2 = "modalFinalTotal = currentTotal - pointsDiscount;";
$good2 = "modalFinalTotal = Math.round((currentTotal - pointsDiscount) * 100) / 100;";
$f = str_replace($bad2, $good2, $f);

file_put_contents("C:/xampp/htdocs/point of sale/assets/js/app.js", $f);
echo "app.js rounded!\n";
?>
