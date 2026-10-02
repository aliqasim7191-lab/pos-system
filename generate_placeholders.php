<?php
$products = [
    'hammer.jpg' => 'Hammer',
    'screwdriver_set.jpg' => 'Screwdriver Set',
    'wrench.jpg' => 'Wrench',
    'power_drill.jpg' => 'Power Drill',
    'measuring_tape.jpg' => 'Measuring Tape',
    'utility_knife.jpg' => 'Utility Knife',
    'pliers.jpg' => 'Pliers',
    'safety_goggles.jpg' => 'Safety Goggles',
    'sticky_notes.jpg' => 'Sticky Notes',
    'stapler.jpg' => 'Stapler',
    'desk_organizer.jpg' => 'Desk Organizer',
    'highlighters.jpg' => 'Highlighters',
    'calculator.jpg' => 'Calculator',
    'whiteboard_markers.jpg' => 'Whiteboard Markers'
];

$dir = __DIR__ . '/uploads/';
if (!is_dir($dir)) mkdir($dir, 0777, true);

foreach ($products as $file => $text) {
    $urlText = urlencode($text);
    $url = "https://placehold.co/600x400/2563eb/ffffff.png?text={$urlText}";
    
    $ch = curl_init($url);
    $fp = fopen($dir . $file, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
    
    echo "Generated placeholder for {$text}\n";
}
echo "All placeholders generated successfully!\n";
?>
