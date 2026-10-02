<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/reports.php");
$f = str_replace(
    '$_SESSION[\'tenant_id\']"',
    '{$_SESSION[\'tenant_id\']}"',
    $f
);
$f = str_replace(
    '= $_SESSION[\'tenant_id\']',
    '= {$_SESSION[\'tenant_id\']}',
    $f
);
file_put_contents("C:/xampp/htdocs/point of sale/reports.php", $f);
echo "Syntax fixed in reports.php!";
?>
