<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/end_of_day.php");

$bad = <<<PHP
                <div class="summary-box">
                    <h3>Revenue & Flow</h3>
                    <p>Total Sales (Inc. Udhaar): <span>PKR <?php echo number_format(\$totalSales, 2); ?></span></p>
                    <p>Total Expenses: <span>- PKR <?php echo number_format(\$totalExpenses, 2); ?></span></p>
                    <p>Payments to Suppliers: <span>- PKR <?php echo number_format(\$totalPurchases, 2); ?></span></p>
                    <hr>
                    <p class="highlight">Net Cash Flow: <span>PKR <?php echo number_format(\$netProfit, 2); ?></span></p>
                </div>
PHP;

$good = <<<PHP
                <div class="summary-box">
                    <h3>Revenue & Flow</h3>
                    <p>Total Sales (Inc. Udhaar): <span>PKR <?php echo number_format(\$totalSales, 2); ?></span></p>
                    <p>Total Expenses: <span>- PKR <?php echo number_format(\$totalExpenses, 2); ?></span></p>
                    <p>Payments to Suppliers: <span>- PKR <?php echo number_format(\$totalPurchases, 2); ?></span></p>
                    <hr>
                    <p>Net Cash Flow: <span>PKR <?php echo number_format(\$netProfit, 2); ?></span></p>
                    <hr>
                    <h4 style="margin: 10px 0 5px 0;">Profit / Loss (Margin)</h4>
                    <p>Cost of Goods Sold (COGS): <span>PKR <?php echo number_format(\$totalCOGS, 2); ?></span></p>
                    <p class="highlight" style="background: <?php echo \$grossMargin >= 0 ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo \$grossMargin >= 0 ? '#166534' : '#991b1b'; ?>; padding: 5px; border-radius: 4px;">Net Profit Margin: <span><?php echo \$grossMargin >= 0 ? '+' : ''; ?> PKR <?php echo number_format(\$grossMargin, 2); ?></span></p>
                </div>
PHP;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/end_of_day.php", $f);
echo "Added gross margin UI to end of day.\n";
?>
