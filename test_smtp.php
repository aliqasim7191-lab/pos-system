<?php
include 'includes/db.php';

$res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'enable_email_receipts')");
$config = [];
while($r = $res->fetch_assoc()) $config[$r['setting_key']] = $r['setting_value'];

echo "Email Enabled: " . ($config['enable_email_receipts'] ?? '0') . "\n";
echo "Host: " . ($config['smtp_host'] ?? '') . "\n";
echo "Port: " . ($config['smtp_port'] ?? '') . "\n";
echo "User: " . ($config['smtp_user'] ?? '') . "\n";

$host = $config['smtp_host'] ?? '';
$port = $config['smtp_port'] ?? '587';
$user = $config['smtp_user'] ?? '';
$pass = $config['smtp_pass'] ?? '';

if (!$host || !$user || !$pass) {
    die("Missing SMTP credentials.\n");
}

$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$socket = stream_socket_client("tcp://$host:$port", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
if (!$socket) {
    die("Socket Error: $errstr ($errno)\n");
}

function get_resp($socket) {
    $res = '';
    stream_set_timeout($socket, 2);
    while ($line = fgets($socket, 515)) {
        $res .= $line;
        if (substr($line, 3, 1) == ' ') break;
    }
    return $res;
}

echo "Connect: " . get_resp($socket);

fwrite($socket, "EHLO localhost\r\n");
echo "EHLO: " . get_resp($socket);

fwrite($socket, "STARTTLS\r\n");
echo "STARTTLS: " . get_resp($socket);

stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

fwrite($socket, "EHLO localhost\r\n");
echo "EHLO 2: " . get_resp($socket);

fwrite($socket, "AUTH LOGIN\r\n");
echo "AUTH LOGIN: " . get_resp($socket);

fwrite($socket, base64_encode($user) . "\r\n");
echo "USER: " . get_resp($socket);

fwrite($socket, base64_encode($pass) . "\r\n");
echo "PASS: " . get_resp($socket);

fwrite($socket, "QUIT\r\n");
fclose($socket);
echo "Done.\n";
?>
