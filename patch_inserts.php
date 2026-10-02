<?php
$files = glob("C:/xampp/htdocs/point of sale/*.php");
foreach ($files as $file) {
    if (basename($file) == "super_admin.php" || basename($file) == "saas_db.php") continue;
    $content = file_get_contents($file);
    if (preg_match_all('/INSERT INTO\s+([a-zA-Z0-9_]+)\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/i', $content, $matches, PREG_SET_ORDER)) {
        $changed = false;
        foreach ($matches as $match) {
            $full_match = $match[0];
            $table = strtolower($match[1]);
            $cols = $match[2];
            $vals = $match[3];
            
            // Skip tables that don't have tenant_id
            if (in_array($table, ['tenants', 'users', 'branches'])) continue;
            
            if (strpos(strtolower($cols), 'tenant_id') === false) {
                $new_cols = $cols . ", tenant_id";
                $new_vals = $vals . ", {\$_SESSION['tenant_id']}";
                $new_insert = "INSERT INTO $table ($new_cols) VALUES ($new_vals)";
                $content = str_replace($full_match, $new_insert, $content);
                $changed = true;
                echo "Patched $table in " . basename($file) . "\n";
            }
        }
        if ($changed) {
            file_put_contents($file, $content);
        }
    }
}
?>
