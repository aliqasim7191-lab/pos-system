<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/receipt.php");

$bad_start = strpos($f, "let storeName =");
$bad_end = strpos($f, "let encoded = encodeURIComponent(text);");

if ($bad_start !== false && $bad_end !== false) {
    $length = $bad_end - $bad_start;
    
    $new_js = <<<JS
let storeName = "<?php echo addslashes(\$settings['store_name'] ?? 'SuperStore POS'); ?>";
            let storePhone = "<?php echo addslashes(\$settings['store_phone'] ?? ''); ?>";
            let receiptNo = "<?php echo addslashes(\$orderNo); ?>";
            let date = "<?php echo addslashes(\$date ?? ''); ?>";
            let customerName = "<?php echo addslashes(\$customerName ?? ''); ?>";
            let paymentMethod = "<?php echo strtoupper(\$paymentMethod); ?>";
            let subtotal = "<?php echo number_format((float)(\$subtotal ?? 0), 2); ?>";
            let discount = "<?php echo number_format((float)(\$discount ?? 0), 2); ?>";
            let tax = "<?php echo number_format((float)(\$taxAmount ?? 0), 2); ?>";
            let total = "<?php echo number_format((float)(\$total ?? 0), 2); ?>";
            let received = "<?php echo number_format((float)(\$displayAmount ?? 0), 2); ?>";
            let change = "<?php echo number_format((float)(\$changeReturned ?? 0), 2); ?>";
            
            let text = "*" + storeName + "*\\n";
            if(storePhone) text += "📱 " + storePhone + "\\n";
            text += "------------------------\\n";
            text += "🧾 *Receipt #: " + receiptNo + "*\\n";
            text += "📅 Date: " + date + "\\n";
            if (customerName) text += "👤 Customer: " + customerName + "\\n";
            text += "------------------------\\n";
            text += "*ITEMS:*\\n";
            
            <?php
            foreach(\$items as \$item) {
                \$itemName = addslashes(\$item['name']);
                \$qty = floatval(\$item['quantity']);
                \$sub = number_format(\$qty * \$item['price'], 2);
                echo "text += \"• {\$itemName} \\n  {\$qty} x {$pri_curr}{\$sub}\\n\";\\n";
            }
            ?>
            
            text += "------------------------\\n";
            text += "Subtotal: <?php echo \$pri_curr; ?>" + subtotal + "\\n";
            if (parseFloat(discount) > 0) text += "Discount: -<?php echo \$pri_curr; ?>" + discount + "\\n";
            if (parseFloat(tax) > 0) text += "Tax: +<?php echo \$pri_curr; ?>" + tax + "\\n";
            text += "------------------------\\n";
            text += "💰 *TOTAL DUE: <?php echo \$pri_curr; ?>" + total + "*\\n";
            text += "------------------------\\n";
            text += "Paid by: " + paymentMethod + "\\n";
            text += "Amount Received: <?php echo \$pri_curr; ?>" + received + "\\n";
            if (parseFloat(change) > 0) text += "Change: <?php echo \$pri_curr; ?>" + change + "\\n";
            text += "------------------------\\n";
            text += "Thank you for shopping! 🌟";
            
            
JS;

    $f = substr_replace($f, $new_js, $bad_start, $length);
    file_put_contents("C:/xampp/htdocs/point of sale/receipt.php", $f);
    echo "WhatsApp string fixed!\n";
} else {
    echo "Could not find string!\n";
}
?>
