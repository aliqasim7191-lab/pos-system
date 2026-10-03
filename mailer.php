<?php
function send_email_smtp($to, $subject, $body, $conn) {
    $res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'enable_email_receipts')");
    $config = [];
    while($r = $res->fetch_assoc()) $config[$r['setting_key']] = $r['setting_value'];
    
    if (!isset($config['enable_email_receipts']) || $config['enable_email_receipts'] !== '1') return false;
    
    $host = $config['smtp_host'] ?? '';
    $port = $config['smtp_port'] ?? '587';
    $user = $config['smtp_user'] ?? '';
    $pass = $config['smtp_pass'] ?? '';
    
    if (!$host || !$user || !$pass || !$to) return false;
    
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    
    $socket = stream_socket_client("tcp://$host:$port", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) return false;
    
    fread($socket, 512);
    fwrite($socket, "EHLO localhost\r\n");
    fread($socket, 512);
    
    fwrite($socket, "STARTTLS\r\n");
    fread($socket, 512);
    stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    
    fwrite($socket, "EHLO localhost\r\n");
    fread($socket, 512);
    
    fwrite($socket, "AUTH LOGIN\r\n");
    fread($socket, 512);
    
    fwrite($socket, base64_encode($user) . "\r\n");
    fread($socket, 512);
    
    fwrite($socket, base64_encode($pass) . "\r\n");
    fread($socket, 512);
    
    fwrite($socket, "MAIL FROM: <$user>\r\n");
    fread($socket, 512);
    
    fwrite($socket, "RCPT TO: <$to>\r\n");
    fread($socket, 512);
    
    fwrite($socket, "DATA\r\n");
    fread($socket, 512);
    
    $headers = "From: Point of Sale <$user>\r\n";
    $headers .= "To: <$to>\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $message = $headers . "\r\n" . $body . "\r\n.\r\n";
    
    fwrite($socket, $message);
    fread($socket, 512);
    
    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    
    return true;
}
?>
