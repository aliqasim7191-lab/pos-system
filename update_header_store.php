<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");
$link = <<<HTML
                    <a href="settings_integrations.php" class="<?php echo \$currentPage == 'settings_integrations.php' ? 'active' : ''; ?>"> &#x1F50C; Integrations</a>
                    <a href="store.php?t=<?php echo \$_SESSION['tenant_id']; ?>" target="_blank" style="background:#ecfdf5; color:#059669; border-left-color:#10b981; font-weight:bold;"> &#x1F6D2; View Online Store</a>
HTML;
$f = preg_replace('/<a href="settings_integrations\.php".*?<\/a>/s', $link, $f);
file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);
echo "Header updated";
?>
