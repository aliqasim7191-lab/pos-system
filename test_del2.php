<?php
include 'C:/xampp/htdocs/point of sale/includes/db.php';
$c_id = 5; 
$tenant_id = 1;

if (!$conn->query("DELETE FROM customer_ledger WHERE customer_id = $c_id AND tenant_id = $tenant_id")) {
    echo "Ledger Error: " . $conn->error . "\n";
} else {
    echo "Ledger OK\n";
}

if (!$conn->query("UPDATE sales SET customer_id = NULL WHERE customer_id = $c_id AND tenant_id = $tenant_id")) {
    echo "Sales Error: " . $conn->error . "\n";
} else {
    echo "Sales OK\n";
}

$stmt = $conn->prepare("DELETE FROM customers WHERE id=? AND tenant_id=?");
$stmt->bind_param("ii", $c_id, $tenant_id);
if (!$stmt->execute()) {
    echo "Customers Error: " . $stmt->error . "\n";
} else {
    echo "Customers OK\n";
}
?>
