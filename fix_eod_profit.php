<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/end_of_day.php");

$bad = <<<PHP
\$purchasesQuery = \$conn->query("SELECT SUM(amount_paid) as p FROM purchases WHERE is_cleared = 0 \$bF_AND");
\$totalPurchases = \$purchasesQuery->fetch_assoc()['p'] ?? 0;

\$netProfit = \$totalSales - \$totalExpenses - \$totalPurchases;
PHP;

$good = <<<PHP
\$purchasesQuery = \$conn->query("SELECT SUM(amount_paid) as p FROM purchases WHERE is_cleared = 0 \$bF_AND");
\$totalPurchases = \$purchasesQuery->fetch_assoc()['p'] ?? 0;

// Gross Profit Margin Calculation (Total Sales - COGS)
\$bF_AND_S = \$isAdmin ? " AND s.tenant_id = {\$_SESSION['tenant_id']}" : " AND s.tenant_id = {\$_SESSION['tenant_id']} AND s.branch_id = \$current_branch_id";
\$cogsQuery = \$conn->query("SELECT SUM(si.quantity * si.cost_price) as cogs FROM sales s JOIN sale_items si ON s.id = si.sale_id WHERE s.is_cleared = 0 \$bF_AND_S");
\$totalCOGS = \$cogsQuery->fetch_assoc()['cogs'] ?? 0;

\$netProfit = \$totalSales - \$totalExpenses - \$totalPurchases;
\$grossMargin = \$totalSales - \$totalCOGS;
PHP;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/end_of_day.php", $f);
echo "Added gross margin to end of day logic.\n";
?>
