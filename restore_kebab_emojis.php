<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");

// Kebab Menu Items
$f = preg_replace('/<i class="fa fa-tag"><\/i> Promos/', "\u{1F381}" . ' Promos', $f);
$f = preg_replace('/<i class="fa fa-globe"><\/i> E-Commerce/', "\u{1F310}" . ' E-Commerce', $f);
$f = preg_replace('/<i class="fa fa-users"><\/i> Staff/', "\u{1F465}" . ' Staff', $f);
$f = preg_replace('/<i class="fa fa-briefcase"><\/i> HR & Payroll/', "\u{1F4BC}" . ' HR & Payroll', $f);
$f = preg_replace('/<i class="fa fa-cog"><\/i> Settings/', "\u{2699}\u{FE0F}" . ' Settings', $f);
$f = preg_replace('/<i class="fa fa-plug"><\/i> Integrations/', "\u{1F50C}" . ' Integrations', $f);
$f = preg_replace('/<i class="fa fa-history"><\/i> Audit Logs/', "\u{1F4DC}" . ' Audit Logs', $f);
$f = preg_replace('/<i class="fa fa-building"><\/i> <\?php echo htmlspecialchars\(\$branchName\); \?>/', "\u{1F3E2}" . ' <?php echo htmlspecialchars($branchName); ?>', $f);

// If they meant the top nav buttons as well, let's restore emojis there if any are missing.
// But I already restored 📊 Dashboard and 📈 Analytics.
// What about Branches in the Kebab Menu or Top Menu?
$f = preg_replace('/dY\? Branches/', "\u{1F3E2}" . ' Branches', $f);
$f = preg_replace('/<i class="fa fa-moon-o"><\/i><\/span> End of Day/', "\u{1F319}" . '</span> End of Day', $f); // Moon emoji for End of Day just in case

file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);
echo "Restored emojis to Kebab menu!";
?>
