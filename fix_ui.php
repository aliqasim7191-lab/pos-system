<?php
// Fix header.php Dashboard & Analytics links
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");
$f = preg_replace('/<li><i class="fa fa-dashboard"><\/i> Dashboard<\/a><\/li>/', '<li><a href="dashboard.php" class="<?php echo $currentPage == \'dashboard.php\' ? \'active\' : \'\'; ?>"><i class="fa fa-dashboard"></i> Dashboard</a></li>', $f);
$f = preg_replace('/<li><i class="fa fa-bar-chart"><\/i> Analytics<\/a><\/li>/', '<li><a href="analytics.php" class="<?php echo $currentPage == \'analytics.php\' ? \'active\' : \'\'; ?>"><i class="fa fa-bar-chart"></i> Analytics</a></li>', $f);
file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);

// Fix index.php emojis
$idx = file_get_contents("C:/xampp/htdocs/point of sale/index.php");
// Scan Barcode
$idx = preg_replace('/placeholder=".*?Scan Barcode/', 'placeholder="🔍 Scan Barcode', $idx);
// in stock
$idx = preg_replace('/<div style="font-size: 0\.85rem; color: #10b981; margin-bottom: 0\.5rem;">.*? (.*?) in stock<\/div>/', '<div style="font-size: 0.85rem; color: #10b981; margin-bottom: 0.5rem;"><i class="fa fa-cube"></i> $1 in stock</div>', $idx);
// Reprint
$idx = preg_replace('/<span style="font-size:1\.2rem;">.*?<\/span><br>Reprint/', '<span style="font-size:1.2rem;"><i class="fa fa-print"></i></span><br>Reprint', $idx);
// Walk-in Customer
$idx = preg_replace('/<option value="" data-type="walk_in".*?>Walk-in Customer.*?<\/option>/', '<option value="" data-type="walk_in" data-points="0">Walk-in Customer <i class="fa fa-user"></i></option>', $idx);
$idx = preg_replace('/<option value=".*?">.*?Walk-in Customer.*?<\/option>/', '<option value="" data-type="walk_in" data-points="0">Walk-in Customer <i class="fa fa-user"></i></option>', $idx); // Sometimes it hardcodes id
// Current Order
$idx = preg_replace('/<h2.*?>.*? Current Order<\/h2>/', '<h2 style="font-size: 1.25rem; font-weight: 700; color: #0f172a; margin: 0;"><i class="fa fa-shopping-cart"></i> Current Order</h2>', $idx);

file_put_contents("C:/xampp/htdocs/point of sale/index.php", $idx);

// Clean app2.js as well if Walk-in Customer has weird chars
$app = file_get_contents("C:/xampp/htdocs/point of sale/assets/js/app2.js");
$app = preg_replace('/Walk-in Customer.*?<\/option>/', 'Walk-in Customer <i class="fa fa-user"></i></option>', $app);
file_put_contents("C:/xampp/htdocs/point of sale/assets/js/app2.js", $app);

echo "Fixed!";
?>
