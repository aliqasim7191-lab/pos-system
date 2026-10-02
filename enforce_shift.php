<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");

$shift_logic = <<<PHP
// ---------- SHIFT ENFORCEMENT ----------
\$shiftCheck = \$conn->query("SELECT id FROM shifts WHERE status = 'open' AND is_cleared = 0 AND branch_id = \$current_branch_id AND tenant_id = {\$_SESSION['tenant_id']} LIMIT 1");
if (\$shiftCheck && \$shiftCheck->num_rows === 0) {
    echo "<div style='position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; display:flex; align-items:center; justify-content:center;'>
            <div style='background:white; padding:3rem; border-radius:12px; text-align:center; max-width:500px;'>
                <div style='font-size:4rem; margin-bottom:1rem;'>🔒</div>
                <h2 style='color:#0f172a; margin-bottom:1rem;'>Register Closed</h2>
                <p style='color:#475569; margin-bottom:2rem; font-size:1.1rem;'>You must open a shift and enter the Opening Cash before you can process sales.</p>
                <a href='shift.php' style='display:inline-block; background:#0ea5e9; color:white; padding:1rem 2rem; border-radius:8px; text-decoration:none; font-weight:bold; font-size:1.1rem;'>Open Register Now</a>
            </div>
          </div>";
}
// ---------------------------------------
PHP;

$f = str_replace('<div class="layout">', $shift_logic . "\n" . '<div class="layout">', $f);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Shift enforcement added to index.php!";
?>
