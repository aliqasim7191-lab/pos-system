<?php
$log_path = 'C:/Users/aliq3/.gemini/antigravity/brain/17815c2b-e9fa-4909-b2dc-e7e3f3383b9b/.system_generated/logs/transcript_full.jsonl';
$handle = fopen($log_path, "r");
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $data = json_decode($line, true);
        if (isset($data['type']) && $data['type'] == 'TOOL_RESPONSE' && isset($data['content'])) {
            $content = $data['content'];
            if (strpos($content, 'C:/xampp/htdocs/point%20of%20sale/index.php') !== false || strpos($content, 'C:\\xampp\\htdocs\\point of sale\\index.php') !== false) {
                file_put_contents('recovered_index.txt', $content . "\n\n=====\n\n", FILE_APPEND);
            }
            if (strpos($content, 'style.css') !== false) {
                file_put_contents('recovered_style.txt', $content . "\n\n=====\n\n", FILE_APPEND);
            }
            if (strpos($content, 'header.php') !== false) {
                file_put_contents('recovered_header.txt', $content . "\n\n=====\n\n", FILE_APPEND);
            }
        }
    }
    fclose($handle);
}
?>
