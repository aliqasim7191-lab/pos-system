<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/super_admin.php");
$s = strpos($f, "Create Account (1 Mo Free)</button>");
echo substr($f, $s - 100, 1000);
?>
