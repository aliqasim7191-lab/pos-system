<?php
include 'C:/xampp/htdocs/point of sale/includes/db.php';
$c_id = 5; // A known ID
$tenant_id = 1;
$stmt = $conn->prepare("DELETE FROM customers WHERE id=? AND tenant_id=?");
if (!$stmt) die("Prepare failed: " . $conn->error);
$stmt->bind_param("ii", $c_id, $tenant_id);
if (!$stmt->execute()) die("Execute failed: " . $stmt->error);
echo "Success!";
?>
