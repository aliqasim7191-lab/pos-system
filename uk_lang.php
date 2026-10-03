<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");

$f = str_replace(
    "includedLanguages: 'en,ur,ar,hi'",
    "includedLanguages: 'en,en-GB,ur,ar,hi'",
    $f
);

file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);
echo "Done";
?>
