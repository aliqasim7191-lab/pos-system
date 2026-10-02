<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");

$bad = <<<PHP
    if (isOffline) {
        let total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        let taxAmount = (total * globalTaxRate) / 100;
        let finalTotal = total + taxAmount;
PHP;

$good = <<<PHP
    if (isOffline) {
        let finalTotal = currentTotal;
PHP;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Offline JS fixed!\n";
?>
