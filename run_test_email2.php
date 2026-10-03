<?php
include 'includes/db.php';
function get_resp($s) {
    stream_set_timeout($s, 2);
    $res='';
    while($l=fgets($s,515)){$res.=$l;if(substr($l,3,1)==' ')break;}
    return trim($res);
}
$conn->query("SELECT * FROM settings LIMIT 1");
$res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass')");
$c = []; while($r = $res->fetch_assoc()) $c[$r['setting_key']] = $r['setting_value'];
$s = stream_socket_client("tcp://".$c['smtp_host'].":".$c['smtp_port'], $e, $es, 15, STREAM_CLIENT_CONNECT, stream_context_create(['ssl'=>['verify_peer'=>false,'verify_peer_name'=>false]]));
get_resp($s);
fwrite($s, "EHLO localhost\r\n"); get_resp($s);
fwrite($s, "STARTTLS\r\n"); get_resp($s);
stream_socket_enable_crypto($s, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
fwrite($s, "EHLO localhost\r\n"); get_resp($s);
fwrite($s, "AUTH LOGIN\r\n"); get_resp($s);
fwrite($s, base64_encode($c['smtp_user'])."\r\n"); get_resp($s);
fwrite($s, base64_encode($c['smtp_pass'])."\r\n"); get_resp($s);
fwrite($s, "MAIL FROM: <".$c['smtp_user'].">\r\n"); echo "MAIL FROM: " . get_resp($s) . "\n";
fwrite($s, "RCPT TO: <aliqasim7191@gmail.com>\r\n"); echo "RCPT TO: " . get_resp($s) . "\n";
fwrite($s, "DATA\r\n"); echo "DATA: " . get_resp($s) . "\n";
$msg = "From: POS <".$c['smtp_user'].">\r\nTo: <aliqasim7191@gmail.com>\r\nSubject: Final SMTP Test\r\n\r\nThis is a final test body.\r\n.\r\n";
fwrite($s, $msg); echo "MSG RESP: " . get_resp($s) . "\n";
fwrite($s, "QUIT\r\n"); fclose($s);
?>
