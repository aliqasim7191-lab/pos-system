<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/print_salary_slip.php");
$f = str_replace('t.name as tenant_name', 't.company_name as tenant_name', $f);
file_put_contents("C:/xampp/htdocs/point of sale/print_salary_slip.php", $f);
echo "print slip fixed!";
?>
