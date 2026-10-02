<?php
$conn = new mysqli("localhost", "root", "", "pos_system");
$res = $conn->query("SELECT id, username, role, tenant_id FROM users ORDER BY id DESC LIMIT 10");
while($row = $res->fetch_assoc()) {
    echo $row['id'] . " | " . $row['username'] . " | " . $row['role'] . " | " . $row['tenant_id'] . "\n";
}
?>
