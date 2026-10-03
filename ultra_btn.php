<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");

// Replace Dashboard
$dashboardStyle = "display:flex; align-items:center; gap:0.4rem; padding:0.45rem 0.9rem; background:linear-gradient(135deg, #8b5cf6, #6366f1); border-radius:8px; color:#fff; font-weight:700; text-decoration:none; font-size:0.85rem; box-shadow:0 4px 10px rgba(99, 102, 241, 0.3); text-shadow:0 1px 1px rgba(0,0,0,0.2);";
$f = preg_replace(
    '/<li><a href="dashboard\.php" class=".*?" style=".*?"><i class="fa fa-dashboard"><\/i> Dashboard<\/a><\/li>/',
    '<li><a href="dashboard.php" style="' . $dashboardStyle . '"><i class="fa fa-dashboard" style="font-size:1.1rem; filter:drop-shadow(0 1px 1px rgba(0,0,0,0.2));"></i> Dashboard</a></li>',
    $f
);

// Replace Analytics
$analyticsStyle = "display:flex; align-items:center; gap:0.4rem; padding:0.45rem 0.9rem; background:linear-gradient(135deg, #14b8a6, #0ea5e9); border-radius:8px; color:#fff; font-weight:700; text-decoration:none; font-size:0.85rem; box-shadow:0 4px 10px rgba(20, 184, 166, 0.3); text-shadow:0 1px 1px rgba(0,0,0,0.2);";
$f = preg_replace(
    '/<li><a href="analytics\.php" class=".*?" style=".*?"><i class="fa fa-bar-chart"><\/i> Analytics<\/a><\/li>/',
    '<li><a href="analytics.php" style="' . $analyticsStyle . '"><i class="fa fa-bar-chart" style="font-size:1.1rem; filter:drop-shadow(0 1px 1px rgba(0,0,0,0.2));"></i> Analytics</a></li>',
    $f
);

file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);
echo "Fixed!";
?>
