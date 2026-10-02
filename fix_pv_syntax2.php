<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/product_variations.php");
$f = str_replace(
    '$_SESSION[\'tenant_id\'])")', 
    '{$_SESSION[\'tenant_id\']})")', 
    $f
);
file_put_contents("C:/xampp/htdocs/point of sale/product_variations.php", $f);
echo "Syntax error 2 fixed!";
?>
