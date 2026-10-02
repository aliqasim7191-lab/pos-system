<?php
$files = ['C:/xampp/htdocs/point of sale/reports.php', 'C:/xampp/htdocs/point of sale/print_z_report.php'];
foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $f = file_get_contents($file);
    
    // Replace all these specific SUM queries
    $f = str_replace(
        'FROM sales WHERE is_cleared = 0',
        'FROM sales WHERE is_cleared = 0 AND tenant_id = {$_SESSION[\'tenant_id\']}',
        $f
    );
    $f = str_replace(
        'FROM sales WHERE payment_method != \'cash\' AND is_cleared = 0',
        'FROM sales WHERE payment_method != \'cash\' AND is_cleared = 0 AND tenant_id = {$_SESSION[\'tenant_id\']}',
        $f
    );
    $f = str_replace(
        'FROM purchases WHERE is_cleared = 0',
        'FROM purchases WHERE is_cleared = 0 AND tenant_id = {$_SESSION[\'tenant_id\']}',
        $f
    );
    $f = str_replace(
        'FROM expenses WHERE is_cleared = 0',
        'FROM expenses WHERE is_cleared = 0 AND tenant_id = {$_SESSION[\'tenant_id\']}',
        $f
    );
    $f = str_replace(
        'FROM sales WHERE payment_method = \'cash\' AND is_cleared = 0',
        'FROM sales WHERE payment_method = \'cash\' AND is_cleared = 0 AND tenant_id = {$_SESSION[\'tenant_id\']}',
        $f
    );
    $f = str_replace(
        'FROM shifts WHERE is_cleared = 0',
        'FROM shifts WHERE is_cleared = 0 AND tenant_id = {$_SESSION[\'tenant_id\']}',
        $f
    );
    $f = str_replace(
        'FROM customer_ledger WHERE type IN (\'payment_received\', \'advance_deposit\') AND is_cleared = 0',
        'FROM customer_ledger WHERE type IN (\'payment_received\', \'advance_deposit\') AND is_cleared = 0 AND customer_id IN (SELECT id FROM customers WHERE tenant_id = {$_SESSION[\'tenant_id\']})',
        $f
    );
    
    // Also in reports.php some monthly uncleared:
    $f = str_replace(
        'AND s.is_monthly_cleared = 0")',
        'AND s.is_monthly_cleared = 0 AND s.tenant_id = {$_SESSION[\'tenant_id\']}")',
        $f
    );
    $f = str_replace(
        'AND is_monthly_cleared = 0 GROUP BY',
        'AND is_monthly_cleared = 0 AND tenant_id = {$_SESSION[\'tenant_id\']} GROUP BY',
        $f
    );
    $f = str_replace(
        'AND is_monthly_cleared = 0"',
        'AND is_monthly_cleared = 0 AND tenant_id = {$_SESSION[\'tenant_id\']}"',
        $f
    );

    file_put_contents($file, $f);
}
echo "SQL Queries patched!";
?>
