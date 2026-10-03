<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/print_z_report.php");

$start = strpos($f, "<?php");
$end = strpos($f, "?>", $start) + 2;

$newPhp = <<<PHP
<?php
session_start();
if (!isset(\$_SESSION['user_id'])) {
    die("Unauthorized");
}
include 'includes/db.php';

// Only Admin
if (!isset(\$_SESSION['role']) || \$_SESSION['role'] !== 'admin') {
    die("Access Denied: Only administrators can view the Z-Report.");
}

\$today = date('Y-m-d');
\$isAdmin = true;
\$current_branch_id = \$_SESSION['branch_id'] ?? 1;
\$bF_AND = " AND tenant_id = {\$_SESSION['tenant_id']}";

// Fetch Uncleared (Active) Summary for the Z-Report
\$salesQuery = \$conn->query("SELECT SUM(total_amount) as s, COUNT(id) as c FROM sales WHERE is_cleared = 0 \$bF_AND");
\$salesData = \$salesQuery->fetch_assoc();
\$totalSales = \$salesData['s'] ?? 0;
\$totalOrders = \$salesData['c'] ?? 0;

\$creditSalesQuery = \$conn->query("SELECT SUM(total_amount) as cs FROM sales WHERE payment_method != 'cash' AND is_cleared = 0 \$bF_AND");
\$totalCreditSales = \$creditSalesQuery->fetch_assoc()['cs'] ?? 0;
\$totalCashSalesOnly = \$totalSales - \$totalCreditSales;

\$expenseQuery = \$conn->query("SELECT SUM(amount) as e FROM expenses WHERE is_cleared = 0 \$bF_AND");
\$totalExpenses = \$expenseQuery->fetch_assoc()['e'] ?? 0;

\$purchasesQuery = \$conn->query("SELECT SUM(amount_paid) as p FROM purchases WHERE is_cleared = 0 \$bF_AND");
\$totalPurchases = \$purchasesQuery->fetch_assoc()['p'] ?? 0;

\$netProfit = \$totalSales - \$totalExpenses - \$totalPurchases;

// Cash Math
\$cashQuery = \$conn->query("SELECT SUM(total_amount) as net_cash FROM sales WHERE payment_method='cash' AND is_cleared = 0 \$bF_AND");
\$netCashSales = \$cashQuery->fetch_assoc()['net_cash'] ?? 0;

\$khataQuery = \$conn->query("SELECT SUM(amount) as c FROM customer_ledger WHERE type IN ('payment_received', 'advance_deposit') AND is_cleared = 0 \$bF_AND");
\$khataPaymentsReceived = \$khataQuery->fetch_assoc()['c'] ?? 0;

\$openShiftsQuery = \$conn->query("SELECT SUM(opening_cash) as oc, SUM(closing_cash) as cc FROM shifts WHERE is_cleared = 0 \$bF_AND");
\$shiftData = \$openShiftsQuery->fetch_assoc();
\$totalOpeningCash = \$shiftData['oc'] ?? 0;
\$totalClosingCash = \$shiftData['cc'] ?? 0;

\$expectedCash = \$totalOpeningCash + \$netCashSales + \$khataPaymentsReceived - \$totalExpenses - \$totalPurchases;
\$finalActualCash = \$totalClosingCash > 0 ? \$totalClosingCash : \$expectedCash;

// Fetch Detailed Orders
\$detailedOrders = \$conn->query("SELECT id, customer_name, total_amount, payment_method, created_at FROM sales WHERE is_cleared = 0 \$bF_AND ORDER BY created_at DESC");

// Fetch Detailed Expenses
\$detailedExpenses = \$conn->query("SELECT category, amount, expense_date FROM expenses WHERE is_cleared = 0 \$bF_AND ORDER BY expense_date DESC");

// Fetch Detailed Supplier Payments
\$detailedPurchases = \$conn->query("SELECT s.name as supplier_name, p.amount_paid, p.created_at FROM purchases p LEFT JOIN suppliers s ON p.supplier_id = s.id WHERE p.is_cleared = 0 \$bF_AND ORDER BY p.created_at DESC");

\$settingsQ = \$conn->query("SELECT setting_key, setting_value FROM settings");
\$settings = [];
while(\$row = \$settingsQ->fetch_assoc()) {
    \$settings[\$row['setting_key']] = \$row['setting_value'];
}
?>
PHP;

$f = substr_replace($f, $newPhp, $start, $end - $start);
file_put_contents("C:/xampp/htdocs/point of sale/print_z_report.php", $f);
echo "Done replacing top block.\n";
?>
