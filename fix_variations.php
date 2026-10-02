<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");

$bad = <<<PHP
\$result = \$conn->query("SELECT p.*, t.rate as custom_tax_rate FROM products p LEFT JOIN tax_classes t ON p.tax_class_id = t.id WHERE p.status='active' AND p.tenant_id = {\$_SESSION['tenant_id']}");
\$products = [];
if (\$result) {
    while(\$row = \$result->fetch_assoc()) {
        \$products[] = \$row;
    }
}
PHP;

$good = <<<PHP
\$result = \$conn->query("SELECT p.*, t.rate as custom_tax_rate FROM products p LEFT JOIN tax_classes t ON p.tax_class_id = t.id WHERE p.status='active' AND p.tenant_id = {\$_SESSION['tenant_id']}");
\$products = [];
if (\$result) {
    while(\$row = \$result->fetch_assoc()) {
        \$row['variations'] = [];
        \$pid = \$row['id'];
        \$vQ = \$conn->query("SELECT * FROM product_variations WHERE product_id = \$pid");
        if (\$vQ) {
            while(\$v = \$vQ->fetch_assoc()) {
                \$row['variations'][] = \$v;
            }
        }
        \$products[] = \$row;
    }
}
PHP;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Variations fetching logic restored!";
?>
