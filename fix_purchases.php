<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/purchases.php");
$f = str_replace(
    'SELECT id, name FROM suppliers ORDER BY name',
    'SELECT id, name FROM suppliers WHERE tenant_id = {$_SESSION[\'tenant_id\']} ORDER BY name',
    $f
);
file_put_contents("C:/xampp/htdocs/point of sale/purchases.php", $f);
echo "Fixed purchases suppliers!";
?>
