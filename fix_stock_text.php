<?php
$idx = file_get_contents("C:/xampp/htdocs/point of sale/index.php");

$bad_snippet = '<div style="font-size: 0.8rem; margin-top: 0.2rem; color: <?php echo $product[\'stock\'] < 20 ? \'<i class="fa fa-exclamation-circle"></i>\' : \'<i class="fa fa-cube"></i>\'; ?>; font-weight: 600;">
                            <?php echo $product[\'stock\'] < 20 ? \'<i class="fa fa-exclamation-circle"></i>\' : \'<i class="fa fa-cube"></i>\'; ?> <?php echo $product[\'stock\']; ?> in stock
                        </div>';

$good_snippet = '<div style="font-size: 0.8rem; margin-top: 0.2rem; color: <?php echo $product[\'stock\'] < 20 ? \'#ef4444\' : \'#10b981\'; ?>; font-weight: 600;">
                            <?php echo $product[\'stock\'] < 20 ? \'<i class="fa fa-exclamation-triangle"></i>\' : \'<i class="fa fa-cube"></i>\'; ?> <?php echo $product[\'stock\']; ?> in stock
                        </div>';

$idx = str_replace($bad_snippet, $good_snippet, $idx);

file_put_contents("C:/xampp/htdocs/point of sale/index.php", $idx);
echo "Fixed stock text in index.php\n";
?>
