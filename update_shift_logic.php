<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/shift.php");

$bad_form = <<<PHP
            <form method="POST" onsubmit="return confirm('Are you sure you want to close this shift?');">
                <input type="hidden" name="action" value="close">
                <div style="text-align: left; margin-bottom: 1rem;">
                    <label style="display:block; font-weight: 500; margin-bottom: 0.5rem;">Enter Actual Cash in Drawer ($)</label>
                    <input type="number" step="0.01" name="actual_cash" required style="width: 100%; padding: 1.2rem; border: 2px solid #cbd5e1; border-radius: 12px; font-size: 1.5rem; text-align: center; color: #1e293b; outline: none; transition: 0.3s;" onfocus="this.style.borderColor='#ef4444'; this.style.boxShadow='0 0 0 4px rgba(239,68,68,0.1)'" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'">
                </div>
                <button type="submit" style="width: 100%; padding: 1.2rem; font-size: 1.2rem; font-weight: bold; background: linear-gradient(135deg, #ef4444, #b91c1c); color: white; border: none; border-radius: 12px; cursor: pointer; box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 15px 25px rgba(239, 68, 68, 0.5)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 20px rgba(239, 68, 68, 0.3)';"><i class="fa fa-door-closed" style="margin-right:8px;"></i> Close Register</button>
            </form>
PHP;

$good_form = <<<PHP
            <p style="color: #059669; font-size: 1.2rem; font-weight: bold; margin-bottom: 1.5rem; background:#ecfdf5; padding:15px; border-radius:8px;">?3 Shift is currently OPEN and recording sales.</p>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Your shift will automatically be closed when the Admin runs the End of Day (Z-Report).</p>
            <a href="reports.php" style="width: 100%; display:block; padding: 1.2rem; font-size: 1.2rem; font-weight: bold; background: linear-gradient(135deg, #0ea5e9, #0284c7); color: white; border: none; border-radius: 12px; cursor: pointer; text-decoration:none; box-shadow: 0 10px 20px rgba(14, 165, 233, 0.3); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 15px 25px rgba(14, 165, 233, 0.5)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 20px rgba(14, 165, 233, 0.3)';"><i class="fa fa-chart-line" style="margin-right:8px;"></i> View End of Day Reports</a>
PHP;

$f = str_replace($bad_form, $good_form, $f);
file_put_contents("C:/xampp/htdocs/point of sale/shift.php", $f);
echo "Shift logic updated to auto-close on End of Day!";
?>
