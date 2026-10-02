<?php
$files = ['customers.php', 'suppliers.php'];
foreach ($files as $file) {
    $f = file_get_contents($file);
    $f = str_replace(
        '$bF_WHERE = $isAdmin ? "" : " WHERE branch_id = $current_branch_id";',
        '$bF_WHERE = $isAdmin ? " WHERE tenant_id = {$_SESSION[\'tenant_id\']}" : " WHERE tenant_id = {$_SESSION[\'tenant_id\']} AND branch_id = $current_branch_id";',
        $f
    );
    $f = str_replace(
        '$bF_AND = $isAdmin ? "" : " AND branch_id = $current_branch_id";',
        '$bF_AND = $isAdmin ? " AND tenant_id = {$_SESSION[\'tenant_id\']}" : " AND tenant_id = {$_SESSION[\'tenant_id\']} AND branch_id = $current_branch_id";',
        $f
    );
    file_put_contents($file, $f);
}
echo "Fixed customers and suppliers!";
?>
