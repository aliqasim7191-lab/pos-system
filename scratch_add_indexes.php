<?php
include 'includes/db.php';

echo "ADDING HIGH-SPEED DATABASE INDEXES...\n\n";

function addIndexIfNotExist($conn, $table, $indexName, $columnsSql) {
    try {
        $check = $conn->query("SHOW INDEX FROM `$table` WHERE Key_name = '$indexName'");
        if ($check && $check->num_rows == 0) {
            $sql = "ALTER TABLE `$table` ADD INDEX `$indexName` ($columnsSql)";
            if ($conn->query($sql)) {
                echo "✅ Added index '$indexName' to table '$table'\n";
            } else {
                echo "⚠️ Failed adding index '$indexName' to '$table': " . $conn->error . "\n";
            }
        } else {
            echo "ℹ️ Index '$indexName' already exists on '$table'\n";
        }
    } catch (\Throwable $e) {
        echo "⚠️ Exception on '$table': " . $e->getMessage() . "\n";
    }
}

addIndexIfNotExist($conn, 'sales', 'idx_sales_tenant_date', 'tenant_id, created_at');
addIndexIfNotExist($conn, 'sales', 'idx_sales_cleared', 'tenant_id, is_cleared');
addIndexIfNotExist($conn, 'sales', 'idx_sales_payment', 'tenant_id, payment_method');
addIndexIfNotExist($conn, 'sales', 'idx_sales_customer', 'tenant_id, customer_id');

addIndexIfNotExist($conn, 'sale_items', 'idx_si_sale_id', 'sale_id');
addIndexIfNotExist($conn, 'sale_items', 'idx_si_product_id', 'product_id');

addIndexIfNotExist($conn, 'products', 'idx_prod_tenant_status', 'tenant_id, status');
addIndexIfNotExist($conn, 'products', 'idx_prod_barcode', 'barcode');
addIndexIfNotExist($conn, 'products', 'idx_prod_category', 'tenant_id, category');

addIndexIfNotExist($conn, 'shifts', 'idx_shifts_status', 'tenant_id, status, is_cleared');
addIndexIfNotExist($conn, 'expenses', 'idx_exp_tenant_date', 'tenant_id, created_at');
addIndexIfNotExist($conn, 'customers', 'idx_cust_tenant_name', 'tenant_id, name');
addIndexIfNotExist($conn, 'suppliers', 'idx_supp_tenant_name', 'tenant_id, name');

echo "\nDatabase Indexing Complete!";
?>
