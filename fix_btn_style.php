<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");

// Dashboard - Purple
$f = str_replace('class="<?php echo $currentPage == \'dashboard.php\' ? \'active\' : \'\'; ?> btn"><i class="fa fa-dashboard">', 'class="<?php echo $currentPage == \'dashboard.php\' ? \'active\' : \'\'; ?> btn" style="background:#8b5cf6; color:white; border-radius:6px;"><i class="fa fa-dashboard">', $f);

// Analytics - Teal
$f = str_replace('class="<?php echo $currentPage == \'analytics.php\' ? \'active\' : \'\'; ?> btn"><i class="fa fa-bar-chart">', 'class="<?php echo $currentPage == \'analytics.php\' ? \'active\' : \'\'; ?> btn" style="background:#14b8a6; color:white; border-radius:6px;"><i class="fa fa-bar-chart">', $f);

file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);
echo "Fixed!";
?>
