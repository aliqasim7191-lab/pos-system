<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");
$f = str_replace(
    "SELECT DISTINCT category FROM products WHERE status='active' AND p.tenant_id = ",
    "SELECT DISTINCT category FROM products WHERE status='active' AND tenant_id = ",
    $f
);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Fixed second query!";
?>
