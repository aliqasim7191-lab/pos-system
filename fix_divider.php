<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");

// 1. Remove the old divider
$old_divider = <<<HTML
              <!-- Solid White Divider -->
              <div style="width: 2px; height: 36px; background: rgba(255, 255, 255, 0.5); margin: 0 0.5rem 0 1rem; border-radius: 2px;"></div>
HTML;
$f = str_replace($old_divider, "", $f);

// 2. Add the divider between Kebab Menu and Logo
$old_logo_start = '<div class="logo">';
$new_logo_start = <<<HTML
              <!-- Solid White Divider -->
              <div style="width: 2px; height: 36px; background: rgba(255, 255, 255, 0.5); margin: 0 0.5rem 0 0; border-radius: 2px;"></div>
              <div class="logo">
HTML;
$f = str_replace($old_logo_start, $new_logo_start, $f);

// 3. To fit 6 buttons without scrolling, let's also reduce the padding in the main-nav links and reduce the max-width of the store name
$f = str_replace(
    'max-width: 180px;', 
    'max-width: 120px;', 
    $f
);

file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);
echo "Divider moved and space optimized!";
?>
