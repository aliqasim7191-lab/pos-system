<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/staff.php");
$f = str_replace('<option value="admin">Super Admin</option>', '<option value="admin">Admin</option>', $f);
file_put_contents("C:/xampp/htdocs/point of sale/staff.php", $f);
echo "Role label fixed!";
?>
