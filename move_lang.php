<?php
\ = 'C:/xampp/htdocs/point of sale/includes/header.php';
\ = file_get_contents(\);

// 1. Remove the div from its current location
\ = str_replace('<div id="google_translate_element" style="margin-right: 0.5rem;"></div>', '', \);

// 2. Insert it into the kebabDropdown (at the very top, under the Main Menu header)
\ = '
<div style="padding: 0.5rem 1.2rem; border-bottom: 1px solid rgba(0,0,0,0.05); margin-bottom: 0.5rem;">
    <div id="google_translate_element"></div>
</div>
';
\ = str_replace('<div class="menu-header">Main Menu</div>', '<div class="menu-header">Main Menu</div>' . \, \);

file_put_contents(\, \);
echo "Moved language selector";
?>
