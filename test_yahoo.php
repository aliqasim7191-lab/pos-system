<?php
$url = 'https://images.search.yahoo.com/search/images?p=' . urlencode('high quality product photography isolated fresh milk bottle');
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$html = curl_exec($ch);
if (preg_match('/<img[^>]+src=["\'](https:\/\/[^"\']+)["\'][^>]*class=["\']process["\']/i', $html, $matches) || 
    preg_match('/src=["\'](https:\/\/tse[0-9]\.mm\.bing\.net[^"\']+)["\']/i', $html, $matches)) {
    echo "Found URL: " . $matches[1] . "\n";
} else {
    echo "No image found.\n";
}
?>
