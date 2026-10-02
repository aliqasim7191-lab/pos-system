<?php
$images = [
    'organic_apples.jpg' => 'https://images.unsplash.com/photo-1567306226416-28f0efdc88ce?w=500&h=350&fit=crop',
    'tomato_sauce.jpg' => 'https://images.unsplash.com/photo-1606757134375-7b5e40e34b9d?w=500&h=350&fit=crop',
    'moisturizer.jpg' => 'https://images.unsplash.com/photo-1556228578-0d85b1a4d571?w=500&h=350&fit=crop',
    'body_wash.jpg' => 'https://images.unsplash.com/photo-1556228578-0d85b1a4d571?w=500&h=350&fit=crop',
    'sunscreen.jpg' => 'https://images.unsplash.com/photo-1523293182086-7651a899d37f?w=500&h=350&fit=crop',
];
$dir = __DIR__ . '/uploads';
$ctx = stream_context_create(array('http'=>array('timeout' => 10)));
foreach ($images as $filename => $url) {
    if (!file_exists($dir . '/' . $filename) || filesize($dir . '/' . $filename) == 0) {
        $path = $dir . '/' . $filename;
        echo "Fixing $filename...\n";
        $data = @file_get_contents($url, false, $ctx);
        if ($data !== false) {
            file_put_contents($path, $data);
            echo "Saved $filename.\n";
        }
    }
}
?>
