<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");

$bad = <<<HTML
                        <div class="product-info-wrapper">
                            <div class="product-category"><?= \$cat ?></div>
                            <h3 style="font-size: 0.95rem; margin-bottom: 0.2rem;"><?= \$name ?></h3>
                            <div style="font-size: 0.75rem; color: #64748b;">SKU: <?= \$sku ?></div>
                            <div style="font-size: 0.8rem; margin-top: 0.2rem; color: #10b981; font-weight: 600;">
                                📦 <?= \$stock ?> in stock
                            </div>
HTML;

$good = <<<HTML
                        <div class="product-info-wrapper">
                            <div class="product-category"><?= \$cat ?></div>
                            <h3 style="font-size: 0.95rem; margin-bottom: 0.2rem;"><?= \$name ?></h3>
                            <div style="font-size: 0.75rem; color: #64748b;">SKU: <?= \$sku ?></div>
                            <div style="font-size: 0.8rem; margin-top: 0.2rem; color: #10b981; font-weight: 600;">
                                🟢 <?= \$stock ?> in stock
                            </div>
HTML;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Card HTML fixed!";
?>
