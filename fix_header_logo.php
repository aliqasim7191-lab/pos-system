<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");

$old_logo_html = <<<HTML
            <div class="logo">
                <img src="<?php echo htmlspecialchars(\$headerLogo); ?>" alt="Logo" style="height: 32px; width: auto; max-width: 80px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); background: white;">
HTML;

$new_logo_html = <<<HTML
            <div class="logo">
                <?php if(!empty(\$tSettings['store_logo'])): ?>
                    <img src="<?php echo htmlspecialchars(\$tSettings['store_logo']); ?>" alt="Logo" style="height: 32px; width: auto; max-width: 80px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); background: white;">
                <?php else: ?>
                    <i class="fa fa-shopping-cart" style="font-size: 1.5rem; color: #f59e0b;"></i>
                <?php endif; ?>
HTML;

$f = str_replace($old_logo_html, $new_logo_html, $f);
file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);
echo "Header logo logic updated!";
?>
