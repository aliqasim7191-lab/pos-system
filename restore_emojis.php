<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");

// Dashboard: Remove FA icon and inline styles, add 📊
$f = preg_replace(
    '/<li><a href="dashboard\.php" style=".*?"><i class="fa fa-dashboard".*?><\/i> Dashboard<\/a><\/li>/',
    '<li><a href="dashboard.php" class="<?php echo $currentPage == \'dashboard.php\' ? \'active\' : \'\'; ?>">' . "\u{1F4CA}" . ' Dashboard</a></li>',
    $f
);

// Analytics: Remove FA icon and inline styles, add 📈
$f = preg_replace(
    '/<li><a href="analytics\.php" style=".*?"><i class="fa fa-bar-chart".*?><\/i> Analytics<\/a><\/li>/',
    '<li><a href="analytics.php" class="<?php echo $currentPage == \'analytics.php\' ? \'active\' : \'\'; ?>">' . "\u{1F4C8}" . ' Analytics</a></li>',
    $f
);

file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);
echo "Restored emojis!";
?>
