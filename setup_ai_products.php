<?php
include 'includes/db.php';

$brain_dir = 'C:/Users/aliq3/.gemini/antigravity/brain/4b6adae5-641a-4306-b88d-0ac014e89081';
$uploads_dir = __DIR__ . '/uploads';

if (!is_dir($uploads_dir)) {
    mkdir($uploads_dir, 0777, true);
}

// Wipe existing products
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("TRUNCATE TABLE products");
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

$products = [
    ['name' => 'Wagyu Beef Steak', 'price' => 45.99, 'unit' => 'kg', 'prefix' => 'wagyu_beef'],
    ['name' => 'Premium Olive Oil', 'price' => 18.50, 'unit' => 'L', 'prefix' => 'olive_oil'],
    ['name' => 'Organic Raw Honey', 'price' => 12.99, 'unit' => 'pcs', 'prefix' => 'organic_honey'],
    ['name' => 'Artisan Sourdough', 'price' => 6.50, 'unit' => 'pcs', 'prefix' => 'artisan_bread'],
    ['name' => 'Gourmet Dark Chocolate', 'price' => 8.99, 'unit' => 'pcs', 'prefix' => 'dark_chocolate'],
    ['name' => 'Norwegian Salmon', 'price' => 28.99, 'unit' => 'kg', 'prefix' => 'fresh_salmon'],
    ['name' => 'Organic Strawberries', 'price' => 9.50, 'unit' => 'kg', 'prefix' => 'organic_strawberries'],
    ['name' => 'Roasted Coffee Beans', 'price' => 16.99, 'unit' => 'kg', 'prefix' => 'coffee_beans'],
    ['name' => 'Ripe Hass Avocados', 'price' => 5.99, 'unit' => 'kg', 'prefix' => 'avocado'],
    ['name' => 'Aged Cheddar Cheese', 'price' => 14.50, 'unit' => 'kg', 'prefix' => 'premium_cheese'],
    ['name' => 'Luxury Earl Grey Tea', 'price' => 22.00, 'unit' => 'pcs', 'prefix' => 'luxury_tea'],
    ['name' => 'Organic Raw Almonds', 'price' => 19.99, 'unit' => 'kg', 'prefix' => 'organic_almonds']
];

$stmt = $conn->prepare("INSERT INTO products (name, barcode, price, unit, image, tenant_id) VALUES (?, ?, ?, ?, ?, {$_SESSION['tenant_id']})");

$count = 0;
foreach ($products as $index => $p) {
    // Find the generated image matching the prefix
    $files = glob($brain_dir . '/' . $p['prefix'] . '_*.jpg');
    if (!empty($files)) {
        $source_file = $files[0];
        $filename = $p['prefix'] . '_' . time() . '.jpg';
        $dest_file = $uploads_dir . '/' . $filename;
        
        if (copy($source_file, $dest_file)) {
            $barcode = '890' . str_pad($index + 1, 9, '0', STR_PAD_LEFT);
            $stmt->bind_param("ssdss", $p['name'], $barcode, $p['price'], $p['unit'], $filename);
            $stmt->execute();
            $count++;
            echo "Imported: " . $p['name'] . "\n";
        } else {
            echo "Failed to copy image for " . $p['name'] . "\n";
        }
    } else {
        echo "Image not found for " . $p['name'] . "\n";
    }
}

echo "\nSuccessfully imported $count highly premium products with AI images!\n";
?>
