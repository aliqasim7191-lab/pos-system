<?php
$file = file_get_contents('receipt.php');

// Define the currency variables fetching at the top
$currencyFetching = <<<PHP
\$pri_curr = \$settings['currency_symbol'] ?? '$';
\$sec_curr = \$settings['secondary_currency_symbol'] ?? '';
\$exch_rate = floatval(\$settings['exchange_rate'] ?? 1);
PHP;

// Find where settings are fetched and inject currency variables
$file = preg_replace('/(\$settings\[\$row\[\'setting_key\'\]\] = \$row\[\'setting_value\'\];\s*\}\s*\})/', "$1\n\n$currencyFetching", $file);

// Replace $ with <?php echo htmlspecialchars($pri_curr); ?> in HTML parts
// We have to be careful not to replace PHP variables like $total.
// Using regex to match $ followed by <?php
$file = preg_replace('/\$<\?php/', '<?php echo htmlspecialchars($pri_curr); ?><?php', $file);

// There's also some text parts in JS WhatsApp script:
$file = preg_replace('/Discount: -\$/', 'Discount: -<?php echo htmlspecialchars($pri_curr); ?>', $file);
$file = preg_replace('/Tax: \+\$/', 'Tax: +<?php echo htmlspecialchars($pri_curr); ?>', $file);
$file = preg_replace('/\*Total: \$/', '*Total: <?php echo htmlspecialchars($pri_curr); ?>', $file);

// And echo "text += \"{\$qty}x {\$itemName} - \${\$sub}\\n\";\n";
$file = preg_replace('/ - \\$\\{\\$sub\\}/', ' - <?php echo addslashes($pri_curr); ?>{$sub}', $file);

file_put_contents('receipt.php', $file);
?>
