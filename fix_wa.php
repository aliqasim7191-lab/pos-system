<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/receipt.php");

$bad = <<<PHP
            // Basic cleanup (remove spaces, dashes)
            phone = phone.replace(/[\s\-]/g, '');
PHP;

$good = <<<PHP
            // Basic cleanup (remove spaces, dashes)
            phone = phone.replace(/[\s\-\+]/g, '');
            // Auto convert local Pakistani format (03xx) to international format (923xx)
            if (phone.startsWith('03') && phone.length === 11) {
                phone = '92' + phone.substring(1);
            }
PHP;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/receipt.php", $f);
echo "WhatsApp phone format fixed!\n";
?>
