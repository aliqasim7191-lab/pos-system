<?php
require 'includes/db.php';
$conn->query("SET FOREIGN_KEY_CHECKS=1");

// create a dummy supplier
$conn->query("INSERT INTO suppliers (name, tenant_id) VALUES ('Dummy', 2)");
$sid = $conn->insert_id;
echo "Inserted $sid\n";

// create a dummy purchase
$conn->query("INSERT INTO purchases (supplier_id, tenant_id, total_amount) VALUES ($sid, 2, 100)");

// Try to delete without nulling
try {
    $conn->query("DELETE FROM suppliers WHERE id = $sid");
    echo "Deleted without nulling?\n";
} catch (Exception $e) {
    echo "Blocked: " . $e->getMessage() . "\n";
}

// Now null it
$conn->query("UPDATE purchases SET supplier_id = NULL WHERE supplier_id = $sid AND tenant_id = 2");

// Try to delete again
try {
    $conn->query("DELETE FROM suppliers WHERE id = $sid");
    echo "Deleted after nulling!\n";
} catch (Exception $e) {
    echo "Still blocked: " . $e->getMessage() . "\n";
}
?>
