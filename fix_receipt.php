<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/receipt.php");

$bad = <<<PHP
            <?php if(!empty(\$sec_curr) && \$exch_rate > 0): ?>
            <div class="row" style="font-size: 11px; color: #666;">
                <span>(In <?php echo htmlspecialchars(\$sec_curr); ?>)</span>
                <span><?php echo htmlspecialchars(\$sec_curr); ?> <?php echo number_format(\$total * \$exch_rate, 2); ?></span>
            </div>
            <?php endif; ?>
PHP;

$good = <<<PHP
            <?php /* Secondary currency conversion removed as per request */ ?>
PHP;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/receipt.php", $f);
echo "Receipt fixed!\n";
?>
