<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/super_admin.php");

$bad = <<<PHP
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
PHP;

$good = <<<PHP
    if (\$d_id !== 1) {
        \$conn->query("SET FOREIGN_KEY_CHECKS=0");
        \$tables = ['alerts', 'attendance', 'audit_logs', 'branches', 'customer_ledger', 'customers', 'damaged_stock', 'expenses', 'held_carts', 'held_sale_items', 'held_sales', 'monthly_reports_history', 'payroll', 'product_batches', 'product_variations', 'products', 'promo_codes', 'purchase_items', 'purchase_order_items', 'purchase_orders', 'purchases', 'return_items', 'returns', 'sale_items', 'sales', 'settings', 'shifts', 'stock_transfers', 'suppliers', 'tax_classes', 'users', 'z_reports_history'];
        foreach (\$tables as \$tbl) {
            // Check if tenant_id exists in table
            \$check = \$conn->query("SHOW COLUMNS FROM \$tbl LIKE 'tenant_id'");
            if (\$check && \$check->num_rows > 0) {
                \$conn->query("DELETE FROM \$tbl WHERE tenant_id = \$d_id");
            }
        }
        \$conn->query("DELETE FROM tenants WHERE id = \$d_id");
        \$conn->query("SET FOREIGN_KEY_CHECKS=1");
        header("Location: super_admin.php?msg=deleted");
        exit();
    }
PHP;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/super_admin.php", $f);
echo "Fixed FK constraints on delete\n";
?>
