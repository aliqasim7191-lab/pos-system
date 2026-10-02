<?php
include 'includes/db.php';
$res = $conn->query("SELECT id, name FROM products");
$products = [];
while($r = $res->fetch_assoc()){
    $products[] = $r['name'];
}
echo "Count: " . count($products) . "\n";
echo "Products:\n";
print_r($products);
