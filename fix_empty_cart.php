<?php
$idx = file_get_contents("C:/xampp/htdocs/point of sale/index.php");
// The cart uses dY>' which was corrupted from 🛒 or 🛍️
$idx = preg_replace('/<div style="font-size: 2\.5rem; margin-bottom: 0\.5rem;">.*?<\/div>/', '<div style="font-size: 2.5rem; margin-bottom: 0.5rem;">' . "\u{1F6D2}" . '</div>', $idx);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $idx);
echo "Fixed empty cart icon in index.php";
?>
