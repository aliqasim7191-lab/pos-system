<?php
$query = "Smartwatch";
$url = "https://en.wikipedia.org/w/api.php?action=query&prop=pageimages&titles=" . urlencode($query) . "&format=json&pithumbsize=500";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$json = curl_exec($ch);
$data = json_decode($json, true);
if (isset($data['query']['pages'])) {
    $pages = $data['query']['pages'];
    $first = reset($pages);
    if (isset($first['thumbnail']['source'])) {
        echo "Found URL: " . $first['thumbnail']['source'] . "\n";
    } else {
        echo "No thumbnail.\n";
    }
}
?>
