<?php
$images = [
    'fresh_milk.jpg' => 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=500&h=350&fit=crop',
    'bread_pack.jpg' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=500&h=350&fit=crop',
    'organic_apples.jpg' => 'https://images.unsplash.com/photo-1560806887-1e4cd0b6fac6?w=500&h=350&fit=crop',
    'eggs_dozen.jpg' => 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?w=500&h=350&fit=crop',
    'orange_juice.jpg' => 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=500&h=350&fit=crop',
    'butter_block.jpg' => 'https://images.unsplash.com/photo-1589985270826-4b7bb135bc9d?w=500&h=350&fit=crop',
    'cheddar_cheese.jpg' => 'https://images.unsplash.com/photo-1486297678162-eb2a19b0a32d?w=500&h=350&fit=crop',
    'pasta_box.jpg' => 'https://images.unsplash.com/photo-1551183053-bf91a1d81141?w=500&h=350&fit=crop',
    'tomato_sauce.jpg' => 'https://images.unsplash.com/photo-1579619146194-e0eb59b52a1a?w=500&h=350&fit=crop',
    'coffee_beans.jpg' => 'https://images.unsplash.com/photo-1559525839-b184a4d698c7?w=500&h=350&fit=crop',

    'perfume.jpg' => 'https://images.unsplash.com/photo-1523293182086-7651a899d37f?w=500&h=350&fit=crop',
    'face_serum.jpg' => 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=500&h=350&fit=crop',
    'moisturizer.jpg' => 'https://images.unsplash.com/photo-1611078512140-ed15a0c1081d?w=500&h=350&fit=crop',
    'lip_balm.jpg' => 'https://images.unsplash.com/photo-1599305090598-fe179d501227?w=500&h=350&fit=crop',
    'body_wash.jpg' => 'https://images.unsplash.com/photo-1585232004423-244e0e69000b?w=500&h=350&fit=crop',
    'shampoo.jpg' => 'https://images.unsplash.com/photo-1535585209827-a15fcdbc4c2d?w=500&h=350&fit=crop',
    'hair_conditioner.jpg' => 'https://images.unsplash.com/photo-1608248543803-ba4f8c70ae0b?w=500&h=350&fit=crop',
    'sunscreen.jpg' => 'https://images.unsplash.com/photo-1556228720-192a6af4e86e?w=500&h=350&fit=crop',
    'eye_cream.jpg' => 'https://images.unsplash.com/photo-1615397323758-a53f65e2ec71?w=500&h=350&fit=crop',

    'vitamin_c.jpg' => 'https://images.unsplash.com/photo-1584308666744-24d5e47852b7?w=500&h=350&fit=crop',
    'pain_reliever.jpg' => 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=500&h=350&fit=crop',
    'first_aid_kit.jpg' => 'https://images.unsplash.com/photo-1603398938378-e54eab446dde?w=500&h=350&fit=crop',
    'cough_syrup.jpg' => 'https://images.unsplash.com/photo-1584017911766-d451b3d0e843?w=500&h=350&fit=crop',
    'allergy_pills.jpg' => 'https://images.unsplash.com/photo-1550572017-edb9c4fbe52b?w=500&h=350&fit=crop',
    'bandages.jpg' => 'https://images.unsplash.com/photo-1631549916768-4119b2e5f926?w=500&h=350&fit=crop',
    'thermometer.jpg' => 'https://images.unsplash.com/photo-1584744982491-665216d95f8b?w=500&h=350&fit=crop',
    'hand_sanitizer.jpg' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?w=500&h=350&fit=crop',

    'smart_watch.jpg' => 'https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?w=500&h=350&fit=crop',
    'wireless_earbuds.jpg' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=500&h=350&fit=crop',
    'power_bank.jpg' => 'https://images.unsplash.com/photo-1609081219090-a6d81d3085bf?w=500&h=350&fit=crop',
    'bluetooth_speaker.jpg' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=500&h=350&fit=crop',
    'phone_charger.jpg' => 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?w=500&h=350&fit=crop',
    'laptop_sleeve.jpg' => 'https://images.unsplash.com/photo-1622288432450-277d0fce5b95?w=500&h=350&fit=crop',
    'mouse_pad.jpg' => 'https://images.unsplash.com/photo-1615663245857-ac1eeb5304af?w=500&h=350&fit=crop',
    'webcam.jpg' => 'https://images.unsplash.com/photo-1596484552834-6a58f850e0a1?w=500&h=350&fit=crop'
];

$dir = __DIR__ . '/uploads';
if (!is_dir($dir)) mkdir($dir, 0777, true);

$ctx = stream_context_create(array('http'=>
    array(
        'timeout' => 10,  // 10 Seconds
    )
));

foreach ($images as $filename => $url) {
    $path = $dir . '/' . $filename;
    echo "Downloading $filename...\n";
    $data = @file_get_contents($url, false, $ctx);
    if ($data !== false) {
        file_put_contents($path, $data);
        echo "Saved $filename.\n";
    } else {
        echo "Failed to download $filename.\n";
    }
}
echo "All done.\n";
?>
