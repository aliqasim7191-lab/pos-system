<?php
include 'includes/db.php';
include 'includes/header.php';

if (!isset($isAdmin) || !$isAdmin) {
    echo "<div class='container' style='padding:2rem;text-align:center;'><h2>Access Denied</h2><p>Only administrators can access settings.</p></div>";
    include 'includes/footer.php';
    exit();
}

if (isset($_GET['delete_logo'])) {
    $conn->query("DELETE FROM settings WHERE setting_key = 'store_logo' AND tenant_id = " . $_SESSION['tenant_id']);
    header('Location: settings.php');
    exit;
}

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
    foreach($_POST['settings'] as $key => $val) {
        $key = $conn->real_escape_string($key);
        $val = $conn->real_escape_string($val);
        $conn->query("INSERT INTO settings (setting_key, setting_value, tenant_id) VALUES ('$key', '$val', {$_SESSION['tenant_id']}) ON DUPLICATE KEY UPDATE setting_value = '$val'");
    }
    echo "<script>alert('Settings saved successfully!'); window.location.href='settings.php';</script>";
    exit;
}

$settingsQ = $conn->query("SELECT * FROM settings WHERE tenant_id = {$_SESSION['tenant_id']}");
$config = [];
if($settingsQ) {
    while($r = $settingsQ->fetch_assoc()) {
        $config[$r['setting_key']] = $r['setting_value'];
    }
}

// Defaults
$storeName = $config['store_name'] ?? 'SuperStore POS';
$storeAddress = $config['store_address'] ?? '123 Main Street, City';
$storePhone = $config['store_phone'] ?? '(555) 123-4567';
$taxRate = $config['tax_rate'] ?? '0';
$currency = $config['currency'] ?? '$';
$receiptFooter = $config['receipt_footer'] ?? 'Thank you for shopping with us!';

?>

