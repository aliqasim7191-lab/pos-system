<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/customer_ledger.php");

$f = str_replace(
    "SELECT outstanding_balance FROM customers WHERE id = \$id LIMIT 1",
    "SELECT outstanding_balance FROM customers WHERE id = \$id AND tenant_id = {\$_SESSION['tenant_id']} LIMIT 1",
    $f
);

$f = str_replace(
    "SELECT * FROM customer_ledger WHERE id = \$ledgerId AND type IN ('payment_received', 'advance_deposit') LIMIT 1",
    "SELECT * FROM customer_ledger WHERE id = \$ledgerId AND tenant_id = {\$_SESSION['tenant_id']} AND type IN ('payment_received', 'advance_deposit') LIMIT 1",
    $f
);

$f = str_replace(
    "SELECT * FROM customers WHERE id = \$id LIMIT 1",
    "SELECT * FROM customers WHERE id = \$id AND tenant_id = {\$_SESSION['tenant_id']} LIMIT 1",
    $f
);

$f = str_replace(
    "WHERE cl.customer_id = \$id \$historyFilter ORDER BY cl.created_at DESC",
    "WHERE cl.customer_id = \$id AND cl.tenant_id = {\$_SESSION['tenant_id']} \$historyFilter ORDER BY cl.created_at DESC",
    $f
);

file_put_contents("C:/xampp/htdocs/point of sale/customer_ledger.php", $f);
echo "customer_ledger queries secured!\n";
?>
