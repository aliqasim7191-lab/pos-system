<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/shift.php");

// 1. Fix OPEN state emoji & button
$f = str_replace('<div style="font-size: 3rem; margin-bottom: 0.5rem;">dY""</div>', '<i class="fa fa-unlock" style="font-size: 4rem; margin-bottom: 1rem; color: #10b981; text-shadow: 0 0 20px rgba(16, 185, 129, 0.4);"></i>', $f);
$f = str_replace(
    '<button type="submit" class="btn" style="background: var(--danger); color: white; width: 100%; padding: 1rem; font-size: 1.1rem;">Close Register</button>',
    '<button type="submit" style="width: 100%; padding: 1.2rem; font-size: 1.2rem; font-weight: bold; background: linear-gradient(135deg, #ef4444, #b91c1c); color: white; border: none; border-radius: 12px; cursor: pointer; box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3); transition: all 0.3s;" onmouseover="this.style.transform=\'translateY(-3px)\'; this.style.boxShadow=\'0 15px 25px rgba(239, 68, 68, 0.5)\';" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 10px 20px rgba(239, 68, 68, 0.3)\';"><i class="fa fa-door-closed" style="margin-right:8px;"></i> Close Register</button>',
    $f
);

// 2. Fix WAITING state emoji
$f = str_replace('<div style="font-size: 4rem; margin-bottom: 1rem;">?3</div>', '<i class="fa fa-hourglass-half" style="font-size: 4rem; margin-bottom: 1rem; color: #eab308; text-shadow: 0 0 20px rgba(234, 179, 8, 0.4);"></i>', $f);
$f = str_replace(
    '<a href="end_of_day.php" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem; text-decoration: none; display: block;">Go to End of Day</a>',
    '<a href="end_of_day.php" style="width: 100%; padding: 1.2rem; font-size: 1.2rem; font-weight: bold; background: linear-gradient(135deg, #eab308, #ca8a04); color: white; border: none; border-radius: 12px; cursor: pointer; text-decoration: none; display: block; box-shadow: 0 10px 20px rgba(234, 179, 8, 0.3); transition: all 0.3s;" onmouseover="this.style.transform=\'translateY(-3px)\'; this.style.boxShadow=\'0 15px 25px rgba(234, 179, 8, 0.5)\';" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 10px 20px rgba(234, 179, 8, 0.3)\';"><i class="fa fa-moon" style="margin-right:8px;"></i> Go to End of Day</a>',
    $f
);

// 3. Fix CLOSED state emoji & button
$f = str_replace('<div style="font-size: 4rem; margin-bottom: 1rem;">dY"\'</div>', '<i class="fa fa-lock" style="font-size: 4rem; margin-bottom: 1rem; color: #ef4444; text-shadow: 0 0 20px rgba(239, 68, 68, 0.4);"></i>', $f);
$f = str_replace(
    '<button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;">Open Register</button>',
    '<button type="submit" style="width: 100%; padding: 1.2rem; font-size: 1.2rem; font-weight: bold; background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; border: none; border-radius: 12px; cursor: pointer; box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3); transition: all 0.3s;" onmouseover="this.style.transform=\'translateY(-3px)\'; this.style.boxShadow=\'0 15px 25px rgba(59, 130, 246, 0.5)\';" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 10px 20px rgba(59, 130, 246, 0.3)\';"><i class="fa fa-door-open" style="margin-right:8px;"></i> Open Register</button>',
    $f
);

// 4. Also fix the input boxes to look better
$f = str_replace(
    '<input type="number" step="0.01" name="starting_cash" value="0.00" required style="width: 100%; padding: 1rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1.2rem;">',
    '<input type="number" step="0.01" name="starting_cash" value="0.00" required style="width: 100%; padding: 1.2rem; border: 2px solid #cbd5e1; border-radius: 12px; font-size: 1.5rem; text-align: center; color: #1e293b; outline: none; transition: 0.3s;" onfocus="this.style.borderColor=\'#3b82f6\'; this.style.boxShadow=\'0 0 0 4px rgba(59,130,246,0.1)\'" onblur="this.style.borderColor=\'#cbd5e1\'; this.style.boxShadow=\'none\'">',
    $f
);

$f = str_replace(
    '<input type="number" step="0.01" name="actual_cash" required style="width: 100%; padding: 1rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1.2rem;">',
    '<input type="number" step="0.01" name="actual_cash" required style="width: 100%; padding: 1.2rem; border: 2px solid #cbd5e1; border-radius: 12px; font-size: 1.5rem; text-align: center; color: #1e293b; outline: none; transition: 0.3s;" onfocus="this.style.borderColor=\'#ef4444\'; this.style.boxShadow=\'0 0 0 4px rgba(239,68,68,0.1)\'" onblur="this.style.borderColor=\'#cbd5e1\'; this.style.boxShadow=\'none\'">',
    $f
);


file_put_contents("C:/xampp/htdocs/point of sale/shift.php", $f);
echo "shift.php UI fixed!";
?>
