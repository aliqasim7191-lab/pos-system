<?php
$_SESSION['user_id'] = 1;
$_SESSION['tenant_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['shift_id'] = 1;

ob_start();
include "C:/xampp/htdocs/point of sale/index.php";
$html = ob_get_clean();
file_put_contents("C:/xampp/htdocs/point of sale/debug_index.html", $html);
echo "HTML saved!";
?>
