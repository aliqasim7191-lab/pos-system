<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/receipt.php");

$bad = <<<PHP
                  echo "text += '• ' + '{\$itemName}' + '\\n  ' + '{\$qty} x {\$pri_curr}{\$sub}\\n';\\n";
PHP;
// Actually, let's just do a clean replace using strpos.
$start = strpos($f, "foreach(\$items as \$item) {");
$end = strpos($f, "?>", $start);

if ($start !== false && $end !== false) {
    $length = $end - $start;
    $new_loop = <<<PHP
foreach(\$items as \$item) {
                  \$itemName = addslashes(\$item['name']);
                  \$qty = floatval(\$item['quantity']);
                  \$sub = number_format(\$qty * \$item['price'], 2);
                  echo "text += '• ' + '{\$itemName}' + '\\\\n  ' + '{\$qty} x {\$pri_curr}{\$sub}\\\\n';\\n";
              }
              
PHP;
    $f = substr_replace($f, $new_loop, $start, $length);
    file_put_contents("C:/xampp/htdocs/point of sale/receipt.php", $f);
    echo "Fixed JS newline error for real!\n";
} else {
    echo "Not found.\n";
}
?>
