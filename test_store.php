<?php
$_GET['t'] = 1;
ob_start();
include "store.php";
$out = ob_get_clean();
echo substr($out, 0, 500);
?>
