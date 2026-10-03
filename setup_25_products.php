<?php
include 'includes/db.php';

$products = [
    ['Sugar (1kg)', 150.00, 50, 'kg', '1001'],
    ['Rice Basmati (5kg)', 1200.00, 20, 'kg', '1002'],
    ['Lays Classic (Large)', 100.00, 40, 'pcs', '1003'],
    ['Coca-Cola 1.5L', 130.00, 30, 'pcs', '1004'],
    ['Nestlé Milkpak 1L', 220.00, 25, 'pcs', '1005'],
    ['Lipton Yellow Label 190g', 350.00, 20, 'pcs', '1006'],
    ['Tapal Danedar Tea 200g', 360.00, 15, 'pcs', '1007'],
    ['National Ketchup 800g', 280.00, 20, 'pcs', '1008'],
    ['Shan Bombay Biryani Masala', 80.00, 50, 'pcs', '1009'],
    ['Dettol Soap 115g', 110.00, 30, 'pcs', '1010'],
    ['Safeguard Soap 115g', 115.00, 30, 'pcs', '1011'],
    ['Surf Excel 1kg', 600.00, 20, 'kg', '1012'],
    ['Ariel Washing Powder 1kg', 580.00, 20, 'kg', '1013'],
    ['Colgate Toothpaste 100g', 150.00, 25, 'pcs', '1014'],
    ['Sensodyne Toothpaste 75g', 220.00, 15, 'pcs', '1015'],
    ['Dalda Cooking Oil 1L', 550.00, 40, 'L', '1016'],
    ['Habib Cooking Oil 1L', 540.00, 40, 'L', '1017'],
    ['Sunsilk Shampoo 200ml', 300.00, 20, 'pcs', '1018'],
    ['Clear Men Shampoo 185ml', 320.00, 20, 'pcs', '1019'],
    ['Pampers Size 4 (44ct)', 1800.00, 10, 'pcs', '1020'],
    ['Nestle Fruita Vitals 1L', 250.00, 15, 'pcs', '1021'],
    ['Olpers Milk 1L', 230.00, 30, 'pcs', '1022'],
    ['Peak Freans Sooper', 30.00, 100, 'pcs', '1023'],
    ['LU Prince Biscuits', 30.00, 100, 'pcs', '1024'],
    ['Everyday Milk Powder 400g', 650.00, 15, 'pcs', '1025']
];

$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

foreach($products as $p) {
    $name = $p[0];
    $price = $p[1];
    $stock = $p[2];
    $unit = $p[3];
    $barcode = $p[4];
    
    // Generate placeholder image using an online service
    $colors = ['2563eb', '16a34a', 'dc2626', 'ca8a04', '9333ea', 'db2777', 'ea580c'];
    $bg = $colors[array_rand($colors)];
    $text = urlencode($name);
    $imgPath = "https://placehold.co/300x300/$bg/FFFFFF?text=$text";
    
    // Insert into DB
    $stmt = $conn->prepare("INSERT IGNORE INTO products (name, price, stock, unit, image, barcode) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdisss", $name, $price, $stock, $unit, $imgPath, $barcode);
    $stmt->execute();
    $stmt->close();
}

echo "25 products created successfully with placeholder URLs and barcodes.";
?>
