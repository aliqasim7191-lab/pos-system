<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/settings.php");

// 1. Fix the POST saving logic to include tenant_id
$old_save = '        $conn->query("INSERT INTO settings (setting_key, setting_value, tenant_id) VALUES (\'$key\', \'$val\', {$_SESSION['tenant_id']}) ON DUPLICATE KEY UPDATE setting_value = \'$val\'");';
$new_save = '        $conn->query("INSERT INTO settings (setting_key, setting_value, tenant_id) VALUES (\'$key\', \'$val\', {$_SESSION[\'tenant_id\']}) ON DUPLICATE KEY UPDATE setting_value = \'$val\'");';
$f = str_replace($old_save, $new_save, $f);

// 2. Fix the SELECT logic
$f = str_replace('SELECT * FROM settings', 'SELECT * FROM settings WHERE tenant_id = {$_SESSION[\'tenant_id\']}', $f);

// 3. Add Logo Upload logic to POST
$post_logic = <<<'PHP'
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Logo Upload
    if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['store_logo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $newName = 'logo_tenant_' . $_SESSION['tenant_id'] . '_' . time() . '.' . $ext;
            $dest = 'uploads/' . $newName;
            if (move_uploaded_file($_FILES['store_logo']['tmp_name'], $dest)) {
                $conn->query("INSERT INTO settings (setting_key, setting_value, tenant_id) VALUES ('store_logo', '$dest', {$_SESSION['tenant_id']}) ON DUPLICATE KEY UPDATE setting_value = '$dest'");
            }
        }
    }
PHP;
$f = preg_replace('/if \(\$_SERVER\[\'REQUEST_METHOD\'\] === \'POST\'\) \{/', $post_logic, $f);

// 4. Update the form to support enctype
$f = str_replace('<form method="POST">', '<form method="POST" enctype="multipart/form-data">', $f);

// 5. Add Logo Upload Input to HTML
$html_logo = <<<'HTML'
            <div class="form-group">
                <label>Store Logo (Shown on POS & Receipts)</label>
                <input type="file" name="store_logo" accept="image/*" style="width:100%; padding:0.8rem; border-radius:6px; border:1px solid #cbd5e1;">
                <?php if(!empty($config['store_logo'])): ?>
                    <div style="margin-top:0.5rem;">
                        <img src="<?php echo htmlspecialchars($config['store_logo']); ?>" alt="Logo" style="height:50px; border-radius:4px; border:1px solid #e2e8f0;">
                    </div>
                <?php endif; ?>
            </div>
HTML;
$f = str_replace('<label>Store Name</label>', $html_logo . "\n            <label>Store Name</label>", $f);

file_put_contents("C:/xampp/htdocs/point of sale/settings.php", $f);
echo "Settings updated!";
?>
