<?php
$products = [
    'Fresh Milk' => 'fresh_milk.jpg',
    'Bread Pack' => 'bread_pack.jpg',
    'Organic Apples' => 'organic_apples.jpg',
    'Eggs Dozen' => 'eggs_dozen.jpg',
    'Orange Juice' => 'orange_juice.jpg',
    'Butter Block' => 'butter_block.jpg',
    'Cheddar Cheese' => 'cheddar_cheese.jpg',
    'Pasta Box' => 'pasta_box.jpg',
    'Tomato Sauce' => 'tomato_sauce.jpg',
    'Coffee Beans' => 'coffee_beans.jpg',

    'Perfume' => 'perfume.jpg',
    'Face Serum' => 'face_serum.jpg',
    'Moisturizer' => 'moisturizer.jpg',
    'Lip Balm' => 'lip_balm.jpg',
    'Body Wash' => 'body_wash.jpg',
    'Shampoo bottle' => 'shampoo.jpg',
    'Hair Conditioner' => 'hair_conditioner.jpg',
    'Sunscreen SPF 50' => 'sunscreen.jpg',
    'Eye Cream' => 'eye_cream.jpg',

    'Vitamin C pills' => 'vitamin_c.jpg',
    'Pain Reliever pills' => 'pain_reliever.jpg',
    'First Aid Kit' => 'first_aid_kit.jpg',
    'Cough Syrup' => 'cough_syrup.jpg',
    'Allergy Pills' => 'allergy_pills.jpg',
    'Bandages' => 'bandages.jpg',
    'Medical Thermometer' => 'thermometer.jpg',
    'Hand Sanitizer' => 'hand_sanitizer.jpg',
    'Medical Masks' => 'medical_masks.jpg',

    'Smart Watch' => 'smart_watch.jpg',
    'Wireless Earbuds' => 'wireless_earbuds.jpg',
    'Power Bank' => 'power_bank.jpg',
    'Bluetooth Speaker' => 'bluetooth_speaker.jpg',
    'Phone Charger' => 'phone_charger.jpg',
    'Laptop Sleeve' => 'laptop_sleeve.jpg',
    'Mouse Pad' => 'mouse_pad.jpg',
    'Webcam' => 'webcam.jpg'
];

$dir = __DIR__ . '/uploads';
if (!is_dir($dir)) mkdir($dir, 0777, true);
array_map('unlink', glob("$dir/*.*"));

function getBingImage($query) {
    // Add "product photo" to ensure we get a good isolated item picture
    $url = 'https://www.bing.com/images/search?q=' . urlencode('product photo isolated ' . $query);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $html = curl_exec($ch);
    curl_close($ch);
    
    // Bing embeds image URLs in the JSON object inside the HTML
    // Looking for "murl":"https://..."
    if (preg_match_all('/"murl":"([^"]+)"/', $html, $matches)) {
        foreach($matches[1] as $imgUrl) {
            $imgUrl = str_replace('\/', '/', $imgUrl);
            if (preg_match('/\.(jpg|jpeg|png)$/i', parse_url($imgUrl, PHP_URL_PATH))) {
                return $imgUrl;
            }
        }
        if (isset($matches[1][0])) return str_replace('\/', '/', $matches[1][0]);
    }
    return false;
}

// Fallback to Wikipedia if Bing fails
function getWikiImage($query) {
    $url = 'https://en.wikipedia.org/w/api.php?action=query&format=json&prop=pageimages&generator=search&gsrsearch=' . urlencode($query) . '&gsrlimit=1&pithumbsize=500';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $json = curl_exec($ch);
    curl_close($ch);
    
    if ($json) {
        $data = json_decode($json, true);
        if (isset($data['query']['pages'])) {
            $pages = $data['query']['pages'];
            $first = reset($pages);
            if (isset($first['thumbnail']['source'])) {
                return $first['thumbnail']['source'];
            }
        }
    }
    return false;
}

$count = 0;
foreach ($products as $name => $filename) {
    $path = $dir . '/' . $filename;
    echo "Searching for $name...\n";
    $imgUrl = getBingImage($name);
    
    if (!$imgUrl) {
        echo "Bing failed, trying Wiki...\n";
        $imgUrl = getWikiImage($name);
    }
    
    if ($imgUrl) {
        echo "Downloading: $imgUrl\n";
        $ch = curl_init($imgUrl);
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
            echo "FAILED to download image data for $name\n";
        }
    } else {
        echo "FAILED to find image URL for $name\n";
    }
}
echo "Downloaded $count out of " . count($products) . " images.\n";
?>
