<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");
$f = str_replace('<div class="product-price" style="font-weight: 800; font-size: 1.1rem; color: #0f172a;">$', '<div class="product-price" style="font-weight: 800; font-size: 1.1rem; color: #0f172a;"><?= htmlspecialchars($pri_curr) ?>', $f);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Currency symbol updated!";
?>
