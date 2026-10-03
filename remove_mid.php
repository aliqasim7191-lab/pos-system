<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/print_z_report.php");

$bad = <<<PHP
        <?php
        // Fetch Active Shifts Data
        \$openShiftsQuery = \$conn->query("SELECT SUM(opening_cash) as oc, SUM(closing_cash) as cc FROM shifts WHERE is_cleared = 0 AND tenant_id = {\$_SESSION['tenant_id']}");
        \$shiftData = \$openShiftsQuery->fetch_assoc();
        \$totalOpeningCash = \$shiftData['oc'] ?? 0;
        \$totalClosingCash = \$shiftData['cc'] ?? 0;
        
        \$cashSalesQuery = \$conn->query("SELECT SUM(total_amount) as c FROM sales WHERE payment_method = 'cash' AND is_cleared = 0 AND tenant_id = {\$_SESSION['tenant_id']}");
        \$netCashSales = \$cashSalesQuery->fetch_assoc()['c'] ?? 0;
        
        \$kQ = \$conn->query("SELECT SUM(amount) as k FROM customer_ledger WHERE type IN ('payment_received', 'advance_deposit') AND is_cleared = 0 AND customer_id IN (SELECT id FROM customers WHERE tenant_id = {\$_SESSION['tenant_id']})");
        \$khataPaymentsReceived = \$kQ->fetch_assoc()['k'] ?? 0;
        
        \$expectedCash = \$totalOpeningCash + \$netCashSales + \$khataPaymentsReceived - \$totalExpenses - \$totalPurchases;
        \$finalActualCash = \$totalClosingCash > 0 ? \$totalClosingCash : \$expectedCash;
        ?>
PHP;

$f = str_replace($bad, "", $f);
file_put_contents("C:/xampp/htdocs/point of sale/print_z_report.php", $f);
echo "Removed duplicate mid-file logic!\n";
?>
