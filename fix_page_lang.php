<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");
$f = str_replace(
    "new google.translate.TranslateElement({includedLanguages:",
    "new google.translate.TranslateElement({pageLanguage: 'auto', includedLanguages:",
    $f
);
file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);
?>
