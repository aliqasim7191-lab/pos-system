<?php
$files = ['apply_pos_real_logo.php', 'update_luxury_icon.php', 'convert_icon.php', 'generate_pwa_icons.php'];
foreach ($files as $f) {
    if (file_exists($f)) {
        unlink($f);
    }
}
echo "Cleaned up temp files.";
?>
