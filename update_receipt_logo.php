<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/receipt.php");

// 1. Add fetching of store_logo at the top
$php_inject = <<<'PHP'
$tSettings = [];
$tSetQ = $conn->query("SELECT setting_key, setting_value FROM settings WHERE tenant_id = " . ($_SESSION['tenant_id'] ?? 1));
if($tSetQ) { while($row = $tSetQ->fetch_assoc()) { $tSettings[$row['setting_key']] = $row['setting_value']; } }
$receiptLogo = !empty($tSettings['store_logo']) ? $tSettings['store_logo'] : '';
$storeName = $tSettings['store_name'] ?? 'SuperStore POS';

PHP;
$f = str_replace('$sale = $saleQ->fetch_assoc();', '$sale = $saleQ->fetch_assoc();' . "\n" . $php_inject, $f);

// 2. Add the logo to the HTML output
$html_logo = <<<'HTML'
    <div style="text-align: center; margin-bottom: 0.5rem;">
        <?php if(!empty($receiptLogo)): ?>
            <img src="<?php echo htmlspecialchars($receiptLogo); ?>" style="max-width: 150px; max-height: 60px; margin-bottom: 5px;">
        <?php endif; ?>
        <h2 style="margin: 0; font-size: 1.2rem;"><?php echo htmlspecialchars($storeName); ?></h2>
    </div>
HTML;
$f = preg_replace('/<h2 style="text-align: center; margin: 0 0 5px 0;">.*?<\/h2>/s', $html_logo, $f);

file_put_contents("C:/xampp/htdocs/point of sale/receipt.php", $f);
echo "Receipt Logo Updated!";
?>