<div class="container" style="padding-top: 1rem; max-width: 900px;">
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.8rem; color: #0f172a; margin-bottom: 0.2rem;">Store Settings</h1>
        <div style="font-size: 0.95rem; color: #64748b;">Manage global application preferences, receipt details, and store information.</div>
    </div>

    <form method="POST" enctype="multipart/form-data" style="background: #ffffff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
        
        <h3 style="margin-top: 0; margin-bottom: 1.5rem; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">General Information</h3>
        
        <div style="margin-bottom: 1.5rem;">
            <label style="display:block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Store Logo (Shown on POS & Receipts)</label>
            <input type="file" name="store_logo" accept="image/*" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 1rem;">
            <?php if(!empty($config['store_logo'])): ?>
                <div style="margin-top:0.5rem; display: flex; align-items: center; gap: 1rem;">
                    <img src="<?php echo htmlspecialchars($config['store_logo']); ?>" alt="Logo" style="height:60px; border-radius:6px; border:1px solid #e2e8f0; background: white;">
                    <a href="settings.php?delete_logo=1" class="btn btn-sm" style="background: #ef4444; color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px;" onclick="return confirm('Are you sure you want to remove the logo? The default shopping cart icon will be restored.');"><i class="fa fa-trash"></i> Remove Logo</a>
                </div>
            <?php endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
            <div>
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Store Name</label>
                <input type="text" name="settings[store_name]" value="<?php echo htmlspecialchars($storeName); ?>" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 1rem;">
            </div>
            <div>
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Store Phone</label>
                <input type="text" name="settings[store_phone]" value="<?php echo htmlspecialchars($storePhone); ?>" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 1rem;">
            </div>
        </div>

        <div style="margin-bottom: 2rem;">
            <label style="display:block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Store Address</label>
            <input type="text" name="settings[store_address]" value="<?php echo htmlspecialchars($storeAddress); ?>" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 1rem;">
        </div>

        <h3 style="margin-bottom: 1.5rem; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">Financial & Localization</h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
            <div>
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Primary Currency Symbol</label>
                <input type="text" name="settings[currency_symbol]" value="<?php echo htmlspecialchars($config['currency_symbol'] ?? '$'); ?>" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 1rem;">
            </div>
            <div>
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Secondary Currency (e.g. PKR)</label>
                <input type="text" name="settings[secondary_currency_symbol]" value="<?php echo htmlspecialchars($config['secondary_currency_symbol'] ?? ''); ?>" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 1rem;">
            </div>
            <div>
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Exchange Rate (1 Primary = ? Sec)</label>
                <input type="number" step="0.01" name="settings[exchange_rate]" value="<?php echo htmlspecialchars($config['exchange_rate'] ?? '1'); ?>" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 1rem;">
            </div>
            <div>
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Default Tax Rate (%)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="number" step="0.01" name="settings[tax_rate]" value="<?php echo htmlspecialchars($taxRate); ?>" style="flex:1; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 1rem;">
                    <a href="tax_classes.php" class="btn" style="background:#e2e8f0; color:#475569; border-radius:8px; display:inline-flex; align-items:center; text-decoration:none;">Advanced Taxes</a>
                </div>
            </div>
            <div>
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Tax ID / NTN / STRN</label>
                <input type="text" name="settings[tax_id]" value="<?php echo htmlspecialchars($config['tax_id'] ?? ''); ?>" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 1rem;" placeholder="e.g. 1234567-8">
            </div>
            <div>
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Timezone</label>
                <select name="settings[timezone]" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 1rem;">
                    <option value="Asia/Karachi" <?php echo ($config['timezone'] ?? 'Asia/Karachi') == 'Asia/Karachi' ? 'selected' : ''; ?>>Asia/Karachi (PKT)</option>
                    <option value="America/New_York" <?php echo ($config['timezone'] ?? '') == 'America/New_York' ? 'selected' : ''; ?>>America/New_York (EST)</option>
                    <option value="Europe/London" <?php echo ($config['timezone'] ?? '') == 'Europe/London' ? 'selected' : ''; ?>>Europe/London (GMT)</option>
                    <option value="Asia/Dubai" <?php echo ($config['timezone'] ?? '') == 'Asia/Dubai' ? 'selected' : ''; ?>>Asia/Dubai (GST)</option>
                </select>
            </div>
            <div>
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">System Language</label>
                <select name="settings[language]" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 1rem;">
                    <option value="en" <?php echo ($config['language'] ?? 'en') == 'en' ? 'selected' : ''; ?>>English</option>
                    <option value="ur" <?php echo ($config['language'] ?? '') == 'ur' ? 'selected' : ''; ?>>Urdu (اردو)</option>
                    <option value="ar" <?php echo ($config['language'] ?? '') == 'ar' ? 'selected' : ''; ?>>Arabic (العربية)</option>
                </select>
            </div>
            <div>
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Date Format</label>
                <select name="settings[date_format]" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 1rem;">
                    <option value="d-m-Y" <?php echo ($config['date_format'] ?? 'd-m-Y') == 'd-m-Y' ? 'selected' : ''; ?>>DD-MM-YYYY</option>
                    <option value="m/d/Y" <?php echo ($config['date_format'] ?? '') == 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                    <option value="Y-m-d" <?php echo ($config['date_format'] ?? '') == 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                </select>
            </div>
        </div>

        <h3 style="margin-bottom: 1.5rem; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">Receipt Configuration</h3>

        <div style="margin-bottom: 2rem;">
            <label style="display:block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Receipt Footer Message</label>
            <input type="text" name="settings[receipt_footer]" value="<?php echo htmlspecialchars($receiptFooter); ?>" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 1rem;" placeholder="e.g. Thank you for shopping with us!">
        </div>
        
        <div style="margin-bottom: 2rem;">
            <label style="display:block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Invoice Terms & Conditions</label>
            <textarea name="settings[invoice_terms]" rows="3" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 1rem;" placeholder="e.g. Goods once sold will not be returned or exchanged."><?php echo htmlspecialchars($config['invoice_terms'] ?? ''); ?></textarea>
        </div>

        <div style="text-align: right; border-top: 1px solid #f1f5f9; padding-top: 1.5rem;">
            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="padding: 0.8rem 2rem; font-weight: 600; font-size: 1.05rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(37,99,235,0.2);">Save Settings</button>
                <a href="backup.php" class="btn" style="background: #10b981; color: white; padding: 0.8rem 2rem; font-weight: 600; font-size: 1.05rem; border-radius: 8px; text-decoration: none; box-shadow: 0 2px 4px rgba(16,185,129,0.2);">⬇ Download Database Backup</a>
            </div>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
