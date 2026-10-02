<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");

$old = <<<HTML
                    <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px;"><span style="font-size: 1.1rem; font-weight: 800; letter-spacing: -0.5px; color: #ffffff;"><?php echo htmlspecialchars(\$headerStoreName); ?></span> <span style="font-size: 0.9rem; font-weight: 400; color: rgba(255,255,255,0.7);">POS</span></div>
HTML;

$new = <<<HTML
                    <div style="white-space: nowrap;"><span style="font-size: 0.95rem; font-weight: 800; color: #ffffff;"><?php echo htmlspecialchars(\$headerStoreName); ?></span> <span style="font-size: 0.8rem; font-weight: 400; color: rgba(255,255,255,0.7);">POS</span></div>
HTML;

$f = str_replace($old, $new, $f);

file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);
echo "Store name visibility fixed!";
?>
