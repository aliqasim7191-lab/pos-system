<?php
include 'includes/db.php';

$new_products = [
    // Hardware
    ['name' => 'Hammer', 'category' => 'Hardware', 'price' => 15.99, 'stock' => 50, 'barcode' => '7000000001', 'image' => 'hammer.jpg'],
    ['name' => 'Screwdriver Set', 'category' => 'Hardware', 'price' => 24.50, 'stock' => 40, 'barcode' => '7000000002', 'image' => 'screwdriver_set.jpg'],
    ['name' => 'Wrench', 'category' => 'Hardware', 'price' => 12.75, 'stock' => 60, 'barcode' => '7000000003', 'image' => 'wrench.jpg'],
    ['name' => 'Power Drill', 'category' => 'Hardware', 'price' => 89.99, 'stock' => 15, 'barcode' => '7000000004', 'image' => 'power_drill.jpg'],
    ['name' => 'Measuring Tape', 'category' => 'Hardware', 'price' => 8.50, 'stock' => 100, 'barcode' => '7000000005', 'image' => 'measuring_tape.jpg'],
    ['name' => 'Utility Knife', 'category' => 'Hardware', 'price' => 6.99, 'stock' => 80, 'barcode' => '7000000006', 'image' => 'utility_knife.jpg'],
    ['name' => 'Pliers', 'category' => 'Hardware', 'price' => 11.25, 'stock' => 55, 'barcode' => '7000000007', 'image' => 'pliers.jpg'],
    ['name' => 'Safety Goggles', 'category' => 'Hardware', 'price' => 9.99, 'stock' => 70, 'barcode' => '7000000008', 'image' => 'safety_goggles.jpg'],

    // Stationery
    ['name' => 'Notebook', 'category' => 'Stationery', 'price' => 4.50, 'stock' => 120, 'barcode' => '8000000001', 'image' => 'notebook.jpg'],
    ['name' => 'Pen Set', 'category' => 'Stationery', 'price' => 7.99, 'stock' => 90, 'barcode' => '8000000002', 'image' => 'pen_set.jpg'],
    ['name' => 'Sticky Notes', 'category' => 'Stationery', 'price' => 3.25, 'stock' => 150, 'barcode' => '8000000003', 'image' => 'sticky_notes.jpg'],
    ['name' => 'Stapler', 'category' => 'Stationery', 'price' => 14.50, 'stock' => 45, 'barcode' => '8000000004', 'image' => 'stapler.jpg'],
    ['name' => 'Desk Organizer', 'category' => 'Stationery', 'price' => 22.00, 'stock' => 30, 'barcode' => '8000000005', 'image' => 'desk_organizer.jpg'],
    ['name' => 'Highlighters', 'category' => 'Stationery', 'price' => 5.99, 'stock' => 85, 'barcode' => '8000000006', 'image' => 'highlighters.jpg'],
    ['name' => 'Calculator', 'category' => 'Stationery', 'price' => 19.99, 'stock' => 40, 'barcode' => '8000000007', 'image' => 'calculator.jpg'],
    ['name' => 'Whiteboard Markers', 'category' => 'Stationery', 'price' => 8.75, 'stock' => 75, 'barcode' => '8000000008', 'image' => 'whiteboard_markers.jpg']
];

$count = 0;
foreach ($new_products as $p) {
    // Check if exists
    $stmt = $conn->prepare("SELECT id FROM products WHERE name = ?");
    $stmt->bind_param("s", $p['name']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows == 0) {
        $stmt_in = $conn->prepare("INSERT INTO products (name, category, price, stock, barcode, image, tenant_id) VALUES (?, ?, ?, ?, ?, ?, {$_SESSION['tenant_id']})");
        $stmt_in->bind_param("ssdiss", $p['name'], $p['category'], $p['price'], $p['stock'], $p['barcode'], $p['image']);
        if($stmt_in->execute()){
            $count++;
            echo "Added: " . $p['name'] . "\n";
        } else {
            echo "Failed: " . $p['name'] . " - " . $conn->error . "\n";
        }
    } else {
        echo "Skipped (already exists): " . $p['name'] . "\n";
    }
}

echo "\nAdded $count new products.\n";

$res = $conn->query("SELECT COUNT(*) as total FROM products");
$row = $res->fetch_assoc();
echo "Total products in DB: " . $row['total'] . "\n";
?>
