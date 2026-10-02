<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");

// Fix the paragraph text to make it extremely visible
$f = str_replace(
    '<p style="margin-bottom: 2.5rem; font-size: 1.2rem; color: #cbd5e1;">You must open a shift with starting cash before processing sales.</p>', 
    '<p style="margin-bottom: 2.5rem; font-size: 1.3rem; color: #ffffff; font-weight: 600; text-shadow: 0 2px 8px rgba(0,0,0,0.9), 0 0 4px rgba(0,0,0,0.8);">You must open a shift with starting cash before processing sales.</p>', 
    $f
);

// Fix the inner box background slightly so it helps readability but stays transparent
$f = str_replace(
    'background: rgba(255, 255, 255, 0.05); padding: 4rem;',
    'background: rgba(0, 0, 0, 0.4); padding: 4rem; backdrop-filter: blur(2px);',
    $f
);

file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Text visibility fixed!";
?>
