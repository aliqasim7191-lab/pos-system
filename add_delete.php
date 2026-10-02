<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/super_admin.php");

$bad = <<<PHP
if (isset(\$_GET['suspend_id'])) {
    \$s_id = (int)\$_GET['suspend_id'];
    \$conn->query("UPDATE tenants SET subscription_status = 'suspended' WHERE id = \$s_id");
    header("Location: super_admin.php?msg=suspended");
    exit();
}
PHP;

$good = <<<PHP
if (isset(\$_GET['suspend_id'])) {
    \$s_id = (int)\$_GET['suspend_id'];
    \$conn->query("UPDATE tenants SET subscription_status = 'suspended' WHERE id = \$s_id");
    header("Location: super_admin.php?msg=suspended");
    exit();
}

if (isset(\$_GET['delete_id'])) {
    \$d_id = (int)\$_GET['delete_id'];
    // Prevent deleting Tenant 1 (Main/Default)
    if (\$d_id !== 1) {
        \$tables = ['alerts', 'attendance', 'audit_logs', 'branches', 'customer_ledger', 'customers', 'damaged_stock', 'expenses', 'held_carts', 'held_sale_items', 'held_sales', 'monthly_reports_history', 'payroll', 'product_batches', 'product_variations', 'products', 'promo_codes', 'purchase_items', 'purchase_order_items', 'purchase_orders', 'purchases', 'return_items', 'returns', 'sale_items', 'sales', 'settings', 'shifts', 'stock_transfers', 'suppliers', 'tax_classes', 'users', 'z_reports_history'];
        foreach (\$tables as \$tbl) {
            // Check if tenant_id exists in table
            \$check = \$conn->query("SHOW COLUMNS FROM \$tbl LIKE 'tenant_id'");
            if (\$check && \$check->num_rows > 0) {
                \$conn->query("DELETE FROM \$tbl WHERE tenant_id = \$d_id");
            }
        }
        \$conn->query("DELETE FROM tenants WHERE id = \$d_id");
        header("Location: super_admin.php?msg=deleted");
        exit();
    }
}
PHP;

$f = str_replace($bad, $good, $f);

$bad2 = <<<PHP
<?php if(isset(\$_GET['msg']) && \$_GET['msg']=='updated') echo "<div style='background:#3b82f6; color:white; padding:1rem; border-radius:8px; margin-bottom:1rem;'>Tenant Details Updated Successfully!</div>"; ?>
PHP;

$good2 = <<<PHP
<?php if(isset(\$_GET['msg']) && \$_GET['msg']=='updated') echo "<div style='background:#3b82f6; color:white; padding:1rem; border-radius:8px; margin-bottom:1rem;'>Tenant Details Updated Successfully!</div>"; ?>
<?php if(isset(\$_GET['msg']) && \$_GET['msg']=='deleted') echo "<div style='background:#ef4444; color:white; padding:1rem; border-radius:8px; margin-bottom:1rem;'>Business Account Permanently Deleted!</div>"; ?>
PHP;

$f = str_replace($bad2, $good2, $f);

$bad3 = <<<PHP
                    <td style="white-space: nowrap;">
                        <a href="edit_tenant.php?id=<?php echo \$t['id']; ?>" class="btn-sm" style="background:#3b82f6; color:white;"><i class="fa fa-pencil"></i> Edit</a>
                        <a href="javascript:void(0);" onclick="var m = prompt('Kitne maheenay (months) add karne hain?', '2'); if(m && !isNaN(m) && m > 0) window.location.href='?extend_id=<?php echo \$t['id']; ?>&months='+m;" class="btn-sm" style="background:#10b981; color:white;"><i class="fa fa-calendar-plus-o"></i> Add Months</a>
                        <a href="?suspend_id=<?php echo \$t['id']; ?>" class="btn-sm" style="background:#ef4444; color:white;"><i class="fa fa-ban"></i> Suspend</a>
                    </td>
PHP;

$good3 = <<<PHP
                    <td style="white-space: nowrap;">
                        <a href="edit_tenant.php?id=<?php echo \$t['id']; ?>" class="btn-sm" style="background:#3b82f6; color:white;"><i class="fa fa-pencil"></i> Edit</a>
                        <a href="javascript:void(0);" onclick="var m = prompt('Kitne maheenay (months) add karne hain?', '2'); if(m && !isNaN(m) && m > 0) window.location.href='?extend_id=<?php echo \$t['id']; ?>&months='+m;" class="btn-sm" style="background:#10b981; color:white;"><i class="fa fa-calendar-plus-o"></i> Add Months</a>
                        <a href="?suspend_id=<?php echo \$t['id']; ?>" class="btn-sm" style="background:#f59e0b; color:white;"><i class="fa fa-ban"></i> Suspend</a>
                        <?php if (\$t['id'] != 1): ?>
                        <a href="javascript:void(0);" onclick="if(confirm('WARNING: Are you sure you want to PERMANENTLY delete this entire business and ALL its data? This cannot be undone.')) window.location.href='?delete_id=<?php echo \$t['id']; ?>';" class="btn-sm" style="background:#ef4444; color:white;"><i class="fa fa-trash"></i> Delete</a>
                        <?php endif; ?>
                    </td>
PHP;

$f = str_replace($bad3, $good3, $f);

file_put_contents("C:/xampp/htdocs/point of sale/super_admin.php", $f);
echo "Added delete functionality\n";
?>
