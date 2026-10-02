<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/print_z_report.php");

$bad_logic = <<<PHP
\$purchasesQuery = \$conn->query("SELECT SUM(amount_paid) as p FROM purchases WHERE is_cleared = 0 \$bF_AND");
\$totalPurchases = \$purchasesQuery->fetch_assoc()['p'] ?? 0;

\$netProfit = \$totalSales - \$totalExpenses - \$totalPurchases;
PHP;

$good_logic = <<<PHP
\$purchasesQuery = \$conn->query("SELECT SUM(amount_paid) as p FROM purchases WHERE is_cleared = 0 \$bF_AND");
\$totalPurchases = \$purchasesQuery->fetch_assoc()['p'] ?? 0;

\$bF_AND_S = " AND s.tenant_id = {\$_SESSION['tenant_id']}";
\$cogsQuery = \$conn->query("SELECT SUM(si.quantity * si.cost_price) as cogs FROM sales s JOIN sale_items si ON s.id = si.sale_id WHERE s.is_cleared = 0 \$bF_AND_S");
\$totalCOGS = \$cogsQuery->fetch_assoc()['cogs'] ?? 0;

\$netProfit = \$totalSales - \$totalExpenses - \$totalPurchases;
\$grossMargin = \$totalSales - \$totalCOGS;
PHP;

$f = str_replace($bad_logic, $good_logic, $f);

$bad_ui = <<<PHP
        <div class="totals">
            <div class="row"><span>Total Orders:</span> <span><?php echo \$totalOrders; ?></span></div>
            <div class="row"><span>Total Sales (Cash+Udhaar):</span> <span><?php echo number_format(\$totalSales, 2); ?></span></div>
            <?php if(\$totalCreditSales > 0): ?>
            <div class="row" style="font-size: 12px; color: #555;"><span>  - Cash Sales:</span> <span><?php echo number_format(\$totalCashSalesOnly, 2); ?></span></div>
            <div class="row" style="font-size: 12px; color: #555;"><span>  - Udhaar/Card:</span> <span><?php echo number_format(\$totalCreditSales, 2); ?></span></div>
            <?php endif; ?>
            <div class="row"><span>Expenses Paid:</span> <span>-<?php echo number_format(\$totalExpenses, 2); ?></span></div>
            <div class="row"><span>Supplier Paid:</span> <span>-<?php echo number_format(\$totalPurchases, 2); ?></span></div>
        </div>
PHP;

$good_ui = <<<PHP
        <div class="totals">
            <div class="row"><span>Total Orders:</span> <span><?php echo \$totalOrders; ?></span></div>
            <div class="row"><span>Total Sales (Cash+Udhaar):</span> <span><?php echo number_format(\$totalSales, 2); ?></span></div>
            <?php if(\$totalCreditSales > 0): ?>
            <div class="row" style="font-size: 12px; color: #555;"><span>  - Cash Sales:</span> <span><?php echo number_format(\$totalCashSalesOnly, 2); ?></span></div>
            <div class="row" style="font-size: 12px; color: #555;"><span>  - Udhaar/Card:</span> <span><?php echo number_format(\$totalCreditSales, 2); ?></span></div>
            <?php endif; ?>
            <div class="row"><span>Expenses Paid:</span> <span>-<?php echo number_format(\$totalExpenses, 2); ?></span></div>
            <div class="row"><span>Supplier Paid:</span> <span>-<?php echo number_format(\$totalPurchases, 2); ?></span></div>
            
            <div class="line" style="margin: 5px 0;"></div>
            <div class="row"><span>Cost of Goods (COGS):</span> <span>-<?php echo number_format(\$totalCOGS, 2); ?></span></div>
            <div class="row" style="font-weight:bold; font-size: 13px;"><span>Net Profit Margin:</span> <span><?php echo \$grossMargin >= 0 ? '+' : ''; ?><?php echo number_format(\$grossMargin, 2); ?></span></div>
        </div>
PHP;

$f = str_replace($bad_ui, $good_ui, $f);

file_put_contents("C:/xampp/htdocs/point of sale/print_z_report.php", $f);
echo "Added gross margin to print_z_report.\n";
?>
