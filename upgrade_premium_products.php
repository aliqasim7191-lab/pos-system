<?php
include 'includes/db.php';

// Clear existing messy data
$conn->query("SET FOREIGN_KEY_CHECKS = 0;");
$conn->query("TRUNCATE TABLE sale_items;");
$conn->query("TRUNCATE TABLE sales;");
$conn->query("TRUNCATE TABLE products;");
$conn->query("SET FOREIGN_KEY_CHECKS = 1;");

// Fetch premium dummy grocery products
$url = 'https://dummyjson.com/products/category/groceries';
$context = stream_context_create([
    'http' => [
        'header' => 'User-Agent: PHP'
    ]
]);
$json = file_get_contents($url, false, $context);
$data = json_decode($json, true);

$barcode_start = 1001;

if(isset($data['products'])) {
    foreach($data['products'] as $p) {
        $name = substr($p['title'], 0, 50); // Ensure it fits
        $price = $p['price'];
        $stock = $p['stock'];
        $image = $p['thumbnail'];
        $unit = 'pcs';
        $barcode = (string)$barcode_start++;
        
        $stmt = $conn->prepare("INSERT INTO products (name, price, stock, image, unit, barcode, tenant_id) VALUES (?, ?, ?, ?, ?, ?, {$_SESSION['tenant_id']})");
        $stmt->bind_param("sdisss", $name, $price, $stock, $image, $unit, $barcode);
        $stmt->execute();
        $stmt->close();
    }
    echo "Premium products loaded successfully.";
} else {
    echo "Failed to load products.";
}
?>
