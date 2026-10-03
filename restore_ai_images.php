<?php
include 'includes/db.php';

$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("TRUNCATE TABLE products");
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

$categories = [
    'GROCERY' => [
        ['name' => 'Fresh Milk', 'desc' => 'Fresh organic milk bottle.', 'price' => 3.50],
        ['name' => 'Bread Pack', 'desc' => 'Sliced bread loaf in paper bag.', 'price' => 2.20],
        ['name' => 'Organic Apples', 'desc' => 'Fresh organic red apples.', 'price' => 4.00],
        ['name' => 'Eggs Dozen', 'desc' => 'Farm fresh eggs in carton.', 'price' => 3.00],
        ['name' => 'Orange Juice', 'desc' => 'Fresh orange juice.', 'price' => 5.50],
        ['name' => 'Butter Block', 'desc' => 'Rich salted butter block.', 'price' => 3.80],
        ['name' => 'Cheddar Cheese', 'desc' => 'Aged cheddar cheese block.', 'price' => 6.20],
        ['name' => 'Pasta Box', 'desc' => 'Premium Italian pasta.', 'price' => 2.50],
        ['name' => 'Tomato Sauce', 'desc' => 'Classic marinara sauce.', 'price' => 3.10],
        ['name' => 'Coffee Beans', 'desc' => 'Roasted coffee beans.', 'price' => 12.00]
    ],
    'BEAUTY' => [
        ['name' => 'Perfume', 'desc' => 'Elegant perfume bottle.', 'price' => 18.75],
        ['name' => 'Face Serum', 'desc' => 'Hydrating face serum.', 'price' => 24.00],
        ['name' => 'Moisturizer', 'desc' => 'Skin moisturizer cream.', 'price' => 15.50],
        ['name' => 'Lip Balm', 'desc' => 'Nourishing lip balm.', 'price' => 4.25],
        ['name' => 'Body Wash', 'desc' => 'Refreshing body wash.', 'price' => 8.99],
        ['name' => 'Shampoo', 'desc' => 'Revitalizing shampoo.', 'price' => 11.00],
        ['name' => 'Hair Conditioner', 'desc' => 'Silky hair conditioner.', 'price' => 11.00],
        ['name' => 'Sunscreen SPF 50', 'desc' => 'Sun protection lotion.', 'price' => 14.50],
        ['name' => 'Eye Cream', 'desc' => 'Anti-aging eye cream.', 'price' => 22.00]
    ],
    'PHARMACY' => [
        ['name' => 'Vitamin C', 'desc' => 'Vitamin C supplement.', 'price' => 9.99],
        ['name' => 'Pain Reliever', 'desc' => 'Fast-acting pain reliever.', 'price' => 6.50],
        ['name' => 'First Aid Kit', 'desc' => 'Compact first aid kit.', 'price' => 19.99],
        ['name' => 'Cough Syrup', 'desc' => 'Soothing cough syrup.', 'price' => 8.25],
        ['name' => 'Allergy Pills', 'desc' => 'Allergy relief pills.', 'price' => 12.50],
        ['name' => 'Bandages', 'desc' => 'Adhesive bandages.', 'price' => 3.99],
        ['name' => 'Thermometer', 'desc' => 'Digital thermometer.', 'price' => 15.00],
        ['name' => 'Hand Sanitizer', 'desc' => 'Hand sanitizer gel.', 'price' => 4.50]
    ],
    'ELECTRONICS' => [
        ['name' => 'Smart Watch', 'desc' => 'Sleek smart watch.', 'price' => 199.99],
        ['name' => 'Wireless Earbuds', 'desc' => 'Wireless earbuds.', 'price' => 129.50],
        ['name' => 'Power Bank', 'desc' => '10000mAh power bank.', 'price' => 29.99],
        ['name' => 'Bluetooth Speaker', 'desc' => 'Portable bluetooth speaker.', 'price' => 45.00],
        ['name' => 'Phone Charger', 'desc' => 'Fast phone charger.', 'price' => 18.00],
        ['name' => 'Laptop Sleeve', 'desc' => '13-inch laptop sleeve.', 'price' => 22.50],
        ['name' => 'Mouse Pad', 'desc' => 'Gaming mouse pad.', 'price' => 15.99],
        ['name' => 'Webcam', 'desc' => '1080p HD webcam.', 'price' => 55.00]
    ]
];

$cat_images = [
    'GROCERY' => 'grocery_basket.jpg',
    'BEAUTY' => 'beauty_products.jpg',
    'PHARMACY' => 'pharmacy_shelf.jpg',
    'ELECTRONICS' => 'electronics_gadgets.jpg'
];

$stmt = $conn->prepare("INSERT INTO products (name, category, description, price, unit, image, stock, barcode, tenant_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, {$_SESSION['tenant_id']})");

$count = 0;
foreach ($categories as $cat => $items) {
    foreach ($items as $index => $item) {
        $unit = 'pcs';
        $stock = 100;
        $barcode = '100' . rand(100000, 999999) . $count;
        $image = $cat_images[$cat];
        $stmt->bind_param("sssdssis", $item['name'], $cat, $item['desc'], $item['price'], $unit, $image, $stock, $barcode);
        $stmt->execute();
        $count++;
    }
}
echo "Successfully restored 35 products with the 4 beautiful AI category images.\n";
?>
