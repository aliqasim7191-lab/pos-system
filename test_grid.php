<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/super_admin.php");
$s = strpos($f, "<div class=\"grid-3\" style=\"margin-top: 2rem; grid-template-columns: 1fr 2fr;\">");
echo substr($f, $s, 1000);
?>
