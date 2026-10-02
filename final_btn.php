<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");

// Replace Dashboard to be a proper btn
$f = preg_replace(
    '/<li><a href="dashboard\.php" class="<\?php echo \$currentPage == \'dashboard\.php\' \? \'active\' : \'\'; \?>"><i class="fa fa-dashboard"><\/i> Dashboard<\/a><\/li>/',
    '<li><a href="dashboard.php" class="<?php echo $currentPage == \'dashboard.php\' ? \'active\' : \'\'; ?> btn" style="background:#8b5cf6; color:white; border-radius:6px; padding:0.4rem 0.8rem; font-size:0.9rem;"><i class="fa fa-dashboard"></i> Dashboard</a></li>',
    $f
);

// Replace Analytics to be a proper btn
$f = preg_replace(
    '/<li><a href="analytics\.php" class="<\?php echo \$currentPage == \'analytics\.php\' \? \'active\' : \'\'; \?>"><i class="fa fa-bar-chart"><\/i> Analytics<\/a><\/li>/',
    '<li><a href="analytics.php" class="<?php echo $currentPage == \'analytics.php\' ? \'active\' : \'\'; ?> btn" style="background:#14b8a6; color:white; border-radius:6px; padding:0.4rem 0.8rem; font-size:0.9rem;"><i class="fa fa-bar-chart"></i> Analytics</a></li>',
    $f
);

file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);
echo "Fixed!";
?>
