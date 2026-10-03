<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}
include 'includes/db.php';
include 'includes/header.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'twilio_sid', 'twilio_token', 'twilio_phone', 'enable_email_receipts', 'enable_sms_receipts', 'admin_email', 'admin_phone'];
    
    foreach ($keys as $key) {
        $val = isset($_POST[$key]) ? trim($_POST[$key]) : '';
        if (in_array($key, ['enable_email_receipts', 'enable_sms_receipts'])) {
            $val = isset($_POST[$key]) ? '1' : '0';
        }
        $stmt = $conn->prepare("UPDATE settings SET setting_value=? WHERE setting_key=?");
        $stmt->bind_param("ss", $val, $key);
        $stmt->execute();
        $stmt->close();
    }
    $message = "Integration settings updated successfully!";
}

$settingsQ = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'twilio_sid', 'twilio_token', 'twilio_phone', 'enable_email_receipts', 'enable_sms_receipts', 'admin_email', 'admin_phone')");
$sysConfig = [];
while($r = $settingsQ->fetch_assoc()) {
    $sysConfig[$r['setting_key']] = $r['setting_value'];
}
?>

<div class="header-banner">
    <h2><i class="fa fa-plug"></i> Integrations & APIs</h2>
    <p>Configure Email (SMTP) and SMS (Twilio) settings for digital receipts and alerts.</p>
</div>

<div class="card" style="max-width: 800px; margin: 2rem auto; padding: 2rem;">
    <?php if($message): ?>
        <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        
        <h3 style="border-bottom: 2px solid #f1f5f9; padding-bottom: 0.5rem; margin-bottom: 1rem; color: #0f172a;">Email (SMTP) Settings</h3>
        <div class="form-group" style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem;">Enable Email Receipts</label>
            <input type="checkbox" name="enable_email_receipts" value="1" <?php echo (isset($sysConfig['enable_email_receipts']) && $sysConfig['enable_email_receipts'] == '1') ? 'checked' : ''; ?>> Yes, send email receipts to customers.
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>SMTP Host</label>
                <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($sysConfig['smtp_host'] ?? ''); ?>" placeholder="smtp.gmail.com" class="form-control" style="width:100%; padding: 0.75rem; border-radius: 6px; border: 1px solid #cbd5e1;">
            </div>
            <div class="form-group">
                <label>SMTP Port</label>
                <input type="text" name="smtp_port" value="<?php echo htmlspecialchars($sysConfig['smtp_port'] ?? ''); ?>" placeholder="587" class="form-control" style="width:100%; padding: 0.75rem; border-radius: 6px; border: 1px solid #cbd5e1;">
            </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
            <div class="form-group">
                <label>SMTP Username / Email</label>
                <input type="text" name="smtp_user" value="<?php echo htmlspecialchars($sysConfig['smtp_user'] ?? ''); ?>" class="form-control" style="width:100%; padding: 0.75rem; border-radius: 6px; border: 1px solid #cbd5e1;">
            </div>
            <div class="form-group">
                <label>SMTP Password (App Password)</label>
                <input type="password" name="smtp_pass" value="<?php echo htmlspecialchars($sysConfig['smtp_pass'] ?? ''); ?>" class="form-control" style="width:100%; padding: 0.75rem; border-radius: 6px; border: 1px solid #cbd5e1;">
            </div>
        </div>

        <h3 style="border-bottom: 2px solid #f1f5f9; padding-bottom: 0.5rem; margin-top: 2rem; margin-bottom: 1rem; color: #0f172a;">SMS (Twilio) Settings</h3>
        <div class="form-group" style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem;">Enable SMS Receipts</label>
            <input type="checkbox" name="enable_sms_receipts" value="1" <?php echo (isset($sysConfig['enable_sms_receipts']) && $sysConfig['enable_sms_receipts'] == '1') ? 'checked' : ''; ?>> Yes, send SMS receipts to customers.
        </div>
        <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
            <div class="form-group">
                <label>Twilio Account SID</label>
                <input type="text" name="twilio_sid" value="<?php echo htmlspecialchars($sysConfig['twilio_sid'] ?? ''); ?>" class="form-control" style="width:100%; padding: 0.75rem; border-radius: 6px; border: 1px solid #cbd5e1;">
            </div>
            <div class="form-group">
                <label>Twilio Auth Token</label>
                <input type="password" name="twilio_token" value="<?php echo htmlspecialchars($sysConfig['twilio_token'] ?? ''); ?>" class="form-control" style="width:100%; padding: 0.75rem; border-radius: 6px; border: 1px solid #cbd5e1;">
            </div>
            <div class="form-group">
                <label>Twilio Phone Number (Sender)</label>
                <input type="text" name="twilio_phone" value="<?php echo htmlspecialchars($sysConfig['twilio_phone'] ?? ''); ?>" class="form-control" style="width:100%; padding: 0.75rem; border-radius: 6px; border: 1px solid #cbd5e1;">
            </div>
        </div>

        <h3 style="border-bottom: 2px solid #f1f5f9; padding-bottom: 0.5rem; margin-top: 2rem; margin-bottom: 1rem; color: #0f172a;">Admin Alert Settings (Low Stock)</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>Admin Email Address</label>
                <input type="email" name="admin_email" value="<?php echo htmlspecialchars($sysConfig['admin_email'] ?? ''); ?>" class="form-control" style="width:100%; padding: 0.75rem; border-radius: 6px; border: 1px solid #cbd5e1;">
            </div>
            <div class="form-group">
                <label>Admin Phone Number</label>
                <input type="text" name="admin_phone" value="<?php echo htmlspecialchars($sysConfig['admin_phone'] ?? ''); ?>" class="form-control" style="width:100%; padding: 0.75rem; border-radius: 6px; border: 1px solid #cbd5e1;">
            </div>
        </div>

        <div style="margin-top: 2rem;">
            <button type="submit" class="btn" style="background: #2563eb; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">Save Integration Settings</button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
