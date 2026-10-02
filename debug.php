<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/assets/js/app.js");
$f = str_replace(
    "if (!p) return;", 
    "if (!p) { alert('Product not found in data! ID: ' + id); return; }", 
    $f
);
file_put_contents("C:/xampp/htdocs/point of sale/assets/js/app.js", $f);
