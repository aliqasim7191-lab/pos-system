<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'includes/db.php';

// Super admins don't need a license
if ($_SESSION['role'] === 'super_admin') {
    header("Location: super_admin.php");
    exit;
}

$tenant_id = $_SESSION['tenant_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['license_key'])) {
    $key = $conn->real_escape_string(trim($_POST['license_key']));
    
    // Check if key is unused
    $checkQ = $conn->query("SELECT id, status FROM license_keys WHERE license_key = '$key'");
    if ($checkQ && $checkQ->num_rows > 0) {
        $row = $checkQ->fetch_assoc();
        if ($row['status'] === 'unused') {
            // Revoke any existing active keys for this tenant
            $conn->query("UPDATE license_keys SET status = 'revoked' WHERE tenant_id = $tenant_id AND status = 'active'");
            
            // Activate the new key
            $key_id = $row['id'];
            $conn->query("UPDATE license_keys SET tenant_id = $tenant_id, status = 'active' WHERE id = $key_id");
            
            $message = "License key activated successfully! Redirecting...";
            echo "<script>setTimeout(() => window.location.href='index.php', 2000);</script>";
        } else {
            $error = "This license key has already been used or revoked.";
        }
    } else {
        $error = "Invalid license key.";
    }
} else {
    // Check if they already have an active license
    $licQ = $conn->query("SELECT status FROM license_keys WHERE tenant_id = $tenant_id AND status = 'active'");
    if ($licQ && $licQ->num_rows > 0) {
        header("Location: index.php");
        exit;
    }
}

// Check if they have a revoked license to show a specific message
$revokedQ = $conn->query("SELECT status FROM license_keys WHERE tenant_id = $tenant_id AND status = 'revoked' ORDER BY updated_at DESC LIMIT 1");
$isRevoked = ($revokedQ && $revokedQ->num_rows > 0);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>License Verification - Point of Sale</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .license-container { background: white; padding: 3rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 450px; text-align: center; }
        h2 { color: #0f172a; margin-top: 0; margin-bottom: 0.5rem; }
        p { color: #64748b; font-size: 0.95rem; margin-bottom: 2rem; }
        input[type="text"] { width: 100%; padding: 1rem; margin-bottom: 1rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 1.1rem; text-align: center; letter-spacing: 2px; font-family: monospace; box-sizing: border-box; }
        input[type="text"]:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.2); }
        .btn { background: #2563eb; color: white; border: none; padding: 1rem; width: 100%; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #1d4ed8; }
        .logout-link { display: inline-block; margin-top: 1.5rem; color: #64748b; text-decoration: none; font-size: 0.9rem; }
        .logout-link:hover { text-decoration: underline; color: #0f172a; }
        .alert-error { background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px; text-align: left; }
        .alert-success { background: #f0fdf4; border-left: 4px solid #22c55e; color: #166534; padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px; text-align: left; }
        .icon { font-size: 4rem; margin-bottom: 1rem; }
    </style>
</head>
<body>

<div class="license-container">
    <?php if($isRevoked): ?>
        <div class="icon">🚫</div>
        <h2>License Suspended</h2>
        <p>Your previous license key was revoked or cancelled by the administrator. Please enter a new valid license key to restore access.</p>
    <?php else: ?>
        <div class="icon">🔑</div>
        <h2>License Required</h2>
        <p>Please enter your software license key to activate your account and access the dashboard.</p>
    <?php endif; ?>

    <?php if ($error): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($message): ?><div class="alert-success"><?php echo $message; ?></div><?php endif; ?>

    <form method="POST">
        <input type="text" name="license_key" placeholder="XXXX-XXXX-XXXX-XXXX" required autocomplete="off">
        <button type="submit" class="btn">Activate Account</button>
    </form>
    
    <a href="logout.php" class="logout-link">Logout and return to login</a>
</div>

</body>
</html>
