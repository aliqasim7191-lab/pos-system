<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/checkout.php");

$bad = <<<PHP
    \$stmtItem->close();
    \$stmtUpdateStock->close();
    \$stmtUpdateVarStock->close();

    // 3. Update customer outstanding balance if it's a credit sale, and add loyalty points
PHP;

$good = <<<PHP
    \$stmtItem->close();
    \$stmtUpdateStock->close();
    \$stmtUpdateVarStock->close();

    // EMAIL RECEIPT LOGIC
    if (\$customerId) {
        \$cQ = \$conn->query("SELECT email, name FROM customers WHERE id = \$customerId LIMIT 1");
        if (\$cQ && \$cQ->num_rows > 0) {
            \$cRow = \$cQ->fetch_assoc();
            if (!empty(\$cRow['email'])) {
                include_once 'includes/mailer.php';
                \$body = "<div style='font-family:sans-serif; padding:20px; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0;'>";
                \$body .= "<h2 style='color:#0f172a;'>Purchase Receipt #\$saleId</h2>";
                \$body .= "<p>Dear " . htmlspecialchars(\$cRow['name']) . ", thank you for your purchase.</p>";
                \$body .= "<table style='width:100%; border-collapse:collapse; margin:20px 0;'>
                            <tr style='background:#e2e8f0;'>
                                <th style='padding:10px; text-align:left;'>Item</th>
                                <th style='padding:10px; text-align:center;'>Qty</th>
                                <th style='padding:10px; text-align:right;'>Price</th>
                                <th style='padding:10px; text-align:right;'>Total</th>
                            </tr>";
                foreach(\$cart as \$item) {
                    \$itemName = htmlspecialchars(\$item['name']);
                    if (!empty(\$item['variation_name'])) {
                        \$itemName .= " (" . htmlspecialchars(\$item['variation_name']) . ")";
                    }
                    \$qty = \$item['quantity'];
                    \$price = number_format(\$item['price'], 2);
                    \$lineTotal = number_format(\$item['price'] * \$qty, 2);
                    \$body .= "<tr style='border-bottom:1px solid #cbd5e1;'>
                                <td style='padding:10px;'>\$itemName</td>
                                <td style='padding:10px; text-align:center;'>\$qty</td>
                                <td style='padding:10px; text-align:right;'>\$\$price</td>
                                <td style='padding:10px; text-align:right;'>\$\$lineTotal</td>
                              </tr>";
                }
                \$body .= "<tr><td colspan='3' style='padding:10px; text-align:right; font-weight:bold;'>Subtotal</td><td style='padding:10px; text-align:right;'>$" . number_format(\$totalAmount, 2) . "</td></tr>";
                if (\$discount > 0) {
                    \$body .= "<tr><td colspan='3' style='padding:10px; text-align:right; font-weight:bold; color:#ef4444;'>Discount</td><td style='padding:10px; text-align:right; color:#ef4444;'>-$" . number_format(\$discount, 2) . "</td></tr>";
                }
                if (\$tax_amount > 0) {
                    \$body .= "<tr><td colspan='3' style='padding:10px; text-align:right; font-weight:bold;'>Tax</td><td style='padding:10px; text-align:right;'>$" . number_format(\$tax_amount, 2) . "</td></tr>";
                }
                \$body .= "<tr><td colspan='3' style='padding:10px; text-align:right; font-weight:bold; font-size:1.2em;'>Grand Total</td><td style='padding:10px; text-align:right; font-weight:bold; font-size:1.2em; color:#10b981;'>$" . number_format(\$finalTotal, 2) . "</td></tr>";
                \$body .= "</table>";
                \$body .= "<p style='color:#64748b; font-size:0.9em; text-align:center;'>Paid via " . htmlspecialchars(\$paymentMethod) . "</p>";
                \$body .= "</div>";
                send_email_smtp(\$cRow['email'], "Purchase Receipt #\$saleId", \$body, \$conn);
            }
        }
    }

    // 3. Update customer outstanding balance if it's a credit sale, and add loyalty points
PHP;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/checkout.php", $f);
echo "checkout.php updated with email receipt!";
?>
