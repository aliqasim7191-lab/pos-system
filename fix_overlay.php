<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");

$old = <<<HTML
<div style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; display:flex; align-items:center; justify-content:center; color:white; text-align:center;">
    <div>
        <div style="font-size: 5rem; margin-bottom:1rem;">ðŸ”’</div>
        <h1 style="margin-bottom: 1rem;">Register is Closed</h1>
        <p style="margin-bottom: 2rem; font-size: 1.2rem;">You must open a shift with starting cash before processing sales.</p>
        <a href="shift.php" class="btn btn-primary" style="font-size: 1.2rem; padding: 1rem 2rem;">Go to Cash Register</a>
    </div>
</div>
HTML;

$new = <<<HTML
<div style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.9); backdrop-filter: blur(8px); z-index:9999; display:flex; align-items:center; justify-content:center; color:white; text-align:center;">
    <div style="background: rgba(255, 255, 255, 0.05); padding: 4rem; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); border: 1px solid rgba(255,255,255,0.1); max-width: 600px;">
        <i class="fa fa-lock" style="font-size: 5rem; margin-bottom: 1.5rem; color: #f59e0b; text-shadow: 0 0 30px rgba(245, 158, 11, 0.5);"></i>
        <h1 style="margin-bottom: 1rem; font-size: 2.5rem; font-weight: 800; letter-spacing: -1px; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">Register is Closed</h1>
        <p style="margin-bottom: 2.5rem; font-size: 1.2rem; color: #cbd5e1;">You must open a shift with starting cash before processing sales.</p>
        <a href="shift.php" style="display: inline-block; font-size: 1.3rem; font-weight: bold; padding: 1.2rem 3rem; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; text-decoration: none; border-radius: 50px; box-shadow: 0 10px 25px rgba(245, 158, 11, 0.4); text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);" onmouseover="this.style.transform='translateY(-3px) scale(1.05)'; this.style.boxShadow='0 15px 35px rgba(245, 158, 11, 0.6)';" onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 10px 25px rgba(245, 158, 11, 0.4)';"><i class="fa fa-door-open" style="margin-right: 8px;"></i> Open Register Now</a>
    </div>
</div>
HTML;

// Note: I will use a regex to replace it since the corrupted emoji string might not match exactly.
$f = preg_replace('/<div style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba\(0,0,0,0\.8\).*?<\/div>\s*<\/div>/s', $new, $f);

file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "index.php overlay updated!";
?>
