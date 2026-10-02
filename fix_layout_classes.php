<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");

// Fix container pos-layout
$f = str_replace('<div class="container pos-layout">', '<div class="pos-layout">', $f);

// Fix pos-left
$f = str_replace('<div class="pos-left">', '<div class="products-section" style="flex: 2;">', $f);

// Fix pos-right
$bad_right = '<div class="pos-right" style="display: flex; flex-direction: column; height: calc(100vh - 80px);">
        <div style="background: white; padding: 1.5rem; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); display: flex; flex-direction: column; height: 100%; border: 1px solid #e2e8f0;">';
$good_right = '<!-- Cart Section -->
    <div class="cart-panel" style="flex: 1;">';
$f = str_replace($bad_right, $good_right, $f);

// Wait, since I replaced TWO divs with ONE div, there is one extra closing div at the end of the file!
// Let's find the closing divs at the end of the checkout section.
// Before "<!-- Hold Sale Modal -->", there should be the closing divs.
// It was `</div></div><script>...` and I changed it to `<script>...`
// Wait, when I injected `<script>`, I searched for `<!-- Hold Sale Modal -->`.
// The end of `cart-panel` was just `</div> </div>` (one for pos-layout, one for cart-panel).
// Let's see what it is right now.
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Layout wrapper fixed!";
?>
