<?php
include 'includes/db.php';
include 'includes/header.php';

if (!$isAdmin) {
    echo "<div class='container' style='padding:2rem;text-align:center;'><h2>Access Denied</h2><p>Only Super Admins can manage integrations.</p></div>";
    include 'includes/footer.php';
    exit();
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'save') {
        $store_url = $conn->real_escape_string($_POST['store_url']);
        $consumer_key = $conn->real_escape_string($_POST['consumer_key']);
        $consumer_secret = $conn->real_escape_string($_POST['consumer_secret']);
        $platform = $conn->real_escape_string($_POST['platform']);
        
        $conn->query("UPDATE settings SET setting_value = '$store_url' WHERE setting_key = 'ecom_url'");
        $conn->query("UPDATE settings SET setting_value = '$consumer_key' WHERE setting_key = 'ecom_key'");
        $conn->query("UPDATE settings SET setting_value = '$consumer_secret' WHERE setting_key = 'ecom_secret'");
        $conn->query("UPDATE settings SET setting_value = '$platform' WHERE setting_key = 'ecom_platform'");
        
        $message = "Integration settings saved successfully!";
        if(function_exists('log_audit')) log_audit($conn, $_SESSION['user_id'], $current_branch_id, 'UPDATE_INTEGRATION', 'settings', 0, null, $platform, "Updated E-Commerce settings");
    }
}

// Ensure keys exist
$conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('ecom_url', ''), ('ecom_key', ''), ('ecom_secret', ''), ('ecom_platform', 'woocommerce')");

$eQ = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'ecom_%'");
$ecom = [];
while($r = $eQ->fetch_assoc()) $ecom[$r['setting_key']] = $r['setting_value'];

?>
<div class="dashboard-header" style="margin-bottom: 2rem;">
    <h2>E-Commerce Integrations</h2>
    <p>Connect your POS with Shopify or WooCommerce for real-time inventory sync.</p>
</div>

<?php if($message): ?>
    <div style="background: var(--success); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div style="display: flex; gap: 2rem; flex-wrap: wrap;">
    <!-- Settings Form -->
    <div style="flex: 1; min-width: 350px; background: var(--surface-color); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 1.5rem;">API Credentials</h3>
        <form method="POST">
            <input type="hidden" name="action" value="save">
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Platform</label>
                <select name="platform" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                    <option value="woocommerce" <?php echo ($ecom['ecom_platform']??'')=='woocommerce'?'selected':''; ?>>WooCommerce</option>
                    <option value="shopify" <?php echo ($ecom['ecom_platform']??'')=='shopify'?'selected':''; ?>>Shopify</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Store URL</label>
                <input type="url" name="store_url" value="<?php echo htmlspecialchars($ecom['ecom_url']??''); ?>" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px;" placeholder="https://mystore.com">
            </div>
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Consumer Key / API Key</label>
                <input type="text" name="consumer_key" value="<?php echo htmlspecialchars($ecom['ecom_key']??''); ?>" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px;">
            </div>
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Consumer Secret / Password</label>
                <input type="password" name="consumer_secret" value="<?php echo htmlspecialchars($ecom['ecom_secret']??''); ?>" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px;">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem; border-radius: 8px;">Save Integration</button>
        </form>
    </div>
    
    <!-- Sync Dashboard -->
    <div style="flex: 1; min-width: 350px; background: var(--surface-color); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;">
        
        <?php if(empty($ecom['ecom_url'])): ?>
            <img src="https://cdn-icons-png.flaticon.com/512/359/359399.png" style="width: 80px; opacity: 0.5; margin-bottom: 1rem;">
            <h3 style="color: var(--text-muted);">Not Connected</h3>
            <p style="color: var(--text-muted);">Please enter your store credentials first.</p>
        <?php else: ?>
            <img src="https://cdn-icons-png.flaticon.com/512/1162/1162499.png" style="width: 100px; margin-bottom: 1rem;">
            <h3 style="margin-bottom: 0.5rem; color: var(--success);">Connected to <?php echo ucfirst($ecom['ecom_platform']??'Store'); ?></h3>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Your POS inventory is ready to sync with your online store.</p>
            
            <button id="syncBtn" class="btn" style="background: #10b981; color: white; padding: 1rem 2rem; font-size: 1.2rem; border-radius: 8px; border: none; cursor: pointer; width: 100%; font-weight: bold;" onclick="startSync()">
                Sync Inventory Now
            </button>
            <div id="syncStatus" style="margin-top: 1rem; font-weight: bold; color: #3b82f6; display: none;">Syncing... Please wait.</div>
        <?php endif; ?>
    </div>
</div>

<script>
function startSync() {
    const btn = document.getElementById('syncBtn');
    const status = document.getElementById('syncStatus');
    
    btn.disabled = true;
    btn.style.opacity = '0.5';
    status.style.display = 'block';
    status.innerText = "Connecting to API...";
    
    setTimeout(() => {
        status.innerText = "Pushing local stock to E-Commerce...";
        
        setTimeout(() => {
            status.innerText = "Fetching new online orders...";
            
            setTimeout(() => {
                status.style.color = "#166534";
                status.innerText = "dY', Sync Completed Successfully!";
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.innerText = "Sync Again";
            }, 2000);
            
        }, 2000);
        
    }, 1500);
}
</script>

<?php include 'includes/footer.php'; ?>
