<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/customers.php");

// Fix table overflow
$f = str_replace(
    '<div class="table-wrapper" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; overflow: hidden; background: #ffffff;">',
    '<div class="table-wrapper" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; overflow-x: auto; overflow-y: hidden; background: #ffffff;">',
    $f
);

// Add min-width to Actions column to prevent squishing
$f = str_replace(
    '<th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Actions</th>',
    '<th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; min-width: 250px;">Actions</th>',
    $f
);

file_put_contents("C:/xampp/htdocs/point of sale/customers.php", $f);
echo "Customers UI fixed!\n";
?>
