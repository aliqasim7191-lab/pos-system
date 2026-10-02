<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/end_of_day.php");

$bad = <<<PHP
    \$stmt = \$conn->prepare("INSERT INTO z_reports_history (report_date, total_orders, total_sales, total_expenses, total_supplier_payments, net_income, snapshot_data, opening_cash, expected_cash, closing_cash, tenant_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, {\$_SESSION['tenant_id']})");
    \$stmt->bind_param("siddddssdd", \$reportDate, \$totalOrders, \$totalSales, \$totalExpenses, \$totalPurchases, \$netProfit, \$snapshot, \$openingCash, \$expectedCash, \$closingCash);
PHP;

$good = <<<PHP
    \$stmt = \$conn->prepare("INSERT INTO z_reports_history (report_date, total_orders, total_sales, total_expenses, total_supplier_payments, total_cogs, net_income, snapshot_data, opening_cash, expected_cash, closing_cash, tenant_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, {\$_SESSION['tenant_id']})");
    \$stmt->bind_param("sidddddssdd", \$reportDate, \$totalOrders, \$totalSales, \$totalExpenses, \$totalPurchases, \$totalCOGS, \$netProfit, \$snapshot, \$openingCash, \$expectedCash, \$closingCash);
PHP;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/end_of_day.php", $f);
echo "Updated end_of_day.php saving logic.\n";
?>
