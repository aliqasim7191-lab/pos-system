<?php
$products = [
    'Hammer' => ['hammer.jpg', 'hammer,tool'],
    'Screwdriver Set' => ['screwdriver_set.jpg', 'screwdriver'],
    'Wrench' => ['wrench.jpg', 'wrench,tool'],
    'Power Drill' => ['power_drill.jpg', 'power,drill'],
    'Measuring Tape' => ['measuring_tape.jpg', 'measuring,tape'],
    'Utility Knife' => ['utility_knife.jpg', 'utility,knife'],
    'Pliers' => ['pliers.jpg', 'pliers,tool'],
    'Safety Goggles' => ['safety_goggles.jpg', 'safety,goggles'],
    'Sticky Notes' => ['sticky_notes.jpg', 'sticky,notes'],
    'Stapler' => ['stapler.jpg', 'stapler'],
    'Desk Organizer' => ['desk_organizer.jpg', 'desk,organizer'],
    'Highlighters' => ['highlighters.jpg', 'highlighters,pen'],
    'Calculator' => ['calculator.jpg', 'calculator'],
    'Whiteboard Markers' => ['whiteboard_markers.jpg', 'whiteboard,markers']
];

$dir = __DIR__ . '/uploads';
if (!is_dir($dir)) mkdir($dir, 0777, true);

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
echo "Downloaded $count images.\n";
?>
