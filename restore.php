<?php
function restoreFile($recoveredFile, $targetFile) {
    $content = file_get_contents($recoveredFile);
    // Extract the first block between "Showing lines..." and "The above content does NOT show" or "====="
    if (preg_match('/Showing lines \d+ to \d+\n(.*?)(?:\nThe above content does NOT show|=====)/s', $content, $matches)) {
        $lines = explode("\n", $matches[1]);
        $clean = [];
        foreach ($lines as $line) {
            if (preg_match('/^\d+: (.*)$/', $line, $m)) {
                $clean[] = $m[1];
            } elseif (preg_match('/^\d+:(.*)$/', $line, $m)) {
                $clean[] = ltrim($m[1], ' ');
            }
        }
        if (!empty($clean)) {
            file_put_contents($targetFile, implode("\n", $clean));
            echo "Restored " . $targetFile . "\n";
            return;
        }
    }
    echo "Failed to restore " . $targetFile . "\n";
}

restoreFile('recovered_index.txt', 'index.php');
restoreFile('recovered_header.txt', 'includes/header.php');
restoreFile('recovered_style.txt', 'assets/css/style.css');
?>
