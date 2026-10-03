<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/customer_ledger.php");

$bad = <<<PHP
            \$conn->commit();
            \$message = "Payment added successfully!";
        } catch (Exception \$e) {
PHP;

$good = <<<PHP
            \$conn->commit();
            \$message = "Payment added successfully!";
            
            // Auto Email Notification
            include_once 'includes/mailer.php';
            \$custQ = \$conn->query("SELECT name, email FROM customers WHERE id = \$id LIMIT 1");
            if (\$custQ && \$custQ->num_rows > 0) {
                \$cust = \$custQ->fetch_assoc();
                if (!empty(\$cust['email'])) {
                    \$subject = "Payment Received - Thank You";
                    \$body = "<div style='font-family:sans-serif; padding:20px; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0;'>
                                <h2 style='color:#0f172a;'>Payment Receipt</h2>
                                <p style='color:#334155;'>Dear <strong>" . htmlspecialchars(\$cust['name']) . "</strong>,</p>
                                <p style='color:#334155;'>We have successfully received your payment of <strong style='color:#10b981; font-size:1.2em;'>$" . number_format(\$amount, 2) . "</strong>.</p>
                                <p style='color:#334155;'>Your updated outstanding balance is <strong>$" . number_format(\$newBal, 2) . "</strong>.</p>
                                <p style='color:#64748b; font-size:0.9em; margin-top:20px;'>Thank you for your business!</p>
                             </div>";
                    if (send_email_smtp(\$cust['email'], \$subject, \$body, \$conn)) {
                        \$message .= " (Email sent to customer)";
                    }
                }
            }
            
        } catch (Exception \$e) {
PHP;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/customer_ledger.php", $f);
echo "customer_ledger.php updated with email notification!";
?>
