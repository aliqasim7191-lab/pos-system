<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "pos_system";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$log = "";

// 1. Create tenants table
$sql = "CREATE TABLE IF NOT EXISTS tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(100) NOT NULL,
    domain VARCHAR(100) NULL,
    subscription_plan VARCHAR(50) DEFAULT 'basic',
    subscription_status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    $log .= "Tenants table created.\n";
} else {
    $log .= "Error creating tenants table: " . $conn->error . "\n";
}

// 2. Insert Default Tenant if not exists
$res = $conn->query("SELECT id FROM tenants WHERE id = 1");
if ($res->num_rows == 0) {
    $conn->query("INSERT INTO tenants (id, company_name) VALUES (1, 'Default Company')");
    $log .= "Default tenant created.\n";
}

// 3. Get all tables
$tables = [];
$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_array()) {
    $tables[] = $row[0];
}

// 4. Add tenant_id to all tables (except tenants)
foreach ($tables as $table) {
    if ($table == 'tenants') continue;
    
    // Check if tenant_id exists
    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'tenant_id'");
    if ($check->num_rows == 0) {
        if ($conn->query("ALTER TABLE `$table` ADD COLUMN tenant_id INT NOT NULL DEFAULT 1")) {
            $log .= "Added tenant_id to $table.\n";
        } else {
            $log .= "Error adding tenant_id to $table: " . $conn->error . "\n";
        }
    }
}

// 5. Add super_admin role to users
$checkRole = $conn->query("SHOW COLUMNS FROM `users` LIKE 'role'");
// The column exists, we just need to ensure super_admin is allowed if it's an ENUM.
// Let's modify the ENUM
$conn->query("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'manager', 'cashier', 'super_admin') NOT NULL DEFAULT 'cashier'");

echo $log;
?>
