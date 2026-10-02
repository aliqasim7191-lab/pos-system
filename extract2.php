<?php
$log_path = 'C:/Users/aliq3/.gemini/antigravity/brain/17815c2b-e9fa-4909-b2dc-e7e3f3383b9b/.system_generated/logs/transcript_full.jsonl';
$handle = fopen($log_path, "r");
$found_index = false;
$found_style = false;
$found_header = false;

if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $data = json_decode($line, true);
        if (isset($data['type']) && $data['type'] == 'TOOL_RESPONSE' && isset($data['content'])) {
            $content = $data['content'];
            
            // Check for index.php
            if (!$found_index && preg_match('/File Path: `file:\/\/\/C:\/xampp\/htdocs\/point%20of%20sale\/index\.php`/', $content)) {
                file_put_contents('old_index.txt', $content);
                $found_index = true;
            }
            // Check for style.css
            if (!$found_style && preg_match('/File Path: `file:\/\/\/C:\/xampp\/htdocs\/point%20of%20sale\/assets\/css\/style\.css`/', $content)) {
                file_put_contents('old_style.txt', $content);
                $found_style = true;
            }
            // Check for header.php
            if (!$found_header && preg_match('/File Path: `file:\/\/\/C:\/xampp\/htdocs\/point%20of%20sale\/includes\/header\.php`/', $content)) {
                file_put_contents('old_header.txt', $content);
                $found_header = true;
            }
        }
    }
    fclose($handle);
}
echo "Done extracting\n";
?>
