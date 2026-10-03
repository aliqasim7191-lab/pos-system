<?php
// Fix index.php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");
$f = str_replace('<i class="fa fa-lock" style="font-size: 5rem; margin-bottom: 1.5rem; color: #f59e0b; text-shadow: 0 0 30px rgba(245, 158, 11, 0.5);"></i>', '<div style="font-size: 5rem; margin-bottom:1rem;">🔒</div>', $f);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);

// Fix shift.php
$f = file_get_contents("C:/xampp/htdocs/point of sale/shift.php");
$f = str_replace('<i class="fa fa-lock" style="font-size: 4rem; margin-bottom: 1rem; color: #ef4444; text-shadow: 0 0 20px rgba(239, 68, 68, 0.4);"></i>', '<div style="font-size: 4rem; margin-bottom: 1rem;">🔒</div>', $f);
$f = str_replace('<i class="fa fa-unlock" style="font-size: 4rem; margin-bottom: 1rem; color: #10b981; text-shadow: 0 0 20px rgba(16, 185, 129, 0.4);"></i>', '<div style="font-size: 4rem; margin-bottom: 1rem;">🔓</div>', $f);
$f = str_replace('<i class="fa fa-hourglass-half" style="font-size: 4rem; margin-bottom: 1rem; color: #eab308; text-shadow: 0 0 20px rgba(234, 179, 8, 0.4);"></i>', '<div style="font-size: 4rem; margin-bottom: 1rem;">⏳</div>', $f);
file_put_contents("C:/xampp/htdocs/point of sale/shift.php", $f);
echo "Emojis restored!";
?>
