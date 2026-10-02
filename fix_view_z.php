<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/view_z_report.php");

$bad_ui = <<<PHP
                    <div class="summary-item">
                        <span>Total Sales:</span>
                        <strong>$<?php echo number_format(\$report['total_sales'], 2); ?></strong>
                    </div>
                    <div class="summary-item" style="color: var(--danger);">
                        <span>Total Expenses:</span>
                        <strong>-$<?php echo number_format(\$report['total_expenses'], 2); ?></strong>
                    </div>
                    <div class="summary-item" style="color: var(--danger);">
                        <span>Supplier Payments:</span>
                        <strong>-$<?php echo number_format(\$report['total_supplier_payments'], 2); ?></strong>
                    </div>
PHP;

$good_ui = <<<PHP
                    <div class="summary-item">
                        <span>Total Sales:</span>
                        <strong>$<?php echo number_format(\$report['total_sales'], 2); ?></strong>
                    </div>
                    <div class="summary-item" style="color: var(--danger);">
                        <span>Total Expenses:</span>
                        <strong>-$<?php echo number_format(\$report['total_expenses'], 2); ?></strong>
                    </div>
                    <div class="summary-item" style="color: var(--danger);">
                        <span>Supplier Payments:</span>
                        <strong>-$<?php echo number_format(\$report['total_supplier_payments'], 2); ?></strong>
                    </div>
                    <hr>
                    <div class="summary-item" style="color: #475569;">
                        <span>Cost of Goods (COGS):</span>
                        <strong>-$<?php echo number_format(\$report['total_cogs'] ?? 0, 2); ?></strong>
                    </div>
                    <?php \$gMargin = \$report['total_sales'] - (\$report['total_cogs'] ?? 0); ?>
                    <div class="summary-item" style="color: <?php echo \$gMargin >= 0 ? 'var(--success)' : 'var(--danger)'; ?>; font-size: 1.1rem;">
                        <span>Net Profit Margin:</span>
                        <strong><?php echo \$gMargin >= 0 ? '+' : ''; ?>$<?php echo number_format(\$gMargin, 2); ?></strong>
                    </div>
PHP;

$f = str_replace($bad_ui, $good_ui, $f);
file_put_contents("C:/xampp/htdocs/point of sale/view_z_report.php", $f);
echo "Updated view_z_report UI.\n";
?>
