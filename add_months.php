<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/super_admin.php");

$bad_php = <<<PHP
if (isset(\$_GET['renew_id'])) {
    \$r_id = (int)\$_GET['renew_id'];
    \$conn->query("UPDATE tenants SET subscription_ends_at = DATE_ADD(COALESCE(subscription_ends_at, CURRENT_DATE), INTERVAL 1 MONTH), subscription_status = 'active' WHERE id = \$r_id");
    header("Location: super_admin.php?msg=renewed");
    exit();
}
PHP;

$good_php = <<<PHP
if (isset(\$_GET['renew_id'])) {
    \$r_id = (int)\$_GET['renew_id'];
    \$conn->query("UPDATE tenants SET subscription_ends_at = DATE_ADD(COALESCE(subscription_ends_at, CURRENT_DATE), INTERVAL 1 MONTH), subscription_status = 'active' WHERE id = \$r_id");
    header("Location: super_admin.php?msg=renewed");
    exit();
}
if (isset(\$_GET['extend_id']) && isset(\$_GET['months'])) {
    \$e_id = (int)\$_GET['extend_id'];
    \$m = (int)\$_GET['months'];
    if (\$m > 0) {
        // If expired, add from today. If active, add to current expiry date
        \$conn->query("UPDATE tenants SET subscription_ends_at = DATE_ADD(IF(subscription_ends_at < CURRENT_DATE, CURRENT_DATE, COALESCE(subscription_ends_at, CURRENT_DATE)), INTERVAL \$m MONTH), subscription_status = 'active' WHERE id = \$e_id");
        header("Location: super_admin.php?msg=extended&months=\$m");
        exit();
    }
}
PHP;

$f = str_replace($bad_php, $good_php, $f);


$bad_msg = <<<PHP
<?php if(isset(\$_GET['msg']) && \$_GET['msg']=='renewed') echo "<div style='background:#10b981; color:white; padding:1rem; border-radius:8px; margin-bottom:1rem;'>Tenant Renewed for 1 Month!</div>"; ?>
PHP;

$good_msg = <<<PHP
<?php if(isset(\$_GET['msg']) && \$_GET['msg']=='renewed') echo "<div style='background:#10b981; color:white; padding:1rem; border-radius:8px; margin-bottom:1rem;'>Tenant Renewed for 1 Month!</div>"; ?>
<?php if(isset(\$_GET['msg']) && \$_GET['msg']=='extended') echo "<div style='background:#10b981; color:white; padding:1rem; border-radius:8px; margin-bottom:1rem;'>Tenant Extended for " . intval(\$_GET['months']) . " Months!</div>"; ?>
PHP;

$f = str_replace($bad_msg, $good_msg, $f);

$bad_btn = <<<PHP
<a href="?renew_id=<?php echo \$t['id']; ?>" class="btn-sm" style="background:#10b981; color:white;">Renew</a>
PHP;

$good_btn = <<<PHP
<a href="javascript:void(0);" onclick="var m = prompt('Kitne maheenay (months) add karne hain?', '2'); if(m && !isNaN(m) && m > 0) window.location.href='?extend_id=<?php echo \$t['id']; ?>&months='+m;" class="btn-sm" style="background:#10b981; color:white;">Add Months</a>
PHP;

$f = str_replace($bad_btn, $good_btn, $f);

file_put_contents("C:/xampp/htdocs/point of sale/super_admin.php", $f);
echo "Custom Months added!";
?>
