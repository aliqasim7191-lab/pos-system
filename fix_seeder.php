<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/super_admin.php");
$f = preg_replace('/\/\/ 6\. Seed Integration Settings \[\s\S]*?\}\s*\}/', '', $f);
file_put_contents("C:/xampp/htdocs/point of sale/super_admin.php", $f);
echo "Fixed seeder code!";
?>
