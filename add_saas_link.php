<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");

// Add Super Admin to kebab menu
$superAdminLink = '<?php if($_SESSION[\'role\'] === \'super_admin\'): ?>
                        <div style="border-top: 1px solid rgba(0,0,0,0.05); margin: 0.5rem 0;"></div>
                        <a href="super_admin.php" style="color:#6366f1; font-weight:bold;">&#x1F310; Super Admin Panel</a>
                      <?php endif; ?>';

$f = preg_replace(
    '/(<div class="menu-header">Main Menu<\/div>)/',
    $superAdminLink . "\n                      $1",
    $f
);

file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);
echo "Added Super Admin to header";
?>
