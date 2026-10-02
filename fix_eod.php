<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/end_of_day.php");

// 1. Fix the Branch Filters to properly isolate by tenant_id
$f = str_replace(
    '$bF_AND = $isAdmin ? "" : " AND branch_id = $current_branch_id";',
    '$bF_AND = $isAdmin ? " AND tenant_id = {$_SESSION[\'tenant_id\']}" : " AND tenant_id = {$_SESSION[\'tenant_id\']} AND branch_id = $current_branch_id";',
    $f
);
$f = str_replace(
    '$bF_AND_CL = $isAdmin ? "" : " AND cl.branch_id = $current_branch_id";',
    '$bF_AND_CL = $isAdmin ? " AND cl.tenant_id = {$_SESSION[\'tenant_id\']}" : " AND cl.tenant_id = {$_SESSION[\'tenant_id\']} AND cl.branch_id = $current_branch_id";',
    $f
);
$f = str_replace(
    '$bF_AND_P = $isAdmin ? "" : " AND p.branch_id = $current_branch_id";',
    '$bF_AND_P = $isAdmin ? " AND p.tenant_id = {$_SESSION[\'tenant_id\']}" : " AND p.tenant_id = {$_SESSION[\'tenant_id\']} AND p.branch_id = $current_branch_id";',
    $f
);

// 2. Fix the missing tenant_id in INSERT INTO z_reports_history
$f = str_replace(
    'closing_cash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
    'closing_cash, tenant_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, {$_SESSION[\'tenant_id\']})',
    $f
);

// 3. Fix the UPDATE shifts force close
$f = str_replace(
    "UPDATE shifts SET closed_at = NOW(), closing_cash = opening_cash, status = 'closed' WHERE status = 'open'",
    "UPDATE shifts SET closed_at = NOW(), closing_cash = opening_cash, status = 'closed' WHERE status = 'open' AND tenant_id = {$_SESSION['tenant_id']}",
    $f
);


file_put_contents("C:/xampp/htdocs/point of sale/end_of_day.php", $f);
echo "EOD Fixed!";
?>
