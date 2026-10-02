<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");

// 1. Force 3 products per row in products-grid
$bad1 = '<div class="products-grid" id="productsGrid" style="grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));">';
$good1 = '<div class="products-grid" id="productsGrid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; overflow-y: auto; max-height: calc(100vh - 250px); padding-right: 0.5rem;">';
$f = str_replace($bad1, $good1, $f);

// 2. Add min-width: 0 to products-section to prevent CSS grid blow-out
$bad2 = '<div class="products-section" style="flex: 2;">';
$good2 = '<div class="products-section" style="min-width: 0;">';
$f = str_replace($bad2, $good2, $f);

// 3. Remove flex: 1 from cart-panel and add min-width: 0
$bad3 = '<div class="cart-panel" style="flex: 1;">';
$good3 = '<div class="cart-panel" style="min-width: 0;">';
$f = str_replace($bad3, $good3, $f);

file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Layout fixes applied!";
?>
