<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/dashboard.php");

// Fix Branch Filters so they ALWAYS filter by tenant_id first
$f = str_replace(
    '$bF_WHERE = $isAdmin ? "" : " WHERE branch_id = $current_branch_id";',
    '$bF_WHERE = $isAdmin ? " WHERE tenant_id = " . $_SESSION[\'tenant_id\'] : " WHERE tenant_id = " . $_SESSION[\'tenant_id\'] . " AND branch_id = $current_branch_id";',
    $f
);
$f = str_replace(
    '$bF_AND = $isAdmin ? "" : " AND branch_id = $current_branch_id";',
    '$bF_AND = $isAdmin ? " AND tenant_id = " . $_SESSION[\'tenant_id\'] : " AND tenant_id = " . $_SESSION[\'tenant_id\'] . " AND branch_id = $current_branch_id";',
    $f
);
$f = str_replace(
    '$bF_WHERE_S = $isAdmin ? "" : " WHERE s.branch_id = $current_branch_id";',
    '$bF_WHERE_S = $isAdmin ? " WHERE s.tenant_id = " . $_SESSION[\'tenant_id\'] : " WHERE s.tenant_id = " . $_SESSION[\'tenant_id\'] . " AND s.branch_id = $current_branch_id";',
    $f
);
$f = str_replace(
    '$bF_AND_S = $isAdmin ? "" : " AND s.branch_id = $current_branch_id";',
    '$bF_AND_S = $isAdmin ? " AND s.tenant_id = " . $_SESSION[\'tenant_id\'] : " AND s.tenant_id = " . $_SESSION[\'tenant_id\'] . " AND s.branch_id = $current_branch_id";',
    $f
);
$f = str_replace(
    '$bF_WHERE_P = $isAdmin ? "" : " WHERE p.branch_id = $current_branch_id";',
    '$bF_WHERE_P = $isAdmin ? " WHERE p.tenant_id = " . $_SESSION[\'tenant_id\'] : " WHERE p.tenant_id = " . $_SESSION[\'tenant_id\'] . " AND p.branch_id = $current_branch_id";',
    $f
);
$f = str_replace(
    '$bF_AND_P = $isAdmin ? "" : " AND p.branch_id = $current_branch_id";',
    '$bF_AND_P = $isAdmin ? " AND p.tenant_id = " . $_SESSION[\'tenant_id\'] : " AND p.tenant_id = " . $_SESSION[\'tenant_id\'] . " AND p.branch_id = $current_branch_id";',
    $f
);

// We must also fix the $cashSalesQ which didn't use $bF_AND
$f = str_replace(
    "SELECT SUM(amount_received - change_returned) as c FROM sales WHERE payment_method = 'cash' AND is_cleared = 0",
    "SELECT SUM(amount_received - change_returned) as c FROM sales WHERE payment_method = 'cash' AND is_cleared = 0 \$bF_AND",
    $f
);

file_put_contents("C:/xampp/htdocs/point of sale/dashboard.php", $f);
echo "Dashboard fully isolated!";
?>
