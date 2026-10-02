<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/reports.php");

$f = str_replace(
    'FROM z_reports_history WHERE MONTH(report_date) = ? AND YEAR(report_date) = ?',
    'FROM z_reports_history WHERE MONTH(report_date) = ? AND YEAR(report_date) = ? AND tenant_id = {$_SESSION[\'tenant_id\']}',
    $f
);

$f = str_replace(
    'AND is_monthly_cleared = 0
      GROUP BY',
    'AND is_monthly_cleared = 0 AND tenant_id = {$_SESSION[\'tenant_id\']}
      GROUP BY',
    $f
);

$f = str_replace(
    'WHERE s.is_cleared = 0
      GROUP BY',
    'WHERE s.is_cleared = 0 AND s.tenant_id = {$_SESSION[\'tenant_id\']}
      GROUP BY',
    $f
);

$f = str_replace(
    'WHERE MONTH(s.created_at) = ? AND YEAR(s.created_at) = ? AND s.is_monthly_cleared = 0
      GROUP BY',
    'WHERE MONTH(s.created_at) = ? AND YEAR(s.created_at) = ? AND s.is_monthly_cleared = 0 AND s.tenant_id = {$_SESSION[\'tenant_id\']}
      GROUP BY',
    $f
);

file_put_contents("C:/xampp/htdocs/point of sale/reports.php", $f);
echo "Reports fixed further";
?>
