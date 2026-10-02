<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");

$bad = <<<PHP
\$taxQ = \$conn->query("SELECT setting_value FROM settings WHERE setting_key='tax_rate'");
PHP;

$good = <<<PHP
// --- STRICT SHIFT BLOCKER ---
\$shiftCheck = \$conn->query("SELECT id FROM shifts WHERE is_cleared = 0 AND status = 'open' AND branch_id = \$current_branch_id AND tenant_id = {\$_SESSION['tenant_id']} LIMIT 1");
if (\$shiftCheck->num_rows == 0) {
    // If there is no open shift, force redirect to shift.php
    echo "<script>window.location.href='shift.php';</script>";
    exit;
}
// ----------------------------

\$taxQ = \$conn->query("SELECT setting_value FROM settings WHERE setting_key='tax_rate'");
PHP;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Blocker added!\n";
?>
