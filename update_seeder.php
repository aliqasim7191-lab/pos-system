<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/super_admin.php");

$seed_code = <<<CODE
    \$new_tenant_id = \$conn->insert_id;
    \$admin_user = strtolower(str_replace(' ', '', \$company_name)) . '_admin';
    \$admin_pass = password_hash('password123', PASSWORD_DEFAULT);
    
    // 1. Create Branch
    \$conn->query("INSERT INTO branches (name, tenant_id) VALUES ('\$company_name Main Branch', \$new_tenant_id)");
    \$branch_id = \$conn->insert_id;
    
    // 2. Create User
    \$conn->query("INSERT INTO users (username, password, role, branch_id, tenant_id) VALUES ('\$admin_user', '\$admin_pass', 'admin', \$branch_id, \$new_tenant_id)");
    
    // 3. Seed Categories
    \$conn->query("INSERT INTO categories (name, tenant_id) VALUES ('General', \$new_tenant_id), ('Grocery', \$new_tenant_id), ('Electronics', \$new_tenant_id)");
    
    // 4. Seed Tax Classes
    \$conn->query("INSERT INTO tax_classes (name, rate, tenant_id) VALUES ('Standard Tax', 0.00, \$new_tenant_id)");
    
    // 5. Seed Settings (Copy from Tenant 1 to ensure UI looks perfect)
    \$res = \$conn->query("SELECT setting_key, setting_value FROM settings WHERE tenant_id = 1");
    if (\$res) {
        while (\$row = \$res->fetch_assoc()) {
            \$k = \$conn->real_escape_string(\$row['setting_key']);
            \$v = \$conn->real_escape_string(\$row['setting_value']);
            // Overwrite specific settings
            if (\$k === 'store_name') \$v = \$company_name;
            \$conn->query("INSERT INTO settings (setting_key, setting_value, tenant_id) VALUES ('\$k', '\$v', \$new_tenant_id)");
        }
    }
    
    // 6. Seed Integration Settings (Copy from Tenant 1)
    \$res = \$conn->query("SELECT setting_key, setting_value FROM integration_settings WHERE tenant_id = 1");
    if (\$res) {
        while (\$row = \$res->fetch_assoc()) {
            \$k = \$conn->real_escape_string(\$row['setting_key']);
            \$v = \$conn->real_escape_string(\$row['setting_value']);
            \$conn->query("INSERT INTO integration_settings (setting_key, setting_value, tenant_id) VALUES ('\$k', '\$v', \$new_tenant_id)");
        }
    }
CODE;

$f = preg_replace(
    '/\$new_tenant_id = \$conn->insert_id;[\s\S]*?\$conn->query\("INSERT INTO users[^"]+"\);/',
    $seed_code,
    $f
);

file_put_contents("C:/xampp/htdocs/point of sale/super_admin.php", $f);
echo "Super admin seeder updated!";
?>
