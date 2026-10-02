<?php
$log = file('C:/Users/aliq3/.gemini/antigravity/brain/17815c2b-e9fa-4909-b2dc-e7e3f3383b9b/.system_generated/logs/transcript_full.jsonl');
foreach($log as $line) {
    $data = json_decode($line, true);
    if (isset($data['type']) && $data['type'] == 'TOOL_RESPONSE' && strpos($data['content'] ?? '', 'index.php') !== false) {
        file_put_contents('extracted_index.txt', $data['content'] . "\n\n======================\n\n", FILE_APPEND);
    }
}
?>
