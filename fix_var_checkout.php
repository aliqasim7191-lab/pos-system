<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/checkout.php");

$bad = <<<PHP
        \$costQ = \$conn->query("SELECT purchase_price FROM products WHERE id = \$prodId LIMIT 1");
        if (\$costQ && \$row = \$costQ->fetch_assoc()) {
            \$cost = floatval(\$row['purchase_price']);
        }
PHP;

$good = <<<PHP
        \$cost = 0;
        \$costQ = \$conn->query("SELECT purchase_price FROM products WHERE id = \$prodId LIMIT 1");
        if (\$costQ && \$row = \$costQ->fetch_assoc()) {
            \$cost = floatval(\$row['purchase_price']);
        }
        
        // If it's a variation, try to get its specific purchase price
        if (\$varId) {
            \$varCostQ = \$conn->query("SELECT purchase_price FROM product_variations WHERE id = \$varId LIMIT 1");
            if (\$varCostQ && \$varRow = \$varCostQ->fetch_assoc()) {
                if (floatval(\$varRow['purchase_price']) > 0) {
                    \$cost = floatval(\$varRow['purchase_price']);
                }
            }
        }
PHP;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/checkout.php", $f);
echo "Updated checkout.php variation cost logic\n";
?>
