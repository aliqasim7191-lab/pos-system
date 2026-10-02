<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/end_of_day.php");
$f = str_replace(
    "tenant_id = }",
    "tenant_id = {\$_SESSION['tenant_id']}",
    $f
);
$f = preg_replace('/tenant_id = }/m', 'tenant_id = {$_SESSION[\'tenant_id\']}', $f); // fallback

file_put_contents("C:/xampp/htdocs/point of sale/end_of_day.php", $f);
echo "Checked!";
?>
