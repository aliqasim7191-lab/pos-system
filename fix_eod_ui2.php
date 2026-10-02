<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/end_of_day.php");

$bad = <<<PHP
        <div style="background: #f0fdf4; padding: 1.5rem; border-radius: 12px; border: 1px solid #bbf7d0;">
            <div style="color: #166534; font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Net Daily Cash Generated</div>
            <?php \$netCashGenerated = \$netCashSales + \$khataPaymentsReceived - \$totalExpenses - \$totalPurchases; ?>
            <div style="font-size: 2rem; font-weight: 800; color: #15803d;">$<?php echo number_format(\$netCashGenerated, 2); ?></div>
        </div>
    </div>
PHP;

$good = <<<PHP
        <div style="background: #f0fdf4; padding: 1.5rem; border-radius: 12px; border: 1px solid #bbf7d0;">
            <div style="color: #166534; font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Net Daily Cash Generated</div>
            <?php \$netCashGenerated = \$netCashSales + \$khataPaymentsReceived - \$totalExpenses - \$totalPurchases; ?>
            <div style="font-size: 2rem; font-weight: 800; color: #15803d;">$<?php echo number_format(\$netCashGenerated, 2); ?></div>
        </div>
        <div style="background: <?php echo \$grossMargin >= 0 ? '#e0e7ff' : '#fee2e2'; ?>; padding: 1.5rem; border-radius: 12px; border: 1px solid <?php echo \$grossMargin >= 0 ? '#a5b4fc' : '#fecaca'; ?>;">
            <div style="color: <?php echo \$grossMargin >= 0 ? '#3730a3' : '#991b1b'; ?>; font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Actual Profit Margin</div>
            <div style="font-size: 2rem; font-weight: 800; color: <?php echo \$grossMargin >= 0 ? '#4338ca' : '#b91c1c'; ?>;"><?php echo \$grossMargin >= 0 ? '+' : ''; ?>$<?php echo number_format(\$grossMargin, 2); ?></div>
            <div style="font-size: 0.85rem; color: <?php echo \$grossMargin >= 0 ? '#4f46e5' : '#dc2626'; ?>; margin-top: 0.5rem;">Sales minus Purchase Cost</div>
        </div>
    </div>
PHP;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/end_of_day.php", $f);
echo "Fixed end of day UI\n";
?>
