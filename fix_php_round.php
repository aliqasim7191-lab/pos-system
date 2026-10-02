<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/checkout.php");

$bad = <<<PHP
\$finalTotal = \$totalAmount + \$tax_amount - \$discount;

if (\$finalTotal < 0) \$finalTotal = 0;
PHP;

$good = <<<PHP
\$finalTotal = round(\$totalAmount + \$tax_amount - \$discount, 2);

if (\$finalTotal < 0) \$finalTotal = 0;
PHP;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/checkout.php", $f);
echo "checkout.php rounded!\n";
?>
