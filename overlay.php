<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");

$bad = <<<PHP
// --- STRICT SHIFT BLOCKER ---
\$shiftCheck = \$conn->query("SELECT id FROM shifts WHERE is_cleared = 0 AND status = 'open' AND branch_id = \$current_branch_id AND tenant_id = {\$_SESSION['tenant_id']} LIMIT 1");
if (\$shiftCheck->num_rows == 0) {
    // If there is no open shift, force redirect to shift.php
    echo "<script>window.location.href='shift.php';</script>";
    exit;
}
// ----------------------------
PHP;

$good = <<<PHP
// --- STRICT SHIFT BLOCKER UI ---
\$shiftCheck = \$conn->query("SELECT id FROM shifts WHERE is_cleared = 0 AND status = 'open' AND branch_id = \$current_branch_id AND tenant_id = {\$_SESSION['tenant_id']} LIMIT 1");
\$isShiftClosed = (\$shiftCheck->num_rows == 0);
// -------------------------------
PHP;

$f = str_replace($bad, $good, $f);

$overlay = <<<HTML
<div class="pos-layout">
HTML;

$overlay_new = <<<HTML
<div class="pos-layout">

<?php if (\$isShiftClosed): ?>
<style>
@keyframes pulseGlow {
    0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
    70% { box-shadow: 0 0 0 20px rgba(59, 130, 246, 0); }
    100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-50px); }
    to { opacity: 1; transform: translateY(0); }
}
.shift-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}
.shift-modal {
    background: white;
    padding: 3rem 4rem;
    border-radius: 24px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    text-align: center;
    animation: slideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    border: 1px solid rgba(0,0,0,0.05);
}
.shift-btn {
    display: inline-block;
    margin-top: 2rem;
    padding: 1.2rem 3rem;
    font-size: 1.2rem;
    font-weight: bold;
    color: white;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    border-radius: 999px;
    text-decoration: none;
    transition: all 0.3s ease;
    animation: pulseGlow 2s infinite;
}
.shift-btn:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 25px -5px rgba(59, 130, 246, 0.4);
}
</style>
<div class="shift-overlay">
    <div class="shift-modal">
        <div style="font-size: 5rem; margin-bottom: 1rem; color: #f59e0b;">🔒</div>
        <h2 style="font-size: 2rem; color: #0f172a; margin-bottom: 0.5rem;">Register is Locked</h2>
        <p style="color: #64748b; font-size: 1.1rem;">You must open the register to start making sales.</p>
        <a href="shift.php" class="shift-btn"><i class="fa fa-cash-register" style="margin-right: 10px;"></i> Open Register Now</a>
    </div>
</div>
<?php endif; ?>
HTML;

$f = str_replace($overlay, $overlay_new, $f);

file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Overlay added!\n";
?>
