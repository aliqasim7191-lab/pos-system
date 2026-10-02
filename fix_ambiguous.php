<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/print_z_report.php");

$bad = <<<PHP
// Fetch Detailed Supplier Payments
\$detailedPurchases = \$conn->query("SELECT s.name as supplier_name, p.amount_paid, p.created_at FROM purchases p LEFT JOIN suppliers s ON p.supplier_id = s.id WHERE p.is_cleared = 0 \$bF_AND ORDER BY p.created_at DESC");
PHP;

$good = <<<PHP
// Fetch Detailed Supplier Payments
\$bF_AND_P = " AND p.tenant_id = {\$_SESSION['tenant_id']}";
\$detailedPurchases = \$conn->query("SELECT s.name as supplier_name, p.amount_paid, p.created_at FROM purchases p LEFT JOIN suppliers s ON p.supplier_id = s.id WHERE p.is_cleared = 0 \$bF_AND_P ORDER BY p.created_at DESC");
PHP;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/print_z_report.php", $f);
echo "Ambiguous column fixed!\n";
?>
