<?php
// Fix header gaps
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");

// Reduce header gap from 1rem to 0.5rem
$f = str_replace(
    '<div style="display: flex; align-items: center; gap: 1rem;">',
    '<div style="display: flex; align-items: center; gap: 0.5rem;">',
    $f
);

// Reduce margin-left on main-nav
$f = str_replace(
    '<nav class="main-nav" style="margin-left: 0.5rem;">',
    '<nav class="main-nav" style="margin-left: 0;">',
    $f
);

// Reduce margin on divider
$f = str_replace(
    '<div style="width: 2px; height: 36px; background: rgba(255, 255, 255, 0.5); margin: 0 0.5rem 0 0; border-radius: 2px;"></div>',
    '<div style="width: 2px; height: 36px; background: rgba(255, 255, 255, 0.3); margin: 0 0.3rem; border-radius: 2px;"></div>',
    $f
);

file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);

// Fix css gaps
$css = file_get_contents("C:/xampp/htdocs/point of sale/assets/css/style.css");

$css = str_replace(
    'padding: 0.4rem 0.6rem;',
    'padding: 0.4rem 0.5rem; font-size: 0.85rem; letter-spacing: -0.2px;',
    $css
);

$css = str_replace(
    'gap: 0.5rem;',
    'gap: 0.3rem;',
    $css
);

file_put_contents("C:/xampp/htdocs/point of sale/assets/css/style.css", $css);

echo "Spacing reduced!";
?>
