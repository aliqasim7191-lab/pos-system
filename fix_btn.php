<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/super_admin.php");

$bad = <<<PHP
                    <td>
                        <a href="edit_tenant.php?id=<?php echo \$t['id']; ?>" class="btn-sm" style="background:#3b82f6; color:white;"><i class="fa fa-pencil"></i> Edit</a>
<a href="javascript:void(0);" onclick="var m = prompt('Kitne maheenay (months) add karne hain?', '2'); if(m && !isNaN(m) && m > 0) window.location.href='?extend_id=<?php echo \$t['id']; ?>&months='+m;" class="btn-sm" style="background:#10b981; color:white;"><i class="fa fa-calendar-plus-o"></i> Add Months</a>
                        <a href="?suspend_id=<?php echo \$t['id']; ?>" class="btn-sm" style="background:#ef4444; color:white;">Suspend</a>
                    </td>
PHP;

$good = <<<PHP
                    <td style="white-space: nowrap;">
                        <a href="edit_tenant.php?id=<?php echo \$t['id']; ?>" class="btn-sm" style="background:#3b82f6; color:white;"><i class="fa fa-pencil"></i> Edit</a>
                        <a href="javascript:void(0);" onclick="var m = prompt('Kitne maheenay (months) add karne hain?', '2'); if(m && !isNaN(m) && m > 0) window.location.href='?extend_id=<?php echo \$t['id']; ?>&months='+m;" class="btn-sm" style="background:#10b981; color:white;"><i class="fa fa-calendar-plus-o"></i> Add Months</a>
                        <a href="?suspend_id=<?php echo \$t['id']; ?>" class="btn-sm" style="background:#ef4444; color:white;"><i class="fa fa-ban"></i> Suspend</a>
                    </td>
PHP;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/super_admin.php", $f);
echo "Fixed button wrapping.\n";
?>
