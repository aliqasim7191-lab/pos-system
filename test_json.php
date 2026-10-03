<?php
$_SESSION['tenant_id'] = 1;
include "C:/xampp/htdocs/point of sale/includes/db.php";

$result = $conn->query("SELECT p.*, t.rate as custom_tax_rate FROM products p LEFT JOIN tax_classes t ON p.tax_class_id = t.id WHERE p.status='active' AND p.tenant_id = 1");
$products = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
echo json_encode($products);
if ($conn->error) echo "ERROR: " . $conn->error;
?>
