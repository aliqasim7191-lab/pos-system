<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/super_admin.php");

$bad_btn = <<<PHP
<a href="javascript:void(0);" onclick="var m = prompt('Kitne maheenay (months) add karne hain?', '2'); if(m && !isNaN(m) && m > 0) window.location.href='?extend_id=<?php echo \$t['id']; ?>&months='+m;" class="btn-sm" style="background:#10b981; color:white;">Add Months</a>
PHP;

$good_btn = <<<PHP
<a href="edit_tenant.php?id=<?php echo \$t['id']; ?>" class="btn-sm" style="background:#3b82f6; color:white;"><i class="fa fa-pencil"></i> Edit</a>
<a href="javascript:void(0);" onclick="var m = prompt('Kitne maheenay (months) add karne hain?', '2'); if(m && !isNaN(m) && m > 0) window.location.href='?extend_id=<?php echo \$t['id']; ?>&months='+m;" class="btn-sm" style="background:#10b981; color:white;"><i class="fa fa-calendar-plus-o"></i> Add Months</a>
PHP;

$f = str_replace($bad_btn, $good_btn, $f);

$bad_msg = <<<PHP
<?php if(isset(\$_GET['msg']) && \$_GET['msg']=='extended') echo "<div style='background:#10b981; color:white; padding:1rem; border-radius:8px; margin-bottom:1rem;'>Tenant Extended for " . intval(\$_GET['months']) . " Months!</div>"; ?>
PHP;

$good_msg = <<<PHP
<?php if(isset(\$_GET['msg']) && \$_GET['msg']=='extended') echo "<div style='background:#10b981; color:white; padding:1rem; border-radius:8px; margin-bottom:1rem;'>Tenant Extended for " . intval(\$_GET['months']) . " Months!</div>"; ?>
<?php if(isset(\$_GET['msg']) && \$_GET['msg']=='updated') echo "<div style='background:#3b82f6; color:white; padding:1rem; border-radius:8px; margin-bottom:1rem;'>Tenant Details Updated Successfully!</div>"; ?>
PHP;

$f = str_replace($bad_msg, $good_msg, $f);

file_put_contents("C:/xampp/htdocs/point of sale/super_admin.php", $f);
echo "Edit button added!";
?>
