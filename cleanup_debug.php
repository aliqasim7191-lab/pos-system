<?php
$files = ['test_direct_ai.php', 'test_syntax.php', 'debug_ai.php'];
foreach ($files as $f) {
    if (file_exists($f)) unlink($f);
}
echo "Cleaned up debug test files.";
?>
