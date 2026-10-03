<?php
session_start();
$_SESSION['tenant_id'] = 2; // Abbas Official
require 'includes/db.php';
$id = 5; // A dummy ID
$conn->query("UPDATE purchases SET supplier_id = NULL WHERE supplier_id = $id AND tenant_id = {$_SESSION['tenant_id']}");
$conn->query("UPDATE purchase_orders SET supplier_id = NULL WHERE supplier_id = $id AND tenant_id = {$_SESSION['tenant_id']}");
echo "Updated.\n";
?>
