<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/settings.php");

// Add PHP handler to delete logo
$handler = <<<PHP
if (isset(\$_GET['delete_logo'])) {
    \$conn->query("DELETE FROM settings WHERE setting_key = 'store_logo' AND tenant_id = " . \$_SESSION['tenant_id']);
    header('Location: settings.php');
    exit;
}

if (\$_SERVER['REQUEST_METHOD'] === 'POST') {
PHP;

$f = str_replace("if (\$_SERVER['REQUEST_METHOD'] === 'POST') {", $handler, $f);

// Add HTML button to delete logo
$old_html = <<<HTML
            <?php if(!empty(\$config['store_logo'])): ?>
                <div style="margin-top:0.5rem;">
                    <img src="<?php echo htmlspecialchars(\$config['store_logo']); ?>" alt="Logo" style="height:60px; border-radius:6px; border:1px solid #e2e8f0;">
                </div>
            <?php endif; ?>
HTML;

$new_html = <<<HTML
            <?php if(!empty(\$config['store_logo'])): ?>
                <div style="margin-top:0.5rem; display: flex; align-items: center; gap: 1rem;">
                    <img src="<?php echo htmlspecialchars(\$config['store_logo']); ?>" alt="Logo" style="height:60px; border-radius:6px; border:1px solid #e2e8f0; background: white;">
                    <a href="settings.php?delete_logo=1" class="btn btn-sm" style="background: #ef4444; color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px;" onclick="return confirm('Are you sure you want to remove the logo? The default shopping cart icon will be restored.');"><i class="fa fa-trash"></i> Remove Logo</a>
                </div>
            <?php endif; ?>
HTML;

$f = str_replace($old_html, $new_html, $f);
file_put_contents("C:/xampp/htdocs/point of sale/settings.php", $f);
echo "Settings updated!";
?>
