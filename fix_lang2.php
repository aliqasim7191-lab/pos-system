<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");
// Let's remove the pageLanguage parameter entirely. Sometimes that helps.
$f = str_replace("pageLanguage: 'auto', ", "", $f);
$f = str_replace("pageLanguage: 'en', ", "", $f);

// Let's also ensure html lang is not set
$f = str_replace('<html lang="en">', '<html>', $f);

file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);
echo "Fixed language again";
?>
