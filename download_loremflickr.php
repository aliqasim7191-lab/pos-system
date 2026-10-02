<?php
$products = [
    'Fresh Milk' => ['fresh_milk.jpg', 'milk,bottle'],
    'Bread Pack' => ['bread_pack.jpg', 'bread,loaf'],
    'Organic Apples' => ['organic_apples.jpg', 'red,apples'],
    'Eggs Dozen' => ['eggs_dozen.jpg', 'chicken,eggs'],
    'Orange Juice' => ['orange_juice.jpg', 'orange,juice'],
    'Butter Block' => ['butter_block.jpg', 'butter,block'],
    'Cheddar Cheese' => ['cheddar_cheese.jpg', 'cheddar,cheese'],
    'Pasta Box' => ['pasta_box.jpg', 'raw,pasta'],
    'Tomato Sauce' => ['tomato_sauce.jpg', 'tomato,sauce'],
    'Coffee Beans' => ['coffee_beans.jpg', 'coffee,beans'],

    'Perfume' => ['perfume.jpg', 'perfume,bottle'],
    'Face Serum' => ['face_serum.jpg', 'face,serum'],
    'Moisturizer' => ['moisturizer.jpg', 'moisturizer,cream'],
    'Lip Balm' => ['lip_balm.jpg', 'lip,balm'],
    'Body Wash' => ['body_wash.jpg', 'body,wash'],
    'Shampoo bottle' => ['shampoo.jpg', 'shampoo,bottle'],
    'Hair Conditioner' => ['hair_conditioner.jpg', 'hair,conditioner'],
    'Sunscreen SPF 50' => ['sunscreen.jpg', 'sunscreen,lotion'],
    'Eye Cream' => ['eye_cream.jpg', 'eye,cream'],

    'Vitamin C pills' => ['vitamin_c.jpg', 'vitamin,pills'],
    'Pain Reliever pills' => ['pain_reliever.jpg', 'painkiller,pills'],
    'First Aid Kit' => ['first_aid_kit.jpg', 'first,aid,kit'],
    'Cough Syrup' => ['cough_syrup.jpg', 'cough,syrup'],
    'Allergy Pills' => ['allergy_pills.jpg', 'medicine,pills'],
    'Bandages' => ['bandages.jpg', 'adhesive,bandage'],
    'Medical Thermometer' => ['thermometer.jpg', 'medical,thermometer'],
    'Hand Sanitizer' => ['hand_sanitizer.jpg', 'hand,sanitizer'],
    'Medical Masks' => ['medical_masks.jpg', 'surgical,mask'],

    'Smart Watch' => ['smart_watch.jpg', 'smart,watch'],
    'Wireless Earbuds' => ['wireless_earbuds.jpg', 'wireless,earbuds'],
    'Power Bank' => ['power_bank.jpg', 'power,bank'],
    'Bluetooth Speaker' => ['bluetooth_speaker.jpg', 'bluetooth,speaker'],
    'Phone Charger' => ['phone_charger.jpg', 'phone,charger'],
    'Laptop Sleeve' => ['laptop_sleeve.jpg', 'laptop,sleeve'],
    'Mouse Pad' => ['mouse_pad.jpg', 'mouse,pad'],
    'Webcam' => ['webcam.jpg', 'webcam']
];

$dir = __DIR__ . '/uploads';
if (!is_dir($dir)) mkdir($dir, 0777, true);

// Clean up existing files
array_map('unlink', glob("$dir/*.*"));

$count = 0;
foreach ($products as $name => $info) {
    $filename = $info[0];
    $keyword = urlencode($info[1]);
    $path = $dir . '/' . $filename;
    
    echo "Downloading LoremFlickr for $name ($keyword)...\n";
    $url = "https://loremflickr.com/500/350/$keyword/all";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $imgData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $imgData && strlen($imgData) > 5000) {
        file_put_contents($path, $imgData);
        echo "Success: $filename\n";
        $count++;
    } else {
        echo "FAILED: $filename\n";
    }
}
echo "Downloaded $count out of " . count($products) . " images.\n";
?>
