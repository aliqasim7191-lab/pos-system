<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/receipt.php");

$bad = <<<PHP
                echo "text += \"• {\$itemName} \\n  {\$qty} x {\$sub}\\n\";\\n";
PHP;

$good = <<<PHP
                echo "text += \"• {\$itemName} \\n    {\$qty} x <?php echo \$pri_curr; ?> {\$sub}\\n\";\\n";
PHP;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/receipt.php", $f);
echo "Currency sign injected!\n";
?>
