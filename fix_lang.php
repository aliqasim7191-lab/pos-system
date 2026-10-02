<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");

$replacements = [
    'dY?,? Promos' => '<i class="fa fa-tag"></i> Promos',
    'dYO? E-Commerce' => '<i class="fa fa-globe"></i> E-Commerce',
    'dY` Staff' => '<i class="fa fa-users"></i> Staff',
    'dY\' HR & Payroll' => '<i class="fa fa-briefcase"></i> HR & Payroll',
    'sT,? Settings' => '<i class="fa fa-cog"></i> Settings',
    'dY"O Integrations' => '<i class="fa fa-plug"></i> Integrations',
    'dY"? Audit Logs' => '<i class="fa fa-history"></i> Audit Logs',
    'dY? <?php echo htmlspecialchars($branchName); ?>' => '<i class="fa fa-building"></i> <?php echo htmlspecialchars($branchName); ?>',
    'dY"S Dashboard' => '<i class="fa fa-dashboard"></i> Dashboard',
    'dY"^ Analytics' => '<i class="fa fa-bar-chart"></i> Analytics'
];

foreach ($replacements as $bad => $good) {
    // We will do a generic replacement because exact string might have invisible bytes
    // So we replace using regex matching the anchor tag contents
}

// More reliable replacement: Regex for the anchor content
$f = preg_replace('/>.*Promos<\/a>/', '><i class="fa fa-tag"></i> Promos</a>', $f);
$f = preg_replace('/>.*E-Commerce<\/a>/', '><i class="fa fa-globe"></i> E-Commerce</a>', $f);
$f = preg_replace('/>.*Staff<\/a>/', '><i class="fa fa-users"></i> Staff</a>', $f);
$f = preg_replace('/>.*HR & Payroll<\/a>/', '><i class="fa fa-briefcase"></i> HR & Payroll</a>', $f);
$f = preg_replace('/>.*Settings<\/a>/', '><i class="fa fa-cog"></i> Settings</a>', $f);
$f = preg_replace('/>.*Integrations<\/a>/', '><i class="fa fa-plug"></i> Integrations</a>', $f);
$f = preg_replace('/>.*Audit Logs<\/a>/', '><i class="fa fa-history"></i> Audit Logs</a>', $f);
$f = preg_replace('/>.*Dashboard<\/a>/', '><i class="fa fa-dashboard"></i> Dashboard</a>', $f);
$f = preg_replace('/>.*Analytics<\/a>/', '><i class="fa fa-bar-chart"></i> Analytics</a>', $f);

// For the branch name
$f = preg_replace('/<div style="font-size: 0\.7rem.*?>.*?<\?php echo htmlspecialchars\(\$branchName\); \?><\/div>/', '<div style="font-size: 0.7rem; font-weight: 600; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.5px;"><i class="fa fa-building"></i> <?php echo htmlspecialchars($branchName); ?></div>', $f);

file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);
echo "Fixed!";
?>
