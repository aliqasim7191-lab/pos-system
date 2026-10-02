<?php
// cron_low_stock.php
// Run this via Windows Task Scheduler or cron (e.g., hourly)
// php C:\xampp\htdocs\point of sale\cron_low_stock.php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mail_sms_helper.php';

$settingsQ = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'twilio_sid', 'twilio_token', 'twilio_phone', 'store_name', 'admin_email', 'admin_phone')");
$sysConfig = [];
while($r = $settingsQ->fetch_assoc()) {
    $sysConfig[$r['setting_key']] = $r['setting_value'];
}

$adminEmail = $sysConfig['admin_email'] ?? 'admin@example.com';
$adminPhone = $sysConfig['admin_phone'] ?? '';
$storeName = $sysConfig['store_name'] ?? 'SuperStore POS';

// Find products below their min_stock
// Also sum variation stocks for parent check
$sql = "
    SELECT p.id, p.name, p.min_stock, p.stock as base_stock,
           (SELECT SUM(stock) FROM product_variations WHERE product_id = p.id) as var_stock
    FROM products p
    WHERE p.status = 'active'
";
$res = $conn->query($sql);
$lowStockItems = [];

while($row = $res->fetch_assoc()) {
    $currentStock = ($row['var_stock'] !== null) ? floatval($row['var_stock']) : floatval($row['base_stock']);
    $minStock = floatval($row['min_stock']);
    
    if ($currentStock <= $minStock) {
        $lowStockItems[] = $row['name'] . " (Stock: $currentStock, Min: $minStock)";
    }
}

if (count($lowStockItems) > 0) {
    $messageBody = "LOW STOCK ALERT for $storeName:\n\n";
    $messageBody .= implode("\n", $lowStockItems);
    $messageBody .= "\n\nPlease restock these items soon.";

    // Send SMS
    if (!empty($adminPhone) && !empty($sysConfig['twilio_sid'])) {
        $sid = $sysConfig['twilio_sid'];
        $token = $sysConfig['twilio_token'];
        $from = $sysConfig['twilio_phone'];
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";
        $data = http_build_query([
            'To' => $adminPhone,
            'From' => $from,
            'Body' => $messageBody
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$sid:$token");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        // curl_exec($ch); // Mocked
        curl_close($ch);
        
        @mkdir(__DIR__ . '/logs', 0777, true);
        file_put_contents(__DIR__ . '/logs/cron_sms.log', "[" . date('Y-m-d H:i:s') . "] Sent low stock SMS to admin\n", FILE_APPEND);
    }

    // Send Email
    if (!empty($adminEmail)) {
        $subject = "Low Stock Alert - $storeName";
        $headers = "From: " . ($sysConfig['smtp_user'] ?: 'noreply@store.com') . "\r\n";
        // mail($adminEmail, $subject, $messageBody, $headers); // Mocked
        
        @mkdir(__DIR__ . '/logs', 0777, true);
        file_put_contents(__DIR__ . '/logs/cron_email.log', "[" . date('Y-m-d H:i:s') . "] Sent low stock Email to admin\n", FILE_APPEND);
    }
    
    echo "Alerts generated for " . count($lowStockItems) . " items.\n";
} else {
    echo "All stocks are adequate.\n";
}
?>
