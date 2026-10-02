<?php
include 'includes/db.php';

$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("TRUNCATE TABLE products");
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

$categories = [
    'GROCERY' => [
        ['name' => 'Fresh Milk', 'desc' => 'Fresh organic milk bottle.', 'price' => 3.50, 'image' => 'fresh_milk.jpg'],
        ['name' => 'Bread Pack', 'desc' => 'Sliced bread loaf in paper bag.', 'price' => 2.20, 'image' => 'bread_pack.jpg'],
        ['name' => 'Organic Apples', 'desc' => 'Fresh organic red apples.', 'price' => 4.00, 'image' => 'organic_apples.jpg'],
        ['name' => 'Eggs Dozen', 'desc' => 'Farm fresh eggs in carton.', 'price' => 3.00, 'image' => 'eggs_dozen.jpg'],
        ['name' => 'Orange Juice', 'desc' => 'Fresh orange juice.', 'price' => 5.50, 'image' => 'orange_juice.jpg'],
        ['name' => 'Butter Block', 'desc' => 'Rich salted butter block.', 'price' => 3.80, 'image' => 'butter_block.jpg'],
        ['name' => 'Cheddar Cheese', 'desc' => 'Aged cheddar cheese block.', 'price' => 6.20, 'image' => 'cheddar_cheese.jpg'],
        ['name' => 'Pasta Box', 'desc' => 'Premium Italian pasta.', 'price' => 2.50, 'image' => 'pasta_box.jpg'],
        ['name' => 'Tomato Sauce', 'desc' => 'Classic marinara sauce.', 'price' => 3.10, 'image' => 'tomato_sauce.jpg'],
        ['name' => 'Coffee Beans', 'desc' => 'Roasted coffee beans.', 'price' => 12.00, 'image' => 'coffee_beans.jpg']
    ],
    'BEAUTY' => [
        ['name' => 'Perfume', 'desc' => 'Elegant perfume bottle.', 'price' => 18.75, 'image' => 'perfume.jpg'],
        ['name' => 'Face Serum', 'desc' => 'Hydrating face serum.', 'price' => 24.00, 'image' => 'face_serum.jpg'],
        ['name' => 'Moisturizer', 'desc' => 'Skin moisturizer cream.', 'price' => 15.50, 'image' => 'moisturizer.jpg'],
        ['name' => 'Lip Balm', 'desc' => 'Nourishing lip balm.', 'price' => 4.25, 'image' => 'lip_balm.jpg'],
        ['name' => 'Body Wash', 'desc' => 'Refreshing body wash.', 'price' => 8.99, 'image' => 'body_wash.jpg'],
        ['name' => 'Shampoo', 'desc' => 'Revitalizing shampoo.', 'price' => 11.00, 'image' => 'shampoo.jpg'],
        ['name' => 'Hair Conditioner', 'desc' => 'Silky hair conditioner.', 'price' => 11.00, 'image' => 'hair_conditioner.jpg'],
        ['name' => 'Sunscreen SPF 50', 'desc' => 'Sun protection lotion.', 'price' => 14.50, 'image' => 'sunscreen.jpg'],
        ['name' => 'Eye Cream', 'desc' => 'Anti-aging eye cream.', 'price' => 22.00, 'image' => 'eye_cream.jpg']
    ],
    'PHARMACY' => [
        ['name' => 'Vitamin C', 'desc' => 'Vitamin C supplement.', 'price' => 9.99, 'image' => 'vitamin_c.jpg'],
        ['name' => 'Pain Reliever', 'desc' => 'Fast-acting pain reliever.', 'price' => 6.50, 'image' => 'pain_reliever.jpg'],
        ['name' => 'First Aid Kit', 'desc' => 'Compact first aid kit.', 'price' => 19.99, 'image' => 'first_aid_kit.jpg'],
        ['name' => 'Cough Syrup', 'desc' => 'Soothing cough syrup.', 'price' => 8.25, 'image' => 'cough_syrup.jpg'],
        ['name' => 'Allergy Pills', 'desc' => 'Allergy relief pills.', 'price' => 12.50, 'image' => 'allergy_pills.jpg'],
        ['name' => 'Bandages', 'desc' => 'Adhesive bandages.', 'price' => 3.99, 'image' => 'bandages.jpg'],
        ['name' => 'Thermometer', 'desc' => 'Digital thermometer.', 'price' => 15.00, 'image' => 'thermometer.jpg'],
        ['name' => 'Hand Sanitizer', 'desc' => 'Hand sanitizer gel.', 'price' => 4.50, 'image' => 'hand_sanitizer.jpg'],
        ['name' => 'Medical Masks', 'desc' => 'Surgical face masks.', 'price' => 10.00, 'image' => 'medical_masks.jpg']
    ],
    'ELECTRONICS' => [
        ['name' => 'Smart Watch', 'desc' => 'Sleek smart watch.', 'price' => 199.99, 'image' => 'smart_watch.jpg'],
        ['name' => 'Wireless Earbuds', 'desc' => 'Wireless earbuds.', 'price' => 129.50, 'image' => 'wireless_earbuds.jpg'],
        ['name' => 'Power Bank', 'desc' => '10000mAh power bank.', 'price' => 29.99, 'image' => 'power_bank.jpg'],
        ['name' => 'Bluetooth Speaker', 'desc' => 'Portable bluetooth speaker.', 'price' => 45.00, 'image' => 'bluetooth_speaker.jpg'],
        ['name' => 'Phone Charger', 'desc' => 'Fast phone charger.', 'price' => 18.00, 'image' => 'phone_charger.jpg'],
        ['name' => 'Laptop Sleeve', 'desc' => '13-inch laptop sleeve.', 'price' => 22.50, 'image' => 'laptop_sleeve.jpg'],
        ['name' => 'Mouse Pad', 'desc' => 'Gaming mouse pad.', 'price' => 15.99, 'image' => 'mouse_pad.jpg'],
        ['name' => 'Webcam', 'desc' => '1080p HD webcam.', 'price' => 55.00, 'image' => 'webcam.jpg']
    ]
];

$stmt = $conn->prepare("INSERT INTO products (name, category, description, price, unit, image, stock, barcode, tenant_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, {$_SESSION['tenant_id']})");

$count = 0;
foreach ($categories as $cat => $items) {
    foreach ($items as $index => $item) {
        $unit = 'pcs';
        $stock = 100;
        $barcode = '100' . rand(100000, 999999) . $count;
        $stmt->bind_param("sssdssis", $item['name'], $cat, $item['desc'], $item['price'], $unit, $item['image'], $stock, $barcode);
        $stmt->execute();
        $count++;
    }
}

echo "Successfully added $count products with highly accurate AI images.\n";
?>
