<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");

$resetBtn = '<a href="#" onclick="document.cookie=\'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/\'; document.cookie=\'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=.\'+location.hostname; window.location.reload(); return false;" class="btn" style="background:#f1f5f9; color:#0f172a; margin-top:0.5rem; text-align:center; font-weight:600; font-size:0.85rem; display:block;">Reset to English</a>';

// Let's place it right under the google_translate_element in the kebab menu
$f = preg_replace(
    '/(<div id="google_translate_element"><\/div>)/',
    '$1' . "\n                        " . $resetBtn,
    $f
);

file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);
echo "Added Reset button!";
?>
