<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "pos_system";
$conn = new mysqli($host, $user, $password, $database);

$username = "superadmin";
$pass = password_hash("superadmin123", PASSWORD_DEFAULT);
$role = "super_admin";
$tenant = 1; // Super admin belongs to tenant 1 initially, but has global access

$conn->query("INSERT INTO users (username, password, role, branch_id, tenant_id) VALUES ('$username', '$pass', '$role', 1, $tenant)");
echo "Super admin created. Username: superadmin, Password: superadmin123";
?>
