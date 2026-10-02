<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");

$old = <<<HTML
                    <div><span style="font-weight: 800; letter-spacing: -0.5px; color: #ffffff;"><?php echo htmlspecialchars(\$headerStoreName); ?></span> <span style="font-weight: 400; color: rgba(255,255,255,0.7);">POS</span></div>
HTML;

$new = <<<HTML
                    <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px;"><span style="font-size: 1.1rem; font-weight: 800; letter-spacing: -0.5px; color: #ffffff;"><?php echo htmlspecialchars(\$headerStoreName); ?></span> <span style="font-size: 0.9rem; font-weight: 400; color: rgba(255,255,255,0.7);">POS</span></div>
HTML;

$f = str_replace($old, $new, $f);

// Also need to check if the old layout had the same issue
$old2 = <<<HTML
                    <i class="fa fa-shopping-cart" style="font-size: 1.5rem; color: #f59e0b;"></i>
                <?php endif; ?>
                <div style="display: flex; flex-direction: column; line-height: 1.1; margin-left: 0.5rem;">
HTML;
// The above is just to check where it is.

file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);
echo "Header text size fixed!";
?>
