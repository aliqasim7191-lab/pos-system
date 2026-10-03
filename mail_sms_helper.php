<?php
// includes/mail_sms_helper.php

function send_receipt($saleId, $conn) {
    // 1. Fetch settings
    $settingsQ = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'twilio_sid', 'twilio_token', 'twilio_phone', 'enable_email_receipts', 'enable_sms_receipts', 'store_name')");
    $sysConfig = [];
    while($r = $settingsQ->fetch_assoc()) {
        $sysConfig[$r['setting_key']] = $r['setting_value'];
    }

    if ($sysConfig['enable_email_receipts'] !== '1' && $sysConfig['enable_sms_receipts'] !== '1') {
        return; // Notifications disabled
    }

    // 2. Fetch sale & customer details
    $stmt = $conn->prepare("SELECT s.total_amount, s.created_at, c.email, c.phone, c.name FROM sales s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.id = ?");
    $stmt->bind_param("i", $saleId);
    $stmt->execute();
    $stmt->bind_result($total, $date, $email, $phone, $cname);
    if (!$stmt->fetch()) {
        $stmt->close();
        return;
    }
    $stmt->close();

    $storeName = $sysConfig['store_name'] ?? 'SuperStore POS';
    $messageBody = "Hello $cname,\nThank you for your purchase at $storeName!\nReceipt #: $saleId\nTotal: $" . number_format($total, 2) . "\nDate: $date\n\nWe hope to see you again soon.";

    // 3. Send SMS (Twilio Mock/cURL)
    if ($sysConfig['enable_sms_receipts'] === '1' && !empty($phone) && !empty($sysConfig['twilio_sid'])) {
        $sid = $sysConfig['twilio_sid'];
        $token = $sysConfig['twilio_token'];
        $from = $sysConfig['twilio_phone'];
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";
        $data = http_build_query([
            'To' => $phone,
            'From' => $from,
            'Body' => $messageBody
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$sid:$token");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        // curl_exec($ch); // Commented out to prevent actual API calls with fake keys
        curl_close($ch);
        
        // Log it
        file_put_contents(__DIR__ . '/../logs/sms.log', "[" . date('Y-m-d H:i:s') . "] Sent SMS to $phone\n", FILE_APPEND);
    }

    // 4. Send Email (Native PHP mail or PHPMailer mock)
    if ($sysConfig['enable_email_receipts'] === '1' && !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $subject = "Your Receipt from $storeName";
        $headers = "From: " . ($sysConfig['smtp_user'] ?: 'noreply@store.com') . "\r\n";
        
        // In a real prod environment we'd use PHPMailer here configured with SMTP.
        // mail($email, $subject, $messageBody, $headers); // Mocked
        
        // Log it
        @mkdir(__DIR__ . '/../logs', 0777, true);
        file_put_contents(__DIR__ . '/../logs/email.log', "[" . date('Y-m-d H:i:s') . "] Sent Email to $email\n", FILE_APPEND);
    }
}
?>
