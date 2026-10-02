<?php
$conn = new mysqli("localhost", "root", "", "pos_system");
$res = $conn->query("SELECT id, name, tenant_id FROM products LIMIT 5");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
echo "Session Tenant: " . ($_SESSION['tenant_id'] ?? 'none');
?>
