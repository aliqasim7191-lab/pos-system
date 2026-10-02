<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/shift.php");

$bad = 'name="starting_cash" value="0.00" required';
$good = 'name="starting_cash" value="" placeholder="Enter opening cash..." required min="0"';
$f = str_replace($bad, $good, $f);

file_put_contents("C:/xampp/htdocs/point of sale/shift.php", $f);
echo "Shift opening cash fixed!\n";
?>
