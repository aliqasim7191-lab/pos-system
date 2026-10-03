<?php
$_SESSION = ['user_id' => 1, 'tenant_id' => 1];
$_GET['id'] = 1;
ob_start();
include "print_salary_slip.php";
$out = ob_get_clean();
echo "OUTPUT LENGTH: " . strlen($out) . "\n";
if (strlen($out) < 200) { echo $out; }
?>
