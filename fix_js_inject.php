<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");
$f = str_replace("<head>", "<head>\n    <script>if(!sessionStorage.getItem('strict_session')) { window.location.href='logout.php'; }</script>", $f);
file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);
echo "Injected into header\n";

$f2 = file_get_contents("C:/xampp/htdocs/point of sale/super_admin.php");
$f2 = str_replace("<head>", "<head>\n    <script>if(!sessionStorage.getItem('strict_session')) { window.location.href='logout.php'; }</script>", $f2);
file_put_contents("C:/xampp/htdocs/point of sale/super_admin.php", $f2);
echo "Injected into super_admin\n";
?>
