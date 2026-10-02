<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");
$f = str_replace(
    'padding: 1.2rem 3rem;', 
    'padding: 1.2rem 2rem; width: 85%; max-width: 400px; display: inline-block;', 
    $f
);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Button width increased!";
?>
